# Server-side tracking: Meta and Google

Owner: Aaron Davis (in-house web development)
Applies to: millboard.com, all six locales; GTM-TBPW5F2; GA4 313291284; Google Ads
7035244456; Meta pixel 239673430701675
Azure work item: AB#TBD, under AB#297 Maintain ongoing code ownership
Last reviewed: 2026-09-03

This is the build plan, not a change request. The change request for each phase is raised
separately per `docs/deployment-and-approval-process.md`.

## 1. Why

Conversion measurement is degrading for reasons no amount of client-side work fixes: ITP
and ETP cap first-party cookies at seven days or less, ad blockers remove the Meta pixel
outright, and iOS Mail and in-app browsers break attribution windows. Google Ads and Meta
are both bidding on an increasingly incomplete picture of what actually converts.

Server-side tagging moves the send off the browser and onto infrastructure we control. It
does not defeat consent and it is not a privacy workaround — see section 7, which is the
part of this document that must not be skipped.

## 2. Current state, verified 2026-09-03

| Layer | What is live |
| --- | --- |
| Web container | `GTM-TBPW5F2`, version 220 |
| GA4 | `G-CDWNWLM5LZ`, property 313291284 |
| Google Ads | `AW-848495806`, customer 7035244456 under MCC 6834799972 |
| Meta | Pixel `239673430701675`, browser only |
| Consent | CookieYes, `cdn-cookieyes.com/client_data/fba0fc734e.../script.js`, loaded ahead of GTM |
| Server container | **None.** No `server_container_url` or `transport_url` is configured |

The `sGTM` and `server_container_url` strings present in `gtm.js` are Google's runtime
library, not our configuration. This is a greenfield build.

The theme's own `_src/components-wholegrain/cookies-preferences/` component does not render
on production. CookieYes supersedes it. Removing the dead component is out of scope here but
worth a separate `chore/` branch, because two consent mechanisms in one codebase is a trap
for whoever reads it next.

### Known data-quality defects that must be fixed first

1. **Tag 419, "GA4 - Decking Calculator Click", fires on the Initialization trigger**
   (`2147479573`) as well as its real click trigger (`418`). The result is roughly 91,500
   events a week of which about 66 are genuine clicks. It is the highest-count event in the
   property and it is ~99.9% noise.
2. **The consent-trigger pattern that caused the July outage.** Moving base tag 108 onto a
   consent-event trigger silently cost about 67,150 page views between 17 and 30 July before
   v213/v214 restored it. Any consent change in this project gets the same scrutiny.

Building server-side on top of an untrusted dataLayer just launders bad data into Ads and
Meta bidding, where it is far more expensive than it is in a report.

## 3. Target architecture

A single server-side GTM container on **Google Cloud Run**, reached on a first-party
subdomain.

```
Browser (CookieYes -> Consent Mode v2)
  |
  +-- GTM-TBPW5F2 web container
  |     Google tag, server_container_url = https://ss.millboard.com
  |     Meta pixel (kept, see 5.2)
  |
  v
ss.millboard.com  ->  Cloud Run  ->  sGTM container
                                       |-- GA4 tag            -> GA4 313291284
                                       |-- Google Ads tag     -> AW-848495806 (+ enhanced conversions)
                                       |-- Meta CAPI tag      -> pixel 239673430701675
                                       ^
WooCommerce order hook (backstop, phase 4) --+
```

### 3.1 Why Cloud Run rather than Stape

We already hold a Google Cloud project and billing relationship for the Maps API. Cloud Run
keeps the data path entirely within processors we already have a lawful basis and a DPA for,
which matters while the HubSpot consent remediation programme is still open. Stape is less
operational work and would be a reasonable answer for a team without GCP, but it introduces
a new processor that has to be added to the privacy notice, the records of processing, and
the secrets register.

### 3.2 The tagging server domain

`ss.millboard.com`, or `sgtm.millboard.com`. It **must** be a subdomain of `millboard.com`.

The registrable domain has to match the site for the server container's cookies to be
first-party. A third-party hostname, or a Cloud Run `*.run.app` URL, gives away the entire
cookie-lifetime benefit that justifies the project. Verify with a real browser after
mapping, not by assuming the DNS record implies it.

**Open item:** who controls `millboard.com` DNS, and what is the change route for adding a
record. This is on the critical path for phase 1 and is not yet answered.

### 3.3 Sizing and cost

Traffic is roughly 16,000 page views a day, so about 480,000 a month.

Google's own guidance for a production tagging server is a minimum of three instances.
Realistically, start at **two always-on instances**, 1 vCPU and 512 MiB each, autoscaling to
about ten, plus a preview server that can scale to zero.

That lands in the region of **£45 to £120 a month** including egress. Treat that as an
order-of-magnitude figure and model it properly in the GCP pricing calculator before the
change request: the number goes in the CR and Finance will ask.

Cold starts drop events. Minimum instances are not an optimisation here, they are
correctness.

## 4. Phasing

Each phase is its own merge request and its own change request. Nothing goes to production
as one release.

### Phase 0, prerequisites and hygiene

- **Meta System User token with `ads_management`.** The token in the secrets register is
  scoped `read_ads_dataset_quality` only and *cannot send conversions*. This is a Business
  Manager admin task with an approval dependency, so it is the long pole. Start it first.
- Confirm GCP project, billing account and Cloud Run API.
- Answer the DNS ownership question in 3.2.
- Fix tag 419: remove trigger `2147479573`, leave `418`.
- **Record the baseline** before changing anything: GA4 purchase count, Google Ads
  conversions, and Meta Event Match Quality per event, for a full week. Without this the
  project cannot prove it worked.

### Phase 1, infrastructure

Deploy the tagging and preview servers to Cloud Run. Map the custom domain and TLS. Point
the web container's Google tag at `server_container_url`, **GA4 only** — no Ads or Meta tags
in the server container yet.

Exit criterion: GA4 traffic through the server container matches the client-side baseline
within 2%, verified over at least three days.

### Phase 2, GA4 validated

Confirm no data loss, confirm first-party cookie behaviour, confirm consent signals arrive
(`gcs` and `gcd` parameters present on the requests). Only then move on.

### Phase 3, purchase and sample orders

The first wave, and the reason for the project.

- Define and document the dataLayer contract for `purchase` and `sample_order`, versioned,
  in this repo. Both must carry `transaction_id`, `value`, `currency`, `items`, and locale.
- Generate a UUID `event_id` per event, shared between the browser pixel and the server send.
- Google Ads conversion tag plus **Enhanced Conversions for Web** via the server container.
- Meta CAPI tag, running **redundantly alongside** the browser pixel — see 5.2.
- Capture consent state on the WooCommerce order at checkout, reusing the consent
  infrastructure built for the French checkout work in August.

Tier 3 requires a real transaction path verified on staging, not a page-load check.

### Phase 4, backstop and monitoring

A WooCommerce order hook posting server-to-server to the tagging server, for orders that
never produced a browser event — ad blocker, browser closed on redirect, payment provider
round trip. Deduplicated on `transaction_id` against the browser event.

Monitoring, which is not optional given the July outage:

- Daily reconciliation of GA4 purchases against WooCommerce orders, alerting on drift
- Cloud Run uptime and error rate
- Meta Event Match Quality tracked against the phase 0 baseline

### Phase 5, remaining conversions

Form leads, quote requests, deck planner. Scoped after phase 4 proves the pipe.

## 5. The parts that are easy to get wrong

### 5.1 Event deduplication

Meta deduplicates on `event_name` plus `event_id`. Both sides must send the same pair. The
browser pixel takes it as `eventID`, the CAPI payload as `event_id`. Generate it once, in
the dataLayer, and pass it to both. Get this wrong and every purchase is counted twice.

Google Ads deduplicates on the conversion's order ID, so `transaction_id` must be present
and identical on both paths.

### 5.2 Do not remove the browser pixel

Meta's recommended configuration is redundant: browser pixel *and* CAPI, deduplicated. The
browser side still contributes signals the server cannot see. Turning the pixel off is a
common and costly misreading of what server-side is for.

### 5.3 Match quality

Meta CAPI accepts `em`, `ph`, `fn`, `ln`, `ct`, `st`, `zp` and `country`, SHA-256 hashed
after lowercasing and trimming, plus unhashed `client_ip_address`, `client_user_agent`,
`fbp`, `fbc` and `external_id`. Every field added lifts Event Match Quality and therefore
attribution.

`fbc` is built from the `_fbc` cookie, or from the `fbclid` URL parameter as
`fb.1.<timestamp>.<fbclid>` when the cookie is absent. Capturing `fbclid` at landing and
persisting it to the order is what makes paid-social purchases attributable days later.

**Hash on the server, never in the browser.** A hashed email in the dataLayer is still a
stable identifier sitting in the page.

### 5.4 Kinsta full-page cache

The order-received page must not be cached, or every purchase event carries the first
customer's order data. Verify explicitly. Kinsta's cache does not purge on publish, and a
`?cachebust=` query string is needed when checking by hand.

### 5.5 Locale

Six locales, one GA4 property, one Ads account, one pixel. Every server-side event needs the
locale as a dimension, and currency must come from the order rather than a container
constant. The v213 fix already made `sample_order` locale-independent; keep that property.

## 6. Governance

Server-side tagging is **tier 3** under `docs/code-ownership-and-branch-protection.md`:
CRM and pixel integrations, plus WooCommerce, plus new infrastructure.

That means, per phase: merge request into `staging`, full staging verification including a
real transaction, a documented second pair of eyes, a **Normal** change request graded
medium or high, Ken Thompson plus the service owner, and CAB. CAB sits Wednesday with a
**Tuesday end of day cut-off**. Phil Hornsby is service owner for business applications and
should be named on anything touching HubSpot attribution.

Plan the submissions backwards from those Wednesdays or the project drifts a week at a time.

### New entries required in `docs/secrets-register.md`

- Meta System User access token, `ads_management` scope, 12 month rotation
- Cloud Run service account key or Workload Identity binding
- sGTM container configuration string, which embeds the container identifier
- Shared secret for the phase 4 WooCommerce to tagging-server call

### Other documentation consequences

- CookieYes cookie declaration needs rescanning once first-party cookies change
- The privacy notice needs to describe the tagging server
- Any new root-level file added by this work goes into `.deployignore` in the same change,
  because the theme directory is publicly served

## 7. Consent

**Server-side tagging does not change the lawful basis for anything.**

The data still originates from a user's browser, and moving the send to our own
infrastructure does not create a permission that CookieYes did not collect. Regulators have
been explicit about this, and Millboard is currently part-way through a consent remediation
programme, so the standard here is higher than "technically it works".

Concretely:

- The GA4 client must receive and honour `gcs` and `gcd`. Do not strip them.
- Meta CAPI sends only when `ad_user_data` is granted. User data fields in 5.3 are gated on
  the same signal.
- The phase 4 WooCommerce backstop **must read the consent state captured on the order** and
  suppress the send when consent was refused. A server-side path that ignores consent because
  it can is the single largest compliance risk in this project.
- Consent changes get reviewed against the July 2026 failure mode before release, not after.

## 8. Success measures

Measured against the phase 0 baseline, not against intuition.

| Measure | Target |
| --- | --- |
| GA4 purchases vs WooCommerce orders | within 2% |
| Meta Event Match Quality, purchase | improved on baseline, 6.0+ |
| Google Ads conversions recorded | increase, with no change in actual order volume |
| Consent regression | none; page views and consent rates flat through each release |

## 9. Open items

- [ ] Who controls `millboard.com` DNS, and the change route for `ss.millboard.com`
- [ ] Meta System User token with `ads_management` requested
- [ ] GCP project and billing confirmed, cost modelled for the change request
- [ ] Azure work item raised, this document updated with the AB number
- [ ] Baseline week recorded
- [ ] Tag 419 Initialization trigger removed

## Related

- `docs/deployment-and-approval-process.md` (AB#310)
- `docs/code-ownership-and-branch-protection.md` (AB#307)
- `docs/secrets-register.md` (AB#309)
