<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentLinkRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLinkRequestController extends Controller
{
    public function index()
    {
        $linkRequests = StudentLinkRequest::query()
            ->with('parent')
            ->where('status', 'pending')
            ->oldest()
            ->paginate(15);

        // Candidates are visible only to administrators.
        $candidates = [];

        foreach ($linkRequests as $linkRequest) {
            $candidates[$linkRequest->id] = Student::query()
                ->with('parent')
                ->whereRaw(
                    "UPPER(REPLACE(REPLACE(ic, ' ', ''), '-', '')) = ?",
                    [$linkRequest->student_ic]
                )
                ->get();
        }

        return view('admin.student-link-requests.index', [
            'linkRequests' => $linkRequests,
            'candidates' => $candidates,
        ]);
    }

    public function approve(
        Request $request,
        StudentLinkRequest $linkRequest
    ) {
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'expected_parent_id' => ['required', 'integer'],
            'verified' => ['accepted'],
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $linkRequest, $data) {
            $review = StudentLinkRequest::query()
                ->whereKey($linkRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($review);

            $parent = User::query()
                ->whereKey($review->parent_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $parent->role !== 'parent'
                || $parent->account_status !== 'active'
            ) {
                throw ValidationException::withMessages([
                    'request' => 'The requesting parent must be active.',
                ]);
            }

            $student = Student::query()
                ->whereKey($data['student_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $studentIc = strtoupper(
                str_replace([' ', '-'], '', $student->ic)
            );

            if ($studentIc !== $review->student_ic) {
                throw ValidationException::withMessages([
                    'request' => 'The selected student does not match '
                        .'the requested IC or passport number.',
                ]);
            }

            if (
                (int) $student->parent_id !== (int) $data['expected_parent_id']
            ) {
                throw ValidationException::withMessages([
                    'request' => 'The assigned parent changed. '
                        .'Refresh this page and review again.',
                ]);
            }

            if ((int) $student->parent_id === (int) $parent->id) {
                throw ValidationException::withMessages([
                    'request' => 'This student already belongs to '
                        .'the requesting parent.',
                ]);
            }

            $previousParentId = $student->parent_id;

            $student->update([
                'parent_id' => $parent->id,
            ]);

            $review->update([
                'status' => 'approved',
                'student_id' => $student->id,
                'previous_parent_id' => $previousParentId,
                'reviewed_by' => $request->user()->id,
                'review_note' => $data['review_note'],
                'reviewed_at' => now(),
            ]);
        }, 3);

        return to_route('admin.student-link-requests.index')
            ->with('success', 'Student link approved.');
    }

    public function reject(
        Request $request,
        StudentLinkRequest $linkRequest
    ) {
        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $linkRequest, $data) {
            $review = StudentLinkRequest::query()
                ->whereKey($linkRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($review);

            $review->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'review_note' => $data['review_note'],
                'reviewed_at' => now(),
            ]);
        }, 3);

        return to_route('admin.student-link-requests.index')
            ->with('success', 'Student link request rejected.');
    }

    private function ensurePending(StudentLinkRequest $request): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'This request has already been reviewed.',
            ]);
        }
    }
}
