<?php

namespace App\Data\Organization;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class CreateOrganizationData extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        public ?string $description = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            description: $data['description'] ?? null,
        );
    }
}
