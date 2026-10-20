<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Queries\AdminFeedbackQuery;
use Inertia\Inertia;
use Inertia\Response;

class AdminFeedbackController extends Controller
{
    public function __construct(
        private readonly AdminFeedbackQuery $feedback,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('admin/Feedback', [
            'feedback' => $this->feedback->all(),
        ]);
    }
}
