<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppSetting extends Model
{
    protected $fillable = [
        'brand_name',
        'logo_path',
        'favicon_path',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'facebook_app_id',
        'facebook_app_secret',
        'facebook_client_token',
        'whatsapp_verify_token',
        'whatsapp_app_secret',
        'whatsapp_embedded_config_id',
        'messenger_verify_token',
        'messenger_app_secret',
        'theme',
    ];

    protected function casts(): array
    {
        return [
            'theme' => 'array',
            'mail_port' => 'integer',
            'mail_password' => 'encrypted',
            'facebook_app_secret' => 'encrypted',
            'facebook_client_token' => 'encrypted',
            'whatsapp_app_secret' => 'encrypted',
            'messenger_app_secret' => 'encrypted',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultTheme(): array
    {
        return [
            'app_bg' => '#faf8f9',
            'shell_bg' => '#f3eef0',
            'sidebar_from' => '#14060a',
            'sidebar_via' => '#4a0c1c',
            'sidebar_to' => '#060203',
            'sidebar_border' => 'rgba(205, 0, 42, 0.38)',
            'sidebar_text' => '#ffffff',
            'sidebar_muted' => '#d4b8bc',
            'sidebar_nav_muted' => '#9a8588',
            'header_bg' => '#ffffff',
            'header_border' => '#f0e8ea',
            'header_text' => '#1a0a0e',
            'card_bg' => '#ffffff',
            'card_border' => '#efe5e8',
            'text_primary' => '#1a0a0e',
            'text_muted' => '#5c4f52',
            'primary' => '#cd002a',
            'primary_hover' => '#a80022',
            'primary_text' => '#ffffff',
            'accent' => '#ff6b8a',
            'code_bg' => '#f5eef0',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'brand_name' => config('app.name', 'Reply'),
            'logo_path' => null,
            'favicon_path' => null,
            'mail_mailer' => 'smtp',
            'mail_host' => null,
            'mail_port' => null,
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => null,
            'mail_from_address' => null,
            'mail_from_name' => null,
            'facebook_app_id' => null,
            'facebook_app_secret' => null,
            'facebook_client_token' => null,
            'whatsapp_verify_token' => null,
            'whatsapp_app_secret' => null,
            'whatsapp_embedded_config_id' => null,
            'messenger_verify_token' => null,
            'messenger_app_secret' => null,
            'theme' => self::defaultTheme(),
        ]);
    }

    /**
     * Apply saved mail settings to runtime config.
     */
    public function applyMailConfig(): void
    {
        $mailer = $this->mail_mailer ?: 'smtp';

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $this->mail_host ?: config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => $this->mail_port ?: config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username' => $this->mail_username ?: config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => $this->mail_password ?: config('mail.mailers.smtp.password'),
            'mail.mailers.smtp.encryption' => $this->mail_encryption ?: config('mail.mailers.smtp.encryption'),
            'mail.from.address' => $this->mail_from_address ?: config('mail.from.address'),
            'mail.from.name' => $this->mail_from_name ?: config('mail.from.name'),
        ]);
    }

    public function applyMetaConfig(): void
    {
        config([
            'services.facebook.app_id' => $this->facebook_app_id ?: config('services.facebook.app_id'),
            'services.facebook.app_secret' => $this->facebook_app_secret ?: config('services.facebook.app_secret'),
            'services.facebook.client_token' => $this->facebook_client_token ?: config('services.facebook.client_token'),
            'services.whatsapp.verify_token' => $this->whatsapp_verify_token ?: config('services.whatsapp.verify_token'),
            'services.whatsapp.app_secret' => $this->whatsapp_app_secret ?: config('services.whatsapp.app_secret'),
            'services.whatsapp.embedded_config_id' => $this->whatsapp_embedded_config_id ?: config('services.whatsapp.embedded_config_id'),
            'services.messenger.verify_token' => $this->messenger_verify_token ?: config('services.messenger.verify_token'),
            'services.messenger.app_secret' => $this->messenger_app_secret ?: config('services.messenger.app_secret'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function mergedTheme(): array
    {
        return array_merge(self::defaultTheme(), $this->theme ?? []);
    }

    /**
     * Public URL for a file on the public disk.
     *
     * Uses Storage URL generation so paths respect APP_URL (including subdirectory
     * installs). Root-relative "/storage/..." breaks when the app is not served from
     * the domain root. Ensure `php artisan storage:link` exists on the server.
     */
    public function publicFileUrl(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $path = Str::of($relativePath)->replace('\\', '/')->ltrim('/')->value();

        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function logoPublicUrl(): ?string
    {
        return $this->publicFileUrl($this->logo_path);
    }

    public function faviconPublicUrl(): ?string
    {
        return $this->publicFileUrl($this->favicon_path);
    }

    /**
     * Map theme array keys to CSS custom property names (without leading --).
     *
     * @return array<string, string>
     */
    public static function themeKeyToCssProperty(): array
    {
        return [
            'app_bg' => '--app-bg',
            'shell_bg' => '--app-shell-bg',
            'sidebar_from' => '--app-sidebar-from',
            'sidebar_via' => '--app-sidebar-via',
            'sidebar_to' => '--app-sidebar-to',
            'sidebar_border' => '--app-sidebar-border',
            'sidebar_text' => '--app-sidebar-text',
            'sidebar_muted' => '--app-sidebar-muted',
            'sidebar_nav_muted' => '--app-sidebar-nav-muted',
            'header_bg' => '--app-header-bg',
            'header_border' => '--app-header-border',
            'header_text' => '--app-header-text',
            'card_bg' => '--app-card-bg',
            'card_border' => '--app-card-border',
            'text_primary' => '--app-text',
            'text_muted' => '--app-text-muted',
            'primary' => '--app-primary',
            'primary_hover' => '--app-primary-hover',
            'primary_text' => '--app-primary-text',
            'accent' => '--app-accent',
            'code_bg' => '--app-code-bg',
        ];
    }

    public function themeStyleTag(): string
    {
        $t = $this->mergedTheme();

        return sprintf(
            ':root {
  --app-bg: %1$s;
  --app-shell-bg: %2$s;
  --app-sidebar-from: %3$s;
  --app-sidebar-via: %4$s;
  --app-sidebar-to: %5$s;
  --app-sidebar-border: %6$s;
  --app-sidebar-text: %7$s;
  --app-sidebar-muted: %8$s;
  --app-sidebar-nav-muted: %9$s;
  --app-header-bg: %10$s;
  --app-header-border: %11$s;
  --app-header-text: %12$s;
  --app-card-bg: %13$s;
  --app-card-border: %14$s;
  --app-text: %15$s;
  --app-text-muted: %16$s;
  --app-primary: %17$s;
  --app-primary-hover: %18$s;
  --app-primary-text: %19$s;
  --app-accent: %20$s;
  --app-code-bg: %21$s;
}',
            e($t['app_bg']),
            e($t['shell_bg']),
            e($t['sidebar_from']),
            e($t['sidebar_via']),
            e($t['sidebar_to']),
            e($t['sidebar_border']),
            e($t['sidebar_text']),
            e($t['sidebar_muted']),
            e($t['sidebar_nav_muted']),
            e($t['header_bg']),
            e($t['header_border']),
            e($t['header_text']),
            e($t['card_bg']),
            e($t['card_border']),
            e($t['text_primary']),
            e($t['text_muted']),
            e($t['primary']),
            e($t['primary_hover']),
            e($t['primary_text']),
            e($t['accent']),
            e($t['code_bg']),
        );
    }
}
