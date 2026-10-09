@includeFirst([
    'students.id-cards.layouts.'.($templateKey ?? 'classic'),
    'students.id-cards.layouts.classic',
], [
    'backgroundUrl' => $backgroundUrl,
    'fluid' => $fluid ?? false,
    'name' => $name ?? '—',
    'fatherName' => $fatherName ?? '—',
    'dateOfBirth' => $dateOfBirth ?? '—',
    'studentId' => $studentId ?? '—',
    'photoUrl' => $photoUrl ?? null,
    'photoInitials' => $photoInitials ?? '—',
])
