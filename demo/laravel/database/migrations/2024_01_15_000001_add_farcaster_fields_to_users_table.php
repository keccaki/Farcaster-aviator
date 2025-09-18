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
        Schema::table('users', function (Blueprint $table) {
            // Add Farcaster-specific fields
            $table->string('farcaster_id')->nullable()->unique()->after('id');
            $table->string('farcaster_username')->nullable()->after('farcaster_id');
            
            // Add social gaming fields
            $table->integer('referral_count')->default(0)->after('status');
            $table->decimal('total_winnings', 12, 2)->default(0.00)->after('referral_count');
            $table->decimal('best_multiplier', 8, 2)->default(0.00)->after('total_winnings');
            $table->integer('games_played')->default(0)->after('best_multiplier');
            
            // Add Farcaster profile data
            $table->text('farcaster_profile_data')->nullable()->after('games_played');
            $table->timestamp('last_farcaster_sync')->nullable()->after('farcaster_profile_data');
            
            // Add indexes for performance
            $table->index('farcaster_id');
            $table->index('farcaster_username');
            $table->index('referral_count');
            $table->index('total_winnings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['farcaster_id']);
            $table->dropIndex(['farcaster_username']);
            $table->dropIndex(['referral_count']);
            $table->dropIndex(['total_winnings']);
            
            $table->dropColumn([
                'farcaster_id',
                'farcaster_username',
                'referral_count',
                'total_winnings',
                'best_multiplier',
                'games_played',
                'farcaster_profile_data',
                'last_farcaster_sync'
            ]);
        });
    }
};

