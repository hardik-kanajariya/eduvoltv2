<?php

declare(strict_types=1);

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Example controller demonstrating policy usage
 *
 * This controller shows how to use tenant-scoped policies
 * in real controller actions with proper authorization.
 */
class ExampleUserController extends Controller
{
    /**
     * Display a listing of users (with tenant scoping).
     */
    public function index(Request $request): JsonResponse
    {
        // Policy automatically checks if user can view any users in their tenant
        $this->authorize('viewAny', User::class);

        // In a real implementation, you would:
        // $users = User::where('tenant_id', auth()->user()->tenant_id)->get();

        return response()->json([
            'message' => 'Users listed successfully',
            'note' => 'Policy check passed - user can view users in their tenant'
        ]);
    }

    /**
     * Display the specified user (with tenant scoping).
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        // In a real implementation, you would:
        // $user = User::findOrFail($userId);

        // For demonstration, create a mock user
        $user = new User(['id' => $userId, 'tenant_id' => 1]);

        // Policy automatically checks tenant ownership
        $this->authorize('view', $user);

        return response()->json([
            'message' => 'User viewed successfully',
            'user_id' => $userId,
            'note' => 'Policy check passed - user belongs to same tenant'
        ]);
    }

    /**
     * Store a newly created user (with tenant scoping).
     */
    public function store(Request $request): JsonResponse
    {
        // Policy checks if user can create users in their tenant
        $this->authorize('create', User::class);

        // In a real implementation, you would:
        // $user = User::create(array_merge($request->validated(), [
        //     'tenant_id' => auth()->user()->tenant_id
        // ]));

        return response()->json([
            'message' => 'User created successfully',
            'note' => 'Policy check passed - user can create users in their tenant'
        ], 201);
    }

    /**
     * Update the specified user (with tenant scoping).
     */
    public function update(Request $request, int $userId): JsonResponse
    {
        // In a real implementation:
        // $user = User::findOrFail($userId);

        // For demonstration
        $user = new User(['id' => $userId, 'tenant_id' => 1]);

        // Policy checks tenant ownership
        $this->authorize('update', $user);

        return response()->json([
            'message' => 'User updated successfully',
            'user_id' => $userId,
            'note' => 'Policy check passed - user belongs to same tenant'
        ]);
    }

    /**
     * Remove the specified user (with tenant scoping).
     */
    public function destroy(Request $request, int $userId): JsonResponse
    {
        // In a real implementation:
        // $user = User::findOrFail($userId);

        // For demonstration
        $user = new User(['id' => $userId, 'tenant_id' => 1]);

        // Policy checks tenant ownership
        $this->authorize('delete', $user);

        return response()->json([
            'message' => 'User deleted successfully',
            'user_id' => $userId,
            'note' => 'Policy check passed - user belongs to same tenant'
        ]);
    }

    /**
     * Example of custom policy method usage.
     */
    public function changePassword(Request $request, int $userId): JsonResponse
    {
        // In a real implementation:
        // $user = User::findOrFail($userId);

        // For demonstration
        $user = new User(['id' => $userId, 'tenant_id' => 1]);

        // Use custom policy method
        $this->authorize('changePassword', $user);

        return response()->json([
            'message' => 'Password changed successfully',
            'user_id' => $userId,
            'note' => 'Custom policy method check passed'
        ]);
    }

    /**
     * Example of transfer between tenants.
     */
    public function transferToTenant(Request $request, int $userId, int $targetTenantId): JsonResponse
    {
        // In a real implementation:
        // $user = User::findOrFail($userId);

        // For demonstration
        $user = new User(['id' => $userId, 'tenant_id' => 1]);

        // Check if user can transfer to target tenant
        if (!$user->getPolicy()->transfer(auth()->user(), $user, $targetTenantId)) {
            abort(403, 'Cannot transfer user to target tenant');
        }

        return response()->json([
            'message' => 'User transfer authorized',
            'user_id' => $userId,
            'target_tenant_id' => $targetTenantId,
            'note' => 'Transfer authorization check passed'
        ]);
    }

    /**
     * Example using Gate facade directly.
     */
    public function checkPermissions(Request $request, int $userId): JsonResponse
    {
        // In a real implementation:
        // $user = User::findOrFail($userId);

        // For demonstration
        $user = new User(['id' => $userId, 'tenant_id' => 1]);

        $permissions = [
            'can_view' => \Gate::allows('view', $user),
            'can_update' => \Gate::allows('update', $user),
            'can_delete' => \Gate::allows('delete', $user),
            'can_change_password' => \Gate::allows('changePassword', $user),
        ];

        return response()->json([
            'message' => 'Permissions checked',
            'user_id' => $userId,
            'permissions' => $permissions,
            'note' => 'All permissions checked using Gate facade'
        ]);
    }
}
