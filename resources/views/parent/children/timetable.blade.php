<x-child-learning-layout :child="$child" title="Timetable">
    @if ($subjectCount === 0)
        <p>
            The timetable becomes available after enrollment approval.
        </p>
    @elseif ($slots->isEmpty())
        <p>
            No timetable slots are available for this child.
            Please contact the centre for the schedule.
        </p>
    @else
        <p class="mb-6">
            Weekly classes for {{ $child->name }}.
        </p>

        <div class="overflow-x-auto rounded-xl border bg-white">
            <table class="w-full text-left">
                <caption class="sr-only">
                    Weekly timetable for {{ $child->name }}
                </caption>

                <thead class="bg-gray-100">
                    <tr>
                        <th scope="col" class="p-4">Day</th>
                        <th scope="col" class="p-4">Time</th>
                        <th scope="col" class="p-4">Subject</th>
                        <th scope="col" class="p-4">Teacher</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($slots as $slot)
                        @php
                            $start = \Carbon\CarbonImmutable::createFromTimeString(
                                $slot->start_time
                            );

                            $end = $start->addMinutes(
                                (int) $slot->duration_minutes
                            );
                        @endphp

                        <tr class="border-t">
                            <td class="p-4">{{ $slot->day }}</td>

                            <td class="whitespace-nowrap p-4">
                                {{ $start->format('H:i') }}
                                –
                                {{ $end->format('H:i') }}
                            </td>

                            <td class="p-4">{{ $slot->subject->name }}</td>
                            <td class="p-4">{{ $slot->teacher->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-child-learning-layout>