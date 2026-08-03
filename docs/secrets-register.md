# Secrets register

Owner: Aaron Davis (in-house web development)
Azure work item: AB#309, under AB#297 Maintain ongoing code ownership
Last reviewed: 2026-08-03

**This file records where each secret lives, who owns it, and how often it is rotated. It
must never contain a secret value.** If you find a value in here, treat it as leaked,
rotate it, and rewrite the history.

The policy that governs this register is *Secrets management and rotation policy*
(AB#309) in SharePoint. This file is the technical inventory the policy points at.

## 1. Rules

1. **No secrets in the repository.** Verified 2026-08-03: `.env.js` is gitignored
   (`.gitignore` line 8), only `.env.js.sample` is tracked, and no tracked file on `main`
   matches a credential naming pattern. Re-verify at each review.
2. **Machine credentials belong in GitLab CI/CD variables**, Masked where the format
   allows and Protected always, so they are only exposed on protected branches.
3. **Human credentials belong in the company password manager**, never in a chat message,
   ticket, or code comment.
4. **No shared logins.** One identity per person. See the access model (AB#308).
5. **Rotate on exposure, immediately**, regardless of cadence. Exposure includes a value
   pasted into a ticket, an email, or a screenshot.
6. **Rotate on departure.** Anything a leaver could have read is treated as exposed. See
   the offboarding checklist (AB#312).

## 1a. Verified state, 2026-08-03

Checked in GitLab rather than assumed:

- `DRYRUN`: Protected, all environments. Not masked, correctly, since it holds no secret.
- `SSH_DEPLOY_KEY`: File type, Protected, all environments. Not masked, because GitLab
  cannot mask a multi-line File variable.
- Protected branches: `main` and `staging` only. `main` now rejects direct pushes.
- Minimum role to use pipeline variables: **No one allowed.** This closes off a real
  attack: without it, a manual pipeline could override `SSH_HOST` or `REMOTE_THEME_PATH`
  and rsync the build to an attacker-controlled host using the real deploy key.
- Project members: **one**, Aaron Davis, Owner, inherited from a personal namespace. No
  group-level variables exist.
- GitHub push mirror to `aaron-millboard/Millboard-Website`: healthy, last attempt and
  last successful update both 2 days ago, so the PAT is currently valid.

## 2. Deployment and repository credentials

| Secret | Location | Type | Owner | Rotation | Blast radius |
| --- | --- | --- | --- | --- | --- |
| `SSH_DEPLOY_KEY` | GitLab CI/CD variables, File type, Protected | SSH private key, no passphrase | Aaron Davis | 12 months, or on departure | Write access to the theme directory on **both** Kinsta environments, production included |
| `millboard_ci_deploy` | `~/.ssh/` on the developer workstation; public half registered in MyKinsta on both environments | SSH keypair, **no passphrase** | Aaron Davis | 12 months | Same as above, plus interactive SSH and wp-cli on production |
| Aaron's personal `id_ed25519` | Developer workstation, registered in MyKinsta | SSH keypair | Aaron Davis | 12 months | Interactive SSH to both Kinsta environments |
| GitLab account credentials and 2FA | Password manager | Human login | Aaron Davis | Password manager policy | Full control of the repository, including CI variables |
| GitHub PAT used by the GitLab push mirror | GitLab project, Settings > Repository > Mirroring repositories | Personal access token | Aaron Davis | **90 days, classic PAT** | Push access to the mirror repo. Expiry breaks the Azure Boards link, not the site |
| Azure DevOps GitHub connection token | Azure DevOps, Project Settings > GitHub connections | OAuth or PAT | Aaron Davis | On expiry | Work item linking only |
| MyKinsta account | Password manager | Human login | Aaron Davis | Password manager policy | Full hosting control: environments, backups, clones, SSH keys |
| Kinsta SFTP and SSH password for `mbwctest` | MyKinsta, per environment | Generated password | Aaron Davis | 12 months | Filesystem and database access per environment |

### Known issues in this section

- **`SSH_DEPLOY_KEY` cannot be masked.** GitLab cannot mask File type or multi-line
  values. It is set to Visible, so anyone with Maintainer access on the project can read
  the private key. Mitigations in force: the variable is Protected, so only pipelines on
  `main` and `staging` can see it, and those branches are protected so an arbitrary
  `.gitlab-ci.yml` cannot be pushed to them. **These two settings only work as a pair.**
  Reducing the Maintainer list is the other lever, and is part of AB#308.
- **The CI deploy key has no passphrase.** This is required for unattended CI. It means
  possession of the file is possession of production write access, which is why the
  Protected flag and the protected branch list are load-bearing rather than nice to have.
- **The mirror PAT expiring is the most likely routine breakage.** Symptom: the GitLab
  mirror row shows a 401 or 403 and stops syncing, and `AB#<id>` commit mentions stop
  appearing on the board. Fix: create a new GitHub PAT with `repo` scope, or fine-grained
  with Contents read and write, update the token on the mirror row, then click Update now.
  If the mirror is green but linking still fails, re-authorise the Azure DevOps side.
  Diarise this rather than waiting for it to break.
- **Both the GitLab namespace and the GitHub mirror sit in personal accounts.** This is a
  single point of failure independent of the secrets themselves, and it is the main subject
  of AB#308.

## 3. Application and platform credentials

Stored in the WordPress database, in `wp-config.php`, or in plugin settings on each
environment. None are in the repository.

| Secret | Location | Owner | Rotation | Notes |
| --- | --- | --- | --- | --- |
| WordPress administrator accounts, per locale | WordPress users, per site in the network | Aaron Davis | Password manager policy | Six locales plus commercial subsites |
| WordPress application passwords (`WP_USER`, `WP_APPPW`) | Per-user in WordPress; consumed as environment variables on the workstation | Aaron Davis | 6 months, or on departure | Used for REST API automation. Revocable per application without changing the account password, which is why they are preferred over the account password |
| WordPress salts and keys | `wp-config.php`, per environment | Aaron Davis | On suspected compromise | Rotating invalidates all sessions |
| Database credentials | `wp-config.php`, per environment | Kinsta generated | On environment rebuild | Not directly reachable from outside |
| Stripe API keys, live and test | WooCommerce Stripe gateway settings, per environment | Aaron Davis with Finance | 12 months | **Production keys must never be present on staging.** Verify after every clone |
| AvaTax credentials | WooCommerce AvaTax plugin settings | Aaron Davis with Finance | 12 months | Tax calculation. Failure is customer-visible at checkout |
| SMTP credentials | Post SMTP plugin settings | Aaron Davis with IT | 12 months | Transactional email. IT owns the mail platform |
| HubSpot private app token and API keys | HubSpot portal 26853518, and plugin settings for LeadIn, Gravity Forms HubSpot, and WooCommerce CRM Perks | Aaron Davis with Phil Hornsby | 12 months | Phil Hornsby is service owner for business applications |
| Google Maps API key | Theme or plugin configuration, plus Google Cloud project | Aaron Davis | 12 months | **Must stay HTTP referrer restricted and billing capped.** An unrestricted key is a direct financial exposure |
| ShortPixel Adaptive Images key | Plugin settings | Aaron Davis | 12 months | Image CDN. Local development depends on it |
| Matomo auth token | Matomo, embedded in the WordPress database | Aaron Davis | 12 months | Note the embedded-in-database deployment is already flagged as a scale risk |
| CookieYes account | Password manager | Aaron Davis | Password manager policy | Consent gating for EU and UK |
| Trustpilot API credentials | `millboard-trustpilot-schema` plugin configuration | Aaron Davis | 12 months | Review schema |

## 4. Marketing and analytics credentials

These are on the workstation for automation and reporting rather than on the site.

| Secret | Location | Owner | Rotation | Notes |
| --- | --- | --- | --- | --- |
| Google service account JSON key (Search Console, GA4) | Workstation key file, referenced by the MCP servers | Aaron Davis | 12 months | **A file on one machine with no backup and no second holder.** Losing the machine loses the access; a copied file grants it silently |
| Google Ads OAuth client and refresh token | Workstation secrets directory | Aaron Davis | 12 months | Customer 7035244456, MCC 6834799972. Refresh tokens do not expire on their own, so rotation is the only control |
| Google Ads developer token | Google Ads API centre | Aaron Davis | On revocation | |
| Google Tag Manager OAuth token | Workstation secrets directory | Aaron Davis | 12 months | Build scopes only, deliberately not publish |
| Meta Conversions API access token | Workstation secrets directory | Aaron Davis | **12 months, enforced by policy** | **Permanent token, no natural expiry.** Scope is currently `read_ads_dataset_quality` only. A permanent credential with no expiry is exactly the case where scheduled rotation is the only protection |
| Google Analytics and Search Console human access | Google account, via the service account and personal login | Aaron Davis | Access review | |
| Meta Business Manager access | Personal Facebook account with business role | Aaron Davis | Access review | Personal-account dependency, in scope for AB#308 |

## 5. Licence keys

Not secrets in the security sense, but losing them blocks security updates, so they belong
in the password manager with a renewal date. Premium plugins in use: Advanced Custom
Fields Pro, Yoast SEO Premium and the WooCommerce add-on, Gravity Forms and the HubSpot
add-on, Perfmatters, FileBird Pro, Post SMTP Pro, ShortPixel, WP Activity Log, WPConsent
Premium, NS Cloner, WooCommerce AvaTax, WooCommerce Table Rate Shipping, WooCommerce
Address Validation, WooCommerce Variation Gallery, Custom Order Numbers, Product Quantity,
Order Blocklist, CRM Perks HubSpot, and the WooCommerce.com account that manages several of
these.

| Item | Location | Owner | Renewal |
| --- | --- | --- | --- |
| WooCommerce.com account and licences | Password manager | Aaron Davis | Annual, diarised |
| Individual plugin vendor accounts | Password manager | Aaron Davis | Annual, diarised |
| Semrush, Screaming Frog | Password manager | Aaron Davis | Annual |

## 6. Rotation cadence summary

| Class | Cadence | Trigger events |
| --- | --- | --- |
| CI and deployment keys | 12 months | Departure, suspected exposure, Kinsta environment rebuild |
| GitHub mirror PAT | 90 days | Expiry, which is routine and should be diarised |
| Payment and tax credentials | 12 months | Departure, provider request, environment clone |
| API tokens with no natural expiry | 12 months | Departure, scope change |
| WordPress application passwords | 6 months | Departure, automation retired |
| Human logins | Password manager policy | Departure, role change |
| Anything exposed | Immediately | Any exposure at all |

## 7. Break-glass

If the developer workstation, the GitLab account, or the MyKinsta account is compromised,
in this order:

1. **MyKinsta:** remove all SSH keys on both environments, reset the SFTP and SSH password.
   This alone cuts deploy and shell access.
2. **GitLab:** revoke the session and personal access tokens, delete and recreate
   `SSH_DEPLOY_KEY`, confirm `main` and `staging` are still protected and that `DRYRUN` is
   as expected.
3. **WordPress:** revoke all application passwords, rotate salts to invalidate sessions,
   audit administrator accounts on all sites in the network.
4. **Payments:** roll Stripe keys and check for unexpected API activity. Involve Finance.
5. **Marketing:** revoke the Google service account key, Google Ads and GTM OAuth grants,
   and the Meta token.
6. Raise an Emergency change request. Ken Thompson verbally first, then
   post-implementation CAB.

Restoring service needs the MyKinsta account and the GitLab account. **If both sit only
with one person, this procedure cannot be executed by anyone else.** That is the core
argument of AB#308.

## Related

- Secrets management and rotation policy (AB#309), SharePoint, the governing policy
- Access model for IT agreement (AB#308), SharePoint
- Access review and offboarding checklist (AB#312), SharePoint
- `docs/deployment-and-approval-process.md` (AB#310)
- `docs/code-ownership-and-branch-protection.md` (AB#307)
