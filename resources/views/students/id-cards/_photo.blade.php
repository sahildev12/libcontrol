@if ($student->photoUrl())
    <img src="{{ $student->photoUrl() }}" alt="{{ $student->name }}" class="{{ $class ?? 'size-[16mm] object-cover' }}">
@else
    <div class="{{ $fallbackClass ?? 'flex size-[16mm] items-center justify-center bg-indigo-100 text-sm font-bold text-indigo-800' }}">
        {{ $student->initials() }}
    </div>
@endif
