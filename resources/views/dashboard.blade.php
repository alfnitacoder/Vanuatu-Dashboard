@extends('layouts.app')

@section('title', 'End of day — Stua')

@section('content')
    {{-- Design slot: replace this Blade with Design EOD screen. Data: $date, $summary, $tenders, $sales --}}
    <section class="eod">
        <header class="eod-head">
            <div>
                <p class="kicker">STUA · {{ $shop->location }}</p>
                <h1>End of day / En blong dei</h1>
                <p class="today">Today / Tude</p>
            </div>
            <form method="get" action="{{ route('dashboard') }}" class="date-form">
                <label>
                    Date
                    <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()">
                </label>
            </form>
        </header>

        <section class="panel">
            <h2>Summary / Sumari</h2>
            <div class="kpi-row" role="list">
                <div role="listitem">
                    <span class="kpi-label">Sales / Sels</span>
                    <strong>{{ $summary['count'] }}</strong>
                </div>
                <div role="listitem">
                    <span class="kpi-label">Gross VAT Inclusive</span>
                    <strong>{{ number_format($summary['gross'], 0) }}</strong>
                    <span class="unit">VUV</span>
                </div>
                <div role="listitem">
                    <span class="kpi-label">VAT 15%</span>
                    <strong>{{ number_format($summary['vat'], 0) }}</strong>
                    <span class="unit">VUV</span>
                </div>
                <div role="listitem">
                    <span class="kpi-label">Net VAT Exclusive</span>
                    <strong>{{ number_format($summary['net'], 0) }}</strong>
                    <span class="unit">VUV</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Tender summary / Samari blong pemen</h2>
            <table class="wide">
                <thead>
                    <tr>
                        <th>Tender</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Cash / Kas</td>
                        <td class="num">{{ number_format($tenders['cash'], 0) }} VUV</td>
                    </tr>
                    <tr>
                        <td>Card / Kad</td>
                        <td class="num">{{ number_format($tenders['card'], 0) }} VUV</td>
                    </tr>
                    <tr>
                        <td>M-Vatu</td>
                        <td class="num">{{ number_format($tenders['mvatu'], 0) }} VUV</td>
                    </tr>
                    <tr>
                        <td>MyCash</td>
                        <td class="num">{{ number_format($tenders['mycash'], 0) }} VUV</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>TOTAL</th>
                        <th class="num">{{ number_format($summary['gross'], 0) }} VUV</th>
                    </tr>
                </tfoot>
            </table>
        </section>

        <section class="panel">
            <h2>Today’s log / Log blong tude</h2>
            <table class="wide log">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Sale</th>
                        <th>Tender</th>
                        <th>Items</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>{{ $sale->created_at->timezone('Pacific/Efate')->format('H:i') }}</td>
                            <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->receipt_no }}</a></td>
                            <td>{{ \App\Support\Tender::bilingual($sale->tender) }}</td>
                            <td>{{ $sale->lines->count() }}</td>
                            <td class="num">{{ number_format($sale->total, 0) }} VUV</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No sales this day / I no gat sel.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <form method="post" action="{{ route('day.close') }}" class="close-day">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <button class="btn-ink" type="submit">Close day / Klosem dei</button>
        </form>
    </section>
@endsection
