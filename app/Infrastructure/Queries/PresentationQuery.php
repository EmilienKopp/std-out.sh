<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\DeckSummary;
use App\Application\Projections\EditorDeck;
use App\Application\Projections\PresentationEmbed;
use App\Application\Projections\PresenterDeck;
use App\Application\Projections\PublicSlideshow;
use App\Domain\Presentation\ValueObjects\YoYoTranslateInfo;
use App\Models\Views\PresentationsView;

class PresentationQuery
{
    /**
     * @return array<int, DeckSummary>
     */
    public function listForTeam(int $teamId): array
    {
        return PresentationsView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (PresentationsView $presentation) => $presentation->toDeckSummary())
            ->all();
    }

    public function findForEmbed(int $presentationId): PresentationEmbed
    {
        return PresentationsView::query()->findOrFail($presentationId)->toPresentationEmbed();
    }

    public function findForSlideshow(int $presentationId): PublicSlideshow
    {
        return PresentationsView::query()->findOrFail($presentationId)->toPublicSlideshow();
    }

    public function findForPresent(int $presentationId): PresenterDeck
    {
        $presentation = PresentationsView::query()->findOrFail($presentationId);

        return $presentation->toPresenterDeck($this->yoyoTranslateInfo($presentation));
    }

    public function findForEditor(int $presentationId): EditorDeck
    {
        $presentation = PresentationsView::query()->findOrFail($presentationId);

        return $presentation->toEditorDeck($this->versionsOfTalk($presentation));
    }

    /**
     * The YoYoTranslate session state for the presenter page. Built here
     * because the websocket URL comes from config, not the row.
     */
    private function yoyoTranslateInfo(PresentationsView $presentation): YoYoTranslateInfo
    {
        $sessionId = $presentation->yoyotranslate_session_id;
        $wsBaseUrl = (string) config('yoyotranslate.ws_base_url');
        // The presenter picks the caption languages at link time. YoYoTranslate
        // no longer accepts the `lang=all` wildcard, so fall back to the single
        // configured code only when none were stored.
        $languages = $presentation->yoyotranslate_languages ?? [];
        $wsLang = $languages !== []
            ? implode(',', $languages)
            : (string) config('yoyotranslate.ws_lang', 'en');

        return new YoYoTranslateInfo(
            session_id: $sessionId,
            websocket_url: $sessionId !== null
                ? rtrim($wsBaseUrl, '/').'/'.$sessionId.'?lang='.$wsLang
                : null,
            active: $sessionId !== null,
            started_at: $presentation->yoyotranslate_session_started_at?->toISOString(),
            languages: $languages,
        );
    }

    /**
     * Every version of the deck's talk, newest first, for the editor's
     * version switcher.
     *
     * @return array<int, \App\Application\Projections\DeckVersion>
     */
    private function versionsOfTalk(PresentationsView $presentation): array
    {
        if ($presentation->talk_id === null) {
            return [];
        }

        return PresentationsView::query()
            ->where('talk_id', $presentation->talk_id)
            ->orderByDesc('version_major')
            ->orderByDesc('version_minor')
            ->get()
            ->map(fn (PresentationsView $deck) => $deck->toDeckVersion(current: $deck->id === $presentation->id))
            ->all();
    }
}
