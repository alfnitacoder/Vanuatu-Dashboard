<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleRecorder;
use App\Support\ReceiptPdf;
use App\Support\Vat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SaleController extends Controller
{
    public function store(StoreSaleRequest $request, SaleRecorder $recorder): JsonResponse
    {
        $user = $request->user();
        $sale = $recorder->record(
            $user->shop,
            $user,
            $request->lines(),
            $request->validated('tender'),
            $request->validated('tendered_vuv'),
        );

        return response()->json($sale->toApiArray(), SymfonyResponse::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
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

        return response()->json([
            'date' => $date->toDateString(),
            'sales' => $sales->map->toApiArray()->values()->all(),
            'vat_summary' => [
                'count' => $sales->count(),
                'subtotal' => $gross - $vat,
                'vat' => $vat,
                'total' => $gross,
            ],
        ]);
    }

    public function receiptPdf(Request $request, Sale $sale): Response
    {
        $this->authorizeSale($request, $sale);

        $pdf = ReceiptPdf::render($sale->receipt_text);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$sale->receipt_no.'.pdf"',
        ]);
    }

    public function smsStub(Request $request, Sale $sale): JsonResponse
    {
        $this->authorizeSale($request, $sale);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        // Stub only: do not send SMS, do not call a gateway.
        return response()->json([
            'to' => $data['phone'],
            'body' => $sale->receipt_text,
        ]);
    }

    private function authorizeSale(Request $request, Sale $sale): void
    {
        abort_unless($sale->shop_id === $request->user()->shop_id, 404);
    }

    private function resolveDate(mixed $date): Carbon
    {
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::createFromFormat('Y-m-d', $date, 'Pacific/Efate')->startOfDay();
        }

        return now('Pacific/Efate')->startOfDay();
    }
}
