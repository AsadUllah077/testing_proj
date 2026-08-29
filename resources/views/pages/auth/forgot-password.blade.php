@extends('layouts.guest')

@section('content')

    <body class="font-inter overflow-hidden">
        <section class="flex justify-center items-center h-screen relative">
            <img src="https://pagedone.io/asset/uploads/1702362010.png" alt="gradient background image"
                class="w-full h-full object-cover fixed">
            <div class="mx-auto max-w-lg px-6 lg:px-8 absolute py-20">

                <div class="rounded-2xl bg-white shadow-xl">
                    <form id="formSubmit" action_url="{{ route('password.email') }}" method="post"
                        class="lg:p-11 p-7 mx-auto">
                        @csrf
                        <div class="mb-11">
                            <h1 class="text-gray-900 text-center font-manrope text-3xl font-bold leading-10 mb-2">Forgot Password</h1>
                            <p class="text-gray-500 text-center text-sm">Enter your email address and we'll send you a link to reset your password.</p>
                        </div>
                        <input type="email" name="email" id="email"
                            class="w-full h-12 text-gray-900 placeholder:text-gray-400 text-lg font-normal leading-7 rounded-[10px] border-gray-300 border shadow-sm focus:outline-none px-4 mb-6"
                            placeholder="Email" required>
                        
                        <button type="submit"
                            class="w-full h-12 text-white text-center text-base font-semibold leading-6 rounded-[10px] hover:bg-indigo-800 transition-all duration-700 bg-indigo-600 shadow-sm mb-6">Send Reset Link</button>

                        <div class="text-center">
                            <a href="{{ route('user.login.view') }}" class="text-indigo-600 font-medium hover:underline">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </body>
@endsection
