<?php

namespace App\Http\Controllers\Api;

use App\Data\Blog\CreatePostData;
use App\Data\Blog\UpdatePostData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post\Post;
use App\Services\PostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    use AuthorizesRequests;

    protected PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $posts = $this->postService->getPosts($perPage);

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        try {
            $validatedData = $request->validated();

            $data = CreatePostData::from($validatedData);

            $post = $this->postService->createPost(Auth::user(), $data);

            return response()->json(new PostResource($post), 201);
        } catch (\Exception $e) {
            Log::error('Error creating post: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du post',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json(new PostResource($post->load('user', 'categories', 'tags', 'comments', 'likes')));
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $data = UpdatePostData::from($request->validated());
        $post = $this->postService->updatePost($post, $data);

        return response()->json(new PostResource($post));
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $this->postService->deletePost($post);

        return response()->json(['message' => 'Post supprimé avec succès'], 200);
    }

    public function userPosts(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $posts = $this->postService->getPostsByUser(Auth::user(), $perPage);

        return response()->json([
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function like(Post $post): JsonResponse
    {
        $this->postService->likePost($post, Auth::user());

        return response()->json(new PostResource($post->load('user', 'categories', 'tags', 'comments', 'likes')), 201);
    }

    public function unlike(Post $post): JsonResponse
    {
        $this->postService->unlikePost($post, Auth::user());

        return response()->json(new PostResource($post->load('user', 'categories', 'tags', 'comments', 'likes')), 200);
    }

    public function likes(Request $request, Post $post): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $likes = $this->postService->getPostLikes($post, $perPage);

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
