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
        Schema::create('crypto_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('wallet_address', 44)->unique();
            $table->text('private_key_encrypted');
            $table->string('derivation_path', 100);
            $table->enum('wallet_type', ['solana', 'usdt_spl'])->default('solana');
            $table->enum('status', ['active', 'suspended', 'compromised'])->default('active');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('wallet_address');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_wallets');
    }
}; 