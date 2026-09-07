<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Attendance Check-in — {{ $branchName }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 font-sans text-gray-900 antialiased">
        <div class="mx-auto min-h-screen max-w-md px-4 py-8">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-indigo-600 px-6 py-5 text-white">
                    <h1 class="text-xl font-bold">Attendance Check-in</h1>
                    <p class="mt-1 text-sm text-indigo-100">{{ $branchName }}</p>
                </div>

                <form method="POST" action="{{ route('attendance.public.check-in.store', $token) }}" class="space-y-4 px-6 py-6">
                    @csrf

                    <p class="text-sm text-gray-600">Enter your student code and registered phone number to mark today's attendance.</p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Student Code</label>
                        <input type="text" name="student_code" value="{{ old('student_code') }}" required autocomplete="off" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                        @error('student_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required inputmode="numeric" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        Check in
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>
