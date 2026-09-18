<dl class="grid grid-cols-3 gap-x-1.5 gap-y-0.5 text-[6px] leading-tight">
    <div>
        <dt class="uppercase tracking-wide text-slate-400">Plan</dt>
        <dd class="font-semibold text-slate-800">{{ $sample['course'] ?? 'BCA' }}</dd>
    </div>
    <div>
        <dt class="uppercase tracking-wide text-slate-400">Valid till</dt>
        <dd class="font-semibold text-slate-800">{{ $sample['valid_till'] }}</dd>
    </div>
    <div>
        <dt class="uppercase tracking-wide text-slate-400">Branch</dt>
        <dd class="truncate font-semibold text-slate-800">{{ $sample['branch'] }}</dd>
    </div>
</dl>
