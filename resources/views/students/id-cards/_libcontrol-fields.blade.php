@php
    $rows = [
        ['label' => 'NAME', 'value' => $name ?? '—'],
        ['label' => 'F/NAME', 'value' => $fatherName ?? '—'],
        ['label' => 'DATE OF BIRTH', 'value' => $dateOfBirth ?? '—'],
        ['label' => 'STUDENT ID', 'value' => $studentId ?? '—'],
    ];
@endphp

<div class="flex min-w-0 flex-1 flex-col justify-center gap-[2.2mm] pr-1">
    @foreach ($rows as $row)
        <div class="flex items-end gap-[1.5mm] text-[6.5px] font-extrabold uppercase leading-none tracking-wide text-[#0c1648]">
            <span class="shrink-0">{{ $row['label'] }} :</span>
            <span class="min-w-0 flex-1 truncate border-b border-dotted border-[#0c1648]/55 pb-[0.6mm] text-[7px] font-bold normal-case tracking-normal">
                {{ $row['value'] }}
            </span>
        </div>
    @endforeach
</div>
