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
        Schema::create('crypto_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_pair', 20)->unique();
            $table->decimal('rate', 20, 8);
            $table->string('source', 50);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
            
            $table->index('currency_pair');
            $table->index('last_updated');
        });
        
        // Insert default rates
        DB::table('crypto_exchange_rates')->insert([
            [
                'currency_pair' => 'SOL/USD',
                'rate' => '100.00',
                'source' => 'default',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'currency_pair' => 'USDT/USD',
                'rate' => '1.00',
                'source' => 'default',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_exchange_rates');
    }
}; 