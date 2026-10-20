<?php

namespace App\Models\Views;

use App\Application\Projections\ScheduledTalkEvent;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $talk_id
 * @property string $name
 * @property Carbon $date
 * @property string|null $start_time
 * @property string|null $talk_title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CalendarEventView extends ReadOnlyModel
{
    protected $table = 'calendar_events';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function toScheduledTalkEvent(): ScheduledTalkEvent
    {
        return new ScheduledTalkEvent(
            id: $this->id,
            name: $this->name,
            date: $this->date->toDateString(),
            start_time: $this->start_time !== null
                ? substr($this->start_time, 0, 5)
                : null,
            talk_id: $this->talk_id,
            talk_title: $this->talk_title,
        );
    }
}
