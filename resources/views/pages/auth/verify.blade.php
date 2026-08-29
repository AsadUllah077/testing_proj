@extends('layouts.guest')

@section('content')

    <body class="font-inter overflow-hidden">
        <section class="flex justify-center items-center h-screen relative">
            <img src="https://pagedone.io/asset/uploads/1702362010.png" alt="gradient background image"
                class="w-full h-full object-cover fixed">
            <div class="mx-auto max-w-xxl px-6 lg:px-8 absolute py-20">

                <div class="rounded-2xl bg-white shadow-xl">
                    <form id="verifyOtp" action-url="{{ route('user.verify') }}" method="post"
                        back_url="{{ route('user.verify.view') }}" class="lg:p-11 p-7 mx-auto">
                        @csrf
                        <div class="mb-11">
                            <h1 class="text-gray-900 text-center font-manrope text-3xl font-bold leading-10 mb-2">Verify
                                Email</h1>
                            <p class="text-center">
                                Please enter the One-Time Password (OTP) sent to your <br /> email address to verify your
                                account.
                            </p>

                        </div>

                        <!-- Hidden input for combined OTP -->
                        <input type="hidden" name="otp" id="combinedOTP">

                        <!-- OTP Input Fields -->
                        <div class="mb-6">
                            <label class="text-gray-900 text-sm font-medium leading-6 mb-2 block text-center">Enter
                                OTP</label>
                            <div class="flex gap-3 justify-center">
                                <input type="text" maxlength="1"
                                    class="otp-input w-12 h-12 text-center text-gray-900 text-2xl font-semibold rounded-[10px] border-gray-300 border shadow-sm focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                                    pattern="[0-9]" inputmode="numeric">
                                <input type="text" maxlength="1"
                                    class="otp-input w-12 h-12 text-center text-gray-900 text-2xl font-semibold rounded-[10px] border-gray-300 border shadow-sm focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                                    pattern="[0-9]" inputmode="numeric">
                                <input type="text" maxlength="1"
                                    class="otp-input w-12 h-12 text-center text-gray-900 text-2xl font-semibold rounded-[10px] border-gray-300 border shadow-sm focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                                    pattern="[0-9]" inputmode="numeric">
                                <input type="text" maxlength="1"
                                    class="otp-input w-12 h-12 text-center text-gray-900 text-2xl font-semibold rounded-[10px] border-gray-300 border shadow-sm focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                                    pattern="[0-9]" inputmode="numeric">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full h-12 text-white text-center text-base font-semibold leading-6 rounded-[10px] hover:bg-indigo-800 transition-all duration-700 bg-indigo-600 shadow-sm mb-11">Verify</button>

                    </form>


                </div>
            </div>
        </section>
    </body>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            const form = document.getElementById('verifyOtp');
            const submitBtn = form.querySelector('button[type="submit"]');
            const actionUrl = form.getAttribute('action-url');

            otpInputs.forEach((input, index) => {
                // Auto-focus next input
                // input.addEventListener('input', function(e) {
                //     if (this.value.length === 1) {
                //         if (index < otpInputs.length - 1) {
                //             otpInputs[index + 1].focus();
                //         }
                //     }
                // });

                input.addEventListener('input', function() {
                    if (this.value.length === 1) {
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        } else {
                            // Last digit entered, submit automatically
                            form.requestSubmit();
                        }
                    }
                });

                // Handle backspace
                // input.addEventListener('keydown', function(e) {
                //     if (e.key === 'Backspace' && !this.value && index > 0) {
                //         otpInputs[index - 1].focus();
                //     }
                // });

                // Handle backspace and Enter key
                input.addEventListener('keydown', function(e) {
                    // Move to previous input on backspace
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        otpInputs[index - 1].focus();
                    }

                    // Submit form on Enter
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        form.requestSubmit(); // triggers your existing submit event
                    }
                });

                // Only allow numbers
                input.addEventListener('keypress', function(e) {
                    if (!/[0-9]/.test(e.key)) {
                        e.preventDefault();
                    }
                });

                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').slice(0, 4);
                    const digits = pastedData.split('');

                    digits.forEach((digit, i) => {
                        if (otpInputs[i] && /[0-9]/.test(digit)) {
                            otpInputs[i].value = digit;
                        }
                    });

                    if (digits.length > 0) {
                        const lastIndex = Math.min(digits.length - 1, otpInputs.length - 1);
                        otpInputs[lastIndex].focus();
                    }
                });
            });

            // AJAX form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Combine all OTP input values
                let otpValue = '';
                otpInputs.forEach(input => {
                    otpValue += input.value;
                });

                // Validate OTP length
                if (otpValue.length !== 4) {
                    Notification.success('Please enter the complete 4-digit OTP');
                    return false;
                }

                // Disable submit button and show loading state
                submitBtn.disabled = true;
                Loading.show('Verifying...');

                // Get CSRF token
                const csrfToken = document.querySelector('input[name="_token"]').value;

                // Make AJAX request
                fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            otp: otpValue
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Loading.show('Success! Redirecting...');
                            Notification.success('OTP verified successfully!');

                            setTimeout(() => {
                                window.location.href = data.redirect_to || '/dashboard';
                            }, 1000);
                        } else {
                            Loading.hide();
                            // Error from backend
                            Notification.error(data.message || 'Invalid OTP. Please try again.');

                            // Clear OTP inputs
                            otpInputs.forEach(input => input.value = '');
                            otpInputs[0].focus();

                            // Re-enable submit button
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        Loading.hide();
                        console.error('Error:', error);
                        Notification.error('An error occurred. Please try again.');

                        // Re-enable submit button
                        submitBtn.disabled = false;
                    });
            });
        });
    </script>
@endpush
