<?php

declare(strict_types=1);

namespace App\Application\Projections;

class PresentationEmbed extends Projection
{
    public function __construct(
        public private(set) string $embed_token,
        /** @var array<string, mixed> */
        public private(set) array $content,
        /** @var array<string, mixed>|null */
        public private(set) ?array $flow,
    ) {}
}
