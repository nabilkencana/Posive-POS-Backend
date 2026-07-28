<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->enum('area', ['Indoor', 'Terrace', 'VIP'])->default('Indoor');
            $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
            $table->integer('seats')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
