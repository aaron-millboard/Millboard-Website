# Millboard HubSpot Order Fields

Pushes the custom WooCommerce checkout fields to the HubSpot contact, because
CRM Perks reads them only intermittently.

**Intended for the DE site only.** Network-install, activate per site.

## Why it exists

The German checkout collects five custom fields and all of them are required.
Every order carries them: 40 of 40 sampled orders had all five present in order
meta. CRM Perks passes them on roughly **39% of contacts and 65% of orders**,
all-or-nothing each time, so it is failing to read the custom field block rather
than mismapping individual fields. Its handling of standard billing fields, the
Orders object, line items and associations is fine.

Two of those fields, Project Size and Project Start Time, carry 50 of the 75 fit
points in the German lead-scoring model. The UK populates them at 97%, Germany
at 38%, which is most of the reason German leads sit in the B and C bands rather
than A.

This plugin does not replace CRM Perks. Remove it if CRM Perks ever fix their end.

## Fields

| Checkout field | HubSpot contact property | Value handling |
| --- | --- | --- |
| `project-size` | `project_size_dropdown` | Passes through. "I don't know" maps to `unknown_project_size`. |
| `project-start-time` | `de_project_start_time` | Passes through, including the en dash in "1–3 Monate". |
| `who-am-i` | `who_am_i_de` | Passes through. |
| `how-did-you-hear-about-us` | `how_did_you_hear_about_us___cloned_` | **Translated.** HubSpot's internal values are English slugs while the checkout sends German labels. |

`marketing-opt-in` is deliberately **not** synced. Marketing consent is legally
sensitive and is already written by another route; a second writer risks
inconsistent consent records. Add it only as a conscious decision.

### The trap this avoids

HubSpot enumeration properties accept the option's **internal value**, not its
label, and an invalid value is discarded silently with a `200` response. That is
exactly how `country` ended up empty on 74 German contacts: the store sent
"Deutschland" to a Select whose only German option is "Germany".

`how-did-you-hear-about-us` has the same problem. "Soziale Medien" would be
thrown away; HubSpot wants `social-media`. So every value here was read from the
live property definitions, and anything unrecognised is **logged and skipped
rather than sent**. If the checkout gains an option the mapping does not know,
it shows up in the activity log instead of vanishing.

## Setup

1. Create a HubSpot private app with `crm.objects.contacts.write`.
2. Preferred: add the token to `wp-config.php` so it never sits in the database
   or a database backup.

   ```php
   define( 'MILLBOARD_HUBSPOT_TOKEN', 'pat-eu1-...' );
   ```

   Otherwise paste it under **WooCommerce → HubSpot Order Fields**. The constant
   wins if both are set.
3. Check the connection status on that screen reads green.

## How the sync runs

Not inline at checkout. Checkout must never be slowed or broken by an outbound
API call, and CRM Perks needs a moment to create the contact before it can be
patched.

- Scheduled 90 seconds after the order is created.
- Retries at 10 minutes and 1 hour if the contact does not exist yet (a race
  with CRM Perks), or on throttling and upstream errors.
- Three attempts maximum, then it logs and stops.
- Orders are flagged once synced, so nothing is sent twice.
- Contacts are patched by email via `idProperty=email`, which is idempotent.

## Backfill

**WooCommerce → HubSpot Order Fields → Run backfill.**

Reads the fields from past orders and writes them to the matching contact. Since
every order carries the fields, this reaches records CRM Perks never transmitted
at all, which a HubSpot workflow reading the Orders object cannot do. Safe to run
repeatedly; already-synced orders are skipped. Paced to stay inside HubSpot's
private-app limit of 100 requests per 10 seconds.

## Source

Kept in the theme repository under `plugins/` so it is version-controlled and
can be rolled back. It is excluded from the theme deploy via `.deployignore`, so
it never rsyncs into the theme directory. Build the ZIP from that folder.
