<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

/**
 * Sends mail through Resend's HTTP API: one HTTPS request with hard timeouts, quicker
 * and steadier than SMTP on a slow network. Registered as the "resend-api" mail
 * transport in AppServiceProvider. Every failure is thrown as a TransportException,
 * so the failover mailer can fall back to the log instead of breaking the page.
 */
class ResendApiTransport extends AbstractTransport
{
    public const ENDPOINT = 'https://api.resend.com/emails';

    public function __construct(
        protected string $key,
        protected int $timeout = 10,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        try {
            [$status, $body] = $this->post($this->payload($email));
        } catch (Throwable $e) {
            throw $this->failure('Could not send through Resend: '.$e->getMessage(), $email, $e);
        }

        $response = json_decode($body, true) ?: [];

        if ($status < 200 || $status >= 300) {
            throw $this->failure(sprintf('Resend rejected the email (HTTP %d): %s', $status, $response['message'] ?? $body), $email);
        }

        if (! empty($response['id'])) {
            $message->setMessageId($response['id']);
        }
    }

    /**
     * Log why an email wasn't sent (the failover mailer only logs the email itself).
     */
    protected function failure(string $reason, Email $email, ?Throwable $previous = null): TransportException
    {
        Log::warning($reason, [
            'to' => array_map(fn (Address $address) => $address->getAddress(), $email->getTo()),
            'subject' => $email->getSubject(),
        ]);

        return new TransportException($reason, 0, $previous);
    }

    /**
     * The Resend "send email" request body.
     *
     * @return array<string, mixed>
     */
    public function payload(Email $email): array
    {
        $format = fn (Address $address): string => $address->getName() !== ''
            ? sprintf('%s <%s>', $address->getName(), $address->getAddress())
            : $address->getAddress();

        return array_filter([
            'from' => $format($email->getFrom()[0]),
            'to' => array_map($format, $email->getTo()),
            'cc' => array_map($format, $email->getCc()),
            'bcc' => array_map($format, $email->getBcc()),
            'reply_to' => array_map($format, $email->getReplyTo()),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
            'attachments' => array_map(fn ($attachment) => [
                'filename' => $attachment->getFilename() ?? 'attachment',
                'content' => base64_encode($attachment->getBody()),
            ], $email->getAttachments()),
        ]);
    }

    /**
     * POST the payload to Resend.
     *
     * Plain cURL rather than the HTTP client: on Windows, PHP's OpenSSL 3 spends ~2s per
     * handshake loading the PEM CA bundle, while the Windows certificate store verifies the
     * same certificates in ~0.2s, and Guzzle doesn't allow switching to it.
     *
     * @param  array<string, mixed>  $payload
     * @return array{0: int, 1: string} status code and response body
     */
    protected function post(array $payload): array
    {
        $curl = curl_init(self::ENDPOINT);

        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$this->key,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_TIMEOUT => $this->timeout,
        ];

        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
            $options[CURLOPT_CAINFO] = null;
        }

        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);

        if ($body === false) {
            throw new TransportException('Could not reach Resend: '.curl_error($curl));
        }

        return [(int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE), (string) $body];
    }

    public function __toString(): string
    {
        return 'resend-api';
    }
}
