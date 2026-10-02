<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentLinkRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        return view('parent.children.index', [
            'children' => $request->user()
                ->children()
                ->with('user')
                ->orderBy('name')
                ->get(),

            'linkRequests' => StudentLinkRequest::query()
                ->where('parent_id', $request->user()->id)
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ic' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'form_level' => ['required', 'integer', 'between:1,5'],
            'school_name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                'confirmed',
            ],
        ]);

        DB::transaction(function () use ($request, $data) {
            /*
             * Serialize registrations through this endpoint.
             * SQLite serializes database writes; the transaction will
             * retry concurrency failures.
             */
            User::query()
                ->where('role', 'parent')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            // Compare without spaces or hyphens to accommodate imported ICs.
            $ic = strtoupper(str_replace([' ', '-'], '', $data['ic']));

            if ($ic === '') {
                throw ValidationException::withMessages([
                    'ic' => 'Enter a valid IC or passport number.',
                ]);
            }

            $exists = Student::query()
                ->whereRaw(
                    "UPPER(REPLACE(REPLACE(ic, ' ', ''), '-', '')) = ?",
                    [$ic]
                )
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'ic' => 'This child cannot be registered here. '
                        .'Please submit an existing-student link request.',
                ]);
            }

            $studentUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                // Your User model already hashes passwords.
                'password' => $data['password'],
                'role' => 'student',
                'account_status' => 'active',
            ]);

            Student::create([
                'user_id' => $studentUser->id,
                'parent_id' => $request->user()->id,
                'name' => $data['name'],
                'ic' => $ic,
                'date_of_birth' => $data['date_of_birth'],
                'form_level' => $data['form_level'],
                'school_name' => $data['school_name'],
            ]);
        }, 3);

        return to_route('parent.children.index')
            ->with('success', 'Child registered successfully.');
    }

    public function requestLink(Request $request)
    {
        $data = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'student_ic' => ['required', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['student_ic'] = strtoupper(
            str_replace([' ', '-'], '', $data['student_ic'])
        );

        if ($data['student_ic'] === '') {
            throw ValidationException::withMessages([
                'student_ic' => 'Enter a valid IC or passport number.',
            ]);
        }

        DB::transaction(function () use ($request, $data) {
            User::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $pendingRequest = StudentLinkRequest::query()
                ->where('parent_id', $request->user()->id)
                ->where('student_ic', $data['student_ic'])
                ->where('status', 'pending')
                ->exists();

            if ($pendingRequest) {
                throw ValidationException::withMessages([
                    'student_ic' => 'You already have a pending request '
                        .'for this IC or passport number.',
                ]);
            }

            StudentLinkRequest::create([
                ...$data,
                'parent_id' => $request->user()->id,
                'status' => 'pending',
            ]);
        }, 3);

        return to_route('parent.children.index')
            ->with('success', 'Your request has been sent for admin review.');
    }
}