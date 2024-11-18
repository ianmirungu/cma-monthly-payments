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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->string('comment');
            $table->string('latest_month');
            $table->boolean('latest_month_paid_in_full');
            $table->integer('latest_month_amount_pending');
            $table->integer('latest_month_pretty');
            $table->integer('amount_owed');
            $table->string('collection_date');

            $table->unsignedInteger('member_id');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
