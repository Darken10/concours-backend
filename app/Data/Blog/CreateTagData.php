<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Data;

class CreateTagData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}
}
