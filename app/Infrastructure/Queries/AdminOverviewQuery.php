<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\PlatformOverview;
use App\Models\Views\AdminOverviewView;

class AdminOverviewQuery
{
    /** Platform-wide roll-up for the admin dashboard. */
    public function summary(): PlatformOverview
    {
        return AdminOverviewView::query()->first()?->toPlatformOverview()
            ?? PlatformOverview::zero();
    }
}
