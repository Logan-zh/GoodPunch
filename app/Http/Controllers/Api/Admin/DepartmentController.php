<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DepartmentController extends Controller
{
    #[OA\Get(
        path: '/api/admin/departments',
        summary: 'List departments for current company',
        security: [['sanctum' => []]],
        tags: ['Admin / Departments'],
        responses: [
            new OA\Response(response: 200, description: 'List of departments'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $departments = Department::where('company_id', $companyId)
            ->with(['children', 'manager', 'parent'])
            ->withCount('users')
            ->get();

        return response()->json(DepartmentResource::collection($departments));
    }

    #[OA\Post(
        path: '/api/admin/departments',
        summary: 'Create a department',
        security: [['sanctum' => []]],
        tags: ['Admin / Departments'],
        responses: [
            new OA\Response(response: 201, description: 'Department created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $department = Department::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'parent_id' => $request->parent_id ?: null,
            'manager_id' => $request->manager_id ?: null,
        ]);

        return response()->json(new DepartmentResource($department), 201);
    }

    #[OA\Put(
        path: '/api/admin/departments/{id}',
        summary: 'Update a department',
        security: [['sanctum' => []]],
        tags: ['Admin / Departments'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
        ]
    )]
    public function update(Request $request, Department $department): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $department->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id ?: null,
            'manager_id' => $request->manager_id ?: null,
        ]);

        return response()->json(new DepartmentResource($department));
    }

    #[OA\Delete(
        path: '/api/admin/departments/{id}',
        summary: 'Delete a department',
        security: [['sanctum' => []]],
        tags: ['Admin / Departments'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 422, description: 'Has assigned users'),
        ]
    )]
    public function destroy(Department $department): JsonResponse
    {
        if ($department->users()->exists()) {
            return response()->json(['message' => 'Cannot delete department with assigned users.'], 422);
        }

        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }

    #[OA\Post(
        path: '/api/admin/departments/{id}/assign',
        summary: 'Assign a user to a department',
        security: [['sanctum' => []]],
        tags: ['Admin / Departments'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [new OA\Property(property: 'user_id', type: 'integer')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Assigned'),
        ]
    )]
    public function assignUser(Request $request, Department $department): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        User::where('id', $request->user_id)
            ->where('company_id', $department->company_id)
            ->update(['department_id' => $department->id]);

        return response()->json(['message' => 'User assigned to department.']);
    }
}
