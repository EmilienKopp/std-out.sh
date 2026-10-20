<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Models\Views\AdminUserView;

class AdminUsersQuery
{
    /**
     * Every registered user, newest first, for the admin user list. The
     * view's columns are already list-shaped, so rows go out as-is.
     *
     * @return array<int, AdminUserView>
     */
    public function all(): array
    {
        return AdminUserView::query()
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }
}
