@extends('layouts.app')

@section('title', 'Record sale — Stua')

@section('content')
    {{-- Desktop open-amount table. Not a clone of the phone sale/pay screens. --}}
    <section class="eod">
        <header class="eod-head">
            <div>
                <p class="kicker">Desktop till</p>
                <h1>Record sale / Rekodem sel</h1>
                <p class="lede">Open amount: qty + unit price (VAT inclusive). No catalog in v1.</p>
            </div>
        </header>

        <form method="post" action="{{ route('sales.store') }}" id="sale-form" class="panel">
            @csrf
            <input type="hidden" name="client_sale_id" id="client_sale_id" value="{{ old('client_sale_id', (string) \Illuminate\Support\Str::uuid()) }}">
            @if ($errors->any())
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li class="error">{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <table class="wide" id="lines">
                <thead>
                    <tr>
                        <th>Item / Nem <span class="opt">(optional)</span></th>
                        <th>Qty</th>
                        <th>Unit price VUV</th>
                        <th class="num">Line</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="line">
                        <td><input type="text" name="lines[0][name]" placeholder="Add item / Adem" maxlength="80"></td>
                        <td><input type="number" name="lines[0][qty]" min="1" step="1" value="1" required></td>
                        <td><input type="number" name="lines[0][unit_price_vuv]" min="0" step="1" required></td>
                        <td class="num line-total">0 VUV</td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn-outline" id="add-line">Add item / Adem</button>

            <fieldset class="tenders">
                <legend>Tender / Pemen</legend>
                <label><input type="radio" name="tender" value="cash" checked> Cash / Kas</label>
                <label><input type="radio" name="tender" value="card"> Card / Kad</label>
                <label><input type="radio" name="tender" value="mvatu"> M-Vatu</label>
                <label><input type="radio" name="tender" value="mycash"> MyCash</label>
            </fieldset>

            <label class="tendered">
                Tendered VUV <span class="opt">(required for Cash / Kas)</span>
                <input type="number" name="tendered_vuv" id="tendered" min="0" step="1">
            </label>

            <dl class="totals">
                <div><dt>Gross VAT Inclusive</dt><dd id="gross">0 VUV</dd></div>
                <div><dt>VAT 15%</dt><dd id="vat">0 VUV</dd></div>
                <div><dt>Net VAT Exclusive</dt><dd id="net">0 VUV</dd></div>
                <div><dt>Senis</dt><dd id="change">0 VUV</dd></div>
            </dl>

            <button class="btn-pay" type="submit">Pay / Pei</button>
        </form>
    </section>

    <script>
        const tbody = document.querySelector('#lines tbody');
        const fmt = (n) => new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(n) + ' VUV';
        const vatOf = (total) => Math.round(total * 15 / 115);

        function recalc() {
            let total = 0;
            tbody.querySelectorAll('tr.line').forEach((row) => {
                const qty = parseInt(row.querySelector('[name$="[qty]"]').value || '0', 10);
                const unit = parseInt(row.querySelector('[name$="[unit_price_vuv]"]').value || '0', 10);
                const line = (Number.isFinite(qty) ? qty : 0) * (Number.isFinite(unit) ? unit : 0);
                total += line;
                row.querySelector('.line-total').textContent = fmt(line);
            });
            const vat = vatOf(total);
            const tenderedInput = document.getElementById('tendered').value;
            const tendered = tenderedInput === '' ? total : parseInt(tenderedInput, 10);
            document.getElementById('gross').textContent = fmt(total);
            document.getElementById('vat').textContent = fmt(vat);
            document.getElementById('net').textContent = fmt(total - vat);
            document.getElementById('change').textContent = fmt(Number.isFinite(tendered) ? tendered - total : 0);
        }

        document.getElementById('add-line').addEventListener('click', () => {
            const i = tbody.querySelectorAll('tr.line').length;
            const tr = document.createElement('tr');
            tr.className = 'line';
            tr.innerHTML = `<td><input type="text" name="lines[${i}][name]" placeholder="Add item / Adem" maxlength="80"></td>
                <td><input type="number" name="lines[${i}][qty]" min="1" step="1" value="1" required></td>
                <td><input type="number" name="lines[${i}][unit_price_vuv]" min="0" step="1" required></td>
                <td class="num line-total">0 VUV</td>`;
            tbody.appendChild(tr);
        });

        function syncTenderedRequired() {
            const cash = document.querySelector('input[name="tender"]:checked')?.value === 'cash';
            document.getElementById('tendered').required = cash;
        }
        document.getElementById('sale-form').addEventListener('input', () => {
            syncTenderedRequired();
            recalc();
        });
        syncTenderedRequired();
        recalc();
    </script>
@endsection
