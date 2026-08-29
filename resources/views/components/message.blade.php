   <div id="showErrorMessage"
            class="bg-red-200 hidden px-6 py-4 mx-2 my-4  space-x-3 rounded-md text-lg flex items-center max-w-lg shadow-lg fixed right-0">
            <svg viewBox="0 0 24 24" class="text-red-600 w-5 h-5 mr-3">
                <path fill="currentColor"
                    d="M11.983,0a12.206,12.206,0,0,0-8.51,3.653A11.8,11.8,0,0,0,0,12.207A11.779,11.779,0,0,0,11.8,24h.214A12.111,12.111,0,0,0,24,11.791A11.766,11.766,0,0,0,11.983,0ZM10.5,16.542a1.476,1.476,0,0,1,1.449-1.53h.027a1.527,1.527,0,0,1,1.523,1.47a1.475,1.475,0,0,1-1.449,1.53h-.027A1.529,1.529,0,0,1,10.5,16.542ZM11,12.5v-6a1,1,0,0,1,2,0v6a1,1,0,1,1-2,0Z" />
            </svg>

            <span class="text-red-800" id="errorMessage"></span>

            <!-- Close Button -->
            <button id="hideErrorMessage" class="absolute right-3 top-3 text-red-700 hover:text-red-900">
                ✕
            </button>
        </div>
        <div id="showSuccessMessage"
            class="bg-green-200 hidden px-6 py-4 mx-2 my-4  space-x-3 rounded-md text-lg flex items-center max-w-lg shadow-lg fixed right-0">
            <svg viewBox="0 0 24 24" class="text-green-600 w-5 h-5 mr-3">
                <path fill="currentColor"
                    d="M11.983,0a12.206,12.206,0,0,0-8.51,3.653A11.8,11.8,0,0,0,0,12.207A11.779,11.779,0,0,0,11.8,24h.214A12.111,12.111,0,0,0,24,11.791A11.766,11.766,0,0,0,11.983,0ZM10.5,16.542a1.476,1.476,0,0,1,1.449-1.53h.027a1.527,1.527,0,0,1,1.523,1.47a1.475,1.475,0,0,1-1.449,1.53h-.027A1.529,1.529,0,0,1,10.5,16.542ZM11,12.5v-6a1,1,0,0,1,2,0v6a1,1,0,1,1-2,0Z" />
            </svg>

            <span class="text-green-800" id="successMessage"></span>

            <!-- Close Button -->
            <button id="hideSuccessMessage" class="absolute right-3 top-3 text-green-700 hover:text-green-900">
                ✕
            </button>
        </div>