@props(['accounts'])

<div class="bg-white rounded-lg shadow p-4">

    <ul class="space-y-1">
        @foreach ($accounts->where('parent_id', null) as $root)
            @include('components.acccount.coa.tree-node', ['account' => $root])
        @endforeach
    </ul>

</div>
