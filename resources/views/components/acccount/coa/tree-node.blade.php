<li class="ml-4">

    <div class="flex items-center justify-between py-2">
        <span class="font-medium">
            {{ $account->account_number }} - {{ $account->name }}
            <span class="text-gray-500 text-sm">({{ $account->category }})</span>
        </span>

        <td class="px-4 py-2 text-center relative">
            <button class="text-gray-500 hover:text-gray-800 dropdown-btn">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
            </button>
            <div
                class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                <div class="w-full px-4 py-2 hover:bg-gray-100 flex items-center">
                    <span>▶</span>
                    <form action="{{ route('account.organization-settings.account.destroy', $account->id) }}"
                        method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Delete this account?')"
                            class="delete-btn w-full text-left px-2 py-2 hover:bg-gray-100 flex items-center gap-2"><svg
                                class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>Delete</button>
                    </form>
                </div>
                <a href="{{ route('account.organization-settings.account.edit', $account->id) }}"
                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                    <span>▶</span>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                    Edit
                </a>
                <a href="{{ route('account.organization-settings.account.detail', $account->id) }}"
                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                    <span>▶</span>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd"
                            d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                            clip-rule="evenodd" />
                    </svg>
                    Details
                </a>
                <!-- Modal Component -->
                <button onclick="openHistoryModal()"
                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                    <span>▶</span>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                            clip-rule="evenodd" />
                    </svg>
                    History
                </button>
            </div>
        </td>
    </div>

    @if ($account->children->count() > 0)
        <ul class="ml-6 border-l pl-4">
            @foreach ($account->children as $child)
                @include('components.acccount.coa.tree-node', ['account' => $child])
            @endforeach
        </ul>
    @endif

</li>
