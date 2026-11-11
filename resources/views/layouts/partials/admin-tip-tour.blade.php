@if ($adminTourEligible ?? false)
    {{-- Floating help — always available for eligible admins --}}
    <button
        type="button"
        x-show="! tipTourOpen"
        x-cloak
        @click="openTipTour()"
        class="fixed bottom-5 right-5 z-[45] inline-flex size-11 items-center justify-center rounded-full bg-[#082a67] text-lg font-bold text-white shadow-lg ring-2 ring-white/80 transition hover:bg-[#0a3580] focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2"
        aria-label="Show quick tips"
        title="Quick tips"
    >?</button>

    <div
        x-show="tipTourOpen"
        x-cloak
        class="fixed inset-0 z-[70]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-tip-tour-title"
    >
        {{-- Click catcher + dim; spotlight cutout uses box-shadow when a target is visible --}}
        <div
            class="absolute inset-0"
            :class="tipSpotlight.visible ? 'bg-transparent' : 'bg-slate-900/50'"
            @click.prevent
        ></div>
        <div
            class="pointer-events-none fixed rounded-xl ring-2 ring-amber-300 transition-[top,left,width,height] duration-200 ease-out"
            :style="tipSpotlightStyle"
        ></div>

        {{-- Tip card sits beside the highlight, never on top of it --}}
        <div
            class="fixed z-[71] w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl transition-[top,left] duration-200 ease-out"
            :style="tipCardStyle"
            @click.stop
        >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600" x-text="tipStepLabel"></p>
            <h2 id="admin-tip-tour-title" class="mt-1 text-lg font-semibold text-gray-900" x-text="tipCurrentStep.title"></h2>
            <p class="mt-2 text-sm leading-relaxed text-gray-600" x-text="tipCurrentStep.body"></p>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <button
                    type="button"
                    class="text-sm font-semibold text-gray-500 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                    @click="skipTipTour()"
                >Skip tips</button>

                <div class="flex items-center gap-2">
                    <a
                        x-show="tipCurrentStep.href"
                        x-cloak
                        :href="tipCurrentStep.href"
                        class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                        @click="skipTipTour()"
                    >Open page</a>
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40"
                        @click="prevTipStep()"
                        :disabled="tipStepIndex === 0"
                    >Back</button>
                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        @click="nextTipStep()"
                        x-text="tipStepIndex >= tipSteps.length - 1 ? 'Got it' : 'Next'"
                    ></button>
                </div>
            </div>
        </div>
    </div>
@endif
