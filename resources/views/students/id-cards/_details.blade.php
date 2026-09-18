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
