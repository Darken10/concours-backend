<?php

namespace App\Services;

use App\Data\Organization\CreateAgentData;
use App\Data\Organization\CreateOrganizationData;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganizationService
{
    public function createOrganization(CreateOrganizationData $data): Organization
    {
        return DB::transaction(function () use ($data) {
            $org = Organization::create([
                'name' => $data->name,
                'description' => $data->description,
            ]);

            return $org;
        });
    }

    /**
     * Assign an admin to the organization.
     * Accepts either an existing user id in $payload['user_id'] or user data (email/first_name/last_name).
     * The performer must be a super-admin or an admin of the organization. If the org has no admin yet,
     * only a super-admin can assign the first admin.
     */
    public function assignAdmin(array $payload, User $performer, Organization $organization): User
    {
        $isSuper = $performer->hasRole('super-admin');
        $isOrgAdmin = $performer->hasRole('admin') && $performer->organization_id === $organization->id;

        // check if org already has an admin
        $hasAdmin = User::where('organization_id', $organization->id)->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->exists();

        if (! $isSuper && ! $isOrgAdmin) {
            throw new AuthorizationException('Not allowed to assign admin for this organization');
        }

        if ($hasAdmin && ! ($isSuper || $isOrgAdmin)) {
            throw new AuthorizationException('Organization already has an admin');
        }

        return DB::transaction(function () use ($payload, $organization) {
            if (! empty($payload['user_id'])) {
                $user = User::findOrFail($payload['user_id']);
                $user->organization_id = $organization->id;
                $user->save();
            } else {
                $user = User::create([
                    'email' => $payload['email'],
                    'password' => Hash::make(Str::random(16)),
                    'firstname' => $payload['firstname'],
                    'lastname' => $payload['lastname'],
                    'phone' => $payload['phone'] ?? null,
                    'avatar' => $payload['avatar'] ?? null,
                    'organization_id' => $organization->id,
                ]);
            }

            if (! $user->hasRole('admin')) {
                $user->assignRole('admin');
            }

            return $user;
        });
    }

    /**
     * Create an agent inside the organization.
     * Only organization admin (of this org) or users with role 'super-admin' can create agents.
     */
    public function createAgent(CreateAgentData $data, User $creator, Organization $organization): User
    {
        // authorization
        $isSuper = $creator->hasRole('super-admin');
        $isOrgAdmin = $creator->hasRole('admin') && $creator->organization_id === $organization->id;

        if (! ($isSuper || $isOrgAdmin)) {
            throw new AuthorizationException('Not allowed to create agents for this organization');
        }

        return DB::transaction(function () use ($data, $organization) {
            $user = User::create([
                'email' => $data->email,
                'password' => Hash::make(Str::random(16)),
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'phone' => $data->phone,
                'avatar' => $data->avatar,
                'organization_id' => $organization->id,
            ]);

            $user->assignRole('agent');

            return $user;
        });
    }
}
