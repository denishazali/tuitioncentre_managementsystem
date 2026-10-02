<x-admin-layout
    :title="($record->exists ? 'Edit ' : 'Add ') . ucfirst($module)">

    <form method="POST" action="{{ $record->exists ? route('admin.' . $module . '.update' , $record) : route('admin.' . $module . '.store') }}" class="max-w-3xl space-y-5 rounded-lg bg-white p-6">

        @csrf

        @if ($record->exists)
            @method('PUT')
        @endif

        @include('admin.' . $module . '.fields')

        <div class="flex gap-3">
            <button type="submit" class="button button-primary">
                Save
            </button>

            <a href="{{ route('admin.' . $module . '.index') }}" class="button button-secondary">
                Cancel
            </a>
        </div>
    </form>
</x-admin-layout>
