<?php

namespace App\Services;

use App\Data\Blog\CreateTagData;
use App\Data\Blog\UpdateTagData;
use App\Models\Post\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagService
{
    public function createTag(CreateTagData $data): Tag
    {
        try {
            DB::beginTransaction();

            $tag = Tag::create([
                'name' => $data->name,
                'slug' => Str::slug($data->name),
            ]);

            DB::commit();

            return $tag;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateTag(Tag $tag, UpdateTagData $data): Tag
    {
        try {
            DB::beginTransaction();

            $updateData = [];

            if ($data->name !== $tag->name) {
                $updateData['name'] = $data->name;
                $updateData['slug'] = Str::slug($data->name);
            }

            if (! empty($updateData)) {
                $tag->update($updateData);
            }

            DB::commit();

            return $tag->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteTag(Tag $tag): bool
    {
        try {
            DB::beginTransaction();

            $tag->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getTags($perPage = 15)
    {
        return Tag::withCount('posts')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getTagBySlug(string $slug): ?Tag
    {
        return Tag::where('slug', $slug)->first();
    }
}
