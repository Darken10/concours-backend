<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Data;

class UpdateCategoryData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
