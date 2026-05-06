<?php

if (! function_exists('app_date')) {
    /**
     * Format a date using the application's saved date_format setting.
     * Falls back to 'M d, Y' if not configured.
     */
    function app_date(\Carbon\Carbon|\Carbon\CarbonInterface|string|null $date, bool $includeTime = false): string
    {
        if (! $date) {
            return '—';
        }
        $carbon = $date instanceof \Carbon\CarbonInterface
            ? $date
            : \Carbon\Carbon::parse($date);

        $format = config('app.date_format', 'M d, Y');
        return $includeTime ? $carbon->format($format . ' H:i') : $carbon->format($format);
    }
}

if (! function_exists('app_datetime')) {
    function app_datetime(\Carbon\Carbon|\Carbon\CarbonInterface|string|null $date): string
    {
        return app_date($date, true);
    }
}
