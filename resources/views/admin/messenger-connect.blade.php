<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Facebook / Messenger') }}</h2>
    </x-slot>

    <div class="app-page app-page--narrow space-y-6">
        <div class="app-card border-blue-200/60 bg-gradient-to-br from-blue-50/90 to-white">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#0084ff] text-white shadow-md ring-1 ring-blue-400/30">
                    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </div>
                <div>
                    <h3 class="app-card__title">{{ __('Messenger connection') }}</h3>
                    <p class="app-card__lead">{{ __('Link your Facebook Page and subscribe to webhooks so inbound messages reach your chat inbox.') }}</p>
                </div>
            </div>
        </div>

        <div class="app-card">
            <h3 class="app-card__title">{{ __('Webhook') }}</h3>
            <p class="app-card__lead">{{ __('In the Meta app → Messenger → Settings, set the callback URL and verify token, then subscribe to messaging events.') }}</p>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-slate-500">{{ __('Callback URL') }}</dt>
                    <dd class="app-code-block mt-1">{{ $webhookUrl }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">{{ __('Verify token') }}</dt>
                    <dd class="app-code-block mt-1">{{ $verifyToken ?: __('Set Messenger Verify Token in Connections') }}</dd>
                </div>
            </dl>
        </div>

        @if (auth()->user()->allows('connections.manage'))
            <div class="app-card border-teal-200/50 bg-teal-50/40">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="app-card__title">{{ __('Multiple Facebook pages') }}</h3>
                        <p class="app-card__lead">{{ __('Add each Page ID and its own access token under Connections. Webhooks are matched to the correct inbox line.') }}</p>
                    </div>
                    <a href="{{ route('connections.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ __('Open Connections') }}
                    </a>
                </div>
            </div>
        @endif

        <div class="app-card">
            <h3 class="app-card__title">{{ __('Required credentials') }}</h3>
            <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-600">
                <li>{{ __('Page access token per page (stored in each Connections row)') }}</li>
                <li>{{ __('Messenger app secret (stored in Connections → Meta app credentials)') }}</li>
                <li>{{ __('Messenger verify token (must match the value in Meta developer console)') }}</li>
            </ul>
            <p class="mt-4 text-xs text-slate-500">
                {{ __('Docs:') }}
                <a href="https://developers.facebook.com/docs/messenger-platform/webhooks" class="font-medium text-blue-600 hover:underline" target="_blank" rel="noopener">Messenger Platform webhooks</a>
            </p>
        </div>

        <p class="text-center">
            <a href="{{ route('inbox') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">{{ __('Go to Chats') }} →</a>
        </p>
    </div>
</x-app-layout>
