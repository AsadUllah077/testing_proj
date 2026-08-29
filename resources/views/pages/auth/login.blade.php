@extends('layouts.guest')

@section('content')

    <body class="font-inter overflow-hidden">
        <section class="flex justify-center items-center h-screen relative">
            <img src="https://pagedone.io/asset/uploads/1702362010.png" alt="gradient background image"
                class="w-full h-full object-cover fixed">
            <div class="mx-auto max-w-lg px-6 lg:px-8 absolute py-20">

                <div class="rounded-2xl bg-white shadow-xl">
                    <form id="formSubmit" action_url="{{ route('user.login.view') }}" method="post"
                        {{-- back_url="{{ route('user.verify.view') }}" class="lg:p-11 p-7 mx-auto"> --}}
                        back_url="{{ route('dashboard') }}" class="lg:p-11 p-7 mx-auto">
                        @csrf
                        <div class="mb-11">
                            <h1 class="text-gray-900 text-center font-manrope text-3xl font-bold leading-10 mb-2">Welcome
                                ERP</h1>
                        </div>
                        @if (session('error'))
                            <div class="mb-6 rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {{ session('error') }}
                            </div>
                        @endif
                        <input type="text" name="email"
                            class="w-full h-12 text-gray-900 placeholder:text-gray-400 text-lg font-normal leading-7 rounded-[10px] border-gray-300 border shadow-sm focus:outline-none px-4 mb-6"
                            placeholder="Email">
                        <input type="password" name="password"
                            class="w-full h-12 text-gray-900 placeholder:text-gray-400 text-lg font-normal leading-7 rounded-[10px] border-gray-300 border shadow-sm focus:outline-none px-4 mb-1"
                            placeholder="Password">
                        <a href="{{ route('password.request') }}" class="flex justify-end my-6">
                            <span class="text-indigo-600 text-right text-base font-normal leading-6">Forgot Password?</span>
                        </a>
                        <button type="submit"
                            class="w-full h-12 text-white text-center text-base font-semibold leading-6 rounded-[10px] hover:bg-indigo-800 transition-all duration-700 bg-indigo-600 shadow-sm mb-11">Login</button>

                        <div class="relative mb-6 flex items-center">
                            <div class="flex-grow border-t border-gray-200"></div>
                            <span class="mx-4 flex-shrink text-sm text-gray-400">or</span>
                            <div class="flex-grow border-t border-gray-200"></div>
                        </div>

                        <a href="{{ route('user.b2c.login') }}"
                            class="flex h-12 w-full items-center justify-center gap-3 rounded-[10px] border border-gray-300 bg-white text-base font-semibold leading-6 text-gray-800 shadow-sm transition-all duration-300 hover:bg-gray-50">
                            <span class="grid h-5 w-5 grid-cols-2 gap-0.5">
                                <span class="bg-[#f25022]"></span>
                                <span class="bg-[#7fba00]"></span>
                                <span class="bg-[#00a4ef]"></span>
                                <span class="bg-[#ffb900]"></span>
                            </span>
                            Login with Microsoft
                        </a>

                    </form>
                </div>
            </div>
        </section>
    </body>
@endsection
