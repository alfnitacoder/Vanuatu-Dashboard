# Stua

Vanuatu shop POS **web dashboard** and sales API.

This repo is WEB only. Android (Dev03) consumes the JSON API. Design owns cheap-phone shop-floor screens. The dashboard is a **desktop day table**, not a phone clone.

Not part of Lending-Scheme / Smart Lend Pacific. EFD / VSMS accreditation is out of scope for v1.

## Money (locked)

- Currency: **integer VUV**, no decimals.
- Shop prices are **VAT-inclusive**.
- `total = sum(qty * unit_price_vuv)` (inclusive)
- `vat_vuv = round(total * 15 / 115)` — 15/115 of the inclusive total, **not** `subtotal * 15 / 100`
- `subtotal` / `net` = `total - vat`
- Store and return **subtotal**, **vat** / **vat_vuv**, **total**, plus **receipt_no**, **created_at**
- Timezone: **Pacific/Efate**
- Tender: `cash` | `card` | `mvatu` | `mycash`
- Cash change: `change_vuv = tendered_vuv - total`

Day totals use extract-from-gross: `vat = round(gross * 15 / 115)`, `net = gross - vat`.

Layout mocks may show round numbers (e.g. Gross 186300 / VAT 24300 / Net 162000). Live math does not copy those figures.

## Receipt

Primary receipt is **SMS-readable plain text** on the sale as `receipt_text` (speech-bubble / paste into an SMS). Integers only. Item lines, then TOTAL, then a VAT note — VAT is **not** a fourth item and does **not** add into total.

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

Demo check: `2400 + 150 + 680 => total 3230`, `vat_vuv 421` (not 3715 / 485).

PDF is extra (`GET .../receipt.pdf`). Web UI labels it **PDF later**.

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
JSON only. All money fields are integers (VUV).

### Auth

- **Web dashboard:** email / password session. Not Google.
- **Android:** shop/device **Sanctum bearer token**. Not Google OAuth.
- `POST /api/v1/login` issues a `shop_device` token. Use it on API calls. It is separate from the dashboard browser session.

```http
Authorization: Bearer {token}
```

### POST `/api/v1/login`

Issues a shop/device token. No Google.

```json
{ "email": "shop@stua.vu", "password": "stua-demo", "device": "till-1" }
```

`device` is optional (token label only).

```json
{
  "token": "1|...",
  "token_type": "shop_device",
  "shop": { "id": 1, "name": "Port Vila Demo", "location": "Port Vila" }
}
```

### POST `/api/v1/sales`

Header: `Authorization: Bearer {token}` (shop/device token)

Open amount. No product catalog.

| Field | Rule |
| --- | --- |
| `lines` | required, min 1 |
| `lines[].name` | optional |
| `lines[].qty` | required integer |
| `lines[].unit_price_vuv` | required integer, VAT-inclusive whole vatu |
| `tender` | `cash` \| `card` \| `mvatu` \| `mycash` |
| `tendered_vuv` | **required when `tender` is `cash`** (for change) |
| `client_sale_id` | **required** — Dev03 offline sync idempotency key. Same shop + `client_sale_id` does **not** create a duplicate sale; the existing sale is returned. |

Server computes:

- `total = sum(qty * unit_price_vuv)`
- `vat_vuv = round(total * 15 / 115)`
- `subtotal` / `net` = `total - vat`
- `change_vuv = tendered_vuv - total` when cash (otherwise 0)

```json
{
  "client_sale_id": "dev03-sale-142",
  "lines": [
    { "name": "Rice", "qty": 1, "unit_price_vuv": 2000 },
    { "qty": 1, "unit_price_vuv": 800 },
    { "qty": 1, "unit_price_vuv": 650 }
  ],
  "tender": "cash",
  "tendered_vuv": 4000
}
```

First write `201`. Replay of the same `client_sale_id` `200` with the original sale.

```json
{
  "id": 1,
  "client_sale_id": "dev03-sale-142",
  "receipt_no": "INV-0001",
  "number": 1,
  "tender": "cash",
  "tendered_vuv": 4000,
  "change_vuv": 550,
  "subtotal": 3000,
  "net": 3000,
  "vat": 450,
  "vat_vuv": 450,
  "total": 3450,
  "receipt_text": "STUA #1\n2000\n800\n650\nTOTAL 3450 VUV\nVAT incl. 450\nKas 4000\nSenis 550\n30 Aug 10:42",
  "created_at": "2026-08-30T10:42:00+11:00",
  "lines": [
    { "name": "Rice", "qty": 1, "unit_price_vuv": 2000, "line_total_vuv": 2000 }
  ]
}
```

`receipt_text` is the primary receipt (SMS speech-bubble body). PDF is extra.

### GET `/api/v1/sales?date=YYYY-MM-DD`

Header: `Authorization: Bearer {token}`  
`date` defaults to **today** in `Pacific/Efate`.

Returns the sales list plus day totals: **gross** (inclusive), **vat** (extracted), **net**, and **tender split**.

```json
{
  "date": "2026-08-30",
  "sales": [],
  "vat_summary": {
    "count": 0,
    "subtotal": 0,
    "vat": 0,
    "total": 0
  },
  "day": {
    "gross": 0,
    "vat": 0,
    "net": 0,
    "tenders": {
      "cash": 0,
      "card": 0,
      "mvatu": 0,
      "mycash": 0
    }
  }
}
```

`vat_summary.total` is gross inclusive. `vat_summary.subtotal` is net. `day.tenders.*` are inclusive totals by tender.

### GET `/api/v1/sales/{id}/receipt.pdf`

Header: `Authorization: Bearer {token}`  
Extra PDF of `receipt_text`.

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
