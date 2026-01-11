<?php

namespace Database\Factories\Post;

use App\Enums\PostStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(5),
            'published_at' => now(),
            'status' => PostStatusEnum::PUBLISHED->value,
        ];
    }
}
