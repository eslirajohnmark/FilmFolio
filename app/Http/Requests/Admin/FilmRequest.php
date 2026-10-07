<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1888', 'max:'.(now()->year + 10)],
            'genre' => ['nullable', 'string', 'max:60'],
            'release_date' => ['nullable', 'date'],
            'synopsis' => ['nullable', 'string', 'max:5000'],
            'poster_path' => ['nullable', 'string', 'max:255'],
            'backdrop_path' => ['nullable', 'string', 'max:255'],
            'trailer' => ['nullable', 'string', 'max:255'],
            'tmdb_id' => ['nullable', 'integer', 'min:1', Rule::unique('films', 'tmdb_id')->ignore($this->route('film'))],
            'is_teaser' => ['boolean'],

            // Files uploaded from the admin's computer
            'poster_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'backdrop_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'trailer_file' => ['nullable', 'file', 'mimetypes:'.self::VIDEO_MIME_TYPES, 'max:'.self::MAX_VIDEO_KB],
            'teaser_file' => ['nullable', 'file', 'mimetypes:'.self::VIDEO_MIME_TYPES, 'max:'.self::MAX_VIDEO_KB],
            'remove_poster' => ['boolean'],
            'remove_backdrop' => ['boolean'],
            'remove_trailer_video' => ['boolean'],
            'remove_teaser_video' => ['boolean'],
        ];
    }

    public const VIDEO_MIME_TYPES = 'video/mp4,video/webm,video/quicktime,video/ogg';

    /**
     * 500 MB.
     */
    public const MAX_VIDEO_KB = 512000;

    /**
     * Validated data mapped onto Film columns, with the trailer URL reduced to a YouTube key.
     * Uploaded files are handled separately by the controller.
     *
     * @return array<string, mixed>
     */
    public function filmData(): array
    {
        $data = $this->safe()->except([
            'trailer', 'poster_file', 'backdrop_file', 'trailer_file', 'teaser_file',
            'remove_poster', 'remove_backdrop', 'remove_trailer_video', 'remove_teaser_video',
        ]);
        $data['trailer_key'] = self::youtubeKey($this->input('trailer'));
        $data['is_teaser'] = $this->boolean('is_teaser');

        return $data;
    }

    /**
     * Accepts a YouTube watch/short/embed URL or a bare 11-character video id.
     */
    public static function youtubeKey(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|[?&]v=|/embed/|/shorts/)([A-Za-z0-9_-]{11})~', $value, $m)) {
            return $m[1];
        }

        return preg_match('~^[A-Za-z0-9_-]{11}$~', $value) ? $value : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.min' => 'Films start in 1888.',
            'trailer_file.mimetypes' => 'Upload an MP4, WebM, MOV or OGG video.',
            'teaser_file.mimetypes' => 'Upload an MP4, WebM, MOV or OGG video.',
            'trailer_file.max' => 'Videos can be up to 500 MB.',
            'teaser_file.max' => 'Videos can be up to 500 MB.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('trailer') && self::youtubeKey($this->input('trailer')) === null) {
                $validator->errors()->add('trailer', 'Enter a YouTube URL or an 11-character video id.');
            }
        });
    }
}
