<?php

namespace App\Services;

use App\Data\Blog\CreateCategoryData;
use App\Data\Blog\UpdateCategoryData;
use App\Models\Post\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    public function createCategory(CreateCategoryData $data): Category
    {
        try {
            DB::beginTransaction();

            $category = Category::create([
                'name' => $data->name,
                'slug' => Str::slug($data->name),
                'description' => $data->description,
            ]);

            DB::commit();

            return $category;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateCategory(Category $category, UpdateCategoryData $data): Category
    {
        try {
            DB::beginTransaction();

            $updateData = [
                'description' => $data->description,
            ];

            if ($data->name !== $category->name) {
                $updateData['name'] = $data->name;
                $updateData['slug'] = Str::slug($data->name);
            }

            $category->update($updateData);

            DB::commit();

            return $category->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteCategory(Category $category): bool
    {
        try {
            DB::beginTransaction();

            $category->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getCategories($perPage = 15)
    {
        return Category::withCount('posts')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }
}
