<?php

namespace App\Models\Views;

use App\Application\Projections\CalendarDayActivity;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $user_id
 * @property string|null $handle
 * @property string $activity_date
 * @property int $rehearsal_count
 * @property int $talk_event_count
 */
class UserActivityDayView extends ReadOnlyModel
{
    protected $table = 'user_activity_days';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rehearsal_count' => 'integer',
            'talk_event_count' => 'integer',
        ];
    }

    public function toCalendarDayActivity(): CalendarDayActivity
    {
        return new CalendarDayActivity(
            rehearsals: $this->rehearsal_count,
            talks: $this->talk_event_count,
        );
    }

    /**
     * @return array<string, CalendarDayActivity>
     */
    public function toUserActivityDay(): array
    {
        return [$this->activity_date => $this->toCalendarDayActivity()];
    }
}
