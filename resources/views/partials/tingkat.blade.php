@php
    $daftarTingkat = ['SP1', 'SP2', 'SP3'];
    $tingkat = strtoupper((string) ($tingkat ?? ''));
    $indeksTingkat = array_search($tingkat, $daftarTingkat, true);
@endphp
@if($indeksTingkat !== false)
    <span class="tingkat-berurutan">
        @foreach(array_slice($daftarTingkat, 0, $indeksTingkat + 1) as $item)
            <span class="lencana {{ strtolower($item) }}">{{ $item }}</span>
        @endforeach
    </span>
@elseif($tingkat !== '')
    <span class="lencana {{ strtolower($tingkat) }}">{{ $tingkat }}</span>
@else
    <span class="lencana aman">Aman</span>
@endif
