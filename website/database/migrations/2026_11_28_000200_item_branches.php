<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Some dishes are only made at one branch. Null means every branch has it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $t) {
            $t->json('branch_ids')->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $t) {
            $t->dropColumn('branch_ids');
        });
    }
};
