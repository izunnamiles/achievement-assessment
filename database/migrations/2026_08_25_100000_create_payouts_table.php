<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->string('status');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // A given reward reason can only ever be pending/paid once per
            // user - this is what makes a retried/redelivered payout job
            // find the existing record instead of creating (and paying) a
            // second one.
            $table->unique(['user_id', 'reason']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
