<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Print · {{ $student->name }}</title>
    @vite(['resources/css/student-id-cards.css'])
    <style>
        @page {
            size: 86mm 54mm;
            margin: 0;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 86mm;
            height: 54mm;
            background: #fff;
        }
        .id-card-print-sheet {
            width: 86mm;
            height: 54mm;
            overflow: hidden;
        }
        .id-card-print-sheet > div {
            width: 100%;
            height: 100%;
            box-shadow: none !important;
        }
        .id-card-print-sheet img {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="id-card-print-sheet">
        @includeFirst([
            'students.id-cards.layouts.'.$layoutKey,
            'students.id-cards.layouts.classic',
        ], [
            'fluid' => true,
            'backgroundUrl' => $backgroundUrl,
            'name' => $name,
            'fatherName' => $fatherName,
            'dateOfBirth' => $dateOfBirth,
            'studentId' => $studentId,
            'photoUrl' => $photoUrl,
            'photoInitials' => $photoInitials,
        ])
    </div>
</body>
</html>
