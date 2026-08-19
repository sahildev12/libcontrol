<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Student Registration — {{ $branchName }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 font-sans text-gray-900 antialiased">
        <div class="mx-auto min-h-screen max-w-2xl px-4 py-8 sm:px-6">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="flex items-start gap-3">
                        <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Student Self-Registration</h1>
                            <p class="mt-1 text-sm text-gray-600">Register for {{ $branchName }}. This link expires in 2 hours and works only once.</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('students.register.store', $invite->token) }}" enctype="multipart/form-data" class="space-y-5 px-6 py-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Rahul Sharma" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                            <select name="gender" required class="admin-select mt-1 block w-full px-3 py-2">
                                <option value="male" @selected(old('gender', 'male') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                            </select>
                            @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required max="{{ now()->subDay()->format('Y-m-d') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            @error('date_of_birth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contact <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}" required maxlength="10" inputmode="numeric" pattern="[6-9][0-9]{9}" placeholder="10-digit mobile" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="For reminders" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Father's Name</label>
                        <input type="text" name="father_name" value="{{ old('father_name') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">{{ old('address') }}</textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID Document Type</label>
                            <select name="id_proof_type" class="admin-select mt-1 block w-full px-3 py-2">
                                <option value="">Select type</option>
                                <option value="Aadhaar" @selected(old('id_proof_type') === 'Aadhaar')>Aadhaar</option>
                                <option value="PAN" @selected(old('id_proof_type') === 'PAN')>PAN</option>
                                <option value="Driving License" @selected(old('id_proof_type') === 'Driving License')>Driving License</option>
                                <option value="Voter ID" @selected(old('id_proof_type') === 'Voter ID')>Voter ID</option>
                                <option value="Passport" @selected(old('id_proof_type') === 'Passport')>Passport</option>
                                <option value="Other" @selected(old('id_proof_type') === 'Other')>Other</option>
                            </select>
                            @error('id_proof_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID Document File</label>
                            <input type="file" name="id_proof" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-gray-600">
                            @error('id_proof') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Student Photo (optional)</label>
                        <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-600">
                        @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-5">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                            Submit Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
