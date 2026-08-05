# Sample basket: Germany pilot

Why the German sample journey was changed, what changed, and how to turn it on
or off. Two per-site toggles on **Product Settings** (ACF options page) gate
everything here, both **off by default**.

| Toggle | Field name | What it does |
| --- | --- | --- |
| Enable AJAX sample basket | `enable_ajax_sample_basket` | Adds samples without a page reload, plus a sticky "x of 3 chosen" bar |
| Hide "View product" overlay on sample cards | `hide_sample_card_view_product` | Removes the competing, non-clickable overlay label |

Because ACF options are stored per site on multisite, switching these on for the
`de-de` site only is what makes this a Germany pilot. With both off the rendered
markup is byte-identical to before the change.

## The problem, in numbers

Source: GA4 property 313291284, Germany, 1 Aug 2025 to 31 Jul 2026. Search data
from Search Console, `sc-domain:millboard.com`.

### Paid traffic arrives and does not convert

German sessions landing on the two sample category pages:

| Landing page | Sessions | Engagement | Reached basket |
| --- | --- | --- | --- |
| `/de-de/order-a-sample` (redirects to the decking page) | 18,963 | 66.1% | 7.75% |
| `/de-de/product-category/terrassendielen-muster` | 10,857 | 45.3% | 4.21% |
| `/de-de/product-category/fassadenmuster` | 4,299 | 55.3% | 2.82% |

68% of all German sessions are paid (cross-network 23,307, paid social 18,525,
paid search 10,280 of 76,353). Organic search is 12.5%. So this is bought
traffic, and it was landing on a page that made the next step expensive.

End to end for the year: **1,453 users added a sample, 534 completed an order.**

The add figure is counted from the `?add-to-cart=` URL, not from the
`add_to_basket` event. The event reports 2,021 and should not be used: it fires
only for sessions whose landing page was `/de-de/order-a-sample` and records
nothing for sessions landing directly on either category page, so it
undercounts some journeys and cannot be compared across landing pages.

### Where people went instead

> **Correction.** An earlier version of this document, and the commit message on
> `c54fa58`, claimed a 7:1 and 17:1 ratio of product-page clicks to basket
> clicks. Those figures were wrong: they summed `totalUsers` across many
> individual product-page rows, which double-counts anyone who viewed more than
> one product page. The corrected figures are below. The commit message is
> already pushed and cannot be edited, so this note is the record.

Onward journeys from a sample listing page, Germany, deduplicated by user:

| Destination | Users | |
| --- | --- | --- |
| A product page | 5,908 | |
| The basket | 2,479 | **2.4 : 1** |

That ratio is not a German problem. The same component ships everywhere and the
ratios are comparable, with the UK slightly worse while converting far better:

| Market | To product | To basket | Ratio |
| --- | --- | --- | --- |
| UK | 63,634 | 24,550 | 2.6 : 1 |
| US | 25,768 | 11,706 | 2.2 : 1 |
| Germany | 5,908 | 2,479 | 2.4 : 1 |
| France | 14,204 | 7,388 | 1.9 : 1 |
| Ireland | 167 | 117 | 1.4 : 1 |
| Australia | 178 | 405 | 0.4 : 1 |

So the tap-through itself is not what separates the markets. What matters is
that most of the people who take it do not come back.

### Does the product page recover them?

The product page renders the same sample buttons, so a visitor who taps through
can still add from there. Measured by the `?add-to-cart=` URL, which is a
server-side fact rather than a tag:

| Add happened on | Users, 12 months |
| --- | --- |
| The sample listing page | 898 |
| A product page | 714 |

Both routes are real. The exact three-step path (listing, then product page,
then add) cannot be measured:

- `pageReferrer` only looks one hop back, and the add happens *on* the product
  page, so its referrer is the product page itself.
- A sequential funnel runs, but its final step depends on `add_to_basket`, which
  fires unreliably (it records nothing for sessions that land directly on the
  category pages). That step returns zero for every market, which is the tag, not
  behaviour.

It can be bounded. Roughly 4,045 German users went from the listing to a product
page (funnel step 2, GA4-sampled at about 47%, so approximate). At most 714 of
them added from a product page, and the true number is lower because that 714
also includes everyone who reached a product page directly from search or
navigation. So **at most 18% of the tap-through traffic recovers, and realistically
well under that.**

The pages carried 25 and 31 view-product links against only 15 and 21 add
buttons. Worse, the "View product" overlay on the card image is not a link at
all - the product link is the stretched card heading - so it was a false
affordance that did nothing on tap while pulling attention off the one control
that mattered.

### Why the reload hurt so much

The add control was a plain `<a href="?add-to-cart=ID">` with WooCommerce AJAX
off (`cart_redirect_after_add: "no"`, no `ajax_add_to_cart` class, no
`data-product_id`). Every add was a full page load that returned the visitor to
`scrollY: 0`.

Measured on a 375x812 mobile viewport:

- decking page 16,226px tall, **20 screens**
- cladding page 18,957px, **23 screens**
- first sample 1,724px down, about 2.1 screens
- samples 582px apart, so the 15th sits 12.7 screens down

Picking three samples meant: scroll, tap, thrown to the top, scroll further,
tap, thrown to the top, scroll further still.

**94% of the traffic landing here is mobile** (9,752 mobile sessions against 638
desktop on the decking page). Mobile engagement is 42.9% against 82.3% on
desktop, so the pattern hits almost all of the audience and the segment that
copes worst.

The add itself was never broken. It showed a confirmation, an "x/3" counter and
a remove link. The cost was repetition, not failure.

## What changed

1. **AJAX sample basket.** Samples toggle in and out over
   `wp_ajax_granola_sample_toggle`. The server stays the single source of truth
   for basket state and returns each sample's position, so the "x/3" labels
   cannot drift. The original `?add-to-cart=` href is kept as a no-JS fallback,
   and a failed request falls through to it rather than stranding the visitor.
2. **Sticky basket bar.** Once a sample is chosen, a fixed bar shows "x of 3
   samples chosen" and links to the basket, so nobody has to scroll back up a
   20-screen page to find it.
3. **View-product overlay hidden on sample cards.** The label is dropped; the
   sample image preview on hover is kept.

## Related tracking work

`de_sample_order` is a registered GA4 key event that had never fired
(`uk_sample_order` 13,959, `us_sample_order` 4,480, `ie_sample_order` 81,
`de_sample_order` 0, `fr_sample_order` 0). German sample completions land on
`/de-de/kasse/order-received/{id}/`. Google Ads already receives a conversion
there via the existing "Checkout - Order Complete - Sample" trigger, so Ads was
not blind, but GA4 reporting for Germany was.

Added in GTM (created in the workspace, publish separately):

- trigger **DE Sample Order Complete**, tag **GA4 - DE Sample Order**
- trigger **FR Sample Order Complete**, tag **GA4 - FR Sample Order**

## Still outstanding

- The German button label renders "handmustermuster hinzufügen". The source
  string is `Add %s sample` interpolating the `pa_sample-size` term. UK terms are
  `Small`/`Large`, giving "Add small sample"; the German term is literally
  `Handmuster` and the German translation re-adds "muster". Fix in Loco Translate
  by changing the German string to `%s hinzufügen`. No deploy needed.
- New strings needing German translation: "View basket", "%1$s of %2$s samples
  chosen", and the sample update error message.
- "Decking Calculator Click" fires roughly 1:1 with `page_view` (25,053 events
  against 26,105 page views on the decking page), so that metric is not usable.
- UK campaigns are landing German users: "[UK] FB & IG | 4 | Decking -
  Remarketing" 886 sessions, plus 153 more from two other UK campaigns. One
  Facebook ad has an unrendered `{{campaign.name}}` in its UTM.
- Some order-received URLs carry a doubled locale prefix
  (`/en-gb/en-gb/checkout/order-received/...`), which the existing conversion
  trigger regex will not match.
