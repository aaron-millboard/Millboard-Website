millboard.com WordPress theme
=============================

The theme powering millboard.com. WordPress **multisite** running WooCommerce across six
regional locales plus a `/commercial/` subsite for each.

| Locale | Path | Production blog IDs |
| --- | --- | --- |
| United Kingdom | `/en-gb/` | 1, 3 |
| United States | `/en-us/` | 5 |
| Germany | `/de-de/` | 7 |
| France | `/fr-fr/` | 8 |
| Ireland | `/en-ie/` | 20 |
| Australia | `/en-au/` | 22 |

Commercial subsites are blog IDs 2, 6, 14, 19, 21, 23.

Development was brought in-house from Wholegrain Digital in July 2026. The theme is built
on **Granola v3**, Wholegrain's WordPress framework, but the upstream Granola project is
not our support route and we do not track it. Everything below describes this repository as
it actually is.

## Requirements

- **PHP** `^8.1`. Production runs 8.2, so target 8.2 locally.
- **Node** `>=25.1.0`, **npm** `>=11.6.0`. See `.nvmrc`.
- **Composer** 2.

## The build is webpack, not gulp

Worth stating plainly, because older documentation and the original Granola readme both
said gulp. **There is no `gulpfile.js` in this repository and never has been in v3.** The
build is webpack, configured at `build/webpack.config.js`.

### Scripts

Straight from `package.json`, so this table is the truth rather than a description of it.

| Command | What it actually runs |
| --- | --- |
| `npm run setup` | `npm install && composer install && npm run build` |
| `npm run build` | `npm run pot` then webpack in **production** mode |
| `npm run dev` | `npm run pot` then webpack in **development** mode, one pass |
| `npm start` | `npm run pot` then webpack in development mode with `--watch` |
| `npm run pot` | Regenerates `granola.pot` via `vendor/bin/wp i18n make-pot` |
| `npm run lint:php` | `vendor/bin/phpcs .` |
| `npm run fix:php` | `vendor/bin/phpcbf .` |
| `npm run php:compat` | PHP 8.2 compatibility check |
| `npm run deploy` | Production build with `composer install --no-dev` |

**`npm run deploy` does not deploy anything.** The name is inherited from Granola and is
misleading. It only produces a production build locally. The site is deployed by GitLab
CI/CD, which runs the equivalent steps itself. See
[docs/deployment-and-approval-process.md](docs/deployment-and-approval-process.md).

`fix:styles` and `fix:scripts` exist in `package.json` but are empty, so `npm run fix`
effectively only fixes PHP.

## Local development

Local sites run under **Local (WP Engine)**, serving `http://millboard.local/<locale>/`.

1. Copy `.env.js.sample` to `.env.js` and set the proxy to your local URL:

   ```js
   export default {
       BROWSERSYNC_PROXY: "http://millboard.local",
       NODE_ENV: "development"
   }
   ```

   Use `http`, not `https`, unless you have enabled SSL in Local. `.env.js` is gitignored
   and is local-only configuration.

2. `npm run setup`
3. `npm start`

### Multisite specifics

- The Local site must be configured as a **subdirectory** multisite (`ms-subdir`). If it is
  set up as a single site, the nginx config generated will not match and **every static
  asset 404s while HTML still loads**, which is easy to miss if you only check that pages
  render.
- Nested paths such as `/en-gb/commercial/` are three path segments deep, which stock
  WordPress multisite does not resolve. This needs `wp-content/sunrise.php` plus
  `define('SUNRISE', true)` in `wp-config.php`. The theme copies `sunrise.php` on
  activation, but a site stood up by importing a database dump needs it copied manually or
  the `/commercial/` subsites will 404.

### Not configured locally, by design

API credentials for live integrations (HubSpot, Stripe, AvaTax, SMTP, Maps) are
deliberately absent or in test mode locally. Premium plugin licences are not needed for
local work; they only gate automatic updates.

Images work locally with no setup. Direct hotlinks to production uploads return 403, but
the ShortPixel Adaptive Images plugin rewrites images to its CDN, which serves them from
the production cache.

## Windows notes

The agency developed on macOS, so these were never hit upstream. This is the maintained
environment now, so they are part of the build.

**npm scripts need a bash shell.** They use Unix-style paths such as `vendor/bin/wp`, which
cmd and PowerShell cannot execute. Point npm at Git Bash once:

```bash
npm config set script-shell "C:\Users\<you>\AppData\Local\Programs\Git\bin\bash.exe"
```

**Composer may refuse to install on a very new PHP.** `sabberworm/php-css-parser` in the
lock file caps at PHP 8.4, so a newer global PHP fails the platform check:

```bash
composer install --ignore-platform-req=php
```

Do **not** run `composer update` to resolve this. The lock file matches production and the
libraries run fine on 8.2.

**Windows path handling in webpack is fixed, but know the symptom.** `build/webpack.config.js`
previously emitted backslash paths in generated SCSS `@import` statements and in component
path normalisation, which produced a cascade of "cannot find `_core.scss`" and `mq` SCSS
errors, plus a fatal missing-partial error. Both are fixed on `main`. If those errors ever
reappear after a merge, that fix has been reverted rather than something new being broken.
Linux CI is unaffected either way, so this only ever breaks local Windows builds, never a
deploy.

## What is not in the repository

`assets/`, `vendor/`, `node_modules/`, `languages/` and `*.pot` are gitignored and generated
by the build. `.env.js` is local-only.

This means **only source needs to be committed**. CI installs dependencies and runs the
webpack build itself, so a broken or absent local build never blocks a deploy.

## Layout

```
Theme/              Theme PHP, PSR-4 autoloaded as Theme\
  WooCommerce/      Store behaviour: shipping, quotes, order flow
  Multisite/        Cross-locale and network behaviour
  Hubspot/  Meta/   CRM and pixel integrations
  PostTypes/        Custom post types
  Taxonomies/       Custom taxonomies
  Shortcodes/
  Plugins/          Plugin-specific integration code
  Utils/
  WordPress/        Core WordPress hooks and behaviour
Granola/            Framework code, PSR-4 as Granola\
_src/               Front-end source
  components/               Blocks and components, each with its own scss/js
  components-wholegrain/    Inherited framework components
  styles/  scripts/  images/  static/
  main.*  admin.*  editor.*  _core.scss  _print.scss
acf-json/           ACF field group definitions, version controlled
page-templates/     Page templates
woocommerce/        WooCommerce template overrides
build/              webpack config
assets/             BUILD OUTPUT, gitignored
vendor/             Composer output, gitignored
```

## Front-end gotchas

### Inline SVG icons need an explicit size

`_src/styles/5-elements/_svg-elements.scss` applies a global responsive reset:

```scss
svg {
    width: 100%;
    height: auto;
    max-height: 100%;
}
```

That is fine for illustrations and deliberate, but it means **any inline icon with no size
of its own expands to fill its container**. This is a recurring bug rather than a one-off,
so treat it as a rule: every component that renders an inline SVG must set an explicit,
class-scoped `width` and `height` on it. Nearly every component in `_src/components/` already
does exactly this, so follow the nearest existing example.

Symptom: an icon rendering enormous, or a layout stretching for no obvious reason after a
new block is added.

## Contributing

`main` **does not accept direct pushes.** Every change reaches production through a merge
request, and production deploys are a manual job in GitLab. Review requirements scale with
risk: a stylesheet change is not treated like a change to checkout.

Read these before your first change:

- [CODEOWNERS](CODEOWNERS)
- [docs/code-ownership-and-branch-protection.md](docs/code-ownership-and-branch-protection.md) for the review tiers and the branch rules
- [docs/deployment-and-approval-process.md](docs/deployment-and-approval-process.md) for how a merge reaches production, and the post-deploy steps that are database state and do not travel with a deploy
- [docs/secrets-register.md](docs/secrets-register.md) before touching anything credential-related

Reference the Azure work item as `AB#<id>` in commit messages so the board links up.

### Editor extensions for inline linting

- [Stylelint](https://marketplace.visualstudio.com/items?itemName=stylelint.vscode-stylelint)
- [ESLint](https://marketplace.visualstudio.com/items?itemName=dbaeumer.vscode-eslint)
- [phpcs](https://marketplace.visualstudio.com/items?itemName=shevaua.phpcs)

Config lives in `.stylelintrc`, `.eslintrc.json` and `.phpcs.xml`.

## Progressive web app assets

The theme ships installable web app support. Update or remove these depending on whether it
is wanted:

- `_src/static/site.webmanifest`
- `_src/images/icon-512.png`
- `_src/images/icon-maskable-512.png`
