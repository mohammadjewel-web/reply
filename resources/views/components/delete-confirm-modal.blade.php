@props([
    'name',
    'title',
    'description',
    'action',
    'confirmLabel' => null,
    'cancelLabel' => null,
])

@php
    $confirmLabel = $confirmLabel ?? __('Delete');
    $cancelLabel = $cancelLabel ?? __('Cancel');
@endphp

<x-modal name="{{ $name }}" maxWidth="md" focusable>
    <div class="overflow-hidden rounded-xl border border-red-200/70 bg-[color:var(--app-card-bg)] shadow-2xl ring-1 ring-red-500/10">
        <div class="border-b border-red-100/90 bg-gradient-to-br from-red-50 via-rose-50/80 to-[color:var(--app-card-bg)] px-5 py-5 sm:px-6 sm:py-6">
            <div class="flex gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600 shadow-sm ring-1 ring-red-200/70"
                    aria-hidden="true"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.75"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>
                <div class="min-w-0 flex-1 pt-0.5">
                    <h3 id="delete-confirm-title-{{ $name }}" class="text-base font-semibold leading-snug text-[color:var(--app-text)]">
                        {{ $title }}
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-[color:var(--app-text-muted)]">
                        {{ $description }}
                    </p>
                </div>
            </div>
        </div>

        <form method="post" action="{{ $action }}" class="flex flex-col-reverse gap-2 border-t border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/60 px-5 py-4 sm:flex-row sm:justify-end sm:gap-3 sm:px-6">
            @csrf
            @method('DELETE')
            <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-4 py-2.5 text-sm font-semibold text-[color:var(--app-text)] shadow-sm transition hover:bg-[color:var(--app-shell-bg)] sm:w-auto"
                x-on:click="$dispatch('close-modal', '{{ $name }}')"
            >
                {{ $cancelLabel }}
            </button>
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-lg border border-red-300 bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:ring-offset-2 sm:w-auto"
            >
                {{ $confirmLabel }}
            </button>
        </form>
    </div>
</x-modal>
