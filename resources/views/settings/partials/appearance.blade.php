<section class="overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] shadow-[0_1px_3px_rgba(0,0,0,0.05)] ring-1 ring-black/[0.03]">
    <div class="border-b border-[color:var(--app-card-border)]/80 bg-gradient-to-r from-[color:var(--app-accent)]/[0.08] to-transparent px-6 py-5 sm:px-8">
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[color:var(--app-accent)]/20 to-[color:var(--app-primary)]/10 text-[color:var(--app-accent)] ring-1 ring-[color:var(--app-accent)]/20">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <h2 class="text-lg font-semibold text-[color:var(--app-text)]">{{ __('Brand & appearance') }}</h2>
                <p class="mt-0.5 text-sm text-[color:var(--app-text-muted)]">{{ __('Name, logo, favicon, and interface color values.') }}</p>
            </div>
        </div>
    </div>

    <div class="px-6 py-6 sm:px-8 sm:py-8">
        @if ($appSettings->logo_path)
            <form method="post" action="{{ route('settings.logo.destroy') }}" class="mb-4 inline-block" onsubmit="return confirm(@json(__('Remove logo?')))">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-red-600 underline-offset-2 hover:underline">{{ __('Remove current logo') }}</button>
            </form>
        @endif

        @if ($appSettings->favicon_path)
            <form method="post" action="{{ route('settings.favicon.destroy') }}" class="mb-6 inline-block ms-4" onsubmit="return confirm(@json(__('Remove favicon?')))">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-red-600 underline-offset-2 hover:underline">{{ __('Remove favicon') }}</button>
            </form>
        @endif

        <form method="post" action="{{ route('settings.appearance') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PATCH')

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="brand_name" :value="__('Application name')" />
                    <x-text-input id="brand_name" name="brand_name" type="text" class="mt-2 block w-full rounded-xl border-[color:var(--app-card-border)] shadow-sm focus:border-[color:var(--app-primary)] focus:ring-[color:var(--app-primary)]/20" :value="old('brand_name', $appSettings->brand_name)" required />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="logo" :value="__('Logo image')" />
                    <input id="logo" name="logo" type="file" accept="image/*" class="mt-2 block w-full text-sm text-[color:var(--app-text-muted)] file:mr-4 file:rounded-xl file:border-0 file:bg-[color:var(--app-primary)] file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-[color:var(--app-primary-text)] hover:file:bg-[color:var(--app-primary-hover)]" />
                    @if ($appSettings->logo_path)
                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ $appSettings->logoPublicUrl() }}" alt="" class="h-12 w-12 rounded-lg border border-[color:var(--app-card-border)] object-contain p-1" />
                            <span class="text-xs text-[color:var(--app-text-muted)]">{{ __('Current logo preview') }}</span>
                        </div>
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="favicon" :value="__('Favicon')" />
                    <input id="favicon" name="favicon" type="file" accept=".ico,.png,.jpg,.jpeg,.gif,.webp,.svg,image/x-icon" class="mt-2 block w-full text-sm text-[color:var(--app-text-muted)] file:mr-4 file:rounded-xl file:border-0 file:bg-[color:var(--app-primary)] file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-[color:var(--app-primary-text)] hover:file:bg-[color:var(--app-primary-hover)]" />
                    @if ($appSettings->favicon_path)
                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ $appSettings->faviconPublicUrl() }}" alt="" class="h-10 w-10 rounded border border-[color:var(--app-card-border)] object-contain p-1" />
                            <span class="text-xs text-[color:var(--app-text-muted)]">{{ __('Current favicon preview') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-[color:var(--app-card-border)] bg-[color:var(--app-shell-bg)]/40 p-5 sm:p-6">
                <h3 class="text-base font-semibold text-[color:var(--app-text)]">{{ __('Interface colors') }}</h3>
                <p class="mt-1 text-sm leading-relaxed text-[color:var(--app-text-muted)]">{{ __('Set color values using hex or valid CSS color syntax. Preview updates live.') }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($themeLabels as $key => $label)
                        @php($currentColor = old('theme.'.$key, $theme[$key] ?? '#000000'))
                        <div class="rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-3">
                            <label for="theme_{{ $key }}" class="block text-xs font-semibold uppercase tracking-wide text-[color:var(--app-text-muted)]">{{ $label }}</label>
                            <div class="mt-2 flex items-center gap-3">
                                <span class="h-8 w-8 shrink-0 rounded-lg border border-[color:var(--app-card-border)] shadow-inner js-theme-swatch" data-theme-swatch="{{ $key }}" style="background: {{ $currentColor }}"></span>
                                <input
                                    type="color"
                                    data-theme-picker="{{ $key }}"
                                    value="{{ preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', (string) $currentColor) ? (strlen((string) $currentColor) === 4 ? sprintf('#%s%s%s%s%s%s', $currentColor[1], $currentColor[1], $currentColor[2], $currentColor[2], $currentColor[3], $currentColor[3]) : $currentColor) : '#000000' }}"
                                    class="h-9 w-12 shrink-0 cursor-pointer rounded-lg border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] p-0.5"
                                    aria-label="{{ $label }}"
                                />
                                <input id="theme_{{ $key }}" name="theme[{{ $key }}]" type="text" value="{{ old('theme.'.$key, $theme[$key] ?? '') }}" autocomplete="off" spellcheck="false" data-theme-key="{{ $key }}" class="block w-full rounded-xl border border-[color:var(--app-card-border)] bg-[color:var(--app-card-bg)] px-3 py-2.5 font-mono text-sm text-[color:var(--app-text)] shadow-inner transition placeholder:text-[color:var(--app-text-muted)]/50 focus:border-[color:var(--app-primary)] focus:outline-none focus:ring-2 focus:ring-[color:var(--app-primary)]/20" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <div
                    id="theme-live-preview"
                    class="mt-6 overflow-hidden rounded-2xl border border-[color:var(--app-card-border)] shadow-inner"
                    style="
                        --app-bg: {{ old('theme.app_bg', $theme['app_bg'] ?? '#f6f8fb') }};
                        --app-shell-bg: {{ old('theme.shell_bg', $theme['shell_bg'] ?? '#eef2f7') }};
                        --app-sidebar-from: {{ old('theme.sidebar_from', $theme['sidebar_from'] ?? '#0f172a') }};
                        --app-sidebar-to: {{ old('theme.sidebar_to', $theme['sidebar_to'] ?? '#1e293b') }};
                        --app-sidebar-border: {{ old('theme.sidebar_border', $theme['sidebar_border'] ?? '#334155') }};
                        --app-sidebar-text: {{ old('theme.sidebar_text', $theme['sidebar_text'] ?? '#e2e8f0') }};
                        --app-sidebar-muted: {{ old('theme.sidebar_muted', $theme['sidebar_muted'] ?? '#94a3b8') }};
                        --app-header-bg: {{ old('theme.header_bg', $theme['header_bg'] ?? '#ffffff') }};
                        --app-header-border: {{ old('theme.header_border', $theme['header_border'] ?? '#dbe2ea') }};
                        --app-header-text: {{ old('theme.header_text', $theme['header_text'] ?? '#0f172a') }};
                        --app-card-bg: {{ old('theme.card_bg', $theme['card_bg'] ?? '#ffffff') }};
                        --app-card-border: {{ old('theme.card_border', $theme['card_border'] ?? '#dbe2ea') }};
                        --app-text: {{ old('theme.text_primary', $theme['text_primary'] ?? '#0f172a') }};
                        --app-text-muted: {{ old('theme.text_muted', $theme['text_muted'] ?? '#64748b') }};
                        --app-primary: {{ old('theme.primary', $theme['primary'] ?? '#2563eb') }};
                        --app-primary-hover: {{ old('theme.primary_hover', $theme['primary_hover'] ?? '#1d4ed8') }};
                        --app-primary-text: {{ old('theme.primary_text', $theme['primary_text'] ?? '#ffffff') }};
                        --app-accent: {{ old('theme.accent', $theme['accent'] ?? '#0ea5e9') }};
                        --app-code-bg: {{ old('theme.code_bg', $theme['code_bg'] ?? '#f1f5f9') }};
                    "
                >
                    <div class="flex h-40">
                        <div class="w-24 p-2 text-[10px]" style="background: linear-gradient(to bottom, var(--app-sidebar-from), var(--app-sidebar-to)); color: var(--app-sidebar-text); border-right: 1px solid var(--app-sidebar-border);">
                            <div style="color: var(--app-sidebar-muted)">{{ __('Menu') }}</div>
                            <div class="mt-2 rounded px-1 py-0.5" style="background: rgba(255,255,255,0.12);">{{ __('Inbox') }}</div>
                        </div>
                        <div class="min-w-0 flex-1 p-3" style="background: var(--app-bg); color: var(--app-text);">
                            <div class="rounded-lg border px-2 py-1 text-[10px]" style="background: var(--app-header-bg); border-color: var(--app-header-border); color: var(--app-header-text);">
                                {{ __('Header preview') }}
                            </div>
                            <div class="mt-2 rounded-lg border p-2 text-[10px]" style="background: var(--app-card-bg); border-color: var(--app-card-border);">
                                <div>{{ __('Card text') }}</div>
                                <div class="mt-1" style="color: var(--app-text-muted);">{{ __('Muted text') }}</div>
                                <div class="mt-2 inline-flex rounded px-2 py-1 text-[9px] font-semibold" style="background: var(--app-primary); color: var(--app-primary-text);">{{ __('Button') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-[color:var(--app-card-border)]/80 pt-6">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[color:var(--app-primary)] px-6 py-3 text-sm font-semibold text-[color:var(--app-primary-text)] shadow-md transition hover:bg-[color:var(--app-primary-hover)] focus:outline-none focus:ring-2 focus:ring-[color:var(--app-primary)]/40">
                    {{ __('Save appearance') }}
                </button>
            </div>
        </form>
    </div>
</section>

<script>
    (function () {
        const previewRoot = document.getElementById('theme-live-preview');
        if (!previewRoot) return;
        const inputSelector = 'input[data-theme-key]';
        const keyToCssVar = {
            app_bg: '--app-bg',
            shell_bg: '--app-shell-bg',
            sidebar_from: '--app-sidebar-from',
            sidebar_to: '--app-sidebar-to',
            sidebar_border: '--app-sidebar-border',
            sidebar_text: '--app-sidebar-text',
            sidebar_muted: '--app-sidebar-muted',
            header_bg: '--app-header-bg',
            header_border: '--app-header-border',
            header_text: '--app-header-text',
            card_bg: '--app-card-bg',
            card_border: '--app-card-border',
            text_primary: '--app-text',
            text_muted: '--app-text-muted',
            primary: '--app-primary',
            primary_hover: '--app-primary-hover',
            primary_text: '--app-primary-text',
            accent: '--app-accent',
            code_bg: '--app-code-bg'
        };

        document.querySelectorAll(inputSelector).forEach(function (input) {
            input.addEventListener('input', function () {
                const key = input.getAttribute('data-theme-key');
                const value = (input.value || '').trim();
                const cssVar = keyToCssVar[key];
                if (cssVar && value !== '') {
                    previewRoot.style.setProperty(cssVar, value);
                }
                const swatch = document.querySelector('[data-theme-swatch="' + key + '"]');
                if (swatch) {
                    swatch.style.background = value !== '' ? value : 'transparent';
                }
                const picker = document.querySelector('[data-theme-picker="' + key + '"]');
                if (picker && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value)) {
                    picker.value = value.length === 4
                        ? '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3]
                        : value;
                }
            });
        });

        document.querySelectorAll('input[data-theme-picker]').forEach(function (picker) {
            picker.addEventListener('input', function () {
                const key = picker.getAttribute('data-theme-picker');
                const textInput = document.querySelector('input[data-theme-key="' + key + '"]');
                if (!textInput) return;
                textInput.value = picker.value;
                textInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    })();
</script>
