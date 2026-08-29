<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden h-full">
    <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Compensation Benchmarking</h2>
        <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-black rounded uppercase tracking-tighter">AI Analysis</span>
    </div>
    
    <div class="p-6 space-y-6">
        {{-- Comp-Ratio Gauge --}}
        <div>
            <div class="flex justify-between items-end mb-2">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Comp-Ratio (Market)</p>
                    <h3 class="text-2xl font-black text-gray-900" id="compRatioDisplay">
                        {{ $benchmarkData['comp_ratio'] ? round($benchmarkData['comp_ratio'], 1) . '%' : 'N/A' }}
                    </h3>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Internal Percentile</p>
                    <h3 class="text-xl font-bold text-blue-600" id="internalPercentileDisplay">
                        {{ round($benchmarkData['internal_percentile']) }}th
                    </h3>
                </div>
            </div>
            
            <div class="relative h-3 w-full bg-gray-100 rounded-full overflow-hidden flex">
                <div class="h-full bg-red-400" style="width: 20%"></div>
                <div class="h-full bg-yellow-400" style="width: 20%"></div>
                <div class="h-full bg-green-500" style="width: 20%"></div>
                <div class="h-full bg-yellow-400" style="width: 20%"></div>
                <div class="h-full bg-red-400" style="width: 20%"></div>
                
                {{-- Indicator Needle --}}
                <div id="compIndicator" class="absolute top-0 w-1 h-full bg-black border-x border-white transition-all duration-700 shadow-lg" 
                     style="left: {{ min(100, max(0, $benchmarkData['comp_ratio'] ?? 50)) }}%"></div>
            </div>
            <div class="flex justify-between mt-1 text-[9px] font-bold text-gray-400 uppercase">
                <span>Underpaid</span>
                <span>Market Median</span>
                <span>Overpaid</span>
            </div>
        </div>

        <hr class="border-gray-50">

        {{-- Market Stats --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Market Median</p>
                <p class="text-sm font-black text-gray-800" id="marketMedianDisplay">
                    {{ currency()?->symbol }} {{ $benchmarkData['market_benchmark'] ? number_format($benchmarkData['market_benchmark']->median_salary) : '---' }}
                </p>
            </div>
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Internal Avg</p>
                <p class="text-sm font-black text-gray-800" id="internalAvgDisplay">
                    {{ currency()?->symbol }} {{ number_format($benchmarkData['internal_stats']['avg']) }}
                </p>
            </div>
        </div>

        {{-- Flight Risk Section --}}
        <div id="flightRiskContainer" class="p-4 rounded-2xl border {{ $benchmarkData['flight_risk']['level'] === 'Critical' || $benchmarkData['flight_risk']['level'] === 'High' ? 'bg-red-50 border-red-100' : 'bg-green-50 border-green-100' }}">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $benchmarkData['flight_risk']['level'] === 'Critical' || $benchmarkData['flight_risk']['level'] === 'High' ? 'bg-red-500 text-white' : 'bg-green-500 text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest {{ $benchmarkData['flight_risk']['level'] === 'Critical' || $benchmarkData['flight_risk']['level'] === 'High' ? 'text-red-600' : 'text-green-600' }}">Retention Risk</p>
                    <h4 class="text-sm font-black {{ $benchmarkData['flight_risk']['level'] === 'Critical' || $benchmarkData['flight_risk']['level'] === 'High' ? 'text-red-800' : 'text-green-800' }}" id="riskLevelDisplay">
                        {{ $benchmarkData['flight_risk']['level'] }} Risk
                    </h4>
                </div>
            </div>
            <ul class="space-y-1" id="riskReasonsList">
                @foreach($benchmarkData['flight_risk']['reasons'] as $reason)
                    <li class="text-[10px] font-medium flex items-center gap-2 {{ $benchmarkData['flight_risk']['level'] === 'Critical' || $benchmarkData['flight_risk']['level'] === 'High' ? 'text-red-700' : 'text-green-700' }}">
                        <span class="w-1 h-1 rounded-full bg-current"></span>
                        {{ $reason }}
                    </li>
                @endforeach
                @if(empty($benchmarkData['flight_risk']['reasons']))
                    <li class="text-[10px] font-medium text-green-700 italic">Compensation is competitive with market and peers.</li>
                @endif
            </ul>
        </div>
    </div>
</div>
