<dl class="grid grid-cols-3 gap-x-2 gap-y-1 text-[7px] leading-tight text-slate-600">
    <div>
        <dt class="uppercase tracking-wide text-slate-400">Plan</dt>
        <dd class="font-semibold text-slate-800">{{ $membershipPlan ?? $student->typeLabel() }}</dd>
    </div>
    <div>
        <dt class="uppercase tracking-wide text-slate-400">Valid till</dt>
        <dd class="font-semibold text-slate-800">{{ $validTill ?? '—' }}</dd>
    </div>
    <div>
        <dt class="uppercase tracking-wide text-slate-400">Branch</dt>
        <dd class="truncate font-semibold text-slate-800">{{ $branchName }}</dd>
    </div>
</dl>
