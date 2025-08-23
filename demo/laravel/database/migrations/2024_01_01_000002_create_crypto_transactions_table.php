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
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('transaction_hash', 88)->unique();
            $table->string('wallet_address', 44);
            $table->enum('transaction_type', ['deposit', 'withdrawal']);
            $table->enum('currency', ['SOL', 'USDT']);
            $table->decimal('amount', 20, 9);
            $table->decimal('amount_usd', 15, 2);
            $table->decimal('network_fee', 20, 9)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'failed', 'cancelled'])->default('pending');
            $table->integer('confirmations')->default(0);
            $table->integer('required_confirmations')->default(32);
            $table->unsignedBigInteger('block_height')->nullable();
            $table->string('from_address', 44)->nullable();
            $table->string('to_address', 44)->nullable();
            $table->text('memo')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('transaction_hash');
            $table->index('wallet_address');
            $table->index('status');
            $table->index('transaction_type');
            $table->index('currency');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_transactions');
    }
}; 