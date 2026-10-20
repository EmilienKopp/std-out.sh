<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\LibraryImage;
use App\Models\Views\PresentationImagesView;

class PresentationImageQuery
{
    /**
     * Lists every content image uploaded across the team's presentations,
     * newest first, for reuse in the editor's image picker.
     *
     * @return array<int, LibraryImage>
     */
    public function listForTeam(int $teamId): array
    {
        return PresentationImagesView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PresentationImagesView $image) => $image->toLibraryImage())
            ->all();
    }
}
