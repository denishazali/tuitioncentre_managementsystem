<x-admin-layout title="Admin dashboard">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($counts as $module => $count)
            <a href="{{ route('admin.' . $module . '.index') }}" class="rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold">
                    {{ ucfirst($module) }}
                </h2>

                <p class="mt-3 text-4xl font-bold">{{ $count }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-8 rounded-lg bg-white p-6">
        <h2 class="text-xl font-semibold">Account awaiting approval</h2>
        <p class="mt-2">{{ $pendingUsers }} pending accounts</p>

        <a href="{{ route('admin.users.index') }}" class="button button-secondary mt-4">
            Manage users
        </a>
    </div>
</x-admin-layout>
            
