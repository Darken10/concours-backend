<?php

namespace App\Data\Blog;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class UpdateCommentData extends Data
{
    public function __construct(
        #[Required, Min(1)]
        public string $content,

        #[Nullable]
        public ?array $attachments = null,
    ) {}
}
