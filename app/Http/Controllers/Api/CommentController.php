<?php

namespace App\Http\Controllers\Api;

use App\Data\Blog\CreateCommentData;
use App\Data\Blog\UpdateCommentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Post\Comment;
use App\Models\Post\Post;
use App\Services\CommentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    use AuthorizesRequests;

    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $data = CreateCommentData::from($request->validated());
        $comment = $this->commentService->createComment($post, Auth::user(), $data);

        return response()->json(new CommentResource($comment), 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $data = UpdateCommentData::from($request->validated());
        $comment = $this->commentService->updateComment($comment, $data);

        return response()->json(new CommentResource($comment));
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return response()->json(['message' => 'Commentaire supprimé avec succès'], 200);
    }

    public function postComments(Request $request, Post $post): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $comments = $this->commentService->getPostComments($post, $perPage);

        return response()->json([
            'data' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    public function commentReplies(Request $request, Comment $comment): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $replies = $this->commentService->getCommentReplies($comment, $perPage);

        return response()->json([
            'data' => CommentResource::collection($replies),
            'meta' => [
                'current_page' => $replies->currentPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
                'last_page' => $replies->lastPage(),
            ],
        ]);
    }

    public function like(Comment $comment): JsonResponse
    {
        $like = $this->commentService->likeComment($comment, Auth::user());

        return response()->json(['message' => 'Commentaire aimé'], 201);
    }

    public function unlike(Comment $comment): JsonResponse
    {
        $this->commentService->unlikeComment($comment, Auth::user());

        return response()->json(['message' => 'Like supprimé'], 200);
    }

    public function likes(Request $request, Comment $comment): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $likes = $this->commentService->getCommentLikes($comment, $perPage);

        return response()->json([
            'data' => $likes->items(),
            'meta' => [
                'current_page' => $likes->currentPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
                'last_page' => $likes->lastPage(),
            ],
        ]);
    }
}
