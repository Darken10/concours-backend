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
        try {
            $this->authorize('create', Post::class);

            \Log::info('Step 1: Authorized');
            \Log::info('Request all:', $request->all());
            \Log::info('Request files:', ['files' => $request->allFiles()]);
            \Log::info('Request hasFile images:', ['hasFile' => $request->hasFile('images')]);
            
            // Vérifier si images est un tableau
            if ($request->has('images')) {
                \Log::info('Images type:', ['type' => gettype($request->input('images'))]);
                \Log::info('Images content:', $request->input('images'));
            }
            
            $validatedData = $request->validated();
            \Log::info('Step 2: Data validated', ['data' => $validatedData]);

            $data = CreatePostData::from($validatedData);
            \Log::info('Step 3: CreatePostData created');

            $post = $this->postService->createPost(auth()->user(), $data);
            \Log::info('Step 4: Post created');

            return response()->json(new PostResource($post), 201);
        } catch (\Exception $e) {
            \Log::error('Error creating post: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la création du post',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json(new PostResource($post->load('user', 'comments', 'likes')));
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
        $posts = $this->postService->getPostsByUser(auth()->user(), $perPage);

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
        $like = $this->postService->likePost($post, auth()->user());

        return response()->json(['message' => 'Post aimé'], 201);
    }

    public function unlike(Post $post): JsonResponse
    {
        $this->postService->unlikePost($post, auth()->user());

        return response()->json(['message' => 'Like supprimé'], 200);
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
