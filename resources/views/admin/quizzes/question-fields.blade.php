@php
    $question = array_filter($question, 'is_string');
@endphp

<section data-question class="space-y-4 rounded-lg border p-4">
    <label class="block font-semibold">
        Question
        <textarea name="questions[{{ $i }}][question]" maxlength="3000" rows="3" required class="mt-2 block w-full rounded border p-3">{{ $question['question'] ?? '' }}</textarea>
    </label>

    @foreach (['A', 'B', 'C', 'D'] as $letter)
        @php
            $key = 'option_' . strtolower($letter);
        @endphp

        <label class="block">
            Option {{ $letter }}
            <input name="questions[{{ $i }}][{{ $key }}]" value="{{ $question[$key] ?? '' }}" maxlength="255" required class="mt-1 block w-full rounded border p-3">
        </label>
    @endforeach

    <label class="block">
        Correct answer
        <select name="questions[{{ $i }}][correct_answer]" required class="mt-1 block w-full rounded border p-3">
            <option value="">Choose an answer</option>

            @foreach (['A', 'B', 'C', 'D'] as $letter)
                <option value="{{ $letter }}" @selected(($question['correct_answer'] ?? '') === $letter)>
                    {{ $letter }}
                </option>
            @endforeach
        </select>
    </label>

    <button type="button" data-remove-question class="button button-secondary">
        Remove question
    </button>
</section>
