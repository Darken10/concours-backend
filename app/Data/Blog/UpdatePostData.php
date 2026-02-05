<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class UpdatePostData extends Data
{
    public function __construct(
        #[Required, Min(3)]
        public string $title,

        #[Required, Min(10)]
        public string $content,

        #[Nullable]
        public ?array $images = null,

        #[Nullable]
        public ?array $attachments = null,

        #[Nullable]
        public ?array $category_ids = null,

        #[Nullable]
        public ?array $tag_ids = null,
    ) {}
}
