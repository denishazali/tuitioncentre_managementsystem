<x-admin-layout title="Student Link Request">
    <p class="mb-6">
        Verify the parent's identity and relationship with the student
        through the centre's records before approving.
        Approval replaces the student's currently assigned parent.
    </p>

    @forelse ($linkRequests as $linkRequest)
        <article class="mb-8 rounded-xl border bg-white p-6">
            <h2 class="text-xl font-bold">
                {{  $linkRequest->student_name }}
            </h2>

            <p>
                Requested by:
                {{ $linkRequest->parent->name }}
                ({{  $linkRequest->parent->email }})
            </p>

            <p>Submitted IC / passport: {{ $linkRequest->student_ic }}</p>

            @if ($linkRequest->message)
                <p class="my-3">{{ $linkRequest->message }}</p>
            @endif

            <h3 class="mb-3 mt-5 font-bold">Matching student records</h3>

            @forelse ($candidates[$linkRequest->id] as $student)
                <div class="mb-4 rounded border p-4">
                    <p>Student #{{  $student->id }}: {{ $student->name }}</p>
                    <p>Date of birth: {{ $student->date_of_birth->format('d M Y') }}</p>
                    <p>Form {{ $student->form_level }} — {{ $student->school_name }}</p>
                    <p>Current parent: {{ $student->parent->name }}({{ $student->parent->email }})</p>
                    <form method="POST" action="{{ route('admin.student-link-requests.approve', $linkRequest) }}" class="mt-4 space-y-3">

                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                        <input type="hidden" name="expected_parent_id" value="{{ $student->parent_id }}">
                        <label class="block">
                            Verification note — internal
                            <textarea name="review_note" required maxlength="1000" rows="3" class="block w-full rounded border p-2" placeholder="Record how the relationship was verified."></textarea>
                        </label>
                        
                        <label class="block">
                            <input type="checkbox" name="verified" value="1" required>
                            I verified this relationship and approve replacing the current assigned parent.
                        </label>

                        <button type="submit" class="button button-primary">Approve this student</button>
                    </form>
                </div>
            @empty 
                <p class="mb-4">No matching student record. Verify details before taking action.</p>
            @endforelse 

            <form method="POST" action="{{  route('admin.student-link-requests.reject', $linkRequest) }}" class="mt-5 space-y-3">
                @csrf
                @method('PATCH')
                <label class="block">
                    Rejection note — internal
                    <textarea name="review_note" required maxlength="1000" rows="2" class="block w-full rounded border p-2"></textarea>
                </label>
                <button type="submit" class="button button-secondary">Reject request</button>
             </form>
        </article>
    @empty 
        <p>No pending student link requests.</p>
    @endforelse

    {{ $linkRequests->links() }}
</x-admin-layout>