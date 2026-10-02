<x-layout title="Review Enrollment">
    <section class="section">
        <div class="container">
            <h1 class="mb-6 text-3xl font-bold">Review Enrollment</h1>

            @include('partials.form-messages')

            <div class="max-w-2xl space-y-4 rounded-xl border bg-white p-6">
                <p>
                    <strong>Child:</strong>
                    {{ $child->name }} — Form {{ $child->form_level }}
                </p>

                <p>
                    <strong>Selection:</strong>
                    {{ $package ? $package->name : 'Individual subjects' }}
                </p>

                <div>
                    <h2 class="mb-2 font-semibold">Selected subjects</h2>

                    <ul class="list-disc pl-5">
                        @foreach ($subjects as $subject)
                            <li>
                                {{ $subject->name }}

                                @unless ($package)
                                    — RM {{ number_format($subject->price,2) }}
                                @endunless 
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="text-xl font-bold">Total fee: RM {{ $total }}</p>

                <p>
                    Submit this enrollment, then pay at the centre counter.
                    Your enrollment requires administrator approval.
                </p>

                <form method="POST" action="{{ route('parent.enrollments.store') }}">
                    @csrf

                    <input type="hidden" name="quote_token" value="{{ $quoteToken }}">

                    <button type="submit" class="button button-primary">Submit enrollment — pay at counter</button>
                </form>

                <a href="{{ route('parent.enrollments.index') }}" class="button button-secondary">
                    Return to catalogue
                </a>
            </div>

        </div>
    </section>


</x-layout>