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
