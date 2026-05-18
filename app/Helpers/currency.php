<?php

if (!function_exists('naira')) {
    function naira(int $kobo, bool $compact = false): string
    {
        $naira = $kobo / 100;
        if ($compact) {
            if ($naira >= 1_000_000_000) return '₦' . number_format($naira / 1_000_000_000, 1) . 'B';
            if ($naira >= 1_000_000) return '₦' . number_format($naira / 1_000_000, 1) . 'M';
            if ($naira >= 1_000) return '₦' . number_format($naira / 1_000, 1) . 'K';
        }
        return '₦' . number_format($naira, 0, '.', ',');
    }
}

if (!function_exists('kobo')) {
    function kobo(string|float $naira): int
    {
        $clean = preg_replace('/[^0-9.]/', '', str_replace(',', '', $naira));
        return (int) round((float) $clean * 100);
    }
}

if (!function_exists('due_in')) {
    function due_in(\Carbon\Carbon $date): string
    {
        $days = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay());
        if ($days <= 6) {
            return $days === 1 ? '1 day' : "{$days} days";
        }
        if ($days <= 28) {
            $weeks = max(1, (int) round($days / 7));
            return $weeks === 1 ? '1 week' : "{$weeks} weeks";
        }
        $months = max(1, (int) round($days / 30.44));
        return $months === 1 ? '1 month' : "{$months} months";
    }
}
