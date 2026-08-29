@extends('layouts.auth')

@section('title', 'Tax Rate All Logs')

@section('content')

    <div class="mb-8">

        <!-- Tabs -->
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap">
                @foreach (config('modules.organization_settings') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                <!-- Header -->
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex justify-between items-center">
                    <h2 class="text-xl font-normal">Tax Rate Logs</h2>

                    <div class="">

                        <a href="{{ route('account.organization-settings.tax_rate.index') }}"
                            class="px-4 py-2 bg-[#0078C8] text-white rounded text-xs hover:bg-[#006bb0]">
                            Back
                        </a>
                    </div>
                </div>

                <!-- Logs Container -->
                <div class="bg-white rounded-md border border-black/10 mt-4">

                    <!-- TABLE -->
                    <div class="overflow-x-auto w-full ">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 rounded-t-lg">
                                <tr>
                                    <th class="px-4 py-3 text-left">User</th>
                                    <th class="px-4 py-3 text-left">Event</th>
                                    <th class="px-4 py-3 text-left">Old Values</th>
                                    <th class="px-4 py-3 text-left">New Values</th>
                                    <th class="px-4 py-3 text-left">Date</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">

                                @forelse($logs as $log)
                                    <tr class="hover:bg-gray-50">

                                        <!-- <td class="px-4 py-2">
                                                                    {{ $log->user->name ?? 'System' }}
                                                                </td> -->
                                        <td class="px-4 py-3">
                                            {{ $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'System' }}
                                        </td>

                                        <td class="px-4 py-2 capitalize">
                                            {{ $log->event }}
                                        </td>

                                        <td class="px-4 py-2 text-xs text-gray-600 align-center">
                                            @if (!empty($log->old_values))
                                                <div class="rounded p-2 max-w-xs text-[11px] leading-4">

                                                    @foreach ($log->old_values as $key => $value)
                                                        @if (in_array($key, ['id', 'company_id', 'created_at', 'updated_at']))
                                                            @continue
                                                        @endif

                                                        <div class="flex justify-between last:border-0 py-1"
                                                            style="border-color:#B8D7EA;">
                                                            <span style="color:#005A92;">
                                                                {{ is_array($value) ? json_encode($value) : $value }}
                                                            </span>
                                                        </div>
                                                    @endforeach

                                                </div>
                                            @else
                                                <span class="text-gray-400 italic">N/A</span>
                                            @endif
                                        </td>



                                        <td class="px-4 py-2 text-xs text-gray-600 align-top">
                                            @if (!empty($log->new_values))
                                                <div class="rounded p-2 max-w-xs text-[11px] leading-4"
                                                    style="background-color:#fff;">

                                                    @foreach ($log->new_values as $key => $value)
                                                        @if (in_array($key, ['id', 'company_id', 'created_at', 'updated_at']))
                                                            @continue
                                                        @endif

                                                        <div class="flex justify-between last:border-0 py-1"
                                                            style="border-color:#A3CBE4;">
                                                            <span style="color:#003E66;">
                                                                {{ is_array($value) ? json_encode($value) : $value }}
                                                            </span>
                                                        </div>
                                                    @endforeach

                                                </div>
                                            @else
                                                <span class="text-gray-400 italic">N/A</span>
                                            @endif
                                        </td>




                                        <td class="px-4 py-2">
                                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-gray-500">
                                            No Logs Found
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>

                    <div class="p-4">
                        {{ $logs->links() }}
                    </div>

                </div>

            </div>
        </div>
    </div>

@endsection
