<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'content' => $this->content,
            'author' => new UserResource($this->whenLoaded('user')),
            'likes_count' => $this->likes()->count() ?? 0,
            'replies_count' => $this->replies_count ?? 0,
            'is_liked' => $this->when(
                auth()->check(),
                fn () => $this->likedBy(auth()->user())
            ),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'attachments' => $this->getMedia('attachments')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
