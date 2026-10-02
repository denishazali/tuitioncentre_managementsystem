<x-admin-layout :title="ucfirst($module)">
    <div class="mb-6">
        <a href="{{ route('admin.' . $module . '.create') }}" class="button button-primary">
            Add record
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    @foreach ($columns as $label)
                        <th scope="col" class="p-4">{{ $label }}</th>
                    @endforeach

                    <th scope="col" class="p-4">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($records as $record)
                    <tr class="border-b">
                        @foreach ($columns as $field => $label)
                            @php
                                $value = data_get($record, $field);

                                if (is_bool($value)) {
                                    $value = $value ? 'Yes' : 'No';
                                } elseif ($value instanceof \DateTimeInterface) {
                                    $value = $value->format('d M Y H:i');
                                }
                            @endphp

                            <td class="p-4">{{ $value ?? '—'}}</td>
                        @endforeach

                        <td class="p-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('admin.' . $module . '.edit', $record) }}" class="button button-secondary">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('admin.' . $module . '.destroy', $record) }}" onsubmit="return confirm('Delete this record?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="button button-secondary">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="p-6">
                            No records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $records->links() }}</div>
</x-admin-layout>
