<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('scheduled_task_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('exit_code')->nullable();
            $table->longText('output')->nullable();
            $table->string('status', 20); // 'running' | 'success' | 'failed'
            $table->string('triggered_by', 20)->default('scheduler'); // 'scheduler' | 'manual'
            $table->timestamps();

            $table->index(['scheduled_task_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_runs');
    }
};
