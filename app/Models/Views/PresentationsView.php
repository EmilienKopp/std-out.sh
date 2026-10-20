<?php

namespace App\Models\Views;

use App\Application\Projections\DeckSummary;
use App\Application\Projections\DeckVersion;
use App\Application\Projections\EditorDeck;
use App\Application\Projections\PresentationEmbed;
use App\Application\Projections\PresenterDeck;
use App\Application\Projections\PublicSlideshow;
use App\Domain\Presentation\ValueObjects\PresentationSource;
use App\Domain\Presentation\ValueObjects\TalkSettings;
use App\Domain\Presentation\ValueObjects\YoYoTranslateInfo;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property bool $is_private
 * @property array<string, mixed> $content
 * @property array<string, mixed>|null $talk_settings
 * @property array<string, mixed>|null $flow
 * @property array<string, mixed>|null $source
 * @property string $embed_token
 * @property string|null $yoyotranslate_session_id
 * @property Carbon|null $yoyotranslate_session_started_at
 * @property list<string>|null $yoyotranslate_languages
 * @property Carbon|null $draft_requested_at
 * @property Carbon|null $draft_completed_at
 * @property Carbon|null $draft_failed_at
 * @property string|null $draft_error
 * @property int|null $talk_id
 * @property int|null $version_major
 * @property int|null $version_minor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PresentationsView extends ReadOnlyModel
{
    protected $table = 'presentations_view';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'content' => 'array',
            'talk_settings' => 'array',
            'flow' => 'array',
            'source' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'yoyotranslate_session_started_at' => 'datetime',
            'yoyotranslate_languages' => 'array',
            'draft_requested_at' => 'datetime',
            'draft_completed_at' => 'datetime',
            'draft_failed_at' => 'datetime',
        ];
    }

    /** "2.1"-style label, or null for decks that never joined a talk. */
    public function versionLabel(): ?string
    {
        if ($this->version_major === null || $this->version_minor === null) {
            return null;
        }

        return $this->version_major.'.'.$this->version_minor;
    }

    /**
     * Derives the deck's AI-draft state from its draft timestamps. Decks created
     * before draft tracking (all timestamps null) are treated as ready.
     *
     * @return 'ready'|'generating'|'failed'
     */
    public function draftStatus(): string
    {
        if ($this->draft_failed_at !== null) {
            return 'failed';
        }

        if ($this->draft_requested_at !== null && $this->draft_completed_at === null) {
            return 'generating';
        }

        return 'ready';
    }

    public function toDeckSummary(): DeckSummary
    {
        return new DeckSummary(
            id: $this->id,
            name: $this->name,
            is_private: $this->is_private,
            slide_count: count($this->content['slides'] ?? []),
            status: $this->draftStatus(),
            draft_error: $this->draft_error,
            updated_at: $this->updated_at?->toISOString(),
            talk_id: $this->talk_id,
            version: $this->versionLabel(),
            // CSS thumbnail sources: the first slide's background (color or
            // gradient) and its image, falling back to the deck-wide
            // background image.
            background: $this->content['slides'][0]['background'] ?? null,
            background_image: $this->content['slides'][0]['backgroundImage']
                ?? $this->content['backgroundImage']
                ?? null,
        );
    }

    public function toDeckVersion(bool $current): DeckVersion
    {
        return new DeckVersion(
            id: $this->id,
            version: $this->versionLabel(),
            name: $this->name,
            updated_at: $this->updated_at?->toISOString(),
            current: $current,
        );
    }

    public function toPresentationEmbed(): PresentationEmbed
    {
        return new PresentationEmbed(
            embed_token: $this->embed_token,
            content: $this->content,
            flow: $this->flow,
        );
    }

    public function toPublicSlideshow(): PublicSlideshow
    {
        return new PublicSlideshow(
            name: $this->name,
            content: $this->content,
            flow: $this->flow,
            source: PresentationSource::fromArray($this->source ?? [])->toArray(),
        );
    }

    public function toPresenterDeck(YoYoTranslateInfo $yoyotranslate): PresenterDeck
    {
        return new PresenterDeck(
            id: $this->id,
            name: $this->name,
            is_private: $this->is_private,
            content: $this->content,
            talk_settings: TalkSettings::fromArray($this->talk_settings ?? [])->toArray(),
            flow: $this->flow,
            source: PresentationSource::fromArray($this->source ?? [])->toArray(),
            embed_token: $this->embed_token,
            updated_at: $this->updated_at?->toISOString(),
            yoyotranslate: $yoyotranslate,
        );
    }

    /** @param array<int, DeckVersion> $versions */
    public function toEditorDeck(array $versions): EditorDeck
    {
        return new EditorDeck(
            id: $this->id,
            name: $this->name,
            is_private: $this->is_private,
            content: $this->content,
            talk_settings: TalkSettings::fromArray($this->talk_settings ?? [])->toArray(),
            flow: $this->flow,
            source: PresentationSource::fromArray($this->source ?? [])->toArray(),
            updated_at: $this->updated_at?->toISOString(),
            version: $this->versionLabel(),
            versions: $versions,
        );
    }
}
