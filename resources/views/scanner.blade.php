{{-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Attendance Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes scan {

            0%,
            100% {
                transform: translateY(0);
                opacity: 1;
            }

            50% {
                transform: translateY(20px);
                opacity: 0.3;
            }
        }

        .scanning-line {
            animation: scan 2s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Employee Attendance System</h1>
            <div id="currentTime" class="text-xl text-gray-600"></div>
        </div>

        <!-- Main Card -->
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Scanner Section -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white">
                <div class="flex items-center justify-center mb-4">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 9a2 2 0 10-4 0v5a2 2 0 01-2 2h6m-6-4h4m8 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-center mb-6">Scan Your Employee Card</h2>

                <form id="scanForm" class="space-y-4">
                    <div class="relative">
                        <input type="text" id="employeeCardId" name="employee_card_id"
                            class="w-full px-6 py-4 text-lg text-gray-800 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all"
                            placeholder="Scan card or enter employee ID..." autocomplete="off" autofocus />
                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2">
                            <div class="w-2 h-8 bg-blue-600 scanning-line"></div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="hidden p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mb-4"></div>
                <p class="text-xl text-gray-700">Processing attendance...</p>
            </div>

            <!-- Result Section -->
            <div id="resultSection" class="hidden p-8">
                <div id="resultContent"></div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="max-w-2xl mx-auto mt-6 bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-3 text-gray-800">Instructions:</h3>
            <ul class="space-y-2 text-gray-600">
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>First scan of the day will record your <strong>Check-In</strong></span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>Second scan of the day will record your <strong>Check-Out</strong></span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>You can only check in and check out <strong>once per day</strong></span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentTime').textContent = now.toLocaleDateString('en-US', options);
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Form submission
        const form = document.getElementById('scanForm');
        const input = document.getElementById('employeeCardId');
        const loadingState = document.getElementById('loadingState');
        const resultSection = document.getElementById('resultSection');
        const resultContent = document.getElementById('resultContent');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const cardId = input.value.trim();
            if (!cardId) return;

            // Show loading
            form.classList.add('hidden');
            loadingState.classList.remove('hidden');
            resultSection.classList.add('hidden');

            try {
                const response = await fetch('{{ route('attendance.process') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        employee_card_id: cardId
                    })
                });

                const data = await response.json();

                // Hide loading
                loadingState.classList.add('hidden');
                resultSection.classList.remove('hidden');

                // Display result
                if (data.success) {
                    if (data.type === 'check_in') {
                        resultContent.innerHTML = `
                            <div class="text-center">
                                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-green-600 mb-2">${data.message}</h3>
                                <p class="text-xl text-gray-700 mb-4">Welcome, ${data.employee.name}!</p>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                    <p class="text-gray-600"><strong>Designation:</strong> ${data.employee.designation || 'N/A'}</p>
                                    <p class="text-gray-600"><strong>Department:</strong> ${data.employee.department || 'N/A'}</p>
                                    <p class="text-lg font-semibold text-blue-600"><strong>Check-In Time:</strong> ${data.check_in}</p>
                                </div>
                            </div>
                        `;
                    } else if (data.type === 'check_out') {
                        resultContent.innerHTML = `
                            <div class="text-center">
                                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-blue-600 mb-2">${data.message}</h3>
                                <p class="text-xl text-gray-700 mb-4">Goodbye, ${data.employee.name}!</p>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                    <p class="text-gray-600"><strong>Check-In:</strong> ${data.check_in}</p>
                                    <p class="text-gray-600"><strong>Check-Out:</strong> ${data.check_out}</p>
                                    <p class="text-lg font-semibold text-green-600"><strong>Total Hours:</strong> ${data.total_hours} hours</p>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    const iconColor = data.type === 'completed' ? 'orange' : 'red';
                    resultContent.innerHTML = `
                        <div class="text-center">
                            <div class="w-20 h-20 bg-${iconColor}-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-12 h-12 text-${iconColor}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-${iconColor}-600 mb-2">${data.message}</h3>
                            ${data.employee ? `<p class="text-lg text-gray-700 mb-4">${data.employee.name}</p>` : ''}
                            ${data.check_in ? `
                                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                            <p class="text-gray-600"><strong>Check-In:</strong> ${data.check_in}</p>
                                            ${data.check_out ? `<p class="text-gray-600"><strong>Check-Out:</strong> ${data.check_out}</p>` : ''}
                                            ${data.total_hours ? `<p class="text-gray-600"><strong>Total Hours:</strong> ${data.total_hours} hours</p>` : ''}
                                        </div>
                                    ` : ''}
                        </div>
                    `;
                }

                // Reset after 5 seconds
                setTimeout(() => {
                    resultSection.classList.add('hidden');
                    form.classList.remove('hidden');
                    input.value = '';
                    input.focus();
                }, 5000);

            } catch (error) {
                loadingState.classList.add('hidden');
                resultSection.classList.remove('hidden');
                resultContent.innerHTML = `
                    <div class="text-center">
                        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-red-600 mb-2">Error</h3>
                        <p class="text-gray-700">Connection error. Please try again.</p>
                    </div>
                `;

                setTimeout(() => {
                    resultSection.classList.add('hidden');
                    form.classList.remove('hidden');
                    input.value = '';
                    input.focus();
                }, 3000);
            }
        });

        // Auto-focus on input
        input.focus();
    </script>
</body>

</html>


 --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Attendance Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes scan {

            0%,
            100% {
                transform: translateY(0);
                opacity: 1;
            }

            50% {
                transform: translateY(20px);
                opacity: 0.3;
            }
        }

        .scanning-line {
            animation: scan 2s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Employee Attendance System</h1>
            <div id="currentTime" class="text-xl text-gray-600"></div>
        </div>

        <!-- Main Card -->
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Scanner Section -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white">
                <div class="flex items-center justify-center mb-4">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 9a2 2 0 10-4 0v5a2 2 0 01-2 2h6m-6-4h4m8 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-center mb-6">Scan Your Employee Card</h2>

                <form id="scanForm" class="space-y-4">
                    <div class="relative">
                        <input type="text" id="employeeCardId" name="employee_card_id"
                            class="w-full px-6 py-4 text-lg text-gray-800 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all"
                            placeholder="Scan card or enter employee ID..." autocomplete="off" autofocus />
                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2">
                            <div class="w-2 h-8 bg-blue-600 scanning-line"></div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="hidden p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mb-4"></div>
                <p class="text-xl text-gray-700">Processing attendance...</p>
            </div>

            <!-- Result Section -->
            <div id="resultSection" class="hidden p-8">
                <div id="resultContent"></div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="max-w-2xl mx-auto mt-6 bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-3 text-gray-800">Instructions:</h3>
            <ul class="space-y-2 text-gray-600">
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>First scan of the day will record your <strong>Check-In</strong></span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>Second scan of the day will record your <strong>Check-Out</strong></span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span>You can only check in and check out <strong>once per day</strong></span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Text-to-Speech function
        function speak(text) {
            // Cancel any ongoing speech
            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'en-US';
            utterance.rate = 1.25;
            utterance.pitch = 1;
            utterance.volume = 1;

            window.speechSynthesis.speak(utterance);
        }

        // Update current time
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentTime').textContent = now.toLocaleDateString('en-US', options);
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Form submission
        const form = document.getElementById('scanForm');
        const input = document.getElementById('employeeCardId');
        const loadingState = document.getElementById('loadingState');
        const resultSection = document.getElementById('resultSection');
        const resultContent = document.getElementById('resultContent');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const cardId = input.value.trim();
            if (!cardId) return;

            // Show loading
            form.classList.add('hidden');
            loadingState.classList.remove('hidden');
            resultSection.classList.add('hidden');

            try {
                const response = await fetch('{{ route('attendance.process') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        employee_card_id: cardId
                    })
                });

                const data = await response.json();

                // Hide loading
                loadingState.classList.add('hidden');
                resultSection.classList.remove('hidden');

                // Display result
                if (data.success) {
                    if (data.type === 'check_in') {
                        // Speak the message
                        speak(
                            // `Check in successful! Welcome ${data.employee.name}. Your check in time is ${data.check_in}`
                            `Check in successful!`
                        );

                        resultContent.innerHTML = `
                            <div class="text-center">
                                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-green-600 mb-2">${data.message}</h3>
                                <p class="text-xl text-gray-700 mb-4">Welcome, ${data.employee.name}!</p>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                    <p class="text-gray-600"><strong>Designation:</strong> ${data.employee.designation || 'N/A'}</p>
                                    <p class="text-gray-600"><strong>Department:</strong> ${data.employee.department || 'N/A'}</p>
                                    <p class="text-lg font-semibold text-blue-600"><strong>Check-In Time:</strong> ${data.check_in}</p>
                                </div>
                            </div>
                        `;
                    } else if (data.type === 'check_out') {
                        // Speak the message
                        speak(
                            `Check out successful! Goodbye ${data.employee.name}. You worked for ${data.total_hours} hours today. Check out time is ${data.check_out}`
                            `Check out successful!`
                        );

                        resultContent.innerHTML = `
                            <div class="text-center">
                                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-blue-600 mb-2">${data.message}</h3>
                                <p class="text-xl text-gray-700 mb-4">Goodbye, ${data.employee.name}!</p>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                    <p class="text-gray-600"><strong>Check-In:</strong> ${data.check_in}</p>
                                    <p class="text-gray-600"><strong>Check-Out:</strong> ${data.check_out}</p>
                                    <p class="text-lg font-semibold text-green-600"><strong>Total Hours:</strong> ${data.total_hours} hours</p>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    const iconColor = data.type === 'completed' ? 'orange' : 'red';

                    // Speak error/warning message
                    if (data.type === 'completed') {
                        speak(
                            // `${data.employee.name}, you have already completed check in and check out for today. Check in was at ${data.check_in} and check out was at ${data.check_out}. Total hours worked: ${data.total_hours} hours`
                            `${data.employee.name}, you have already completed check in and check out for today`
                        );
                    } else {
                        speak(data.message);
                    }

                    resultContent.innerHTML = `
                        <div class="text-center">
                            <div class="w-20 h-20 bg-${iconColor}-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-12 h-12 text-${iconColor}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-${iconColor}-600 mb-2">${data.message}</h3>
                            ${data.employee ? `<p class="text-lg text-gray-700 mb-4">${data.employee.name}</p>` : ''}
                            ${data.check_in ? `
                                                                                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                                                                        <p class="text-gray-600"><strong>Check-In:</strong> ${data.check_in}</p>
                                                                                        ${data.check_out ? `<p class="text-gray-600"><strong>Check-Out:</strong> ${data.check_out}</p>` : ''}
                                                                                        ${data.total_hours ? `<p class="text-gray-600"><strong>Total Hours:</strong> ${data.total_hours} hours</p>` : ''}
                                                                                    </div>
                                                                                ` : ''}
                        </div>
                    `;
                }

                // Reset after 5 seconds
                setTimeout(() => {
                    resultSection.classList.add('hidden');
                    form.classList.remove('hidden');
                    input.value = '';
                    input.focus();
                }, 5000);

            } catch (error) {
                loadingState.classList.add('hidden');
                resultSection.classList.remove('hidden');

                // Speak error message
                speak('Connection error. Please try again.');

                resultContent.innerHTML = `
                    <div class="text-center">
                        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-red-600 mb-2">Error</h3>
                        <p class="text-gray-700">Connection error. Please try again.</p>
                    </div>
                `;

                setTimeout(() => {
                    resultSection.classList.add('hidden');
                    form.classList.remove('hidden');
                    input.value = '';
                    input.focus();
                }, 3000);
            }
        });

        // Auto-focus on input
        input.focus();
    </script>
</body>

</html>
