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
        // Social payments table
        Schema::create('farcaster_social_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->string('from_fid');
            $table->string('to_fid');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['tip', 'gift_bet', 'split_win', 'referral_bonus']);
            $table->text('message')->nullable();
            $table->string('related_bet_id')->nullable();
            $table->string('transaction_hash')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();
            
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['from_fid', 'to_fid']);
            $table->index('created_at');
        });

        // Viral shares tracking
        Schema::create('farcaster_viral_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('fid');
            $table->enum('share_type', ['big_win', 'tournament_invite', 'referral', 'leaderboard', 'custom']);
            $table->json('metadata')->nullable(); // Store multiplier, win amount, etc.
            $table->string('frame_id')->nullable();
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('viral_score', 8, 2)->default(0.00);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['fid', 'share_type']);
            $table->index('created_at');
        });

        // Tournament system
        Schema::create('farcaster_tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('tournament_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['biggest_multiplier', 'most_wins', 'highest_profit', 'longest_streak']);
            $table->decimal('prize_pool', 12, 2);
            $table->decimal('entry_fee', 8, 2)->default(0.00);
            $table->integer('max_participants');
            $table->integer('current_participants')->default(0);
            $table->json('prize_distribution'); // [50, 30, 20] percentages
            $table->enum('status', ['upcoming', 'active', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->json('rules')->nullable();
            $table->string('created_by_fid')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'start_time']);
            $table->index('tournament_id');
        });

        // Tournament participants
        Schema::create('farcaster_tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id');
            $table->unsignedBigInteger('user_id');
            $table->string('fid');
            $table->decimal('score', 10, 2)->default(0.00);
            $table->integer('position')->nullable();
            $table->decimal('prize_won', 10, 2)->default(0.00);
            $table->boolean('paid_entry_fee')->default(false);
            $table->timestamp('joined_at');
            $table->timestamp('last_activity')->nullable();
            $table->json('performance_data')->nullable();
            $table->timestamps();
            
            $table->foreign('tournament_id')->references('id')->on('farcaster_tournaments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['tournament_id', 'user_id']);
            $table->index(['tournament_id', 'score']);
        });

        // Referral system
        Schema::create('farcaster_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referred_id');
            $table->string('referrer_fid');
            $table->string('referred_fid');
            $table->decimal('bonus_amount', 8, 2)->default(0.00);
            $table->boolean('bonus_paid')->default(false);
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->timestamp('referred_at');
            $table->timestamp('activated_at')->nullable();
            $table->decimal('referred_user_volume', 12, 2)->default(0.00);
            $table->integer('referred_user_games')->default(0);
            $table->timestamps();
            
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['referrer_fid', 'status']);
            $table->index('referred_at');
        });

        // Achievements system
        Schema::create('farcaster_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('achievement_key')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('icon')->nullable();
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary']);
            $table->json('conditions'); // Requirements to unlock
            $table->decimal('reward_amount', 8, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User achievements
        Schema::create('farcaster_user_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('fid');
            $table->unsignedBigInteger('achievement_id');
            $table->timestamp('unlocked_at');
            $table->boolean('reward_claimed')->default(false);
            $table->timestamp('reward_claimed_at')->nullable();
            $table->json('unlock_data')->nullable(); // Context about how it was unlocked
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('achievement_id')->references('id')->on('farcaster_achievements')->onDelete('cascade');
            $table->unique(['user_id', 'achievement_id']);
            $table->index(['fid', 'unlocked_at']);
        });

        // Frame analytics
        Schema::create('farcaster_frame_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('fid')->nullable();
            $table->string('frame_type'); // game, betting, results, social, etc.
            $table->string('action'); // view, click, bet, share, etc.
            $table->json('properties')->nullable();
            $table->string('session_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['fid', 'frame_type', 'action']);
            $table->index('created_at');
        });

        // Copy trading system
        Schema::create('farcaster_copy_trading', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('follower_id');
            $table->unsignedBigInteger('trader_id');
            $table->string('follower_fid');
            $table->string('trader_fid');
            $table->decimal('copy_percentage', 5, 2)->default(100.00); // What % of trader's bet
            $table->decimal('max_bet_amount', 8, 2)->default(100.00);
            $table->boolean('is_active')->default(true);
            $table->integer('copied_bets_count')->default(0);
            $table->decimal('total_copied_volume', 12, 2)->default(0.00);
            $table->decimal('total_profit', 10, 2)->default(0.00);
            $table->timestamps();
            
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('trader_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['follower_id', 'trader_id']);
            $table->index(['trader_fid', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('farcaster_copy_trading');
        Schema::dropIfExists('farcaster_frame_analytics');
        Schema::dropIfExists('farcaster_user_achievements');
        Schema::dropIfExists('farcaster_achievements');
        Schema::dropIfExists('farcaster_referrals');
        Schema::dropIfExists('farcaster_tournament_participants');
        Schema::dropIfExists('farcaster_tournaments');
        Schema::dropIfExists('farcaster_viral_shares');
        Schema::dropIfExists('farcaster_social_payments');
    }
};

