<x-layout title="My Enrollments">
    <section class="section">
        <div class="container">
            <h1 class="mb-6 text-3xl font-bold">My Enrollments</h1>

            @include('partials.form-messages')

            <a href="{{ route('parent.enrollments.index') }}" class="button button-primary mb-6">
                Browse subjects and packages 
            </a>

            @forelse ($enrollments as $enrollment)
                <article class="mb-6 space-y-3 rounded-xl border bg-white p-6">
                    <h2 class="text-xl font-bold">
                        Enrollment #{{ $enrollment->id }}
                        — {{ $enrollment->student->name }}
                    </h2>

                    <p>
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
                        Total fees:
                        RM {{ number_format($enrollment->total_amount, 2) }}
                    </p>

                    <p>
                        Enrollment:
                        {{ ucwords(str_replace('_', ' ', $enrollment->status)) }}
                    </p>

                    <p>
                        Payment:
                        {{ ucfirst($enrollment->payment?->payment_status ?? 'Not recorded') }}
                    </p>

                    @if ($enrollment->status === 'pending_payment')
                        <p>
                            Please pay at the centre counter and provide
                            enrollment number #{{ $enrollment->id }}.
                        </p>
                    @elseif ($enrollment->status === 'pending_approval')
                        <p>
                            Payment has been confirmed.
                            Your enrollment is awaiting approval.
                        </p>
                    @elseif ($enrollment->status === 'approved')
                        <p>Your enrollment has been approved.</p>
                    @endif

                    @if ($enrollment->rejection_reason)
                        <p>
                            <strong>Reason:</strong>
                            {{ $enrollment->rejection_reason }}
                        </p>
                    @endif

                    @if (
                        $enrollment->status === "rejected"
                        && $enrollment->payment?->payment_status === 'paid'
                    )
                        <p>
                            Payment was received for this enrollment.
                            Contact the centre to arrange a refund.
                        </p>
                    @endif
                </article>
            @empty
                <p>You have no enrollments to display.</p>
            @endforelse

            {{ $enrollments->links() }}
        </div>
    </section>
</x-layout>