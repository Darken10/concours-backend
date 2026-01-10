<?php

namespace App\Http\Controllers\Api;

use App\Models\Organization;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\OrganizationService;
use App\Data\Organization\CreateAgentData;
use App\Http\Resources\OrganizationResource;
use App\Data\Organization\CreateOrganizationData;
use App\Http\Requests\Organization\CreateAgentRequest;
use App\Http\Requests\Organization\CreateOrganizationRequest;
use App\Http\Requests\Organization\AssignAdminRequest;

class OrganizationController extends Controller
{
    protected OrganizationService $service;

    public function __construct(OrganizationService $service)
    {
        $this->service = $service;
    }

    public function store(CreateOrganizationRequest $request)
    {
        $data = CreateOrganizationData::fromArray($request->validated());

        $org = $this->service->createOrganization($data);

        return response()->json(new OrganizationResource($org), 201);
    }

    public function show(Organization $organization)
    {
        $organization->load('users');
        return response()->json(new OrganizationResource($organization));
    }

    public function createAgent(CreateAgentRequest $request, Organization $organization)
    {
        $data = CreateAgentData::fromArray($request->validated());
        $user = $this->service->createAgent($data, $request->user(), $organization);

        return response()->json(['agent' => $user], 201);
    }

    public function assignAdmin(AssignAdminRequest $request, Organization $organization)
    {
        $payload = $request->validated();

        $user = $this->service->assignAdmin($payload, $request->user(), $organization);

        return response()->json(['admin' => $user], 201);
    }
}
