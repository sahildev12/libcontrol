@include('students.id-cards._image-template', [
    'templateKey' => 'classic',
    'backgroundUrl' => asset(config('libcontrol.id_card_layouts.classic.background')),
    'name' => $student->name,
    'fatherName' => $student->father_name ?: '—',
    'dateOfBirth' => $student->date_of_birth?->format('d-m-Y') ?? '—',
    'studentId' => $student->student_code,
    'photoUrl' => $student->photoUrl(),
    'photoInitials' => $student->initials(),
])
