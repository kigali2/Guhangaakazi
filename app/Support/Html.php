<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class Html
{
    private static ?HtmlSanitizer $sanitizer = null;

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer === null) {
            $config = (new HtmlSanitizerConfig())
                ->allowElement('p', ['class'])
                ->allowElement('br')
                ->allowElement('h2', ['class'])
                ->allowElement('h3', ['class'])
                ->allowElement('h4', ['class'])
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('u')
                ->allowElement('s')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('blockquote')
                ->allowElement('hr')
                ->allowElement('a', ['href', 'title'])
                ->allowElement('table', ['class'])
                ->allowElement('thead')
                ->allowElement('tbody')
                ->allowElement('tr')
                ->allowElement('th', ['class', 'colspan', 'rowspan'])
                ->allowElement('td', ['class', 'colspan', 'rowspan'])
                ->dropElement('script')
                ->dropElement('style')
                ->dropElement('iframe')
                ->dropElement('object')
                ->dropElement('embed')
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
                ->withMaxInputLength(300000);

            self::$sanitizer = new HtmlSanitizer($config);
        }

        return self::$sanitizer;
    }

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $map = ['left' => 'text-start', 'center' => 'text-center', 'right' => 'text-end', 'justify' => 'text-justify'];
        $html = preg_replace_callback('/<(p|h[2-4]|td|th)\b[^>]*?style="([^"]*)"[^>]*>/i', function ($m) use ($map) {
            $class = '';
            if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $m[2], $a)) {
                $class = ' class="' . $map[strtolower($a[1])] . '"';
            }

            return '<' . strtolower($m[1]) . $class . '>';
        }, $html) ?? $html;

        return trim(self::sanitizer()->sanitize($html));
    }

    public static function render(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        if ($text === strip_tags($text)) {
            $paragraphs = preg_split('/\R{2,}/', $text) ?: [$text];

            return implode('', array_map(fn ($p) => '<p>' . nl2br(e($p)) . '</p>', $paragraphs));
        }

        return self::clean($text);
    }
}
