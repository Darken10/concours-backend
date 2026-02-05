<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Data;

class UpdateTagData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}
}
