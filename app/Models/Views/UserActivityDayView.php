<?php

namespace App\Models\Views;

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
}
