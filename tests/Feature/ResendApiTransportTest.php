<?php

namespace Tests\Feature;

use App\Services\ResendApiTransport;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ResendApiTransportTest extends TestCase
{
    /**
     * Use a transport whose network call is replaced by the given response.
     *
     * @param  array{0: int, 1: string}|\Throwable  $response
     */
    private function fakeResend(array|\Throwable $response): object
    {
        $transport = new class('re_test_key', 5) extends ResendApiTransport
        {
            public array $sent = [];

            public array|\Throwable $response = [200, '{"id":"email_123"}'];

            protected function post(array $payload): array
            {
                $this->sent[] = $payload;

                if ($this->response instanceof \Throwable) {
                    throw $this->response;
                }

                return $this->response;
            }
        };
        $transport->response = $response;

        Mail::extend('resend-api', fn () => $transport);
        config([
            'mail.mailers.resend-api' => ['transport' => 'resend-api', 'key' => 're_test_key'],
            'mail.mailers.failover.mailers' => ['resend-api', 'log'],
            'mail.from' => ['address' => 'onboarding@resend.dev', 'name' => 'FilmFolio'],
        ]);
        Mail::forgetMailers();

        return $transport;
    }

    public function test_emails_are_sent_as_a_resend_api_request(): void
    {
        $transport = $this->fakeResend([200, '{"id":"email_123"}']);

        Mail::mailer('resend-api')->raw('Hello there', fn ($message) => $message->to('member@example.com', 'Member')->subject('Hi'));

        $this->assertCount(1, $transport->sent);
        $this->assertSame('FilmFolio <onboarding@resend.dev>', $transport->sent[0]['from']);
        $this->assertSame(['Member <member@example.com>'], $transport->sent[0]['to']);
        $this->assertSame('Hi', $transport->sent[0]['subject']);
        $this->assertSame('Hello there', $transport->sent[0]['text']);
    }

    public function test_a_rejected_email_explains_why_and_falls_back_to_the_log(): void
    {
        $this->fakeResend([403, '{"message":"You can only send testing emails to your own email address"}']);
        config(['mail.mailers.failover.mailers' => ['resend-api', 'array']]);
        $warnings = [];
        Event::listen(function (MessageLogged $log) use (&$warnings): void {
            if ($log->level === 'warning') {
                $warnings[] = $log->message;
            }
        });

        // No exception: the failover mailer caught the rejection and used the fallback mailer.
        $sent = Mail::mailer('failover')->raw('Hello', fn ($message) => $message->to('someone@example.com')->subject('Hi'));

        $this->assertNotNull($sent);
        $this->assertStringContainsString('only send testing emails', $warnings[0] ?? '');
    }

    public function test_network_errors_become_transport_errors(): void
    {
        $this->fakeResend(new \RuntimeException('Connection timed out'));

        $this->expectException(TransportException::class);

        Mail::mailer('resend-api')->raw('Hello', fn ($message) => $message->to('someone@example.com')->subject('Hi'));
    }
}
