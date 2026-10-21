<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Renders the 1200x630 Open Graph card for a public slideshow as a PNG:
 * the deck's background color with its title set in the display font.
 * Deliberately dumb (no slide content) — link previews only need a
 * recognizable, branded card, and OG images must be raster, not SVG.
 */
final class PresentationCardPng
{
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    private const string FALLBACK_BACKGROUND = '#171717';

    private const int MARGIN = 80;

    private const float MAX_FONT_PT = 56.0;

    private const float MIN_FONT_PT = 30.0;

    private const int MAX_LINES = 4;

    /**
     * @param  string|null  $background  The first slide's background value — a CSS
     *                                   color or gradient string; the first hex color
     *                                   found in it becomes the card background.
     */
    public static function render(string $title, ?string $background, string $siteName): string
    {
        [$r, $g, $b] = self::parseColor($background);

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, $r, $g, $b));

        // Pick legible text colors for the given background luminance.
        [$tr, $tg, $tb] = self::luminance($r, $g, $b) < 0.55 ? [250, 250, 249] : [23, 23, 23];
        $text = (int) imagecolorallocate($image, $tr, $tg, $tb);
        $muted = (int) imagecolorallocatealpha($image, $tr, $tg, $tb, 55);

        $font = resource_path('fonts/card/bricolage-grotesque-600.ttf');
        $maxWidth = self::WIDTH - self::MARGIN * 2;

        [$size, $lines] = self::fitTitle($title, $font, $maxWidth);

        $lineHeight = (int) round($size * 1.85);
        $baseline = intdiv(self::HEIGHT - count($lines) * $lineHeight, 2) + (int) round($size * 1.33);

        foreach ($lines as $line) {
            imagettftext($image, $size, 0, self::MARGIN, $baseline, $text, $font, $line);
            $baseline += $lineHeight;
        }

        imagettftext($image, 18, 0, self::MARGIN, self::HEIGHT - 56, $muted, $font, $siteName);

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * Largest font size (stepping down from MAX to MIN) whose word-wrapped
     * lines all fit the width within MAX_LINES; at MIN the title is
     * truncated with an ellipsis instead.
     *
     * @return array{float, list<string>}
     */
    private static function fitTitle(string $title, string $font, int $maxWidth): array
    {
        for ($size = self::MAX_FONT_PT; $size >= self::MIN_FONT_PT; $size -= 2.0) {
            $lines = self::wrap($title, $font, $size, $maxWidth);

            $fits = count($lines) <= self::MAX_LINES
                && array_all($lines, fn (string $line): bool => self::textWidth($line, $font, $size) <= $maxWidth);

            if ($fits) {
                return [$size, $lines];
            }
        }

        $lines = self::wrap($title, $font, self::MIN_FONT_PT, $maxWidth);

        if (count($lines) > self::MAX_LINES) {
            $lines = array_slice($lines, 0, self::MAX_LINES);
            $last = &$lines[self::MAX_LINES - 1];

            while ($last !== '' && self::textWidth($last.'…', $font, self::MIN_FONT_PT) > $maxWidth) {
                $last = mb_substr($last, 0, -1);
            }

            $last .= '…';
        }

        return [self::MIN_FONT_PT, $lines];
    }

    /** @return list<string> */
    private static function wrap(string $title, string $font, float $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($title)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if ($current === '' || self::textWidth($candidate, $font, $size) <= $maxWidth) {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private static function textWidth(string $text, string $font, float $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        if ($box === false) {
            return 0;
        }

        $xs = [$box[0], $box[2], $box[4], $box[6]];

        return max($xs) - min($xs);
    }

    /** @return array{int, int, int} */
    private static function parseColor(?string $background): array
    {
        $candidate = preg_match('/#([0-9a-f]{6}|[0-9a-f]{3})\b/i', (string) $background, $matches) === 1
            ? $matches[1]
            : mb_substr(self::FALLBACK_BACKGROUND, 1);

        if (mb_strlen($candidate) === 3) {
            $candidate = $candidate[0].$candidate[0].$candidate[1].$candidate[1].$candidate[2].$candidate[2];
        }

        return [
            (int) hexdec(mb_substr($candidate, 0, 2)),
            (int) hexdec(mb_substr($candidate, 2, 2)),
            (int) hexdec(mb_substr($candidate, 4, 2)),
        ];
    }

    private static function luminance(int $r, int $g, int $b): float
    {
        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    }
}
