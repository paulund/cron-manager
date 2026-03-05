<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Cron\CronExpression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CronPreviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expression = $request->string('expression')->toString();

        try {
            $cron = new CronExpression($expression);
        } catch (\InvalidArgumentException) {
            return response()->json(['error' => 'Invalid cron expression'], 422);
        }

        $runs = [];
        $next = new \DateTime;

        for ($i = 0; $i < 5; $i++) {
            $next = $cron->getNextRunDate($next, 0, true);
            $runs[] = $next->format('D d M Y H:i');
        }

        return response()->json(['runs' => $runs]);
    }
}
