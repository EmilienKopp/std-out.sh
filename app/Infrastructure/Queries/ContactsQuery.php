<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\ContactConnection;
use App\Application\Projections\FollowableProfile;
use App\Application\Projections\FollowedTalk;
use App\Application\Projections\UserFollowRequest;
use App\Enums\FollowStatus;
use App\Models\Views\ContactProfileView;
use App\Models\Views\ContactRelationshipView;
use App\Models\Views\ContactTalkView;
use Illuminate\Database\Eloquent\Builder;

class ContactsQuery
{
    /**
     * @return array<int, FollowableProfile>
     */
    public function directoryForUser(int $userId, ?string $search = null, int $limit = 20): array
    {
        $followStatuses = $this->followStatuses($userId);
        $term = $this->normalizedSearch($search);

        return ContactProfileView::query()
            ->where('id', '!=', $userId)
            ->when($term === null, function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->whereRaw("COALESCE(handle, '') != ''")
                        ->orWhereRaw("COALESCE(social_x_handle, '') != ''")
                        ->orWhereRaw("COALESCE(social_github_handle, '') != ''");
                });
            })
            ->when($term !== null, function (Builder $query) use ($term): void {
                $like = '%'.$term.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw("LOWER(COALESCE(handle, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(social_x_handle, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(social_github_handle, '')) LIKE ?", [$like]);
                });
            })
            ->orderByDesc('talks_count')
            ->orderByDesc('followers_count')
            ->orderByRaw('LOWER(name)')
            ->limit($limit)
            ->get()
            ->map(fn (ContactProfileView $profile): FollowableProfile => $this->mapProfile($profile, $followStatuses[$profile->id] ?? 'none'))
            ->all();
    }

    /**
     * @return array<int, ContactConnection>
     */
    public function followingForUser(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('follower_user_id', $userId)
            ->where('status', FollowStatus::Accepted->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContactRelationshipView $relationship) => $relationship->toFollowedConnection())
            ->all();
    }

    /**
     * @return array<int, ContactConnection>
     */
    public function followersForUser(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('followed_user_id', $userId)
            ->where('status', FollowStatus::Accepted->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContactRelationshipView $relationship) => $relationship->toFollowerConnection())
            ->all();
    }

    /**
     * @return array<int, FollowedTalk>
     */
    public function talksFromPeopleUserFollows(int $userId, int $limit = 12): array
    {
        $followingIds = $this->followingIds($userId);

        if ($followingIds === []) {
            return [];
        }

        return ContactTalkView::query()
            ->whereIn('user_id', $followingIds)
            ->orderByRaw('COALESCE(last_presented_at, updated_at) DESC')
            ->limit($limit)
            ->get()
            ->map(fn (ContactTalkView $talk) => $talk->toFollowedTalk())
            ->all();
    }

    /**
     * People who asked to follow this user and are waiting on an answer.
     *
     * @return array<int, UserFollowRequest>
     */
    public function followRequestsForUser(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('followed_user_id', $userId)
            ->where('status', FollowStatus::Pending->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContactRelationshipView $relationship) => $relationship->toUserFollowRequest())
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function followingIds(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('follower_user_id', $userId)
            ->where('status', FollowStatus::Accepted->value)
            ->pluck('followed_user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * The signed-in user's outgoing follow rows, keyed by followed user id.
     *
     * @return array<int, string>
     */
    private function followStatuses(int $userId): array
    {
        return ContactRelationshipView::query()
            ->where('follower_user_id', $userId)
            ->pluck('status', 'followed_user_id')
            ->mapWithKeys(fn (mixed $status, mixed $id): array => [(int) $id => (string) $status])
            ->all();
    }

    private function mapProfile(ContactProfileView $profile, string $followStatus): FollowableProfile
    {
        return new FollowableProfile(
            id: $profile->id,
            handle: $profile->handle ?? '',
            name: $profile->name,
            avatar: $profile->avatar,
            talks_count: $profile->talks_count,
            total_viewers: $profile->total_viewers,
            total_reactions: $profile->total_reactions,
            followers_count: $profile->followers_count,
            following_count: $profile->following_count,
            follow_status: $followStatus,
            social_x_handle: $profile->social_x_handle,
            social_github_handle: $profile->social_github_handle,
        );
    }

    private function normalizedSearch(?string $search): ?string
    {
        if (! is_string($search)) {
            return null;
        }

        $term = ltrim(mb_strtolower(trim($search)), '@');

        return $term === '' ? null : $term;
    }
}
