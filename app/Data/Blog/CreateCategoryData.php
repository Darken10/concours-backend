<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Data;

class CreateCategoryData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
