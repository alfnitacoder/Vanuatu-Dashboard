# Design slot

Web owns this desktop day book. Design owns cheap-phone shop-floor screens.

Swap targets (keep the data contract):

- `resources/views/dashboard.blade.php` — End of day / En blong dei
- `resources/views/sales/create.blade.php` — desktop open-amount table (not a phone clone)
- `resources/views/sales/show.blade.php` — SMS receipt text
- `public/css/stua.css` — tokens Paper `#F6F0E6`, Ink `#111111`, Pay `#0F8A45`, Void `#C62828`

Dashboard data: `$date`, `$summary` (count, gross, vat, net), `$tenders`, `$sales`.

VAT lock is in `App\Support\Vat`. Do not copy exclusive sale-screen arithmetic.
