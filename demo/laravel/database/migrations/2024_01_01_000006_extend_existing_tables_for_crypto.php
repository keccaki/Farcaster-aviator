<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added missing import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Extend wallets table for crypto compatibility
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('sol_balance', 20, 9)->default(0)->after('amount');
            $table->decimal('usdt_balance', 20, 9)->default(0)->after('sol_balance');
            $table->decimal('total_usd_value', 15, 2)->default(0)->after('usdt_balance');
            $table->timestamp('last_crypto_update')->nullable()->after('total_usd_value');
        });
        
        // Extend transactions table for crypto compatibility
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('crypto_transaction_id')->nullable()->after('transactionno');
            $table->enum('currency_type', ['fiat', 'crypto'])->default('fiat')->after('amount');
            $table->enum('crypto_currency', ['SOL', 'USDT'])->nullable()->after('currency_type');
            $table->decimal('exchange_rate', 20, 8)->nullable()->after('crypto_currency');
            $table->decimal('network_fee', 20, 9)->default(0)->after('exchange_rate');
            
            $table->index('crypto_transaction_id');
            $table->index('currency_type');
            $table->index('crypto_currency');
        });
        
        // Add crypto settings to existing settings table
        DB::table('settings')->insert([
            [
                'category' => 'crypto_enabled',
                'value' => 'false',
                'status' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'min_deposit_sol',
                'value' => '0.01',
                'status' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'min_deposit_usdt',
                'value' => '5.00',
                'status' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'auto_approval_limit_usd',
                'value' => '100.00',
                'status' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'withdrawal_fee_sol',
                'value' => '0.001',
                'status' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'withdrawal_fee_usdt',
                'value' => '1.00',
                'status' => '1',
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
        // Remove crypto columns from wallets table
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['sol_balance', 'usdt_balance', 'total_usd_value', 'last_crypto_update']);
        });
        
        // Remove crypto columns from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['crypto_transaction_id']);
            $table->dropIndex(['currency_type']);
            $table->dropIndex(['crypto_currency']);
            $table->dropColumn(['crypto_transaction_id', 'currency_type', 'crypto_currency', 'exchange_rate', 'network_fee']);
        });
        
        // Remove crypto settings
        DB::table('settings')->whereIn('category', [
            'crypto_enabled',
            'min_deposit_sol',
            'min_deposit_usdt',
            'auto_approval_limit_usd',
            'withdrawal_fee_sol',
            'withdrawal_fee_usdt'
        ])->delete();
    }
}; 