<?php

namespace App\Http\Controllers\Api;

use App\Data\Blog\CreateTagData;
use App\Data\Blog\UpdateTagData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StoreTagRequest;
use App\Http\Requests\Post\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Post\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TagController extends Controller
{
    protected TagService $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $tags = $this->tagService->getTags($perPage);

        return response()->json([
            'data' => TagResource::collection($tags),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
                'last_page' => $tags->lastPage(),
            ],
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $data = CreateTagData::from($validatedData);

            $tag = $this->tagService->createTag($data);

            return response()->json(new TagResource($tag), 201);
        } catch (\Exception $e) {
            Log::error('Error creating tag: '.$e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la création du tag',
            ], 500);
        }
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json(new TagResource($tag->loadCount('posts')));
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        try {
            $data = UpdateTagData::from($request->validated());
            $tag = $this->tagService->updateTag($tag, $data);

            return response()->json(new TagResource($tag));
        } catch (\Exception $e) {
            Log::error('Error updating tag: '.$e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du tag',
            ], 500);
        }
    }

    public function destroy(Tag $tag): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user || (! $user->isSuperAdmin() && ! $user->isAdmin() && ! ($user->isAgent() && $user->can('edit tags')))) {
                return response()->json(['message' => 'Non autorisé'], 403);
            }

            $this->tagService->deleteTag($tag);

            return response()->json(['message' => 'Tag supprimé avec succès'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting tag: '.$e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la suppression du tag',
            ], 500);
        }
    }
}
