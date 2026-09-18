<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Card · {{ $student->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: auto; margin: 12mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .id-card { box-shadow: none !important; break-inside: avoid; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <div class="no-print mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 py-4">
        <a href="{{ route('students.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to students</a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
            Print ID Card
        </button>
    </div>

    <div class="flex justify-center px-4 pb-10 pt-2">
        @php
            $cardView = 'students.id-cards.'.$template;
            if (! view()->exists($cardView)) {
                $cardView = 'students.id-cards.classic';
            }
        @endphp
        @include($cardView)
    </div>
</body>
</html>
