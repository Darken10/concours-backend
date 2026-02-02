<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\UserCreatorResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => new UserCreatorResource($this->whenLoaded('user')),
            'likes_count' => $this->likes_count ?? 0,
            'comments_count' => $this->comments_count ?? 0,
            'shares_count' => $this->shares_count ?? 0,
            'is_liked' => $this->when(
                auth()->check(),
                fn () => $this->likedBy(auth()->user())
            ),
            'images' => $this->getMedia('images')->map(fn ($media) => [
                'url' => $media->getFullUrl(),
                'mime_type' => $media->mime_type,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
