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
        Schema::create('crypto_security_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action', 100);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->integer('risk_score')->default(0);
            $table->json('security_flags')->nullable();
            $table->json('geo_location')->nullable();
            $table->string('device_fingerprint', 255)->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('action');
            $table->index('risk_score');
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
        Schema::dropIfExists('crypto_security_logs');
    }
}; 