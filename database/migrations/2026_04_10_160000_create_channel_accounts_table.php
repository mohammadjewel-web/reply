<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('external_id', 128)->nullable()->comment('WhatsApp phone_number_id or Messenger page id');
            $table->text('access_token')->nullable();
            $table->string('waba_id')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['type', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_accounts');
    }
};
