<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Models\Views\TalkOverviewView;

class TalkQuery
{
    /**
     * The team's talks for pickers: id, title, how many deck versions each
     * one has accumulated and which presentation is the latest finished deck.
     * generated_at is null while the talk's only deck is still an AI draft in
     * progress, so pickers can hide it. The view's columns are already
     * picker-shaped, so rows go out as-is.
     *
     * @return array<int, TalkOverviewView>
     */
    public function listForTeam(int $teamId): array
    {
        return TalkOverviewView::query()
            ->where('team_id', $teamId)
            ->orderBy('title')
            ->get()
            ->all();
    }
}
