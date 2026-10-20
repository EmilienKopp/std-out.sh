<?php

namespace App\Application\Projections;

class CalendarDayActivity extends Projection
{
    public function __construct(
        public private(set) int $rehearsals,
        public private(set) int $talks,
        public private(set) int $decks,
    ) {}
}
