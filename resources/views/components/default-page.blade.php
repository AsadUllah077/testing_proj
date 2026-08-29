<label class="block text-xs text-gray-500 mb-1">Per Page</label>
<select name="per_page"
    class="select2 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
    @foreach (config('default.per_page') as $value)
        <option value="{{ $value }}" {{ request('per_page') == $value ? 'selected' : '' }}>
            {{ $value }}
        </option>
    @endforeach
</select>