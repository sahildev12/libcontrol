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
        <article class="id-card relative w-[86mm] overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200" style="height: 54mm;">
            <div class="flex h-full">
                <div class="flex w-[22mm] flex-col items-center justify-between bg-indigo-700 px-1.5 py-2 text-center text-white">
                    <p class="text-[8px] font-bold uppercase tracking-[0.18em]">{{ $branchName }}</p>
                    @if ($student->photoUrl())
                        <img src="{{ $student->photoUrl() }}" alt="" class="size-[16mm] rounded-md object-cover ring-2 ring-white/40">
                    @else
                        <div class="flex size-[16mm] items-center justify-center rounded-md bg-indigo-500 text-base font-bold ring-2 ring-white/40">{{ $student->initials() }}</div>
                    @endif
                    <p class="text-[7px] font-semibold uppercase tracking-widest">{{ $student->typeLabel() }}</p>
                </div>
                <div class="flex flex-1 flex-col justify-between px-3 py-2">
                    <div>
                        <p class="text-[8px] font-semibold uppercase tracking-[0.16em] text-indigo-600">Student identity card</p>
                        <h1 class="mt-0.5 text-[13px] font-bold leading-tight text-slate-900">{{ $student->name }}</h1>
                        <p class="mt-0.5 font-mono text-[10px] font-semibold text-indigo-700">{{ $student->student_code }}</p>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-2 gap-y-0.5 text-[8px] leading-tight text-slate-600">
                        <div>
                            <dt class="uppercase tracking-wide text-slate-400">Gender</dt>
                            <dd class="font-medium text-slate-800">{{ $student->gender ? ucfirst($student->gender) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="uppercase tracking-wide text-slate-400">DOB</dt>
                            <dd class="font-medium text-slate-800">{{ $student->date_of_birth?->format('d M Y') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="uppercase tracking-wide text-slate-400">Phone</dt>
                            <dd class="font-medium text-slate-800">{{ $student->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="uppercase tracking-wide text-slate-400">Status</dt>
                            <dd class="font-medium capitalize text-slate-800">{{ $student->status }}</dd>
                        </div>
                    </dl>
                    <p class="text-[7px] text-slate-400">{{ $branchName }} · Keep this card with you in the library</p>
                </div>
            </div>
        </article>
    </div>
</body>
</html>
