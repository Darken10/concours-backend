<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserGenderEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable,TwoFactorAuthenticatable;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
        'firstname',
        'lastname',
        'gender',
        'date_of_birth',
        'phone',
        'status',
        'organization_id',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected $append = [
        'name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
            'gender' => UserGenderEnum::class,
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function getNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function isAdmin()
    {
        return $this->hasRole(UserRoleEnum::ADMIN->value);
    }

    public function isSuperAdmin()
    {
        return $this->hasRole(UserRoleEnum::SUPER_ADMIN->value);
    }

    public function isUser()
    {
        return $this->hasRole(UserRoleEnum::USER->value);
    }

    public function isAgent()
    {
        return $this->hasRole(UserRoleEnum::AGENT->value);
    }

    public function posts()
    {
        return $this->hasMany(\App\Models\Post\Post::class);
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\Post\Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(\App\Models\Post\Like::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
