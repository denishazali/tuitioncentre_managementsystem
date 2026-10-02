<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends AdminController
{
    protected string $module = 'users';

    protected array $columns = [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'account_status' => 'Status',
    ];

    public function index()
    {
        return $this->listing(User::orderBy('name'));
    }

    public function create()
    {
        return $this->form(new User([
            'role' => 'parent',
            'account_status' => 'active',
        ]));
    }

    public function store(Request $request)
    {
        // The existing User model hashes passwords through its cast.
        User::create($this->validated($request));

        return $this->saved('User created.');
    }

    public function edit(User $user)
    {
        return $this->form($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if (
            $request->user()->is($user) && ($data['role'] !== 'admin' || $data['account_status'] !== 'active')
        ) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own active administrator access.',
            ]);
        }

        if (
            $data['role'] !== $user->role && $this->hasStudentLinks($user)
        ) {
            throw ValidationException::withMessages([
                'role' => 'This account has student or parent records. Its role cannot be changed here.',
            ]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return $this->saved('User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'record' => 'You cannot delete your own account.',
            ]);
        }

        $inUse = $this->hasStudentLinks($user)
            || $user->createdQuizzes()->exists()
            || DB::table('payments')
                ->where('approved_by', $user->id)->exists()
            || DB::table('exam_results')
                ->where('created_by', $user->id)->exists();

        return $this->deleteRecord($user, $inUse);
    }

    private function hasStudentLinks(User $user): bool
    {
        return $user->studentProfile()->exists()
            || $user->children()->exists()
            || DB::table('enrollments')
                ->where('parent_id', $user->id)->exists();
    }

    private function validated(
        Request $request,
        ?User $user = null
    ): array {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:72',
                'confirmed',
            ],
            'role' => [
                'required',
                Rule::in(['admin', 'parent', 'student']),
            ],
            'account_status' => [
                'required',
                Rule::in(['pending', 'active', 'inactive']),
            ],
        ]);
    }
}