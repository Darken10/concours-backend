<?php

namespace App\Services;

use App\Data\Blog\CreatePostData;
use App\Data\Blog\UpdatePostData;
use App\Models\Post\Like;
use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostService
{
    public function createPost(User $user, CreatePostData $data): Post
    {
        try {
            DB::beginTransaction();

            $post = Post::create([
                'user_id' => $user->id,
                'title' => $data->title,
                'content' => $data->content,
            ]);

            if ($data->images) {
                foreach ($data->images as $image) {
                    $post->addMedia($image)->toMediaCollection('images', 'public');
                }
            }
            DB::commit();

            return $post->load('user', 'comments', 'likes');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePost(Post $post, UpdatePostData $data): Post
    {
        try {
            DB::beginTransaction();

            $post->update([
                'title' => $data->title,
                'content' => $data->content,
            ]);

            if ($data->images) {
                $post->clearMediaCollection('images');
                foreach ($data->images as $image) {
                    $post->addMedia($image)->toMediaCollection('images', 'public');
                }
            }

            if ($data->attachments) {
                $post->clearMediaCollection('attachments');
                foreach ($data->attachments as $attachment) {
                    $post->addMedia($attachment)->toMediaCollection('attachments', 'public');
                }
            }

            DB::commit();

            return $post->fresh()->load('user', 'comments', 'likes');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deletePost(Post $post): bool
    {
        try {
            DB::beginTransaction();

            $post->clearMediaCollection('images');
            $post->clearMediaCollection('attachments');
            $post->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getPosts($perPage = 15)
    {
        return Post::with('user', 'comments', 'likes')
            ->latest()
            ->paginate($perPage);
    }

    public function getPostsByUser(User $user, $perPage = 15)
    {
        return Post::where('user_id', $user->id)
            ->with('user', 'comments', 'likes')
            ->latest()
            ->paginate($perPage);
    }

    public function likePost(Post $post, User $user): Like
    {
        return Like::firstOrCreate(
            [
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]
        );
    }

    public function unlikePost(Post $post, User $user): bool
    {
        return (bool) Like::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->delete();
    }

    public function getPostLikes(Post $post, $perPage = 15)
    {
        return $post->likes()
            ->with('user')
            ->paginate($perPage);
    }
}
