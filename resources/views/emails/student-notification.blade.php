<x-mail::message>
# {{ $subjectLine }}

@foreach (preg_split('/\r\n|\r|\n/', $bodyText) as $line)
{{ $line }}

@endforeach

@if ($showStudentCode && $student->student_code)
**Student code:** {{ $student->student_code }}
@endif

Thanks,  
{{ $libraryName }}

{{ config('libcontrol.product.byline') }}
</x-mail::message>
