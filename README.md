# Stua

Vanuatu shop POS **web dashboard** and sales API. Product name is **Stua** (not VatuTill).

This repo is WEB only. Android (Dev03) consumes the JSON API. Design owns cheap-phone shop-floor screens. The dashboard is a **desktop day table**, not a phone clone.

Not part of Lending-Scheme / Smart Lend Pacific. EFD / VSMS accreditation is out of scope for v1.

## Money (locked)

- Currency: **integer VUV**, no decimals.
- Shop prices are **VAT-inclusive**.
- `total = sum(line.qty * line.unit_price_vuv)` (inclusive)
- `vat = round(total * 15 / 115)` — 15/115 of the inclusive total, **not** `subtotal * 15 / 100`
- `subtotal = total - vat` (ex VAT)
- Store and return **subtotal**, **vat**, **total**, plus **receipt_no**, **created_at**
- Timezone: **Pacific/Efate**
- Tender: `cash` | `card` | `mvatu` | `mycash`

Day `vat_summary` uses the same extract-from-gross rule: `vat = round(gross * 15 / 115)`, `subtotal = gross - vat`.

Layout mocks may show round numbers (e.g. Gross 186300 / VAT 24300 / Net 162000). Live math does not copy those figures.

## Receipt

Primary receipt is **SMS-readable plain text** on the sale as `receipt_text` (paste into an SMS). Integers only. Item lines, then TOTAL, then a VAT note — not exclusive add-on tax.

```
STUA #142
2000
800
650
TOTAL 3450 VUV
VAT incl. 450
Kas 4000
Senis 550
30 Aug 10:42
```

`tendered_vuv` is optional on create. If omitted, tendered = total and Senis = 0.

PDF is secondary (`GET .../receipt.pdf`). Web UI labels it **PDF later**.

SMS send is a **stub**: `POST .../sms-stub` returns `{to, body}` and **does not send**.

## Demo shop

| | |
| --- | --- |
| Email | `shop@stua.vu` |
| Password | `stua-demo` |
| Shop | Port Vila Demo, Port Vila |

## Run (SQLite)

Needs PHP 8.3+ with sqlite, mbstring, xml, curl, zip, tokenizer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Open `http://localhost:8000` and log in. Dashboard home is **End of day / En blong dei**.

```bash
php artisan test
```

No npm build is required for v1 (CSS is in `public/css/stua.css`).

## Android API contract

Base URL: `/api/v1`  
Auth: Sanctum bearer token from login.  
JSON only. All money fields are integers (VUV).

### POST `/api/v1/login`

```json
{ "email": "shop@stua.vu", "password": "stua-demo" }
```

```json
{
  "token": "1|...",
  "shop": { "id": 1, "name": "Port Vila Demo", "location": "Port Vila" }
}
```

### POST `/api/v1/sales`

Header: `Authorization: Bearer {token}`

Line is an open amount: `qty` + `unit_price_vuv`. `name` optional. No catalog in v1.

```json
{
  "lines": [
    { "name": "Rice", "qty": 1, "unit_price_vuv": 2000 },
    { "qty": 1, "unit_price_vuv": 800 },
    { "qty": 1, "unit_price_vuv": 650 }
  ],
  "tender": "cash",
  "tendered_vuv": 4000
}
```

`tender` is required: `cash` | `card` | `mvatu` | `mycash`.  
`tendered_vuv` is optional.

Response `201` includes stored totals and SMS body:

```json
{
  "id": 1,
  "receipt_no": "INV-0001",
  "number": 1,
  "tender": "cash",
  "tendered_vuv": 4000,
  "change_vuv": 550,
  "subtotal": 3000,
  "vat": 450,
  "total": 3450,
  "receipt_text": "STUA #1\n2000\n800\n650\nTOTAL 3450 VUV\nVAT incl. 450\nKas 4000\nSenis 550\n30 Aug 10:42",
  "created_at": "2026-08-30T10:42:00+11:00",
  "lines": [
    { "name": "Rice", "qty": 1, "unit_price_vuv": 2000, "line_total_vuv": 2000 }
  ]
}
```

### GET `/api/v1/sales?date=YYYY-MM-DD`

Header: `Authorization: Bearer {token}`  
`date` defaults to **today** in `Pacific/Efate`.

```json
{
  "date": "2026-08-30",
  "sales": [],
  "vat_summary": {
    "count": 0,
    "subtotal": 0,
    "vat": 0,
    "total": 0
  }
}
```

### GET `/api/v1/sales/{id}/receipt.pdf`

Header: `Authorization: Bearer {token}`  
Secondary PDF of `receipt_text`.

### POST `/api/v1/sales/{id}/sms-stub`

Header: `Authorization: Bearer {token}`

```json
{ "phone": "+678123456" }
```

```json
{ "to": "+678123456", "body": "STUA #1\n..." }
```

Does **not** send a message.

## Web dashboard

Desktop **End of day / En blong dei** (not a stacked phone UI):

- Today / Tude + date
- Summary / Sumari — Sales/Sels, Gross VAT Inclusive, VAT 15%, Net VAT Exclusive
- Tender summary / Samari blong pemen — Cash/Kas, Card/Kad, M-Vatu, MyCash, TOTAL
- Today’s log / Log blong tude
- Close day / Klosem dei (v1 does not lock the till)

Record sale is a wide open-amount table (Add item / Adem, Pay / Pei). Design screens swap in later: see `resources/views/design/README.md`.

Tokens: Paper `#F6F0E6`, Ink `#111111`, Pay `#0F8A45`, Void `#C62828`.
