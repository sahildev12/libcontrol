<div
    x-data="confirmDialogHost()"
    @confirm-dialog.window="openDialog($event.detail)"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[110] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="title ? 'confirm-dialog-title' : null"
>
    <div class="absolute inset-0 bg-gray-900/60" @click="cancel()"></div>
    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl" @click.stop>
        <div class="border-b border-white/10 bg-gradient-to-r from-brand-navy to-brand-blue px-5 py-4">
            <h2 id="confirm-dialog-title" class="text-base font-semibold text-white" x-text="title"></h2>
        </div>
        <div class="px-5 py-4">
            <p class="text-sm leading-relaxed text-gray-600" x-text="message"></p>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
            <button
                type="button"
                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                @click="cancel()"
                x-text="cancelLabel"
            ></button>
            <button
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
                :class="tone === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-blue hover:bg-brand-navy'"
                @click="confirm()"
                x-text="confirmLabel"
            ></button>
        </div>
    </div>
</div>
