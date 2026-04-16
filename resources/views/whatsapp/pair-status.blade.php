<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — WhatsApp</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white shadow-md rounded-lg p-8">
        <h1 class="text-lg font-semibold text-gray-900">{{ __('WhatsApp link status') }}</h1>

        @if (session('status'))
            <p class="mt-4 text-sm text-green-700">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="mt-4 text-sm text-red-600">{{ session('error') }}</p>
        @endif

        <dl class="mt-6 text-sm space-y-2">
            <dt class="text-gray-500">{{ __('Status') }}</dt>
            <dd class="font-medium">{{ $session->status }}</dd>
            @if ($session->error_message)
                <dt class="text-gray-500">{{ __('Detail') }}</dt>
                <dd class="text-red-600 text-xs break-all">{{ $session->error_message }}</dd>
            @endif
        </dl>

        <p class="mt-6 text-xs text-gray-500">
            {{ __('Webhook URL for inbound messages:') }}
            <span class="font-mono block mt-1 break-all">{{ $webhookUrl }}</span>
        </p>

        <a href="{{ route('login') }}" class="mt-8 inline-block text-sm text-indigo-600 hover:text-indigo-800">
            {{ __('Admin login') }}
        </a>
    </div>
</body>
</html>
