<?php

namespace App\Models\Views;

use App\Application\Projections\PlatformOverview;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $total_users
 * @property int $total_teams
 * @property int $total_workspaces
 * @property int $total_presentations
 * @property int $total_sessions
 * @property int $live_sessions
 * @property int $total_reactions
 * @property int $total_viewers
 */
class AdminOverviewView extends ReadOnlyModel
{
    protected $table = 'admin_overview';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_users' => 'integer',
            'total_teams' => 'integer',
            'total_workspaces' => 'integer',
            'total_presentations' => 'integer',
            'total_sessions' => 'integer',
            'live_sessions' => 'integer',
            'total_reactions' => 'integer',
            'total_viewers' => 'integer',
        ];
    }

    public function toPlatformOverview(): PlatformOverview
    {
        return new PlatformOverview(
            total_users: $this->total_users ?? 0,
            total_teams: $this->total_teams ?? 0,
            total_workspaces: $this->total_workspaces ?? 0,
            total_presentations: $this->total_presentations ?? 0,
            total_sessions: $this->total_sessions ?? 0,
            live_sessions: $this->live_sessions ?? 0,
            total_reactions: $this->total_reactions ?? 0,
            total_viewers: $this->total_viewers ?? 0,
        );
    }
}
