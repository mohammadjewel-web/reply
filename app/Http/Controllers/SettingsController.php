<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $appSettings = AppSetting::current();

        return view('settings.index', [
            'whatsappWebhook' => url('/webhooks/whatsapp'),
            'messengerWebhook' => url('/webhooks/messenger'),
            'appSettings' => $appSettings,
            'themeDefaults' => AppSetting::defaultTheme(),
            'theme' => $appSettings->mergedTheme(),
            'themeKeyCss' => AppSetting::themeKeyToCssProperty(),
            'themeLabels' => [
                'app_bg' => __('Main content background'),
                'shell_bg' => __('Outer shell background'),
                'sidebar_from' => __('Sidebar gradient (top)'),
                'sidebar_via' => __('Sidebar gradient (middle)'),
                'sidebar_to' => __('Sidebar gradient (bottom)'),
                'sidebar_border' => __('Sidebar border'),
                'sidebar_text' => __('Sidebar primary text'),
                'sidebar_muted' => __('Sidebar muted text'),
                'sidebar_nav_muted' => __('Sidebar section labels'),
                'header_bg' => __('Top header background'),
                'header_border' => __('Top header border'),
                'header_text' => __('Top header title text'),
                'card_bg' => __('Card / panel background'),
                'card_border' => __('Card border'),
                'text_primary' => __('Primary text'),
                'text_muted' => __('Muted / secondary text'),
                'primary' => __('Primary button / links'),
                'primary_hover' => __('Primary button hover'),
                'primary_text' => __('Text on primary buttons'),
                'accent' => __('Accent (secondary actions)'),
                'code_bg' => __('Code / monospace blocks'),
            ],
            'mail' => [
                'mail_mailer' => $appSettings->mail_mailer ?: 'smtp',
                'mail_host' => $appSettings->mail_host ?: config('mail.mailers.smtp.host'),
                'mail_port' => $appSettings->mail_port ?: config('mail.mailers.smtp.port'),
                'mail_username' => $appSettings->mail_username ?: config('mail.mailers.smtp.username'),
                'mail_password' => $appSettings->mail_password ?: '',
                'mail_encryption' => $appSettings->mail_encryption ?: config('mail.mailers.smtp.encryption'),
                'mail_from_address' => $appSettings->mail_from_address ?: config('mail.from.address'),
                'mail_from_name' => $appSettings->mail_from_name ?: config('mail.from.name'),
            ],
        ]);
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $request->validate([
            'mute_sound' => ['nullable'],
            'mute_desktop' => ['nullable'],
        ]);

        $request->user()->update([
            'notification_preferences' => [
                'mute_sound' => $request->boolean('mute_sound'),
                'mute_desktop' => $request->boolean('mute_desktop'),
            ],
        ]);

        return back()->with('status', __('Notification preferences saved.'));
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        if (! $request->user()->allows('settings.integrations')) {
            abort(403);
        }

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'])->max(1024)],
            'theme' => ['required', 'array'],
            'theme.*' => ['required', 'string', 'max:80'],
        ]);

        $settings = AppSetting::current();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $path = $request->file('logo')->store('brand', 'public');
            $settings->logo_path = $path;
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }
            $settings->favicon_path = $request->file('favicon')->store('brand', 'public');
        }

        $settings->brand_name = $validated['brand_name'];
        $settings->theme = array_merge(AppSetting::defaultTheme(), $validated['theme']);
        $settings->save();

        return back()->with('status', __('Appearance saved.'));
    }

    public function updateEmailConfiguration(Request $request): RedirectResponse
    {
        if (! $request->user()->allows('settings.integrations')) {
            abort(403);
        }

        $validated = $request->validate([
            'mail_mailer' => ['required', 'string', 'in:smtp'],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,null'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        $settings = AppSetting::current();
        $settings->fill([
            'mail_mailer' => $validated['mail_mailer'],
            'mail_host' => trim($validated['mail_host']),
            'mail_port' => (int) $validated['mail_port'],
            'mail_username' => $validated['mail_username'] !== null ? trim($validated['mail_username']) : null,
            'mail_encryption' => ($validated['mail_encryption'] ?? null) === 'null' ? null : ($validated['mail_encryption'] ?? null),
            'mail_from_address' => trim($validated['mail_from_address']),
            'mail_from_name' => trim($validated['mail_from_name']),
        ]);

        if (($validated['mail_password'] ?? '') !== '') {
            $settings->mail_password = $validated['mail_password'];
        }

        $settings->save();
        $settings->applyMailConfig();

        return back()->with('status', __('Email configuration saved.'));
    }

    public function resetTheme(Request $request): RedirectResponse
    {
        if (! $request->user()->allows('settings.integrations')) {
            abort(403);
        }

        $settings = AppSetting::current();
        $settings->theme = AppSetting::defaultTheme();
        $settings->save();

        return back()->with('status', __('Colors reset to defaults.'));
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        if (! $request->user()->allows('settings.integrations')) {
            abort(403);
        }

        $settings = AppSetting::current();
        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->logo_path = null;
            $settings->save();
        }

        return back()->with('status', __('Logo removed.'));
    }

    public function removeFavicon(Request $request): RedirectResponse
    {
        if (! $request->user()->allows('settings.integrations')) {
            abort(403);
        }

        $settings = AppSetting::current();
        if ($settings->favicon_path) {
            Storage::disk('public')->delete($settings->favicon_path);
            $settings->favicon_path = null;
            $settings->save();
        }

        return back()->with('status', __('Favicon removed.'));
    }
}
