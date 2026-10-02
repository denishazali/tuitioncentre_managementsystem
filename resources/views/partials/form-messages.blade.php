@if (session('success'))
    <div class="alert alert-success mb-6" role="status">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error mb-6" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
