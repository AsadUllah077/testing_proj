<!-- Table -->
<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left">S.No</th>
                <th class="px-4 py-3 text-left">Full Name</th>
                <th class="px-4 py-3 text-left">Username</th>
                <th class="px-4 py-3 text-left">Email</th>
                <th class="px-4 py-3 text-left">Phone Number</th>
                <!-- <th class="px-4 py-3 text-left">Profile Picture</th> -->
                <th class="px-4 py-3 text-left">Designation</th>
                <th class="px-4 py-3 text-left">Company</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @php use Illuminate\Support\Str; @endphp

            @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2">{{ $loop->iteration }}</td>
                    <td class="px-4 py-2">
                        @php
                            $pic = $user->profile_pic;

                            if (
                                $pic &&
                                (Str::startsWith($pic, ['http://', 'https://']) || file_exists(public_path($pic)))
                            ) {
                                $src = Str::startsWith($pic, ['http://', 'https://']) ? $pic : asset($pic);
                            } else {
                                $src = asset('assets/images/default.png');
                            }
                        @endphp

                        <div class="flex items-center gap-2">
                            <img src="{{ $src }}" alt="avatar"
                                class="h-10 w-10 rounded-full object-cover border">

                            <span>{{ $user->first_name }} {{ $user->last_name }}</span>
                        </div>
                    </td>

                    <td class="px-4 py-2">{{ $user->username ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $user->email }}</td>
                    <td class="px-4 py-2">{{ $user->phone_number ?? '—' }}</td>

                    <td class="px-4 py-2 text-left">{{ $user->designation ?? '—' }}</td>
                    <td class="px-4 py-2 text-left">{{ $user->company->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex justify-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" disabled class="sr-only peer"
                                    {{ $user->status ? 'checked' : '' }}>
                                <div
                                    class="relative w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-[#0078C8] transition-colors">
                                    <div
                                        class="absolute left-[2px] top-[2px] bg-white w-5 h-5 rounded-full transition-transform peer-checked:translate-x-full">
                                    </div>
                                </div>
                                <span
                                    class="ml-2 text-sm font-medium {{ $user->status ? 'text-[#0078C8]' : 'text-gray-500' }}">
                                    {{ $user->status ? 'Enabled' : 'Disabled' }}
                                </span>
                            </label>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-1 rounded hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div
                            class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                            <button type="button"
                                class="delete-btn w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors"
                                data-url="{{ route('users.user.destroy', $user->id) }}"
                                data-row="#row-{{ $user->id }}" onclick="confirmDelete(this)">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Delete
                            </button>

                            <a href="{{ route('users.user.edit', $user->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>

                            <a href="{{ route('users.user.show', $user->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Details
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">No users found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$users" />
