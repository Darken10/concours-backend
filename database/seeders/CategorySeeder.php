<?php

namespace Database\Seeders;

use App\Models\Post\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Technologie', 'slug' => 'technologie', 'description' => 'Articles sur la technologie et l\'innovation'],
            ['name' => 'Science', 'slug' => 'science', 'description' => 'Découvertes et recherches scientifiques'],
            ['name' => 'Sport', 'slug' => 'sport', 'description' => 'Actualités sportives'],
            ['name' => 'Culture', 'slug' => 'culture', 'description' => 'Art, musique, cinéma et culture'],
            ['name' => 'Éducation', 'slug' => 'education', 'description' => 'Ressources et actualités éducatives'],
            ['name' => 'Santé', 'slug' => 'sante', 'description' => 'Santé et bien-être'],
            ['name' => 'Voyage', 'slug' => 'voyage', 'description' => 'Destinations et conseils de voyage'],
            ['name' => 'Cuisine', 'slug' => 'cuisine', 'description' => 'Recettes et gastronomie'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
