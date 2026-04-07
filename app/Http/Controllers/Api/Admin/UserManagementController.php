<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

class UserManagementController extends Controller
{
    #[OA\Get(
        path: '/api/admin/users',
        summary: 'List users for current company (managers see own company; admin can pass company_id)',
        security: [['sanctum' => []]],
        tags: ['Admin / Users'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of users'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        $companyId = $currentUser->role === 'admin' && $request->filled('company_id')
            ? (int) $request->company_id
            : $currentUser->company_id;

        $users = User::where('company_id', $companyId)
            ->with('supervisor')
            ->latest()
            ->paginate(10);

        return response()->json(UserResource::collection($users)->response()->getData(true));
    }

    #[OA\Post(
        path: '/api/admin/users',
        summary: 'Create a new user',
        security: [['sanctum' => []]],
        tags: ['Admin / Users'],
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:admin,manager,user',
            'hired_at' => 'nullable|date',
            'supervisor_id' => 'nullable|exists:users,id',
            'employee_id' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        /** @var User $currentUser */
        $currentUser = $request->user();
        $companyId = $currentUser->role === 'admin' && $request->filled('company_id')
            ? (int) $request->company_id
            : $currentUser->company_id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'hired_at' => $request->hired_at,
            'supervisor_id' => $request->supervisor_id,
            'employee_id' => $request->employee_id,
            'position' => $request->position,
            'department_id' => $request->department_id,
            'company_id' => $companyId,
        ]);

        return response()->json(new UserResource($user), 201);
    }

    #[OA\Put(
        path: '/api/admin/users/{id}',
        summary: 'Update a user',
        security: [['sanctum' => []]],
        tags: ['Admin / Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'User updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,manager,user',
            'hired_at' => 'nullable|date',
            'supervisor_id' => 'nullable|exists:users,id',
            'employee_id' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'hired_at' => $request->hired_at,
            'supervisor_id' => $request->supervisor_id,
            'employee_id' => $request->employee_id,
            'position' => $request->position,
            'department_id' => $request->department_id,
        ]);

        return response()->json(new UserResource($user));
    }

    #[OA\Delete(
        path: '/api/admin/users/{id}',
        summary: 'Delete a user',
        security: [['sanctum' => []]],
        tags: ['Admin / Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
