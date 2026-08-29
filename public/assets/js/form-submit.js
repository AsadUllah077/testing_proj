// Notification System
const Notification = {
    show: function (message, type = 'success') {
        const id = 'notification-' + Date.now();
        const colors = {
            success: {
                bg: 'bg-green-200',
                text: 'text-green-800',
                icon: 'text-green-600',
                hover: 'hover:text-green-900',
                svg: '<path fill="currentColor" d="M12,0A12,12,0,1,0,24,12A12.014,12.014,0,0,0,12,0Zm6.927,8.2l-6.845,9.289a1.011,1.011,0,0,1-1.43.188L5.764,13.769a1,1,0,1,1,1.25-1.562l4.076,3.261l6.227-8.451A1,1,0,1,1,18.927,8.2Z" />'
            },
            error: {
                bg: 'bg-red-200',
                text: 'text-red-800',
                icon: 'text-red-600',
                hover: 'hover:text-red-900',
                svg: '<path fill="currentColor" d="M11.983,0a12.206,12.206,0,0,0-8.51,3.653A11.8,11.8,0,0,0,0,12.207A11.779,11.779,0,0,0,11.8,24h.214A12.111,12.111,0,0,0,24,11.791A11.766,11.766,0,0,0,11.983,0ZM10.5,16.542a1.476,1.476,0,0,1,1.449-1.53h.027a1.527,1.527,0,0,1,1.523,1.47a1.475,1.475,0,0,1-1.449,1.53h-.027A1.529,1.529,0,0,1,10.5,16.542ZM11,12.5v-6a1,1,0,0,1,2,0v6a1,1,0,1,1-2,0Z" />'
            },
            warning: {
                bg: 'bg-yellow-200',
                text: 'text-yellow-800',
                icon: 'text-yellow-600',
                hover: 'hover:text-yellow-900',
                svg: '<path fill="currentColor" d="M12,0A12,12,0,1,0,24,12,12.013,12.013,0,0,0,12,0Zm0,19a1.5,1.5,0,1,1,1.5-1.5A1.5,1.5,0,0,1,12,19Zm1-5.16a1,1,0,0,1-2,0V7a1,1,0,0,1,2,0Z"/>'
            },
            info: {
                bg: 'bg-blue-200',
                text: 'text-blue-800',
                icon: 'text-blue-600',
                hover: 'hover:text-blue-900',
                svg: '<path fill="currentColor" d="M12,0A12,12,0,1,0,24,12,12.013,12.013,0,0,0,12,0Zm0,19a1.5,1.5,0,1,1,1.5-1.5A1.5,1.5,0,0,1,12,19Zm1.6-6.08a1,1,0,0,0-.6.917,1,1,0,1,1-2,0,3,3,0,0,1,1.8-2.75A2,2,0,1,0,10,9.255a1,1,0,1,1-2,0,4,4,0,1,1,5.6,3.666Z"/>'
            }
        };

        const theme = colors[type] || colors.info;

        if ($('#notificationContainer').length === 0) {
            $('body').append('<div id="notificationContainer" style="position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;"></div>');
        }

        const notification = $(`
            <div id="${id}" class="${theme.bg} px-6 py-4 my-2 rounded-md text-lg flex items-center shadow-lg relative" style="opacity: 0; transform: translateX(100%); transition: all 0.3s ease;">
                <svg viewBox="0 0 24 24" class="${theme.icon} w-5 h-5 mr-3 flex-shrink-0">
                    ${theme.svg}
                </svg>
                <span class="${theme.text} flex-1">${message}</span>
                <button onclick="Notification.close('${id}')" class="ml-3 ${theme.text} ${theme.hover} font-bold">✕</button>
            </div>
        `);

        $('#notificationContainer').append(notification);

        setTimeout(() => {
            notification.css({ opacity: '1', transform: 'translateX(0)' });
        }, 10);

        setTimeout(() => {
            Notification.close(id);
        }, 5000);
    },

    close: function (id) {
        const notification = $(`#${id}`);
        notification.css({ opacity: '0', transform: 'translateX(100%)' });
        setTimeout(() => notification.remove(), 300);
    },

    success: function (message) {
        this.show(message, 'success');
    },

    error: function (message) {
        this.show(message, 'error');
    },

    warning: function (message) {
        this.show(message, 'warning');
    },

    info: function (message) {
        this.show(message, 'info');
    }
};

// Global loading overlay functions
const Loading = {
    show: function (message = 'Processing...') {
        if ($('#globalLoading').length === 0) {
            $('body').append(`
                <div id="globalLoading"
                    class="fixed inset-0 z-50 flex items-center justify-center
                            bg-black/40 backdrop-blur-sm">
                    <div
                        class="flex items-center gap-4
                                bg-white/90 px-6 py-4
                                rounded-xl shadow-2xl
                                ring-1 ring-gray-200">

                        <i class="fas fa-spinner fa-spin text-2xl
                                text-blue-600"></i>

                        <span class="loading-text text-sm font-semibold
                                    text-gray-800 tracking-wide">
                            ${message}
                        </span>
                    </div>
                </div>
            `);
        } else {
            $('#globalLoading').removeClass('hidden').find('.loading-text').text(message);
        }
    },
    hide: function () {
        $('#globalLoading').addClass('hidden');
    },
    remove: function () {
        $('#globalLoading').remove();
    }
};

// Add CSS for loading overlay
if ($('#loadingStyles').length === 0) {
    $('head').append(`
        <style id="loadingStyles">
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                backdrop-filter: blur(4px);
            }

            .loading-overlay.hidden {
                display: none;
            }

            .loading-spinner {
                text-align: center;
                background: white;
                padding: 30px 40px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            }

            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #0078C8;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .loading-text {
                margin: 0;
                color: #333;
                font-size: 16px;
                font-weight: 500;
            }

            .field-error {
                color: #dc2626;
                font-size: 14px;
                margin-top: 8px;
                display: block;
                text-align: center;
            }

            .input-error {
                border-color: #dc2626 !important;
                background-color: #fef2f2 !important;
            }

            .otp-error-container {
                text-align: center;
                margin-top: 8px;
            }
        </style>
    `);
}

function clearValidationErrors() {
    $('.field-error').remove();
    $('.input-error').removeClass('input-error');
    $('.otp-error-container').remove();
}

function displayValidationErrors(errors) {
    clearValidationErrors();

    if (Array.isArray(errors)) {
        const form = $('#formSubmit');
        errors.forEach(msg => {
            form.prepend(`<div class="field-error">${msg}</div>`);
        });
        $('html, body').animate({
            scrollTop: $('#formSubmit .field-error:first').offset().top - 100
        }, 500);
    } else {
        $.each(errors, function (field, messages) {
            if (field === 'otp') {
                const otpInputs = $('.otp-input');
                otpInputs.addClass('input-error');
                const otpContainer = otpInputs.first().parent();
                otpContainer.after(`<div class="otp-error-container"><span class="field-error">${messages[0]}</span></div>`);
                $('html, body').animate({
                    scrollTop: otpContainer.offset().top - 150
                }, 500);
            } else {
                const input = $(`[name="${field}"]`);
                input.addClass('input-error');
                input.parent().append(`<span class="field-error">${messages[0]}</span>`);
            }
        });

        if (!errors.hasOwnProperty('otp')) {
            const firstError = $('.input-error').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }
        }
    }
}

$(document).on('submit', "#formSubmit", function (e) {
    e.preventDefault();
    clearValidationErrors();

    const type = $(this).attr('method');
    const action_url = $(this).attr('action_url');
    const back_url = $(this).attr('back_url');
    const formData = new FormData(this);
    const submitBtn = $(this).find('button[type="submit"]');

    submitBtn.prop('disabled', true);
    Loading.show('Processing...');

    $.ajax({
        url: action_url,
        type: type,
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            console.log(data)
            submitBtn.prop('disabled', false);
            if (data.success) {
                Loading.show('Success! Redirecting...');

                if (Array.isArray(data.message)) {
                    data.message.forEach(msg => Notification.success(msg));
                } else if (typeof data.message === 'string') {
                    Notification.success(data.message);
                }

                setTimeout(() => window.location.href = data.redirect_to || back_url, 2000);
            } else {
                Loading.hide();

                if (Array.isArray(data.message)) {
                    data.message.forEach(msg => Notification.error(msg));
                } else if (typeof data.message === 'object' && data.message !== null) {
                    Object.values(data.message).forEach(messages => {
                        messages.forEach(msg => Notification.error(msg));
                    });
                } else if (typeof data.message === 'string') {
                    Notification.error(data.message);
                }
            }
        },
        error: function (xhr) {
            submitBtn.prop('disabled', false);
            Loading.hide();

            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                displayValidationErrors(errors);
                
                // Show each error in an alert as well
                Object.values(errors).forEach(messages => {
                    messages.forEach(msg => Notification.error(msg));
                });
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                Notification.error(xhr.responseJSON.message);
            } else {
                Notification.error('An error occurred. Please try again.');
            }
        }
    });
});

$(document).on('focus', 'input, select, textarea', function () {
    $(this).removeClass('input-error');
    $(this).parent().find('.field-error').remove();
    $('.otp-error-container').remove();
});

$(document).on('focus', '.otp-input', function () {
    $('.otp-input').removeClass('input-error');
    $('.otp-error-container').remove();
});
