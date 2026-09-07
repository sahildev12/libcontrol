<x-admin-layout>
    @if ($mode === 'admin')
        @include('dashboard.admin')
    @else
        @include('dashboard.branch')
    @endif
</x-admin-layout>
