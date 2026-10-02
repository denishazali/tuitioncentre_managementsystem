<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function index()
    {
        return view('admin.enrollments.index', [
            'enrollments' => Enrollment::query()
                ->with(['student', 'parent', 'subjects', 'payment', 'package'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['paid', 'approve', 'reject'])],

            'received_amount' => [
                'required_if:action,paid',
                'nullable',
                'regex:/^\d{1,8}(\.\d{1,2})?$/',
            ],

            'payment_received' => ['accepted_if:action,paid'],

            'reason' => [
                'required_if:action,reject',
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use ($request, $enrollment, $data) {
            $student = Student::query()
                ->whereKey($enrollment->student_id)
                ->lockForUpdate()
                ->firstOrFail();

            $record = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $record->payment()
                ->lockForUpdate()
                ->first();

            $action = $data['action'];

            if ($action == 'reject') {
                $this->ensure(
                    in_array($record->status, [
                        'pending_payment',
                        'pending_approval',
                    ], true),
                    'Only pending enrollments can be rejected.'
                );

                $record->update([
                    'status' => 'rejected',
                    'rejection_reason' => $data['reason'],
                ]);

                if ($payment && $payment->payment_status === 'pending') {
                    $payment->update([
                        'payment_status' => 'rejected',
                        'rejection_reason'=> $data['reason'],
                    ]);
                }

                return;
            }

            $this->ensure(
                (int) $student->parent_id === (int) $record->parent_id,
                'The child\'s assigned parent has changed. '
                    .'Resolve the enrollment with the family before proceeding.'
            );

            $this->ensure(
                $payment !== null,
                'This enrollment has no payment record.'
            );

            $this->ensure(
                $payment->amount === $record->total_amount,
                'The payment amount does not match the enrollment fee.'
            );

            if ($action === 'paid') {
                $this->ensure(
                    $record->status === 'pending_payment'
                        && $payment->payment_status === 'pending'
                        && $payment->payment_method === 'counter',
                    'This enrollment is not awaiting a counter payment.'
                );

                $this->ensure(
                    $this->toCents($data['received_amount'])
                        === $this->toCents($record->total_amount),
                    'The received amount must equal the full enrollment fees.'
                );

                $payment->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'approved_at' => now(),
                    'approved_by' => $request->user()->id,
                    'rejection_reason' => null,
                ]);

                $record->update([
                    'status' => 'pending_approval',
                ]);

                return;
            }

            $this->ensure(
                $record->status === 'pending_approval'
                    && $payment->payment_status === 'paid',
                'Confirm payment before approving this enrollment.'
            );

            $record->update([
                'status' => 'approved',
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);
        }, 3);

        return to_route('admin.enrollments.index')
            ->with('success', 'Enrollment updated successfully.');
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([
                'enrollment' => $message,
            ]);
        }
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100)
        + (int) str_pad($fraction, 2, '0');
    }
}
