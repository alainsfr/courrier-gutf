<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserGuideController extends Controller
{
    public function __invoke(): View
    {
        $parts = preg_split('/^## /m', File::get(resource_path('docs/manuel-utilisation.md')));
        $intro = array_shift($parts);
        $render = fn (string $text) => Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $sections = collect($parts)->map(function (string $part, int $index) use ($render) {
            [$title, $body] = array_pad(explode("\n", $part, 2), 2, '');

            return ['id' => 'guide-'.($index + 1), 'title' => trim($title), 'html' => $render($body)];
        });

        return view('help.user-guide', ['intro' => $render($intro), 'sections' => $sections]);
    }
}
