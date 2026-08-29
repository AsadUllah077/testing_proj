@extends('layouts.guest')

@section('content')

    <body class="font-inter overflow-hidden">
        <section class="flex justify-center items-center h-screen relative">
            <img src="https://pagedone.io/asset/uploads/1702362010.png" alt="gradient background image"
                class="w-full h-full object-cover fixed">
            <div class="mx-auto max-w-lg px-6 lg:px-8 absolute py-20">

                <div class="rounded-2xl bg-white shadow-xl">
                    <form id="formSubmit" action_url="{{ route('password.update') }}" method="post"
                        class="lg:p-11 p-7 mx-auto">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <div class="mb-11">
                            <h1 class="text-gray-900 text-center font-manrope text-3xl font-bold leading-10 mb-2">Reset Password</h1>
                            <p class="text-gray-500 text-center text-sm">Please enter your new password below.</p>
                        </div>
                        
                        <input type="email" name="email" value="{{ $email ?? old('email') }}"
                            class="w-full h-12 text-gray-900 placeholder:text-gray-400 text-lg font-normal leading-7 rounded-[10px] border-gray-300 border shadow-sm focus:outline-none px-4 mb-6"
                            placeholder="Email" required readonly>

                        <input type="password" name="password"
                            class="w-full h-12 text-gray-900 placeholder:text-gray-400 text-lg font-normal leading-7 rounded-[10px] border-gray-300 border shadow-sm focus:outline-none px-4 mb-6"
                            placeholder="New Password" required minlength="8">

                        <input type="password" name="password_confirmation"
                            class="w-full h-12 text-gray-900 placeholder:text-gray-400 text-lg font-normal leading-7 rounded-[10px] border-gray-300 border shadow-sm focus:outline-none px-4 mb-11"
                            placeholder="Confirm New Password" required minlength="8">
                        
                        <button type="submit"
                            class="w-full h-12 text-white text-center text-base font-semibold leading-6 rounded-[10px] hover:bg-indigo-800 transition-all duration-700 bg-indigo-600 shadow-sm mb-6">Reset Password</button>

                    </form>
                </div>
            </div>
        </section>
    </body>
@endsection
