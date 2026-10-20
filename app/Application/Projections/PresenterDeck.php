<?php

declare(strict_types=1);

namespace App\Application\Projections;

use App\Domain\Presentation\ValueObjects\YoYoTranslateInfo;

class PresenterDeck extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        public private(set) bool $is_private,
        /** @var array<string, mixed> */
        public private(set) array $content,
        /** @var array<string, mixed> */
        public private(set) array $talk_settings,
        /** @var array<string, mixed>|null */
        public private(set) ?array $flow,
        /** @var array<string, mixed> */
        public private(set) array $source,
        public private(set) string $embed_token,
        public private(set) ?string $updated_at,
        public private(set) YoYoTranslateInfo $yoyotranslate,
    ) {}
}
