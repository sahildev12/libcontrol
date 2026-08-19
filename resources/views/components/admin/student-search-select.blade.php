@props([
    'label' => 'Student',
    'required' => false,
    'placeholder' => 'Search by name, code, or phone...',
])

<div class="relative" @click.outside="closeStudentPicker()">
    <label class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <button
        type="button"
        x-ref="studentPickerTrigger"
        @click="openStudentPicker()"
        class="mt-1 flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
        :class="getStudentSelectId() ? 'text-gray-900' : 'text-gray-500'"
    >
        <span class="truncate" x-text="selectedStudentLabel() || 'Choose a student'"></span>
        <svg class="size-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div
        x-show="studentPickerOpen"
        x-cloak
        x-transition
        @click.stop
        class="fixed z-[70] flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl"
        :style="`top: ${studentPickerStyle.top}; left: ${studentPickerStyle.left}; width: ${studentPickerStyle.width}; max-height: ${studentPickerStyle.maxHeight};`"
    >
        <div class="shrink-0 border-b border-gray-100 p-2">
            <input
                type="search"
                x-ref="studentPickerSearch"
                x-model="studentPickerQuery"
                placeholder="{{ $placeholder }}"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                @keydown.escape.prevent="closeStudentPicker()"
            >
        </div>

        <ul class="min-h-0 flex-1 overflow-y-auto py-1 text-sm">
            <li x-show="studentPickerShowAddNew">
                <button
                    type="button"
                    @click="selectAddNewFromPicker()"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left font-semibold text-indigo-600 hover:bg-indigo-50"
                >
                    <span class="text-base leading-none">+</span>
                    Add new student
                </button>
            </li>

            <template x-for="student in filteredStudentsForPicker()" :key="student.id">
                <li>
                    <button
                        type="button"
                        @click="selectStudentFromPicker(student)"
                        class="flex w-full flex-col px-3 py-2 text-left hover:bg-gray-50"
                        :class="String(getStudentSelectId()) === String(student.id) ? 'bg-indigo-50 text-indigo-900' : 'text-gray-900'"
                    >
                        <span class="font-medium" x-text="`${student.student_code} — ${student.name}`"></span>
                        <span x-show="student.phone" class="text-xs text-gray-500" x-text="student.phone"></span>
                    </button>
                </li>
            </template>

            <li x-show="filteredStudentsForPicker().length === 0 && ! studentPickerShowAddNew" class="px-3 py-3 text-center text-gray-500">
                No students found.
            </li>
            <li x-show="filteredStudentsForPicker().length === 0 && studentPickerShowAddNew && studentPickerQuery.trim()" class="px-3 py-3 text-center text-gray-500">
                No matches. Try another search or add a new student.
            </li>
        </ul>
    </div>
</div>
