<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') — {{ $platformName ?? 'EduBook' }}</title>
    <meta name="description" content="@yield('meta_description', 'EduBook connects parents and students with verified teachers for CBSE, State Board, Matriculation and ICSE tuition. Book online or offline classes at transparent hourly rates.')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-full flex-col">
@include('partials.navbar')

<main class="flex-1">
    @if (session('success'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="alert-success" role="status" data-auto-dismiss="6000">{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="alert-error" role="alert" data-auto-dismiss="8000">{{ session('error') }}</div>
        </div>
    @endif

    @if (session('warning'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="alert-warning" role="status" data-auto-dismiss="8000">{{ session('warning') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="alert-error" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @yield('content')
</main>

@include('partials.footer')

<script>
    window.EduBook = {
        csrf: document.querySelector('meta[name="csrf-token"]')?.content,
        isOnline: navigator.onLine,
    };
</script>
@stack('scripts')
</body>
</html>
