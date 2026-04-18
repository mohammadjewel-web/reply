@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\ChannelAccount> $whatsapp */
@endphp
@forelse ($whatsapp as $acc)
    <tr class="align-top border-b border-slate-100 bg-white {{ $acc->is_active ? '' : 'opacity-75' }}">
        <td class="px-4 py-3 font-medium text-slate-900">{{ $acc->name }}</td>
        <td class="max-w-[12rem] truncate px-4 py-3 font-mono text-xs text-slate-600" title="{{ $acc->external_id }}">{{ $acc->external_id ?: '—' }}</td>
        <td class="max-w-[10rem] truncate px-4 py-3 font-mono text-xs text-slate-600" title="{{ $acc->waba_id }}">{{ $acc->waba_id ?: '—' }}</td>
        <td class="whitespace-nowrap px-4 py-3">
            @if ($acc->is_active)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ __('Active') }}
                </span>
            @else
                <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">{{ __('Disabled') }}</span>
            @endif
        </td>
        <td class="px-4 py-3 text-right">
            <div class="flex flex-wrap items-center justify-end gap-1.5">
                <button type="button" data-toggle-edit class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('Edit') }}
                </button>
                <form method="post" action="{{ route('connections.toggle', $acc) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        {{ $acc->is_active ? __('Disable') : __('Enable') }}
                    </button>
                </form>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50"
                    data-connection-remove
                    data-remove-url="{{ route('connections.destroy', $acc) }}"
                    data-remove-name="{{ $acc->name }}"
                >
                    {{ __('Remove') }}
                </button>
                <a href="{{ route('whatsapp.connect', ['account' => $acc->id]) }}" class="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                    {{ __('OAuth / QR') }}
                </a>
            </div>
        </td>
    </tr>
    <tr class="hidden border-b border-slate-200 bg-slate-50/90" data-edit-row>
        <td colspan="5" class="px-4 py-4">
            <form method="post" action="{{ route('connections.update', $acc) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label :value="__('Display name')" for="name_wa_{{ $acc->id }}" />
                        <x-text-input id="name_wa_{{ $acc->id }}" name="name" type="text" class="mt-1 block w-full" :value="$acc->name" required />
                    </div>
                    <div>
                        <x-input-label :value="__('Phone number ID')" for="ext_wa_{{ $acc->id }}" />
                        <x-text-input id="ext_wa_{{ $acc->id }}" name="external_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="$acc->external_id" />
                    </div>
                    <div>
                        <x-input-label :value="__('WABA ID (optional)')" for="waba_{{ $acc->id }}" />
                        <x-text-input id="waba_{{ $acc->id }}" name="waba_id" type="text" class="mt-1 block w-full font-mono text-sm" :value="$acc->waba_id" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label :value="__('New access token (leave blank to keep)')" for="tok_wa_{{ $acc->id }}" />
                        <x-text-input id="tok_wa_{{ $acc->id }}" name="access_token" type="password" class="mt-1 block w-full font-mono text-sm" autocomplete="new-password" />
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Save changes') }}
                    </button>
                </div>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No WhatsApp connections yet.') }}</td>
    </tr>
@endforelse
