<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CourrierDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(__('courrier.document_invalid'));

            return;
        }

        $expected = match (strtolower($value->getClientOriginalExtension())) {
            'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg', default => null,
        };
        // Inspect server-side bytes; do not trust the browser's Content-Type.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath());
        $valid = $expected !== null && $mime === $expected;
        if ($valid && $expected !== 'application/pdf') {
            $valid = @getimagesize($value->getRealPath()) !== false;
        }
        if (! $valid) {
            $fail(__('courrier.document_invalid'));
        }
    }
}
