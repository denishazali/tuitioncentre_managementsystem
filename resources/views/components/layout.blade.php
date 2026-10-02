@props([
    'title' => 'Bijak Tuition Centre',
    'description' => 'Quality tuition and academic support for secondary school students'
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $description }}">
    <title>{{ $title }} | Bijak Tuition Centre </title>
    <link rel="icon" type="image/png" href="{{ asset('favicon/graduation-logo.png') }}">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    @include('partials.public-navigation')

    <main id="main-content"> {{ $slot }}</main>

    @include('partials.public-footer')
</body>
</html>
