<?php

use App\Http\Controllers\Api\MdReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Read-only, token-protected reporting endpoint consumed by the external
| Infiniti/Strongforce MD dashboard (mdReports.php, hosted on Namecheap).
| Auth is a shared secret (config services.md_reports.token) sent via the
| `X-Report-Token` header or `?token=` query param. No session/cookies.
*/

Route::get('/md-reports', [MdReportController::class, 'index']);
