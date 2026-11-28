<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Customer accounts for the app: sign in with a UAE mobile number and a one-time code. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->unique(); // 9715XXXXXXXX
            $table->string('name')->nullable();
            $table->string('address', 300)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        // one row per code we sent; only used by the "log" driver (MSG91 keeps its own codes)
        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->index();
            $table->string('code_hash')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // a signed-in device; the app keeps the plain token, we keep only its sha256
        Schema::create('customer_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('device', 100)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
        Schema::dropIfExists('customer_tokens');
        Schema::dropIfExists('customer_otps');
        Schema::dropIfExists('customers');
    }
};
