@php
$variants = [
    'primary' => 'bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 focus:ring-indigo-500 text-white',
    'success' => 'bg-green-600 hover:bg-green-700 active:bg-green-800 focus:ring-green-500 text-white',
    'danger' => 'bg-red-600 hover:bg-red-700 active:bg-red-800 focus:ring-red-500 text-white',
    'warning' => 'bg-yellow-600 hover:bg-yellow-700 active:bg-yellow-800 focus:ring-yellow-500 text-white',
    'info' => 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 focus:ring-blue-500 text-white',
    'secondary' => 'bg-gray-600 hover:bg-gray-700 active:bg-gray-800 focus:ring-gray-500 text-white',
    'outline' => 'bg-transparent border-2 border-gray-400 hover:bg-gray-100 text-gray-700 focus:ring-gray-500',
    'light' => 'bg-gray-200 hover:bg-gray-300 text-gray-700 focus:ring-gray-500',
];

$variantClass = $variants[$variant ?? 'primary'] ?? $variants['primary'];

$isLink = isset($href);
$tag = $isLink ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($isLink)
        href="{{ $href }}"
    @else
        type="{{ $type ?? 'submit' }}"
    @endif

    {{ $attributes->merge([
        'class' => "inline-flex items-center gap-2 px-4 py-2 rounded-md font-semibold text-sm uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 {$variantClass}"
    ]) }}
>

    @isset($icon)
        <span class="text-lg">{{ $icon }}</span>
    @endisset

    @isset($text)
        <span>{{ $text }}</span>
    @endisset

    {{ $slot }}

</{{ $tag }}>