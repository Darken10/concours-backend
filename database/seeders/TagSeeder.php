<?php

namespace Database\Seeders;

use App\Models\Post\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'JavaScript', 'slug' => 'javascript'],
            ['name' => 'Vue.js', 'slug' => 'vuejs'],
            ['name' => 'React', 'slug' => 'react'],
            ['name' => 'AI', 'slug' => 'ai'],
            ['name' => 'Machine Learning', 'slug' => 'machine-learning'],
            ['name' => 'DevOps', 'slug' => 'devops'],
            ['name' => 'Cloud', 'slug' => 'cloud'],
            ['name' => 'Mobile', 'slug' => 'mobile'],
            ['name' => 'Web', 'slug' => 'web'],
            ['name' => 'Tutorial', 'slug' => 'tutorial'],
            ['name' => 'News', 'slug' => 'news'],
            ['name' => 'Opinion', 'slug' => 'opinion'],
            ['name' => 'Analysis', 'slug' => 'analysis'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }
    }
}
