<?php

namespace App\Http\Controllers\Api;

use App\Data\Blog\CreateCategoryData;
use App\Data\Blog\UpdateCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StoreCategoryRequest;
use App\Http\Requests\Post\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Post\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $categories = $this->categoryService->getCategories($perPage);

        return response()->json([
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $data = CreateCategoryData::from($validatedData);

            $category = $this->categoryService->createCategory($data);

            return response()->json(new CategoryResource($category), 201);
        } catch (\Exception $e) {
            Log::error('Error creating category: '.$e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la création de la catégorie',
            ], 500);
        }
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(new CategoryResource($category->loadCount('posts')));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $data = UpdateCategoryData::from($request->validated());
            $category = $this->categoryService->updateCategory($category, $data);

            return response()->json(new CategoryResource($category));
        } catch (\Exception $e) {
            Log::error('Error updating category: '.$e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la catégorie',
            ], 500);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user || (! $user->isSuperAdmin() && ! $user->isAdmin() && ! ($user->isAgent() && $user->can('edit categories')))) {
                return response()->json(['message' => 'Non autorisé'], 403);
            }

            $this->categoryService->deleteCategory($category);

            return response()->json(['message' => 'Catégorie supprimée avec succès'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting category: '.$e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la suppression de la catégorie',
            ], 500);
        }
    }
}
