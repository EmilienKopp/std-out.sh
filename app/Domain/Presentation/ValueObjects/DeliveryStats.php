<?php

declare(strict_types=1);

namespace App\Domain\Presentation\ValueObjects;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What the presenter's own history says about their delivery of a deck:
 * rehearsals plus finished live sessions, boiled down to the few numbers the
 * talk-length dialog shows next to the linter's estimate. Averages are null
 * until there is at least one measured run.
 */
#[TypeScript]
class DeliveryStats
{
    public int $totalSpokenSeconds;

    public ?int $avgRunSeconds;

    public ?int $avgSecondsPerSlide;

    public ?int $avgWordsPerMinute;

    public function __construct(
        public int $rehearsalCount = 0,
        public int $sessionCount = 0,
        public int $sumRehearsedSeconds = 0,
        public int $sessionSeconds = 0,
        public int $timedSlideCount = 0,
        public int $timedSlideSeconds = 0,
        public int $pacedWords = 0,
        public int $pacedSeconds = 0,
    ) {
        $this->totalSpokenSeconds = $this->sumRehearsedSeconds + $this->sessionSeconds;
        $this->avgRunSeconds = $this->computeAvgRunSeconds();
        $this->avgSecondsPerSlide = $this->computeAvgSecondsPerSlide();
        $this->avgWordsPerMinute = $this->computeAvgWordsPerMinute();
    }

    /**
     * Aggregates measured runs into stats. Words-per-minute only pairs runs
     * that stored a word count (older runs predate the column); words and
     * seconds must come from the same runs or the average would skew.
     *
     * @param  list<array{seconds: int, wordCount: int|null}>  $sessions
     * @param  list<array{slideTimings: list<array{slide: int, seconds: int}>, wordCount: int|null, durationSeconds: int}>  $rehearsals
     */
    public static function fromMeasuredRuns(
        int $rehearsalCount,
        int $sessionCount,
        int $sumRehearsedSeconds,
        array $sessions,
        array $rehearsals,
    ): self {
        $sessionSeconds = 0;
        $pacedWords = 0;
        $pacedSeconds = 0;

        foreach ($sessions as $session) {
            $sessionSeconds += $session['seconds'];

            if ($session['wordCount'] !== null && $session['seconds'] > 0) {
                $pacedWords += $session['wordCount'];
                $pacedSeconds += $session['seconds'];
            }
        }

        $timedSlideCount = 0;
        $timedSlideSeconds = 0;

        foreach ($rehearsals as $run) {
            foreach ($run['slideTimings'] as $timing) {
                $timedSlideCount++;
                $timedSlideSeconds += $timing['seconds'];
            }

            if ($run['wordCount'] !== null && $run['durationSeconds'] > 0) {
                $pacedWords += $run['wordCount'];
                $pacedSeconds += $run['durationSeconds'];
            }
        }

        return new self(
            rehearsalCount: $rehearsalCount,
            sessionCount: $sessionCount,
            sumRehearsedSeconds: $sumRehearsedSeconds,
            sessionSeconds: $sessionSeconds,
            timedSlideCount: $timedSlideCount,
            timedSlideSeconds: $timedSlideSeconds,
            pacedWords: $pacedWords,
            pacedSeconds: $pacedSeconds,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'rehearsalCount' => $this->rehearsalCount,
            'sessionCount' => $this->sessionCount,
            'totalSpokenSeconds' => $this->totalSpokenSeconds,
            'avgRunSeconds' => $this->avgRunSeconds,
            'avgSecondsPerSlide' => $this->avgSecondsPerSlide,
            'avgWordsPerMinute' => $this->avgWordsPerMinute,
            'sumRehearsedSeconds' => $this->sumRehearsedSeconds,
            'sessionSeconds' => $this->sessionSeconds,
        ];
    }

    private function computeAvgRunSeconds(): ?int
    {
        $runCount = $this->rehearsalCount + $this->sessionCount;

        if ($runCount === 0) {
            return null;
        }

        return (int) round($this->totalSpokenSeconds / $runCount);
    }

    private function computeAvgSecondsPerSlide(): ?int
    {
        if ($this->timedSlideCount === 0) {
            return null;
        }

        return (int) round($this->timedSlideSeconds / $this->timedSlideCount);
    }

    private function computeAvgWordsPerMinute(): ?int
    {
        if ($this->pacedSeconds === 0) {
            return null;
        }

        return (int) round($this->pacedWords / ($this->pacedSeconds / 60));
    }
}
