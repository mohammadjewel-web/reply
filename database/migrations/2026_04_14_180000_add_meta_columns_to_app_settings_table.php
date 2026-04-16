<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('facebook_app_id')->nullable()->after('mail_from_name');
            $table->text('facebook_app_secret')->nullable()->after('facebook_app_id');
            $table->text('facebook_client_token')->nullable()->after('facebook_app_secret');
            $table->string('whatsapp_verify_token')->nullable()->after('facebook_client_token');
            $table->text('whatsapp_app_secret')->nullable()->after('whatsapp_verify_token');
            $table->string('whatsapp_embedded_config_id')->nullable()->after('whatsapp_app_secret');
            $table->string('messenger_verify_token')->nullable()->after('whatsapp_embedded_config_id');
            $table->text('messenger_app_secret')->nullable()->after('messenger_verify_token');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_app_id',
                'facebook_app_secret',
                'facebook_client_token',
                'whatsapp_verify_token',
                'whatsapp_app_secret',
                'whatsapp_embedded_config_id',
                'messenger_verify_token',
                'messenger_app_secret',
            ]);
        });
    }
};
