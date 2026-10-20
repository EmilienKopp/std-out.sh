<?php

declare(strict_types=1);

namespace App\Support;

use App\Application\Projections\CalendarDayActivity;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Renders a GitHub-style contribution calendar as a standalone SVG string.
 * Rehearsal days ramp through the tungsten amber scale; days with a talk
 * event render in the live emerald, since being on stage outranks practice.
 * Days where only a deck (or deck version) was created render in a quiet
 * neutral, one step above empty.
 */
class ActivityCalendarSvg
{
    private const int CELL = 10;

    private const int GAP = 2;

    private const int PAD_LEFT = 30;

    private const int PAD_TOP = 48;

    private const string BACKGROUND = '#131315';

    private const string TEXT = '#8d8d94';

    private const string TITLE_TEXT = '#f2f2f0';

    private const string ACCENT = '#f5a623';

    private const string TALK = '#34d399';

    private const string DECK = '#3d3d42';

    /** @var list<string> index = intensity level 0-4 */
    private const array LEVELS = ['#232326', '#3a2a0e', '#6b4a0c', '#b06e10', '#f5a623'];

    private const string FONT = 'font-family="ui-monospace, SFMono-Regular, Menlo, monospace"';

    /**
     * Expects $start to be a Sunday so the first column is a full week:
     * row = weekday, column = week index.
     *
     * @param  array<string, CalendarDayActivity>  $days  keyed by Y-m-d
     */
    public static function render(string $handle, array $days, DateTimeInterface $start, DateTimeInterface $end): string
    {
        $pitch = self::CELL + self::GAP;
        $cursor = DateTimeImmutable::createFromInterface($start);
        $endDate = DateTimeImmutable::createFromInterface($end);
        $oneDay = new DateInterval('P1D');

        $cells = [];
        $monthLabels = [];
        $totalRehearsals = 0;
        $totalTalks = 0;
        $totalDecks = 0;
        $previousMonth = null;
        $index = 0;

        while ($cursor <= $endDate) {
            $row = $index % 7;
            $column = intdiv($index, 7);
            $key = $cursor->format('Y-m-d');
            $rehearsals = isset($days[$key]) ? $days[$key]->rehearsals : 0;
            $talks = isset($days[$key]) ? $days[$key]->talks : 0;
            $decks = isset($days[$key]) ? $days[$key]->decks : 0;
            $totalRehearsals += $rehearsals;
            $totalTalks += $talks;
            $totalDecks += $decks;

            $x = self::PAD_LEFT + $column * $pitch;
            $y = self::PAD_TOP + $row * $pitch;
            $fill = match (true) {
                $talks > 0 => self::TALK,
                $rehearsals > 0 => self::LEVELS[self::level($rehearsals)],
                $decks > 0 => self::DECK,
                default => self::LEVELS[0],
            };
            $label = sprintf(
                '%s: %d rehearsal%s%s%s',
                $key,
                $rehearsals,
                $rehearsals === 1 ? '' : 's',
                $talks > 0 ? sprintf(', %d talk%s', $talks, $talks === 1 ? '' : 's') : '',
                $decks > 0 ? sprintf(', %d deck%s', $decks, $decks === 1 ? '' : 's') : '',
            );

            $cells[] = sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="2" fill="%s"><title>%s</title></rect>',
                $x,
                $y,
                self::CELL,
                self::CELL,
                $fill,
                htmlspecialchars($label, ENT_XML1),
            );

            if ($row === 0) {
                $month = $cursor->format('M');
                if ($month !== $previousMonth) {
                    $monthLabels[] = sprintf(
                        '<text x="%d" y="%d" font-size="9" fill="%s" %s>%s</text>',
                        $x,
                        self::PAD_TOP - 6,
                        self::TEXT,
                        self::FONT,
                        $month,
                    );
                    $previousMonth = $month;
                }
            }

            $cursor = $cursor->add($oneDay);
            $index++;
        }

        $columns = $index > 0 ? intdiv($index - 1, 7) + 1 : 0;
        $width = self::PAD_LEFT + $columns * $pitch + 8;
        $gridBottom = self::PAD_TOP + 7 * $pitch - self::GAP;
        $height = $gridBottom + 24;

        $dayLabels = [];
        foreach ([1 => 'Mon', 3 => 'Wed', 5 => 'Fri'] as $row => $name) {
            $dayLabels[] = sprintf(
                '<text x="4" y="%d" font-size="8" fill="%s" %s>%s</text>',
                self::PAD_TOP + $row * $pitch + self::CELL - 2,
                self::TEXT,
                self::FONT,
                $name,
            );
        }

        $safeHandle = htmlspecialchars($handle, ENT_XML1);
        $summary = sprintf(
            '%d deck%s · %d rehearsal%s · %d talk%s on stage',
            $totalDecks,
            $totalDecks === 1 ? '' : 's',
            $totalRehearsals,
            $totalRehearsals === 1 ? '' : 's',
            $totalTalks,
            $totalTalks === 1 ? '' : 's',
        );

        $parts = [
            sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="%s">',
                $width,
                $height,
                $width,
                $height,
                htmlspecialchars(sprintf('Tecturn activity of @%s: %s', $handle, $summary), ENT_XML1),
            ),
            sprintf('<rect width="%d" height="%d" rx="6" fill="%s"/>', $width, $height, self::BACKGROUND),
            sprintf(
                '<text x="%d" y="20" font-size="12" font-weight="bold" fill="%s" %s>@%s<tspan fill="%s"> · Tecturn</tspan></text>',
                self::PAD_LEFT,
                self::TITLE_TEXT,
                self::FONT,
                $safeHandle,
                self::ACCENT,
            ),
            ...$monthLabels,
            ...$dayLabels,
            ...$cells,
            sprintf(
                '<text x="%d" y="%d" font-size="9" fill="%s" %s>%s</text>',
                self::PAD_LEFT,
                $gridBottom + 16,
                self::TEXT,
                self::FONT,
                htmlspecialchars($summary.' · last 12 months', ENT_XML1),
            ),
            '</svg>',
        ];

        return implode("\n", $parts);
    }

    private static function level(int $count): int
    {
        return match (true) {
            $count <= 0 => 0,
            $count === 1 => 1,
            $count === 2 => 2,
            $count <= 4 => 3,
            default => 4,
        };
    }
}
