<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Queries\AdminOverviewQuery;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminOverviewQuery $overview,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'overview' => $this->overview->summary(),
        ]);
    }
}
