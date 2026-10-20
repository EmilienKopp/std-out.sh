<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Models\Views\AdminFeedbackView;

class AdminFeedbackQuery
{
    /**
     * Every piece of feedback, newest first, for the admin list. The view's
     * columns are already list-shaped, so rows go out as-is.
     *
     * @return array<int, AdminFeedbackView>
     */
    public function all(): array
    {
        return AdminFeedbackView::query()
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }
}
