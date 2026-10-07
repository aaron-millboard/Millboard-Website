# Millboard Internal Sample Ordering — WooCommerce build

A bulk sample-ordering grid as a **WooCommerce My Account tab** at
`/my-account/sample-ordering/`. Products come from the site's own WooCommerce
catalogue; submitting adds every selected line to the basket and sends the
user to the cart, so checkout, payment and order records are all
WooCommerce's.

Version 2.0.0. Supersedes the HubSpot build in `../child-theme/`.

## 1. Copy the files

Into your **child theme** (not the parent — a parent theme update would wipe
them):

    wp-content/themes/<your-child-theme>/
      inc/millboard-sample-ordering.php
      inc/mb-sof-catalogue.php
      inc/mb-sof-basket.php
      template-parts/millboard-sample-ordering.php
      assets/millboard-sample-ordering/sample-ordering.css
      assets/millboard-sample-ordering/sample-ordering.js

## 2. Load it from functions.php

One line. The other two `inc/` files are required by this one.

```php
require_once get_stylesheet_directory() . '/inc/millboard-sample-ordering.php';
```

## 3. Flush permalinks

The tab is a rewrite endpoint. The code flushes once automatically on first
load, but if `/my-account/sample-ordering/` 404s, go to
**Settings → Permalinks** and press **Save Changes**. Standard fix, safe to
repeat.

## 4. Check what the catalogue found

Visit the tab with `?mb_sof_debug=1` on the end while logged in as an admin:

    /my-account/sample-ordering/?mb_sof_debug=1

That renders a coverage panel — how many sample products were found, and the
count per category. **Do this before go-live.** Not every sample SKU exists as
a WooCommerce product yet, and a partial catalogue looks exactly like a
complete one from the front end.

## 5. Narrow the product query (recommended)

By default the catalogue is every published product whose name contains
"sample", plus two brochure SKUs whitelisted in `mb-sof-catalogue.php`. That
mirrors how the HubSpot source query worked, but it is a blunt instrument.

Better: put the sample products in a WooCommerce product category and set its
slug:

```php
define( 'MB_SOF_PRODUCT_CAT', 'samples' );
```

The query is then restricted to that category — faster, and immune to a real
sellable product being caught by the name match.

## Configuration

| Constant | Purpose | Default |
|---|---|---|
| `MB_SOF_PRODUCT_CAT` | Product category slug to source from | empty — match by name |
| `MB_SOF_ENDPOINT` | URL slug of the tab | `sample-ordering` |
| `MB_SOF_LABEL` | Menu label and tab heading | `Sample Ordering` |
| `MB_SOF_VERSION` | Bump to force a permalink re-flush | `2.0.0` |

All are wrapped in `defined()` checks, so any can be set in `wp-config.php`
instead and the theme file left untouched.

### Filters

| Filter | Purpose |
|---|---|
| `mb_sof_user_can_order` | Who sees the tab and may submit. Default: any logged-in user. |
| `mb_sof_max_qty` | Cap per line. Default 99. |
| `mb_sof_extra_skus` | SKUs to include that are not named "Sample". |
| `mb_sof_in_scope` | Last-resort override for whether a product belongs in the catalogue. |
| `mb_sof_expected_skus` | The full expected SKU list, so the coverage panel can report what is missing. |

### Restricting who sees the tab

**Decide this before go-live.** The default is every logged-in user, which on a
WooCommerce site means every customer with an account. Add to `functions.php`
*after* the `require_once`:

```php
add_filter( 'mb_sof_user_can_order', function () {
    return current_user_can( 'edit_shop_orders' );
} );
```

The filter gates the menu item, the render *and* the basket handler, so it
cannot be bypassed by posting directly.

### Reporting missing SKUs

To make the coverage panel report what is absent rather than only what was
found, give it the expected list:

```php
add_filter( 'mb_sof_expected_skus', function () {
    return array( 'AME105D', 'AMB6B165', /* ... */ );
} );
```

`products.csv` in the project folder holds all of them as `sku,name`.

## How it works

**Catalogue** — `mb-sof-catalogue.php` reads published products (one SQL query
for id/title/SKU, a second for stock status), filters to sample products,
classifies each by name into one of 22 categories, sorts by name and caches the
result in a transient for 12 hours. The cache is dropped whenever a product is
saved, deleted or has its stock status changed.

The 22 categories are derived by parsing product names, because the
WooCommerce category tree does not mirror this taxonomy. The classifier is a
line-for-line transcription of the Python original in `build.md` section 5, and
was validated against all 460 rows of `products.csv` — it agrees with the
Python output exactly, including the deliberate exclusions (all 600mm, 300mm
Fascia, 300mm flexible edges, rigid Square Step Edge (Standard)).

**Basket** — the form posts back to its own URL and is handled on
`template_redirect`, not through `admin-post.php`. That is deliberate:
`admin-post.php` runs in admin context where WooCommerce does not initialise
the cart, so `WC()->cart` would be null.

Submitted product ids are validated against the catalogue before anything is
added, so a forged post cannot drop an arbitrary product into the basket.
Quantities are capped. Any line WooCommerce refuses is named explicitly in a
cart notice rather than being silently dropped.

**Quantities** are held in a JavaScript object, not in the rendered inputs.
Search re-renders the accordion bodies, which would destroy the inputs for
non-matching products and silently shorten the order. On submit the script
writes one hidden input per line.

## Notes and limitations

- **JavaScript is required.** The accordions are built client-side. Without JS
  the form renders empty.
- **One instance per page.** Fixed element ids; the shortcode guards against a
  second render.
- **Out-of-stock products** are listed but greyed out with no stepper, because
  WooCommerce would refuse them anyway.
- **Simple products only.** Variations are not queried.
- **`position: sticky`** on the basket bar needs no ancestor with
  `overflow: hidden`. If the bar stops pinning, that is why.
- **Pricing and checkout are not addressed here.** If samples should be free
  to staff, or should skip payment, that is WooCommerce pricing and checkout
  configuration, not this code.
- **Catalogue visibility.** If sample products should not appear in the public
  shop or search, set their catalogue visibility to hidden in WooCommerce.
- A shortcode alias is registered for testing on an ordinary page:
  `[millboard_sample_ordering]`

## Previewing locally

`../preview-woocommerce.html` drives the real CSS and JS with a mock 385-product
catalogue, inside a deliberately hostile mock theme, alongside decoy elements
carrying the same ids and classes the widget uses. Open it in a browser; no
server or WordPress needed. **Do not copy it to the server** — it is a test
harness, which is why it sits outside this folder.

It cannot exercise the PHP. See the handover document for what that means.
