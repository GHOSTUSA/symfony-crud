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
        Schema::create('saga_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('saga_id')->unique();
            $table->enum('transaction_type', ['create_user', 'delete_user']);
            $table->enum('status', ['pending', 'user_created', 'account_creating', 'account_created', 'completed', 'compensating', 'compensated', 'failed']);
            $table->json('user_data')->nullable();
            $table->json('account_data')->nullable();
            $table->json('compensation_data')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('next_step')->nullable();
            $table->timestamps();
            
            $table->index(['saga_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saga_transactions');
    }
};
