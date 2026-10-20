<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\CalendarDayActivity;
use App\Models\Views\UserActivityDayView;
use DateTimeInterface;

class ActivityQuery
{
    /**
     * Per-day activity counts for a user within a date range, keyed by Y-m-d.
     *
     * @return array<string, CalendarDayActivity>
     */
    public function calendarForUser(int $userId, DateTimeInterface $from, DateTimeInterface $to): array
    {
        return UserActivityDayView::query()
            ->where('user_id', $userId)
            ->whereBetween('activity_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->get()
            ->mapWithKeys(fn (UserActivityDayView $day) => $day->toUserActivityDay())
            ->all();
    }
}
