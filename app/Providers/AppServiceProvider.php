<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app()->setLocale('cs');

        if (app()->runningInConsole() || ! request()->boolean('perf_log')) {
            return;
        }

        $startedAt = microtime(true);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = [
                'time_ms' => round((float) $query->time, 2),
                'sql' => preg_replace('/\s+/', ' ', $query->sql),
            ];
        });

        app()->terminating(function () use ($startedAt, &$queries): void {
            $totalQueryTime = array_sum(array_column($queries, 'time_ms'));
            usort($queries, fn (array $a, array $b) => $b['time_ms'] <=> $a['time_ms']);

            Log::warning('perf_log request', [
                'method' => request()->method(),
                'path' => request()->path(),
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'query_count' => count($queries),
                'query_time_ms' => round($totalQueryTime, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'slow_queries' => array_slice($queries, 0, 12),
            ]);
        });
    }
}
