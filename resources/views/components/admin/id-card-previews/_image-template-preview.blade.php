@php
    $sample = $sample ?? config('libcontrol.id_card_preview_sample', []);
@endphp

<article class="id-card-preview__frame relative w-full overflow-hidden bg-white shadow-md ring-1 ring-slate-200" style="aspect-ratio: 86 / 54;">
    @include('students.id-cards._image-template', [
        'templateKey' => $templateKey ?? 'classic',
        'backgroundUrl' => asset(config('libcontrol.id_card_layouts.'.($templateKey ?? 'classic').'.background')),
        'fluid' => true,
        'name' => $sample['name'] ?? '—',
        'fatherName' => $sample['father_name'] ?? '—',
        'dateOfBirth' => $sample['date_of_birth'] ?? '—',
        'studentId' => $sample['student_id'] ?? '—',
        'photoUrl' => null,
        'photoInitials' => $sample['initials'] ?? 'LC',
    ])
</article>
