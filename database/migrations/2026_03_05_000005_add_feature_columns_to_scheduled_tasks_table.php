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
            $table->boolean('prevent_overlap')->default(false)->after('is_enabled');
            $table->unsignedSmallInteger('runs_to_keep')->nullable()->after('prevent_overlap');
            $table->boolean('notify_on_failure')->default(false)->after('runs_to_keep');
            $table->string('notification_email')->nullable()->after('notify_on_failure');
            $table->string('notification_webhook')->nullable()->after('notification_email');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_tasks', function (Blueprint $table): void {
            $table->dropColumn([
                'prevent_overlap',
                'runs_to_keep',
                'notify_on_failure',
                'notification_email',
                'notification_webhook',
            ]);
        });
    }
};
