<div
    x-data="toastHost()"
    x-init="init()"
    class="pointer-events-none fixed inset-x-0 bottom-6 z-[100] flex flex-col items-center gap-2 px-4"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-6"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur-sm"
            :class="toast.type === 'error'
                ? 'border-red-200 bg-red-50/95 text-red-900'
                : 'border-emerald-200 bg-emerald-50/95 text-emerald-900'"
        >
            <span
                class="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                :class="toast.type === 'error' ? 'bg-red-500' : 'bg-emerald-500'"
                x-text="toast.type === 'error' ? '!' : '✓'"
            ></span>
            <p class="min-w-0 flex-1 text-sm font-medium leading-5" x-text="toast.message"></p>
            <button
                type="button"
                class="shrink-0 text-gray-400 hover:text-gray-600"
                @click="dismiss(toast.id)"
                aria-label="Dismiss notification"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
