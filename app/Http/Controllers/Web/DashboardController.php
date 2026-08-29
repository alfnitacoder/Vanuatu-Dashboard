<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Support\Tender;
use App\Support\Vat;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $date = $this->resolveDate($request->query('date'));
        $shop = $request->user()->shop;

        $sales = Sale::query()
            ->where('shop_id', $shop->id)
            ->whereDate('created_at', $date->toDateString())
            ->with('lines')
            ->orderByDesc('created_at')
            ->get();

        $gross = (int) $sales->sum('total');
        $vat = Vat::extract($gross);
        $tenders = [];
        foreach (Tender::values() as $tender) {
            $tenders[$tender] = (int) $sales->where('tender', $tender)->sum('total');
        }

        return view('dashboard', [
            'shop' => $shop,
            'date' => $date,
            'sales' => $sales,
            'summary' => [
                'count' => $sales->count(),
                'gross' => $gross,
                'vat' => $vat,
                'net' => $gross - $vat,
            ],
            'tenders' => $tenders,
        ]);
    }

    public function closeDay(Request $request): RedirectResponse
    {
        $date = $this->resolveDate($request->input('date'));

        return redirect()
            ->route('dashboard', ['date' => $date->toDateString()])
            ->with('status', 'Dei i klosem. v1 does not lock the till — Design owns close-day later.');
    }

    private function resolveDate(mixed $date): Carbon
    {
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::createFromFormat('Y-m-d', $date, 'Pacific/Efate')->startOfDay();
        }

        return now('Pacific/Efate')->startOfDay();
    }
}
