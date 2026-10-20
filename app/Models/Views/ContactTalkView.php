<?php

namespace App\Models\Views;

use App\Application\Projections\ContactCard;
use App\Application\Projections\FollowedTalk;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $user_id
 * @property string $user_name
 * @property string $user_avatar
 * @property string|null $user_handle
 * @property string|null $user_social_x_handle
 * @property string|null $user_social_github_handle
 * @property string $name
 * @property bool $is_private
 * @property Carbon|null $updated_at
 * @property Carbon|null $last_presented_at
 * @property int $viewer_count
 * @property int $reaction_total
 */
class ContactTalkView extends ReadOnlyModel
{
    protected $table = 'contact_talks';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'updated_at' => 'datetime',
            'last_presented_at' => 'datetime',
            'viewer_count' => 'integer',
            'reaction_total' => 'integer',
        ];
    }

    public function toFollowedTalk(): FollowedTalk
    {
        return new FollowedTalk(
            id: $this->id,
            name: $this->name,
            updated_at: $this->updated_at?->toISOString(),
            last_presented_at: $this->last_presented_at?->toISOString(),
            viewer_count: $this->viewer_count,
            reaction_total: $this->reaction_total,
            user: new ContactCard(
                id: $this->user_id,
                name: $this->user_name,
                avatar: $this->user_avatar,
                handle: $this->user_handle,
                social_x_handle: $this->user_social_x_handle,
                social_github_handle: $this->user_social_github_handle,
            ),
        );
    }
}
