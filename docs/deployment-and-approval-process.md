# Deployment and approval process

Owner: Aaron Davis (in-house web development)
Applies to: millboard.com theme, GitLab CI/CD to Kinsta
Azure work item: AB#310, under AB#297 Maintain ongoing code ownership
Last reviewed: 2026-08-03

## 1. The mechanism in one table

Deployment is entirely branch driven. There is no separate deploy tool. DeployHQ, which
the agency used, was decommissioned at handover in July 2026.

| Push to | Pipeline | Deploys | Gate | Target |
| --- | --- | --- | --- | --- |
| `staging` | build then `deploy:staging` | Yes, automatically | None | stg-mbwctest-staging.kinsta.cloud, SSH port 19646 |
| `main` | build then `deploy:production` | Only when the button is clicked | `when: manual` | www.millboard.com, SSH port 37154 |
| any other branch | **No pipeline is created** | No | n/a | n/a |

Config: `.gitlab-ci.yml` at the repository root, present on `main` and `staging`.

## 2. What the pipeline does

**build** stage, on `node:20-bookworm`:

1. Installs PHP 8.2, Composer, rsync into the image
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci`
4. `npm run pot -- --allow-root` (the flag is required because CI runs as root and wp-cli
   otherwise refuses)
5. `npx webpack --config build/webpack.config.js --mode=production`
6. Assembles a clean deploy tree into `.deploy/` with rsync, honouring `.deployignore`,
   which ships `vendor/` and `assets/` but not `_src/`

The build tree is kept as an artifact for one day.

**deploy** stage, on `alpine:3.20`: rsync of `.deploy/` over SSH to the Kinsta theme path
`/www/mbwctest_659/public/wp-content/themes/millboard`.

Two consequences worth knowing:

- **Only source needs to be committed.** `assets/` and `vendor/` are gitignored locally
  and rebuilt by CI, so a missing local build never blocks a deploy.
- **Local Windows build failures do not block deploys.** CI builds on Linux, where the
  webpack backslash path problem does not occur.

## 3. The DRYRUN safety control

`.gitlab-ci.yml` defaults `DRYRUN: "--dry-run"`, so a pipeline run from the committed
config transfers nothing. The real switch is the GitLab CI/CD **variable** `DRYRUN`, set
to empty, which overrides the file value and makes transfers real.

This is deliberate. Anyone who clones the repo and runs CI without the project variable
gets an inert deploy. **Do not remove the `--dry-run` default from the file.** If a deploy
ever needs to be made safe in a hurry, deleting or repopulating the project variable
disarms production without touching code.

To verify which mode a pipeline ran in, read the rsync output in the `deploy:*` job log.
Note the raw log capture truncates at roughly 50,000 characters, so the rsync byte summary
at the end is often unreadable. The deployed site is the reliable proof.

## 4. Standard release flow

1. **Build the change** on a `feat/*` or `fix/*` branch. Push it. No pipeline runs, so
   nothing deploys.
2. **Determine the tier** from `docs/code-ownership-and-branch-protection.md`. The tier
   is set by the riskiest file touched, and it determines whether a change request is
   needed.
3. **Raise the change request** if the tier requires it, before the production release.
   Freshdesk, Type = Change Request. Complete Change Type, Risk Assessment, Impacted
   Systems, Location, Proposed Timeline, Implementation Date, Rollback Plan, and set
   `DevOps Ref = AB#<id>`. Attach the Planned Change Checklist. Freshdesk emails the
   approvers automatically off Change Type and Risk, so do not chase people by hand.
   When in doubt on risk, grade higher. CAB can downgrade at review but cannot upgrade
   retrospectively.
4. **Merge to `staging`** by merge request, so CI runs and the diff is reviewable.
   `staging` is often many commits diverged from `main`, so a merge request surfaces
   conflicts before they land.
5. **Verify on staging.** Check the actual behaviour on the affected locale or locales,
   not just that the page loads. Record what you checked in the merge request.
6. **Wait for approval** where the tier requires it. Normal changes need CAB, which sits
   on Wednesday with a Tuesday end of day submission cut-off.
7. **Merge to `main`** by merge request. Keep it a merge commit so the rollback in
   section 6 works.
8. **Click the manual `deploy:production` job** in GitLab Pipelines or Environments.
9. **Run the post-deploy steps** in section 5. Several of them are not optional.
10. **Verify production**, then close the change request and move the Azure work item to
    Closed.

## 5. Post-deploy steps

These are database-level, per-environment, and are not carried by the code. Skipping them
has already caused a silent production-class failure once.

### Always

- **Clear the Kinsta full-page cache.** It does not purge on publish. When checking a page
  by hand, add a `?cachebust=` query string.
- **Verify on the real front end using a browser.** The Kinsta WAF returns 403 to
  PowerShell and curl for front-end pages and for `/wp-content/uploads/...` regardless of
  User-Agent. REST with Basic auth is unaffected.

### When the release registers a rewrite rule, endpoint, custom post type, or permalink change

**Flush rewrite rules on every site in the network.** `wp_options.rewrite_rules` is
database state, so it does not travel with the deploy, and a stale copy silently drops the
new endpoint.

```bash
for B in 3 20 8 7 5 22; do U=$(wp site list --field=url --blog_id=$B | head -1); wp rewrite flush --hard --url="$U"; done
```

Verify with `wp option get rewrite_rules --format=json --url=<site>` and check the new
rule is present. This is exactly how the Order Essentials basket step went missing on
staging on 30 July 2026: every file was correct and the endpoint was absent from the
rules.

### When the release depends on ACF options-page configuration

Options-page data lives in `wp_options`, not in the theme, and the field group returning
with the code does **not** bring the data. Order Essentials is the known case: its
recommendation matrix is an ACF options repeater, and with zero rules the basket silently
skips the essentials step. Any such configuration must be recreated on the target
environment as a documented deploy step.

### Before any Kinsta environment clone

**Back up `wp_options` as well as posts.** A live-to-staging clone overwrites options, and
the Order Essentials matrix was lost this way on 30 July 2026. The only surviving copy was
Kinsta's automatic pre-push backup.

## 6. Rollback

The proven rollback is to revert the merge commit and redeploy. This was used
successfully on 23 July 2026 to pull the broken hero header off production while leaving
the performance and header fixes from the same release live.

```bash
git revert -m 1 <merge-commit-sha>
```

Then push `main` and click `deploy:production` again, then clear the Kinsta cache.

This only works if releases go in as **isolated merge commits**, one feature per merge.
That is why the merge method must stay "merge commit" and squash must not be mandatory.
Merging several features as one commit means you cannot roll back just the broken one.

The rollback plan recorded on the change request should name the specific merge commit or,
before it exists, the specific merge request.

Rewrite rule flushes and ACF options data are not reverted by a code revert. If the
release included either, undo them explicitly.

## 7. Approval matrix

Taken from the IT change-management process. The application criticality in the IT Service
Catalogue Applications Register decides whether a change request is needed at all: Low
means none, Medium and above means one.

| Change Type | Risk | Approvers | CAB | Typical turnaround |
| --- | --- | --- | --- | --- |
| Standard | Low | Rob Smith | No | Same day |
| Standard | Medium | Rob Smith plus service owner | No | Same day |
| Normal | Low to Medium | Ken Thompson plus Rob Smith | Yes | Next Wednesday |
| Normal | High | Ken Thompson plus service owner | Yes | Next Wednesday |
| Emergency | Any | Ken Thompson verbally **first**, never proceed without it | Post-implementation | Immediate |

Service owners: Rob Smith (default, end-user computing, identity), Phil Hornsby (business
applications including HubSpot, SAP, Sage, Power BI), Simon Williams (infrastructure,
network, Azure). Ken Thompson is Global Head of IT and change process owner. Rob Smith
chairs CAB.

Mapping from the review tiers: tier 1 needs no change request, tier 2 is normally
Standard, tier 3 is Normal and goes to CAB.

## 8. Direct production access

Direct SSH with wp-cli is available for work the REST API cannot do, such as protected
post meta, plugin APIs, and database queries.

```bash
ssh -i "$env:USERPROFILE\.ssh\millboard_ci_deploy" -p 37154 -o BatchMode=yes mbwctest@130.162.162.23
```

Port 37154 is production, 19646 is staging, user `mbwctest` on both.

**The filesystem path is `/www/mbwctest_659/public` on both environments, so the path does
not tell you which box you are on.** Always confirm before writing:

```bash
wp option get home
```

Production returns `https://www.millboard.com/en-gb`. Production network blog IDs: 1 and
3 en-gb, 5 en-us, 7 de-de, 8 fr-fr, 20 en-ie, 22 en-au, plus commercial subsites 2, 6,
14, 19, 21, 23.

Direct production changes bypass the pipeline and leave no diff, so they are for
operational fixes and investigation only. Anything repeatable belongs in the theme. Where
a direct change alters production behaviour, it still needs a change request.

## 9. Known operational hazards

- **PowerShell plus native executables.** With `$ErrorActionPreference = 'Stop'`, anything
  a native binary writes to stderr becomes a terminating error even on exit code 0. `mysql`
  prints a password warning on every call, and `git push` writes progress to stderr. Set
  `$ErrorActionPreference = 'Continue'` around native calls and judge success only by
  `$LASTEXITCODE`.
- **Never inline a remote command with nested quotes.** PowerShell mangles
  `ssh host "... 2>/dev/null"`. Write a `.sh` or `.php` file, `scp` it, then run it.
- **Never `tar -xzf` a bare `.gz`.** Use a gzip stream.

## Related

- `docs/code-ownership-and-branch-protection.md` (AB#307)
- `docs/secrets-register.md` (AB#309)
- Secrets management and rotation policy (AB#309), SharePoint
