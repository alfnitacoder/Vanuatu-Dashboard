@extends('layouts.app')

@section('title', $sale->receipt_no.' — Stua')

@section('content')
    <section class="eod">
        <header class="eod-head">
            <div>
                <p class="kicker">Receipt / Risit</p>
                <h1>{{ $sale->receipt_no }}</h1>
            </div>
            <a class="btn-outline" href="{{ route('dashboard') }}">Done / Finis</a>
        </header>

        <section class="panel split-receipt">
            <div>
                <h2>SMS receipt</h2>
                <pre class="sms-bubble">{{ $sale->receipt_text }}</pre>
            </div>
            <div>
                <h2>Sale</h2>
                <table class="wide">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th class="num">Unit</th>
                            <th class="num">Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->lines as $line)
                            <tr>
                                <td>{{ $line->name ?: '—' }}</td>
                                <td>{{ $line->qty }}</td>
                                <td class="num">{{ number_format($line->unit_price_vuv, 0) }}</td>
                                <td class="num">{{ number_format($line->line_total_vuv, 0) }} VUV</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <dl class="totals">
                    <div><dt>Gross VAT Inclusive</dt><dd>{{ number_format($sale->total, 0) }} VUV</dd></div>
                    <div><dt>VAT 15%</dt><dd>{{ number_format($sale->vat, 0) }} VUV</dd></div>
                    <div><dt>Net VAT Exclusive</dt><dd>{{ number_format($sale->subtotal, 0) }} VUV</dd></div>
                    <div><dt>{{ \App\Support\Tender::bilingual($sale->tender) }}</dt><dd>{{ number_format($sale->tendered_vuv, 0) }} VUV</dd></div>
                    <div><dt>Senis</dt><dd>{{ number_format($sale->change_vuv, 0) }} VUV</dd></div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <h2>SMS stub / no send</h2>
            <form method="post" action="{{ route('sales.sms-stub', $sale) }}" class="stack">
                @csrf
                <label>
                    Phone
                    <input type="text" name="phone" placeholder="+678" required>
                </label>
                <button class="btn-pay" type="submit">Preview SMS</button>
            </form>
            @if (session('sms_stub'))
                <pre class="sms-bubble">to: {{ session('sms_stub')['to'] }}

{{ session('sms_stub')['body'] }}</pre>
            @endif
            <p class="pdf-later"><a href="{{ route('sales.pdf', $sale) }}">PDF later</a></p>
        </section>
    </section>
@endsection
