<x-admin-layout title="Enrollments">
    @forelse ($enrollments as $enrollment)
        <article class="mb-6 space-y-3 rounded-xl border bg-white p-6">
            <h2 class="text-xl font-bold">
                Enrollment #{{ $enrollment->id }}
                — {{ $enrollment->student->name }}
            </h2>

            <p>
                Parent:
                {{ $enrollment->parent->name }}
                ({{ $enrollment->parent->email }})
            </p>

            <p>
                Selection:
                {{ $enrollment->type === 'bundle'
                    ? ($enrollment->package?->name ?? 'Package')
                    : 'Individual subjects' }}
            </p>

            <ul class="list-disc pl-5">
                @foreach ($enrollment->subjects as $subject)
                    <li>{{ $subject->name }}</li>
                @endforeach
            </ul>

            <p class="font-semibold">
                Total: RM {{ number_format($enrollment->total_amount, 2) }}
            </p>

            <p>
                Enrollment:
                {{ ucwords(str_replace('_', ' ', $enrollment->status)) }}
            </p>

            <p>
                Payment:
                {{ ucfirst($enrollment->payment?->payment_status ?? 'Missing') }}
            </p>

            @if ($enrollment->rejection_reason)
                <p>Reason: {{ $enrollment->rejection_reason }}</p>
            @endif

            @if (
                $enrollment->status === 'rejected'
                && $enrollment->payment?->payment_status === 'paid'
            )
                <p>
                    Payment was received. Arrange any required refund
                    separately; this page does not issue refunds.
                </p>
            @endif

            @if (in_array($enrollment->status, [
                'pending_payment',
                'pending_approval',
            ], true))
                <form
                    method="POST"
                    action="{{ route('admin.enrollments.update', $enrollment) }}"
                    class="mt-4 max-w-xl space-y-4"
                >
                    @csrf
                    @method('PATCH')

                    <label class="block">
                        Action
                        <select
                            name="action"
                            required
                            class="block w-full rounded border p-2"
                        >
                            <option value="">Choose an action</option>

                            @if ($enrollment->status === 'pending_payment')
                                <option value="paid">
                                    Confirm counter payment received
                                </option>
                            @else
                                <option value="approve">
                                    Approve enrollment
                                </option>
                            @endif

                            <option value="reject">Reject enrollment</option>
                        </select>
                    </label>

                    @if ($enrollment->status === 'pending_payment')
                        <label class="block">
                            Amount received (RM)
                            <input
                                type="number"
                                name="received_amount"
                                min="0"
                                max="99999999.99"
                                step="0.01"
                                class="block w-full rounded border p-2"
                            >
                        </label>

                        <label class="block">
                            <input
                                type="checkbox"
                                name="payment_received"
                                value="1"
                            >
                            I confirm the centre received this payment.
                        </label>
                    @endif

                    <label class="block">
                        Rejection reason — required when rejecting
                        <textarea
                            name="reason"
                            rows="3"
                            maxlength="1000"
                            class="block w-full rounded border p-2"
                        ></textarea>
                    </label>

                    <button type="submit" class="button button-primary">
                        Apply action
                    </button>
                </form>
            @endif
        </article>
    @empty
        <p>No enrollments have been submitted.</p>
    @endforelse

    {{ $enrollments->links() }}
</x-admin-layout>