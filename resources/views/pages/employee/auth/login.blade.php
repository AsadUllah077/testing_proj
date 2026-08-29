<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-[#F2F3F4] min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg border border-black/10 shadow-sm p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-[#0078C8]">Employee Portal</h1>
                <p class="text-sm text-gray-500 mt-1">Sign in to view your payslips</p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded p-3 mb-4 text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('employee.login.submit') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-[#0078C8] focus:border-[#0078C8] outline-none">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-[#0078C8] focus:border-[#0078C8] outline-none">
                </div>
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300">
                        Remember me
                    </label>
                </div>
                <button type="submit"
                    class="w-full py-2.5 bg-[#0078C8] text-white rounded font-semibold hover:bg-[#006bb0] transition">
                    Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
