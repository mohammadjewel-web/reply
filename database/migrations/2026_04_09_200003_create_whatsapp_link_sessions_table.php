<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_link_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('status', 32)->default('pending');
            $table->text('access_token')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('waba_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_link_sessions');
    }
};
