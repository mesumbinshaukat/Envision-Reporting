@props([
    'direction' => 'asc',
    'active' => false,
])

<span class="inline-flex flex-col ml-1 text-[10px] leading-none align-middle select-none">
    <span class="{{ $active && $direction === 'asc' ? 'text-navy-900' : 'text-gray-400' }}">▲</span>
    <span class="{{ $active && $direction === 'desc' ? 'text-navy-900' : 'text-gray-400' }}">▼</span>
</span>
