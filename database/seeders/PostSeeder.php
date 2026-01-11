<?php

namespace Database\Seeders;

use App\Models\Post\Comment;
use App\Models\Post\Like;
use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer 5 utilisateurs
        $users = User::factory(5)->create();

        // Créer 10 posts
        $posts = Post::factory(10)
            ->recycle($users)
            ->create();

        // Créer des commentaires pour chaque post
        foreach ($posts as $post) {
            Comment::factory(fake()->numberBetween(2, 5))
                ->recycle($users)
                ->for($post)
                ->create();
        }

        // Créer des likes pour les posts et commentaires
        foreach ($posts as $post) {
            $usersToLike = $users->random(fake()->numberBetween(1, 3));
            foreach ($usersToLike as $user) {
                Like::create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                ]);
            }
        }

        // Créer des likes pour les commentaires
        foreach (Comment::all() as $comment) {
            $usersToLike = $users->random(fake()->numberBetween(0, 2));
            foreach ($usersToLike as $user) {
                Like::create([
                    'user_id' => $user->id,
                    'comment_id' => $comment->id,
                ]);
            }
        }
    }
}
