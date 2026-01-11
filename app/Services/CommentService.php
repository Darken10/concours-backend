<?php

namespace App\Services;

use App\Data\Blog\CreateCommentData;
use App\Data\Blog\UpdateCommentData;
use App\Models\Post\Comment;
use App\Models\Post\Like;
use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function createComment(Post $post, User $user, CreateCommentData $data): Comment
    {
        try {
            DB::beginTransaction();

            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'parent_id' => $data->parent_id,
                'content' => $data->content,
            ]);

            // Incrémenter le compteur de commentaires
            if ($data->parent_id) {
                Comment::find($data->parent_id)->increment('replies_count');
            } else {
                $post->increment('comments_count');
            }

            if ($data->attachments) {
                foreach ($data->attachments as $attachment) {
                    $comment->addMedia($attachment)->toMediaCollection('attachments');
                }
            }

            DB::commit();

            return $comment->load('user', 'likes');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateComment(Comment $comment, UpdateCommentData $data): Comment
    {
        try {
            DB::beginTransaction();

            $comment->update([
                'content' => $data->content,
            ]);

            if ($data->attachments) {
                $comment->clearMediaCollection('attachments');
                foreach ($data->attachments as $attachment) {
                    $comment->addMedia($attachment)->toMediaCollection('attachments');
                }
            }

            DB::commit();

            return $comment->fresh()->load('user', 'likes');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteComment(Comment $comment): bool
    {
        try {
            DB::beginTransaction();

            // Décrémenter les compteurs
            if ($comment->parent_id) {
                Comment::find($comment->parent_id)->decrement('replies_count');
            } else {
                $comment->post->decrement('comments_count');
            }

            // Supprimer les réponses associées
            if ($comment->replies()->exists()) {
                foreach ($comment->replies as $reply) {
                    $this->deleteComment($reply);
                }
            }

            $comment->clearMediaCollection('attachments');
            $comment->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getPostComments(Post $post, $perPage = 15)
    {
        return $post->comments()
            ->whereNull('parent_id')
            ->with('user', 'likes', 'replies.user', 'replies.likes')
            ->latest()
            ->paginate($perPage);
    }

    public function getCommentReplies(Comment $comment, $perPage = 15)
    {
        return $comment->replies()
            ->with('user', 'likes')
            ->latest()
            ->paginate($perPage);
    }

    public function likeComment(Comment $comment, User $user): Like
    {
        return Like::firstOrCreate(
            [
                'user_id' => $user->id,
                'comment_id' => $comment->id,
            ]
        );
    }

    public function unlikeComment(Comment $comment, User $user): bool
    {
        return (bool) Like::where('user_id', $user->id)
            ->where('comment_id', $comment->id)
            ->delete();
    }

    public function getCommentLikes(Comment $comment, $perPage = 15)
    {
        return $comment->likes()
            ->with('user')
            ->paginate($perPage);
    }
}
