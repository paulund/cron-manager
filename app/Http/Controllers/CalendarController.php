<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ScheduledTask;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class CalendarController
{
    public function __invoke(Request $request): View
    {
        $monthParam = $request->query('month');

        try {
            $month = $monthParam
                ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Exception) {
            $month = Carbon::now()->startOfMonth();
        }

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $upcoming = $this->projectUpcomingRuns($startOfMonth, $endOfMonth);

        return view('calendar.index', [
            'month' => $month,
            'weeks' => $this->buildGrid($month),
            'upcoming' => $upcoming,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * @return array<string, array<int, array{task: ScheduledTask, time: Carbon}>>
     */
    private function projectUpcomingRuns(Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $upcoming = [];

        if ($endOfMonth->isPast()) {
            return $upcoming;
        }

        $projectionStart = Carbon::now()->isAfter($startOfMonth)
            ? Carbon::now()
            : $startOfMonth;

        ScheduledTask::enabled()->get()->each(function (ScheduledTask $task) use ($projectionStart, $endOfMonth, &$upcoming): void {
            try {
                $expr = new CronExpression($task->cron_expression);

                for ($nth = 0; $nth < 30; $nth++) {
                    $runDate = Carbon::instance($expr->getNextRunDate($projectionStart->toDateTimeString(), $nth));

                    if ($runDate->isAfter($endOfMonth)) {
                        break;
                    }

                    $dateKey = $runDate->format('Y-m-d');
                    $upcoming[$dateKey][] = ['task' => $task, 'time' => $runDate];
                }
            } catch (\Exception) {
                // Invalid cron expression — skip
            }
        });

        foreach ($upcoming as &$dayRuns) {
            usort($dayRuns, fn ($a, $b) => $a['time']->timestamp <=> $b['time']->timestamp);
        }

        return $upcoming;
    }

    /**
     * @return array<int, array<int, Carbon>>
     */
    private function buildGrid(Carbon $month): array
    {
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $week = [];
        $current = $gridStart->copy();

        while ($current->lessThanOrEqualTo($gridEnd)) {
            $week[] = $current->copy();

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $current->addDay();
        }

        return $weeks;
    }
}
