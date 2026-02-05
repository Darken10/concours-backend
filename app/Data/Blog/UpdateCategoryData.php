<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Data;

class UpdateCategoryData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
    ) {}
}
