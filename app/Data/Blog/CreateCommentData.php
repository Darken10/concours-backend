<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CreateCommentData extends Data
{
    public function __construct(
        #[Required, Min(1)]
        public string $content,

        #[Nullable]
        public ?string $parent_id = null,

        #[Nullable]
        public ?array $attachments = null,
    ) {}
}
