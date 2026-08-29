@props(['items' => null])

<!-- History Modal -->
<div id="historyModal"
    class="fixed inset-0 bg-black/50 flex items-center justify-center hidden transition-opacity duration-300"
    style="z-index: 1000;">
    <!-- Modal Box -->
    <div id="historyModalBox"
        class="bg-white rounded-lg shadow-lg w-full max-w-3xl mx-4 overflow-hidden transform transition-all duration-300 scale-95">

        <!-- Header -->
        <div class="flex justify-between items-center bg-gray-100 px-5 py-3 border-b">
            <h2 class="text-lg font-semibold text-gray-800">History</h2>
            <button onclick="closeHistoryModal()"
                class="text-white bg-red-500 hover:bg-red-600 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold transition-colors">
                ×
            </button>
        </div>

        <!-- Table -->
        <div class="p-4 overflow-x-auto max-h-96">
            <table class="min-w-full text-sm text-left border border-gray-200">
                <thead class="bg-gray-50 border-b sticky top-0">
                    <tr>
                        <th class="px-4 py-2 font-medium text-gray-700">Date</th>
                        <th class="px-4 py-2 font-medium text-gray-700">User</th>
                        <th class="px-4 py-2 font-medium text-gray-700">Action</th>
                        <th class="px-4 py-2 font-medium text-gray-700">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @if ($items && count($items) > 0)
                        @foreach ($items as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-2">{{ $item['date'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $item['user'] ?? 'N/A' }}</td>
                                <td class="px-4 py-2">
                                    <span
                                        class="px-2 py-1 text-xs rounded {{ $item['action_class'] ?? 'bg-blue-100 text-blue-800' }}">
                                        {{ $item['action'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $item['details'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2">9 MAY 2025 9:42pm</td>
                            <td class="px-4 py-2">Izaz</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">
                                    Partially Paid
                                </span>
                            </td>
                            <td class="px-4 py-2">1000 Payment Receive From Umar</td>
                        </tr>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2">8 MAY 2025 2:15pm</td>
                            <td class="px-4 py-2">Admin</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                    Created
                                </span>
                            </td>
                            <td class="px-4 py-2">Bill Invoice Created</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="flex justify-end px-5 py-3 bg-gray-50 border-t">
            <button onclick="closeHistoryModal()"
                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md text-sm transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // Open History Modal
    function openHistoryModal() {
        const modal = document.getElementById('historyModal');
        const modalBox = document.getElementById('historyModalBox');

        modal.classList.remove('hidden');

        // Trigger animation
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');
        }, 10);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    // Close History Modal
    function closeHistoryModal() {
        const modal = document.getElementById('historyModal');
        const modalBox = document.getElementById('historyModalBox');

        modal.classList.remove('opacity-100');
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
            // Restore body scroll
            document.body.style.overflow = '';
        }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('historyModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeHistoryModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('historyModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeHistoryModal();
            }
        }
    });
</script>
