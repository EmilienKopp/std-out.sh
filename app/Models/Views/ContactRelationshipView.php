<?php

namespace App\Models\Views;

use App\Application\Projections\ContactConnection;
use App\Application\Projections\UserFollowRequest;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $follower_user_id
 * @property int $followed_user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $follower_name
 * @property string $follower_avatar
 * @property string|null $follower_handle
 * @property string|null $follower_social_x_handle
 * @property string|null $follower_social_github_handle
 * @property string $followed_name
 * @property string $followed_avatar
 * @property string|null $followed_handle
 * @property string|null $followed_social_x_handle
 * @property string|null $followed_social_github_handle
 */
class ContactRelationshipView extends ReadOnlyModel
{
    protected $table = 'contact_relationships';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** The person asking to follow is the follower side of the row. */
    public function toUserFollowRequest(): UserFollowRequest
    {
        return new UserFollowRequest(
            id: $this->follower_user_id,
            name: $this->follower_name,
            avatar: $this->follower_avatar,
            handle: $this->follower_handle,
            social_x_handle: $this->follower_social_x_handle,
            social_github_handle: $this->follower_social_github_handle,
            requested_at: $this->created_at?->toISOString(),
        );
    }

    /** The person this row's follower is following. */
    public function toFollowedConnection(): ContactConnection
    {
        return new ContactConnection(
            id: $this->followed_user_id,
            name: $this->followed_name,
            avatar: $this->followed_avatar,
            handle: $this->followed_handle,
            social_x_handle: $this->followed_social_x_handle,
            social_github_handle: $this->followed_social_github_handle,
            followed_at: $this->created_at?->toISOString(),
        );
    }

    /** The person following this row's followed user. */
    public function toFollowerConnection(): ContactConnection
    {
        return new ContactConnection(
            id: $this->follower_user_id,
            name: $this->follower_name,
            avatar: $this->follower_avatar,
            handle: $this->follower_handle,
            social_x_handle: $this->follower_social_x_handle,
            social_github_handle: $this->follower_social_github_handle,
            followed_at: $this->created_at?->toISOString(),
        );
    }
}
