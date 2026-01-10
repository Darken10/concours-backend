<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
          public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [

            'id' => $this->id,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'avatar' => $this->avatar,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,

            'roles' => $this->getRoleNames(),
            // 'permissions' => $this->getAllPermissions()->pluck('name'),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
