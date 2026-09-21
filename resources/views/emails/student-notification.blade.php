<x-mail::message>
# {{ $subjectLine }}

@foreach (preg_split('/\r\n|\r|\n/', $bodyText) as $line)
{{ $line }}

@endforeach

@if ($showStudentCode && $student->student_code)
**Student code:** {{ $student->student_code }}
@endif

Thanks,<br>
{{ $libraryName }}<br>
<span style="color: #6b7280;">{{ config('libcontrol.product.byline') }}</span>
</x-mail::message>
