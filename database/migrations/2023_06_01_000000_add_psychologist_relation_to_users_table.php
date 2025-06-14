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
        // Create psychologists table if it doesn't exist
        if (!Schema::hasTable('psychologists')) {
            Schema::create('psychologists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('name');
                $table->string('nip')->unique();
                $table->string('specialization')->nullable();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('online_chat')->default(false);
                $table->unsignedBigInteger('user_input');
                $table->unsignedBigInteger('user_update')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psychologists');
    }
};