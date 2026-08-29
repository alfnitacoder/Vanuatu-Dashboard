<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleRecorder;
use App\Support\ReceiptPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function create(): View
    {
        return view('sales.create');
    }

    public function store(StoreSaleRequest $request, SaleRecorder $recorder): RedirectResponse
    {
        $user = $request->user();
        $sale = $recorder->record(
            $user->shop,
            $user,
            $request->lines(),
            $request->validated('tender'),
            $request->validated('tendered_vuv'),
        );

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', 'Sale recorded / Sel i rekodem.');
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeSale($request, $sale);
        $sale->load('lines');

        return view('sales.show', ['sale' => $sale]);
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

    public function smsStub(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorizeSale($request, $sale);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        // Stub only: do not send SMS.
        return back()->with('sms_stub', [
            'to' => $data['phone'],
            'body' => $sale->receipt_text,
        ]);
    }

    private function authorizeSale(Request $request, Sale $sale): void
    {
        abort_unless($sale->shop_id === $request->user()->shop_id, 404);
    }
}
