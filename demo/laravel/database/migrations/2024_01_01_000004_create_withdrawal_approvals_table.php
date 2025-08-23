<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawal_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crypto_transaction_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 20, 9);
            $table->enum('approval_tier', ['auto', 'manual', 'multi_sig']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('crypto_transaction_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('approval_tier');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdrawal_approvals');
    }
}; 