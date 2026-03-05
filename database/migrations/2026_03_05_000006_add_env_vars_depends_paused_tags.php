<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_tasks', function (Blueprint $table): void {
            $table->json('env_vars')->nullable()->after('working_directory');
            $table->foreignId('depends_on_task_id')
                ->nullable()
                ->after('env_vars')
                ->constrained('scheduled_tasks')
                ->nullOnDelete();
            $table->timestamp('paused_until')->nullable()->after('depends_on_task_id');
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7)->default('#6366f1'); // indigo hex
            $table->timestamps();
        });

        Schema::create('scheduled_task_tag', function (Blueprint $table): void {
            $table->foreignId('scheduled_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['scheduled_task_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_tag');
        Schema::dropIfExists('tags');

        Schema::table('scheduled_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('depends_on_task_id');
            $table->dropColumn(['env_vars', 'paused_until']);
        });
    }
};
