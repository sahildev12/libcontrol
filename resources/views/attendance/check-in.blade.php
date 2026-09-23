<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Attendance — {{ $branchName }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 font-sans text-gray-900 antialiased">
        <div class="mx-auto min-h-screen max-w-md px-4 py-8">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lc-card">
                <div class="border-b border-white/10 bg-gradient-to-r from-brand-navy to-brand-blue px-6 py-5 text-white">
                    <h1 class="text-xl font-bold">Attendance</h1>
                    <p class="mt-1 text-sm text-white/80">{{ $branchName }}</p>
                </div>

                <form
                    id="attendance-check-in-form"
                    method="POST"
                    action="{{ route('attendance.public.check-in.store', $token) }}"
                    class="flex flex-col"
                    novalidate
                >
                    @csrf

                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-600">Enter your student number and registered phone to mark attendance for today.</p>

                        <div>
                            <label for="action" class="block text-sm font-medium text-gray-700">Action</label>
                            <select
                                id="action"
                                name="action"
                                required
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand-blue focus:outline-none focus:ring-2 focus:ring-brand-blue/30"
                            >
                                <option value="check_in" @selected(old('action', 'check_in') === 'check_in')>Check in</option>
                                <option value="check_out" @selected(old('action') === 'check_out')>Check out</option>
                            </select>
                            @error('action') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="student_code_suffix" class="block text-sm font-medium text-gray-700">Student Code</label>
                            <div class="mt-1 flex overflow-hidden rounded-lg border border-gray-300 shadow-sm focus-within:border-brand-blue focus-within:ring-2 focus-within:ring-brand-blue/30">
                                <span class="inline-flex shrink-0 items-center border-r border-gray-300 bg-brand-blue/5 px-3 text-sm font-semibold tracking-wide text-brand-blue select-none" aria-hidden="true">{{ $studentCodePrefix }}-</span>
                                <input
                                    type="text"
                                    id="student_code_suffix"
                                    name="student_code_suffix"
                                    value="{{ old('student_code_suffix') }}"
                                    required
                                    autocomplete="off"
                                    inputmode="numeric"
                                    pattern="[0-9]{1,6}"
                                    maxlength="6"
                                    placeholder="e.g. 1"
                                    aria-describedby="student-code-hint"
                                    class="block min-w-0 flex-1 border-0 px-3 py-2.5 text-sm focus:outline-none focus:ring-0"
                                >
                            </div>
                            <p id="student-code-hint" class="mt-1 text-xs text-gray-500">Full code example: <span class="font-medium text-gray-700">{{ $studentCodeSample }}</span></p>
                            @error('student_code_suffix') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                required
                                inputmode="numeric"
                                pattern="[6-9][0-9]{9}"
                                maxlength="10"
                                minlength="10"
                                placeholder="10-digit mobile"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-brand-blue focus:outline-none focus:ring-2 focus:ring-brand-blue/30"
                            >
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-200 bg-white px-6 py-4">
                        <button
                            type="submit"
                            id="submit-btn"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-brand-blue px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-navy focus:outline-none focus:ring-2 focus:ring-brand-blue/30"
                        >
                            Check in
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function () {
                const action = document.getElementById('action');
                const submitBtn = document.getElementById('submit-btn');
                const suffix = document.getElementById('student_code_suffix');
                const phone = document.getElementById('phone');

                function digitsOnly(input, maxLen) {
                    input.addEventListener('input', function () {
                        const cleaned = input.value.replace(/\D/g, '').slice(0, maxLen);
                        if (input.value !== cleaned) {
                            input.value = cleaned;
                        }
                    });
                }

                digitsOnly(suffix, 6);
                digitsOnly(phone, 10);

                function syncSubmitLabel() {
                    submitBtn.textContent = action.value === 'check_out' ? 'Check out' : 'Check in';
                }

                action.addEventListener('change', syncSubmitLabel);
                syncSubmitLabel();
            })();
        </script>
    </body>
</html>
