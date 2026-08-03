# Code ownership and branch protection

Owner: Aaron Davis (in-house web development)
Applies to: `gitlab.com/aarondavismillboard1/millboard` (millboard.com WordPress theme)
Azure work item: AB#307, under AB#297 Maintain ongoing code ownership
Last reviewed: 2026-08-03

## Why this exists

Development of millboard.com moved in-house from Wholegrain Digital in July 2026. With
the agency gone there is no external gate on what reaches production, so ownership,
review, and the rules that protect the deploy branches have to be written down rather
than held in one person's head.

## 1. Ownership

The `CODEOWNERS` file at the repository root is the register of owners. Today there is a
single in-house developer, so every path resolves to the same owner. The file is still
worth maintaining for two reasons: it groups the codebase into risk tiers that drive the
review route below, and it becomes the routing table the moment a second developer or a
retained agency is added.

### What CODEOWNERS actually enforces

| Control | GitLab Free | GitLab Premium |
| --- | --- | --- |
| Owners shown on the merge request | Yes | Yes |
| Owners auto-suggested as reviewers | Yes | Yes |
| Merge blocked until an owner approves | **No** | Yes |
| Approval rules per path or section | No | Yes |

The project is on **GitLab Free**, confirmed 2026-08-03, so treat `CODEOWNERS` as advisory.
The controls that are genuinely enforced are in section 3. The accountability control for
anything above low risk is the Freshdesk change request and Wednesday CAB, not the
repository.

**Action if the tier is ever upgraded:** turn on Settings > Merge requests > "Require
approval from code owners" and add `main` to the protected branches covered by it.

## 2. Review tiers

Review effort follows risk, matching the risk grading already used for change requests.
The tier is set by the highest-risk path the merge request touches.

### Tier 1, low risk

**Scope:** styling, images, static assets, translation strings, documentation, editor
and lint config. `_src/styles/`, `_src/images/`, `_src/static/`, `docs/`.

**Route:** work on a `fix/*` or `chore/*` branch, merge request into `staging`, self
merge once the pipeline is green. No change request. Verify on the staging site before
promoting to `main`.

**Rationale:** visual only, reverts cleanly, no data or money at risk.

### Tier 2, medium risk

**Scope:** templates, blocks, post types, taxonomies, ACF field definitions, shortcodes,
front-end scripts. `Theme/PostTypes/`, `Theme/Taxonomies/`, `Theme/WordPress/`,
`Theme/Plugins/`, `Theme/Utils/`, `acf-json/`, `page-templates/`, `_src/components/`,
`_src/scripts/`, and the root template files.

**Route:** merge request into `staging`, deploy to staging, record the verification
evidence (what you checked, on which locale, with a screenshot or URL) in the merge
request description. Raise a **Standard** change request in Freshdesk with
`DevOps Ref = AB#<id>`. Approver per the matrix: Rob Smith for low risk, Rob Smith plus
the service owner for medium. No CAB. Then merge to `main` and release.

**Rationale:** sitewide and customer-visible across six locales, but recoverable by
reverting the merge commit and redeploying.

### Tier 3, high risk

**Scope:** anything touching money, orders, tax, shipping, customer data, the multisite
layer, CRM and pixel integrations, or the build and deploy configuration.
`Theme/WooCommerce/`, `woocommerce/`, `Theme/Multisite/`, `Theme/Hubspot/`,
`Theme/Meta/`, `functions.php`, `.gitlab-ci.yml`, `.deployignore`, `build/`, and the
dependency lock files.

**Route:** merge request into `staging`, full staging verification including a real
transaction path where payment or checkout is involved. **A second pair of eyes is
required** before merge to `main`. Raise a **Normal** change request, risk graded medium
or high, approver Ken Thompson plus Rob Smith or the service owner, and take it to CAB.
Submit by Tuesday end of day or it defers a week. Merge to `main` only after CAB
approval, then use the manual production deploy button.

**Rationale:** a defect here can take payments offline or corrupt order data, and
`.gitlab-ci.yml` holds the credentials that reach the Kinsta production box.

### The second pair of eyes on tier 3

There is currently no second developer, so "second pair of eyes" resolves in this order,
whichever is available:

1. A second internal developer, once one exists. Preferred.
2. A retained external reviewer, if an agency retainer is in place.
3. Where neither is available: a documented self-review recorded on the merge request,
   consisting of the diff walkthrough, the staging test evidence, and the rollback
   command, presented at CAB. CAB then carries the approval, and this substitution must
   be stated explicitly in the change request so IT is not under the impression a
   technical peer review happened.

Option 3 is a real gap, not a solution. It is the strongest argument for the second
reviewer requested in the access model (AB#308) and should be reviewed at each access
review (AB#312).

## 3. Branch protection

### Branch model

| Branch | Purpose | Deploys to | Trigger |
| --- | --- | --- | --- |
| `main` | Production truth | www.millboard.com | Manual button in GitLab |
| `staging` | Integration and verification | stg-mbwctest-staging.kinsta.cloud | Automatic on push |
| `feat/*`, `fix/*`, `perf/*`, `chore/*`, `ci/*`, `docs/*` | Work in progress | Nothing | No pipeline at all |

The pipeline `workflow.rules` in `.gitlab-ci.yml` only match `staging` and `main`, so
pushing a feature branch cannot deploy anything. That is a deliberate safety property
and must not be relaxed.

### Required protected branch settings

Settings > Repository > Protected branches.

**`main`**
- Allowed to merge: **Maintainers**
- Allowed to push and merge: **No one**. All changes arrive by merge request. This is the
  single most important control in this document, because it makes the merge request the
  only path to production.
- Allowed to force push: **off**
- Code owner approval: on if the tier ever supports it, otherwise unavailable

**`staging`**
- Allowed to merge: **Maintainers**
- Allowed to push and merge: **Maintainers**. Direct push is deliberately allowed here so
  test cycles stay fast, since staging is disposable and gets cloned from production.
- Allowed to force push: **off**
- Code owner approval: not required

**Everything else:** unprotected. Do not add wildcard protection for `feat/*` and
similar; it would block the rebasing and force-pushing that normal feature work needs.

### Required merge request settings

Settings > Merge requests.

- All threads must be resolved before merge: **on**
- Pipelines must succeed before merge: **off, deliberately.** See the caveat below.
- Minimum role to use pipeline variables: **No one allowed.** This is important. It stops
  anyone overriding `DRYRUN`, `SSH_HOST`, or `REMOTE_THEME_PATH` at pipeline run time,
  which would otherwise let a manual pipeline redirect a production deploy.
- Allow merge request pipelines to access protected variables and runners: on. This is
  safe as configured, because GitLab only grants protected resources to a merge request
  pipeline when **both** source and target branches are protected. Feature branches are
  unprotected, so the only path it covers is `staging` into `main`, which is already
  trusted. Revisit if merge request pipelines are ever introduced.

#### Caveat: why "Pipelines must succeed" is off

Turning it on would block every merge request from a feature branch into `staging`. The
`workflow.rules` in `.gitlab-ci.yml` only match `staging` and `main`, so a feature branch
never produces a pipeline, and GitLab treats "no pipeline" as not succeeded. The setting
would therefore deadlock the normal release flow rather than protect it.

To make it enableable, add a build-only job that runs on merge request events, so a merge
request gets its build validated without any deploy job attached:

```yaml
workflow:
  rules:
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
    - if: '$CI_COMMIT_BRANCH == "staging"'
    - if: '$CI_COMMIT_BRANCH == "main"'
```

with the `deploy:*` jobs left as they are, since their rules already key off
`$CI_COMMIT_BRANCH` and so will not run for a merge request pipeline. Verify that before
enabling: a deploy job firing on a merge request pipeline would be a serious regression.

This is a change to `.gitlab-ci.yml`, which is tier 3 by the rules in this document, so it
needs a Normal change request and CAB. It is worth doing, because it catches a broken
build before it reaches `staging` rather than after.
- Squash commits when merging: **Allow**, not require. Isolated merge commits matter,
  because the proven production rollback is `git revert -m 1 <merge-commit>` and that
  needs the merge commit to exist.
- Delete source branch by default: **on**, with one caveat below
- Merge method: **Merge commit**. Do not switch to fast-forward, for the same rollback
  reason.

**Caveat on deleting source branches:** Azure Boards can only link branches that still
exist on the GitHub mirror, so a merged and deleted branch disappears from the work item
branch picker. Link the commit or merge request instead, or rely on `AB#<id>` mentions in
the commit message. This is expected, not a fault.

### Protected variables

`SSH_DEPLOY_KEY` and `DRYRUN` are marked Protected in Settings > CI/CD > Variables, which
means they are only exposed to pipelines running on protected branches. Combined with
`main` and `staging` being the only protected branches, this is what stops someone
pushing a branch containing a malicious `.gitlab-ci.yml` and exfiltrating the Kinsta
deploy key. **Keep both variables Protected and keep both branches protected. The two
settings only work as a pair.**

`SSH_DEPLOY_KEY` is currently set to Visible rather than Masked because it is a File type
variable holding a multi-line private key, which GitLab cannot mask. See
`docs/secrets-register.md` for the mitigation and rotation cadence.

## 4. State as applied on 2026-08-03

Verified in GitLab, not assumed.

| Setting | Before | After |
| --- | --- | --- |
| `main` allowed to merge | Maintainers | Maintainers, unchanged |
| `main` allowed to push and merge | Maintainers | **No one** |
| `main` allowed to force push | Off | Off, unchanged |
| `staging` allowed to merge | Maintainers | Maintainers, unchanged |
| `staging` allowed to push and merge | Maintainers | Maintainers, unchanged, by design |
| `staging` allowed to force push | Off | Off, unchanged |
| All threads must be resolved | Off | **On** |
| Pipelines must succeed | Off | Off, see the caveat above |
| Merge method | Merge commit | Merge commit, unchanged |
| Squash | Allow | Allow, unchanged |
| Delete source branch by default | On | On, unchanged |
| Minimum role for pipeline variables | No one allowed | Unchanged |
| `DRYRUN` variable | Protected | Unchanged |
| `SSH_DEPLOY_KEY` variable | File, Protected | Unchanged |

The single substantive change is `main` no longer accepting direct pushes. Before this,
the merge request was optional on production: anyone with Maintainer rights could push
straight to `main` and then click the deploy button, bypassing review, the change request,
and CAB entirely. That is now closed.

### Project access as found

The project has **one member**: Aaron Davis, `@aarondavismillboard`, role Owner, inherited
from the personal namespace `aarondavismillboard1`. There are no group-level variables,
because there is no group.

This is the honest limit on everything in this document. Protected branches stop accidents
and stop a third party who gains Developer access, but they cannot create a second pair of
eyes, and an Owner can always unprotect a branch. The controls here are real but they are
guardrails on a single trusted person, not separation of duties. Fixing that is AB#308.

## 5. Commit and branch conventions

- Branch names: `feat/`, `fix/`, `perf/`, `chore/`, `ci/`, `docs/` prefix, then a short
  kebab-case description. Include the ticket where one exists.
- Reference the Azure work item as `AB#<id>` in the commit message or merge request
  title. That is what creates the link on the board, through the GitHub mirror.
- One logical change per merge request. The tier is set by the riskiest file touched, so
  mixing a stylesheet tweak into a WooCommerce change drags the whole thing to tier 3.

## 6. Review and revision

Review this document at each quarterly access review (AB#312), and immediately whenever:

- a developer joins or leaves, since `CODEOWNERS` and the tier 3 reviewer both change
- the GitLab tier changes, since that changes what is enforceable
- the branch or environment model changes
- a production incident traces back to a gap in these rules

## Related

- `docs/deployment-and-approval-process.md` (AB#310) - how a merge reaches production
- `docs/secrets-register.md` (AB#309) - the credentials the pipeline uses
- Access model for IT agreement (AB#308) - identity-based access, SharePoint
- Access review and offboarding checklist (AB#312) - SharePoint
