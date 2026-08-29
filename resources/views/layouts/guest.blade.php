<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
    {{-- @if (env('APP_DEBUG')) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- @else
        <link rel="stylesheet" href="{{ asset('build/assets/app-C5Cb5btP.css') }}">
        <script src="{{ asset('build/assets/app-D7LlfAo0.js') }}"></script>
    @endif --}}
</head>

<body>
    @yield('content')
    <x-message />
    <script src="{{ asset('assets/js/jquery.js') }}"></script>
    <script src="{{ asset('assets/js/form-submit.js') }}"></script>
    <script src="{{ asset('assets/js/message.js') }}"></script>
    @stack('scripts')
</body>

</html>
