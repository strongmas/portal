<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only sales & collections report for the external MD dashboard.
 *
 * All monetary values are stored in kobo and returned as naira (float, 2dp)
 * so the consuming PHP app can format them directly. Protected by a shared
 * secret token; no personal data beyond buyer name on the instalment list.
 */
class MdReportController extends Controller
{
    public function index(Request $request)
    {
        // ── Auth: shared secret ────────────────────────────────────────────
        $expected = config('services.md_reports.token');
        $provided = $request->header('X-Report-Token') ?: $request->query('token');

        if (empty($expected) || !is_string($provided) || !hash_equals($expected, $provided)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $toNaira = fn ($kobo) => round(((float) $kobo) / 100, 2);
        $today   = Carbon::today()->toDateString();
        $in60    = Carbon::today()->addDays(60)->toDateString();
        $sixMo   = Carbon::today()->subMonths(5)->startOfMonth()->toDateString();

        // ── Ownership / buyer aggregates ───────────────────────────────────
        $own = DB::table('ownerships')->selectRaw(
            'COUNT(*) ownerships,
             COUNT(DISTINCT user_id) buyers,
             COALESCE(SUM(agreed_price_kobo),0) agreed_kobo,
             COALESCE(SUM(current_value_kobo),0) current_value_kobo,
             SUM(status = "completed") completed,
             SUM(status = "active") active'
        )->first();

        $collectedKobo          = (float) DB::table('payments')->sum('amount_kobo');
        $collectedThisMonthKobo = (float) DB::table('payments')
            ->whereYear('paid_on', Carbon::today()->year)
            ->whereMonth('paid_on', Carbon::today()->month)
            ->sum('amount_kobo');

        $agreedKobo    = (float) ($own->agreed_kobo ?? 0);
        $outstandingKobo = max(0, $agreedKobo - $collectedKobo);

        // ── Inventory ──────────────────────────────────────────────────────
        $totalUnits = (int) DB::table('property_units')->sum('total_units');
        $soldUnits  = (int) ($own->ownerships ?? 0); // one ownership == one unit sold

        // ── Per-development breakdown ──────────────────────────────────────
        $devRows = DB::table('ownerships as o')
            ->join('properties as p', 'p.id', '=', 'o.property_id')
            ->selectRaw('p.development_name,
                         COUNT(o.id) ownerships,
                         COUNT(DISTINCT o.user_id) buyers,
                         COALESCE(SUM(o.agreed_price_kobo),0) agreed_kobo')
            ->groupBy('p.development_name')
            ->orderByDesc('agreed_kobo')
            ->get();

        $collByDev = DB::table('payments as pay')
            ->join('ownerships as o', 'o.id', '=', 'pay.ownership_id')
            ->join('properties as p', 'p.id', '=', 'o.property_id')
            ->selectRaw('p.development_name, COALESCE(SUM(pay.amount_kobo),0) kobo')
            ->groupBy('p.development_name')
            ->pluck('kobo', 'p.development_name');

        $unitsByDev = DB::table('property_units as pu')
            ->join('properties as p', 'p.id', '=', 'pu.property_id')
            ->selectRaw('p.development_name, COALESCE(SUM(pu.total_units),0) total_units')
            ->groupBy('p.development_name')
            ->pluck('total_units', 'p.development_name');

        $byDevelopment = $devRows->map(function ($r) use ($toNaira, $collByDev, $unitsByDev) {
            $collected = (float) ($collByDev[$r->development_name] ?? 0);
            $agreed    = (float) $r->agreed_kobo;
            $total     = (int) ($unitsByDev[$r->development_name] ?? 0);
            return [
                'development_name' => $r->development_name,
                'buyers'           => (int) $r->buyers,
                'ownerships'       => (int) $r->ownerships,
                'total_units'      => $total,
                'sold_units'       => (int) $r->ownerships,
                'available_units'  => max(0, $total - (int) $r->ownerships),
                'agreed_value'     => $toNaira($agreed),
                'collected'        => $toNaira($collected),
                'outstanding'      => $toNaira(max(0, $agreed - $collected)),
            ];
        })->values();

        // ── Per-unit-type breakdown ────────────────────────────────────────
        $byUnitType = DB::table('ownerships as o')
            ->join('property_units as pu', 'pu.id', '=', 'o.property_unit_id')
            ->selectRaw('pu.bedrooms, pu.unit_type,
                         COUNT(*) units_sold,
                         COALESCE(SUM(o.agreed_price_kobo),0) agreed_kobo')
            ->groupBy('pu.bedrooms', 'pu.unit_type')
            ->get()
            ->map(function ($r) use ($toNaira) {
                $type  = ucfirst((string) $r->unit_type);
                $label = $r->bedrooms ? "{$r->bedrooms} Bedroom {$type}" : ($type ?: 'Unit');
                return [
                    'label'        => $label,
                    'units_sold'   => (int) $r->units_sold,
                    'agreed_value' => $toNaira($r->agreed_kobo),
                ];
            })
            ->sortByDesc('agreed_value')
            ->values();

        // ── Instalments ────────────────────────────────────────────────────
        $inst = DB::table('instalments')->selectRaw("
            COALESCE(SUM(CASE WHEN status='paid' THEN amount_kobo ELSE 0 END),0) paid_kobo,
            COALESCE(SUM(CASE WHEN status!='paid' THEN amount_kobo ELSE 0 END),0) unpaid_kobo,
            COALESCE(SUM(CASE WHEN status!='paid' AND due_date < '{$today}' THEN amount_kobo ELSE 0 END),0) overdue_kobo,
            SUM(CASE WHEN status!='paid' AND due_date < '{$today}' THEN 1 ELSE 0 END) overdue_count,
            COALESCE(SUM(CASE WHEN status!='paid' AND due_date BETWEEN '{$today}' AND '{$in60}' THEN amount_kobo ELSE 0 END),0) due60_kobo,
            SUM(CASE WHEN status!='paid' AND due_date BETWEEN '{$today}' AND '{$in60}' THEN 1 ELSE 0 END) due60_count
        ")->first();

        $upcoming = DB::table('instalments as i')
            ->join('ownerships as o', 'o.id', '=', 'i.ownership_id')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->join('properties as p', 'p.id', '=', 'o.property_id')
            ->where('i.status', '!=', 'paid')
            ->whereDate('i.due_date', '<=', $in60)
            ->orderBy('i.due_date')
            ->limit(50)
            ->get(['u.name as buyer', 'p.development_name', 'i.due_date', 'i.amount_kobo', 'i.status', 'i.instalment_number'])
            ->map(fn ($r) => [
                'buyer'             => $r->buyer,
                'development_name'  => $r->development_name,
                'due_date'          => $r->due_date,
                'amount'            => $toNaira($r->amount_kobo),
                'status'            => $r->due_date < $today ? 'overdue' : $r->status,
                'instalment_number' => (int) $r->instalment_number,
            ]);

        // ── Collection trend (last 6 months) ───────────────────────────────
        $trend = DB::table('payments')
            ->selectRaw("DATE_FORMAT(paid_on,'%Y-%m') ym, DATE_FORMAT(paid_on,'%b %Y') lbl, COALESCE(SUM(amount_kobo),0) kobo")
            ->whereNotNull('paid_on')
            ->where('paid_on', '>=', $sixMo)
            ->groupBy('ym', 'lbl')
            ->orderBy('ym')
            ->get()
            ->map(fn ($r) => ['label' => $r->lbl, 'amount' => $toNaira($r->kobo)]);

        return response()->json([
            'status'       => 'ok',
            'generated_at' => now()->toIso8601String(),
            'currency'     => 'NGN',
            'summary' => [
                'buyers'                    => (int) ($own->buyers ?? 0),
                'ownerships'                => (int) ($own->ownerships ?? 0),
                'ownerships_active'         => (int) ($own->active ?? 0),
                'ownerships_completed'      => (int) ($own->completed ?? 0),
                'agreed_value'              => $toNaira($agreedKobo),
                'current_value'             => $toNaira($own->current_value_kobo ?? 0),
                'collected'                 => $toNaira($collectedKobo),
                'collected_this_month'      => $toNaira($collectedThisMonthKobo),
                'outstanding'               => $toNaira($outstandingKobo),
                'collection_rate'           => $agreedKobo > 0 ? round($collectedKobo / $agreedKobo * 100, 1) : 0,
                'total_units'               => $totalUnits,
                'sold_units'                => $soldUnits,
                'available_units'           => max(0, $totalUnits - $soldUnits),
                'instalments_paid'          => $toNaira($inst->paid_kobo ?? 0),
                'instalments_unpaid'        => $toNaira($inst->unpaid_kobo ?? 0),
                'instalments_overdue'       => $toNaira($inst->overdue_kobo ?? 0),
                'instalments_overdue_count' => (int) ($inst->overdue_count ?? 0),
                'instalments_due_60'        => $toNaira($inst->due60_kobo ?? 0),
                'instalments_due_60_count'  => (int) ($inst->due60_count ?? 0),
            ],
            'by_development'       => $byDevelopment,
            'by_unit_type'         => $byUnitType,
            'collection_trend'     => $trend,
            'upcoming_instalments' => $upcoming,
        ]);
    }
}
