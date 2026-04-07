<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CompanyManagementController extends Controller
{
    #[OA\Get(
        path: '/api/admin/companies',
        summary: 'List all companies (super admin)',
        security: [['sanctum' => []]],
        tags: ['Admin / Companies'],
        responses: [
            new OA\Response(response: 200, description: 'List of companies'),
        ]
    )]
    public function index(): JsonResponse
    {
        $companies = Company::withCount('users')->latest()->get();

        return response()->json(CompanyResource::collection($companies));
    }

    #[OA\Post(
        path: '/api/admin/companies',
        summary: 'Create a new company',
        security: [['sanctum' => []]],
        tags: ['Admin / Companies'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'code', type: 'string', nullable: true),
                    new OA\Property(property: 'tax_id', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Company created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:companies,name',
            'code' => 'nullable|string|max:50|unique:companies,code',
            'tax_id' => 'nullable|string|max:20|unique:companies,tax_id',
        ]);

        $company = Company::create($validated);

        return response()->json(new CompanyResource($company), 201);
    }

    #[OA\Delete(
        path: '/api/admin/companies/{id}',
        summary: 'Delete a company',
        security: [['sanctum' => []]],
        tags: ['Admin / Companies'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
        ]
    )]
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(['message' => 'Company deleted.']);
    }
}
