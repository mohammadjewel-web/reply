<?php

use App\Http\Controllers\Admin\ChannelAccountController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\InboxMessageMediaController;
use App\Http\Controllers\Admin\MessengerConnectController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\WhatsappBaileysController;
use App\Http\Controllers\Admin\WhatsappConnectController;
use App\Http\Controllers\Admin\WhatsappCredentialController;
use App\Http\Controllers\Api\MessengerWebhookController;
use App\Http\Controllers\Api\WhatsappBaileysWebhookController;
use App\Http\Controllers\Api\WhatsappWebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WhatsappOAuthController;
use App\Http\Controllers\WhatsappPairStatusController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/login');

/**
 * Public disk files via Laravel (works without `public/storage` symlink). Laravel reserves
 * GET /storage/{path} for storage.local in some setups, so we use a dedicated path.
 */
Route::get('/disk/public/{path}', function (string $path) {
    $normalized = str_replace('\\', '/', $path);
    if ($normalized === '' || str_contains($normalized, '..')) {
        abort(404);
    }
    if (! Storage::disk('public')->exists($normalized)) {
        abort(404);
    }

    return Storage::disk('public')->response($normalized);
})->where('path', '.*')->name('storage.public_file');

Route::prefix('webhooks')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->group(function () {
        Route::get('/whatsapp', [WhatsappWebhookController::class, 'verify']);
        Route::post('/whatsapp', [WhatsappWebhookController::class, 'handle']);
        Route::post('/whatsapp-baileys', [WhatsappBaileysWebhookController::class, 'handle']);
        Route::get('/messenger', [MessengerWebhookController::class, 'verify']);
        Route::post('/messenger', [MessengerWebhookController::class, 'handle']);
    });

Route::get('/whatsapp/oauth/callback', [WhatsappOAuthController::class, 'callback'])
    ->name('whatsapp.oauth.callback');

Route::get('/whatsapp/pair/{token}', [WhatsappPairStatusController::class, 'show'])
    ->name('whatsapp.pair.status');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/notifications', [SettingsController::class, 'updateNotificationPreferences'])->name('settings.notifications');
    Route::patch('/settings/email', [SettingsController::class, 'updateEmailConfiguration'])->name('settings.email');
    Route::patch('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance');
    Route::post('/settings/appearance/reset-theme', [SettingsController::class, 'resetTheme'])->name('settings.appearance.reset');
    Route::delete('/settings/logo', [SettingsController::class, 'removeLogo'])->name('settings.logo.destroy');
    Route::delete('/settings/favicon', [SettingsController::class, 'removeFavicon'])->name('settings.favicon.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

Route::middleware(['auth', 'verified', 'active', 'perm:inbox.access'])->group(function () {
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::get('/inbox/poll', [InboxController::class, 'poll'])->name('inbox.poll');
    Route::get('/inbox/messages/{message}/media', [InboxMessageMediaController::class, 'show'])->name('inbox.message.media');
    Route::post('/inbox/{conversation}/reply', [InboxController::class, 'reply'])->name('inbox.reply');
    Route::patch('/inbox/{conversation}/assign', [InboxController::class, 'assign'])->name('inbox.assign');
});

Route::middleware(['auth', 'verified', 'active', 'perm:whatsapp.manage'])->group(function () {
    Route::get('/whatsapp/connect', [WhatsappConnectController::class, 'show'])->name('whatsapp.connect');
    Route::post('/whatsapp/credentials', [WhatsappCredentialController::class, 'update'])->name('whatsapp.credentials');
    Route::post('/whatsapp/baileys/start', [WhatsappBaileysController::class, 'start'])->name('whatsapp.baileys.start');
    Route::get('/whatsapp/baileys/status', [WhatsappBaileysController::class, 'status'])->name('whatsapp.baileys.status');
});

Route::middleware(['auth', 'verified', 'active', 'perm:messenger.manage'])->group(function () {
    Route::get('/messenger/connect', [MessengerConnectController::class, 'show'])->name('messenger.connect');
});

Route::middleware(['auth', 'verified', 'active', 'perm:connections.manage'])->group(function () {
    Route::get('/connections', [ChannelAccountController::class, 'index'])->name('connections.index');
    Route::get('/connections/search', [ChannelAccountController::class, 'search'])->name('connections.search');
    Route::get('/connections/whatsapp', function () {
        return redirect()->to(route('connections.index').'#connections-whatsapp');
    })->name('connections.whatsapp');
    Route::get('/connections/messenger', function () {
        return redirect()->to(route('connections.index').'#connections-messenger');
    })->name('connections.messenger');
    Route::post('/connections/whatsapp', [ChannelAccountController::class, 'storeWhatsapp'])->name('connections.store.whatsapp');
    Route::post('/connections/messenger', [ChannelAccountController::class, 'storeMessenger'])->name('connections.store.messenger');
    Route::patch('/connections/meta', [ChannelAccountController::class, 'updateMetaConfig'])->name('connections.meta');
    Route::patch('/connections/{channelAccount}', [ChannelAccountController::class, 'update'])->name('connections.update');
    Route::delete('/connections/{channelAccount}', [ChannelAccountController::class, 'destroy'])->name('connections.destroy');
    Route::patch('/connections/{channelAccount}/toggle', [ChannelAccountController::class, 'toggleActive'])->name('connections.toggle');
});

Route::middleware(['auth', 'verified', 'active', 'perm:employees.manage'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/search', [EmployeeController::class, 'search'])->name('employees.search');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::post('/employees/{user}/send-verification', [EmployeeController::class, 'sendVerification'])->name('employees.verification.send');
    Route::get('/employees/{user}/profile', [EmployeeController::class, 'profile'])->name('employees.profile');
    Route::get('/employees/{user}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::patch('/employees/{user}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{user}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
});

Route::middleware(['auth', 'verified', 'active', 'perm:roles.manage'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});

Route::middleware(['auth', 'verified', 'active', 'perm:permissions.manage'])->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::patch('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
