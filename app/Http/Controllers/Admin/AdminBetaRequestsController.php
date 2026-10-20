<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Queries\AdminBetaRequestsQuery;
use Inertia\Inertia;
use Inertia\Response;

class AdminBetaRequestsController extends Controller
{
    public function __construct(
        private readonly AdminBetaRequestsQuery $betaRequests,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('admin/BetaRequests', [
            'requests' => $this->betaRequests->all(),
        ]);
    }
}
