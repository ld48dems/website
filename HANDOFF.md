# Handoff: LD 48 Democrats website

## What this is

This is the deployable LD 48 Democrats website, not a design mockup. It is a static site built from `.dc.html` pages, structured JSON content, and the custom `support.js` runtime. Netlify currently provides only the temporary preview, form handling, and preview editor infrastructure. The production site will migrate to LD 48's existing web host.

## Current production hosting

Public hosting research completed August 17, 2026 found:

- `ld48dems.org` resolves to `65.181.111.232`.
- The server runs LiteSpeed and identifies as `s1355.use1.mysecurecloudhost.com`.
- The IP is registered to WHG Hosting Services Ltd, also known as World Host Group infrastructure.
- Domain registry records reference MochaHost nameservers. The provider is likely MochaHost or a MochaHost reseller.
- Live DNS uses `ns1` through `ns4.mysecurecloudhost.com`.
- Tucows Domains Inc. is the registrar through OpenSRS.
- The current WordPress site lives at `/wp/`, and the root domain redirects there.

Public records cannot identify the exact customer account, billing owner, reseller, hosting plan, or control-panel credentials. Confirm those details from invoices, renewal emails, password-manager entries, or the LD 48 payment account before migration.

## How the files actually work (important)
Each `NAME.dc.html` file is **not** plain static HTML. It's parsed at runtime by `support.js` (included in this folder), which:
- Reads the markup inside `<x-dc>...</x-dc>` as a template.
- Reads the `<script data-dc-script>` block as a small component class (`class Component extends DCLogic`) providing state/behavior, rendered via React (loaded internally by `support.js`).
- Understands a few custom template tags: `<sc-for list="...">` (loops), `<sc-if value="...">` (conditionals), `<dc-import name="nav">` (mounts a sibling `nav.dc.html`/`footer.dc.html` as a shared partial).
- `{{ dotted.path }}` in the markup is a template hole resolved from the component's `renderVals()` output, not a JavaScript expression.

Every page loads `support.js`. There is no build command, bundler, or package installation. Serve the files over HTTP. Treat `support.js` as generated code and do not edit it.

If you'd rather not carry this runtime forward in production, the recommended path is to reimplement each page as plain HTML/CSS (or your framework of choice) 1:1 from what renders, since the visual design and copy are final.

## Site map (files → routes)
| File | Route | Notes |
|---|---|---|
| `index.html`, `home.dc.html` | `/` (homepage) | These files must remain byte-for-byte synchronized |
| `who-we-are.dc.html` | Who we are | Mission, board (text-only, **no headshots by design**), elected officials, PCOs |
| `where-we-stand.dc.html` | Where we stand | Issue positions |
| `endorsements.dc.html` | Endorsements | Endorsed candidates/measures |
| `events.dc.html` | Events | Full event feed (see events-data.js below) |
| `get-involved.dc.html` | Get involved | Hub linking to become-member / volunteer / become-pco |
| `become-member.dc.html` | Become a member | Membership form + district lookup (live ArcGIS geocoding) + SMS opt-in |
| `volunteer.dc.html` | Volunteer / take action | Shift + partner-action feed, volunteer signup form |
| `become-pco.dc.html` | Become a PCO | Role guidance, applications, resources, and the complete public roster |
| `contact.dc.html` | Contact | Contact form |
| `donate.dc.html` | Donate | ActBlue, in-kind gifts, checks, and compliance information |
| `about-resolutions-bylaws.dc.html`, `resolutions.dc.html` | Resolutions/bylaws | Complete governing-document and resolution archives |
| `privacy.dc.html`, `mobile-terms.dc.html` | Legal | Static content |
| `nav.dc.html` | (partial) | Shared header/nav, imported via `<dc-import name="nav">` on every page |
| `footer.dc.html` | (partial) | Shared footer, imported the same way |
| `admin/index.html` | `/admin/` | Temporary Decap CMS interface; permanent authentication and publishing method are not decided |

## Design system
- Colors: navy `#011D3E` (primary dark), blue `#0267BD` (CTA/links), light blue `#0BA5E9` (hover/accent), green `#63A434` (secondary accent), light panel `#E1EFF8`.
- Fonts: **Archivo** (headings, weight 500–900) and **Libre Franklin** (body, weight 400–700), both loaded from Google Fonts in each page's `<helmet>`.
- All styling is inline (`style="..."`) by convention in this file format , no external stylesheet.
- Accessibility: skip-to-content link + `:focus-visible` outlines added in `nav.dc.html`/`footer.dc.html` and each page's own `<style>` block; all interactive elements are ≥44px tall.
- Accessibility review completed August 17, 2026 across all 15 public routes at 1440px desktop and 375px mobile widths. Pages declare English, expose one main landmark and one H1, retain visible keyboard focus, provide labels and image alternatives, meet text contrast checks, and render without horizontal overflow. The mobile menu exposes its expanded state and changes its accessible name between Open menu and Close menu.

## Managed content

The temporary preview editor at `/admin/` can update these files after Netlify-specific authentication is activated:

- `assets/content/endorsements.json`
- `assets/content/resolutions.json`
- `assets/content/opportunities.json`
- `assets/pco-roster.json`

Each public page retains embedded fallback data where appropriate. Keep fallback arrays synchronized when changing page architecture. The permanent host needs a replacement authentication and publishing method for Decap CMS, a host-native editor, or a documented GitHub workflow.

## Events system (`assets/events-data.js`)

The shared module supplies events to the homepage, events page, and volunteer page:

1. Live Google Calendar events when `GOOGLE_API_KEY` is configured.
2. A standing second-Wednesday meeting at 6:00 PM on Zoom when live calendar access is unavailable.
3. Approved opportunities from `assets/content/opportunities.json`.

Generated meeting dates are labeled as standing-schedule dates until live calendar access is connected.

## Forms
All forms (membership, SMS opt-in, contact, volunteer, home-page email signup) are wired for **Netlify Forms in the temporary preview**:
- Each `<form>` has `data-netlify="true"`, a `name`, a hidden `form-name` input, and a honeypot field (`bot-field`) for spam.
- On submit, JS does `fetch('/', { method: 'POST', body: new URLSearchParams(new FormData(form)) })` to Netlify's form-handling endpoint, then shows a success state.
- **This works only on Netlify.** Before migrating, replace it with a PHP endpoint, WordPress form service, or another approved form provider. Test storage, delivery, spam protection, privacy, retention, and SMS consent on the permanent host.

## Migration requirements

1. Confirm the hosting provider, account owner, plan, control panel, SFTP access, domain access, and DNS access.
2. Back up the current WordPress files and database.
3. Confirm LiteSpeed serves `.dc.html` files as `text/html`, adding a MIME rule if needed.
4. Choose the permanent GitHub-to-host deployment method.
5. Replace Netlify Identity and Git Gateway or select a different editor workflow.
6. Replace Netlify Forms and test all five forms.
7. Remove or replace the root redirect to `/wp/`.
8. Decide whether to retain the old WordPress installation as an archive.
9. Test HTTPS, routes, forms, editor access, mobile behavior, and rollback before cutover.

## Remaining owner actions

1. Identify the exact current hosting provider or reseller and obtain account access.
2. Choose the permanent deployment, editor, and form architecture.
3. Obtain Google Calendar access from Katie and configure a restricted API key in `assets/events-data.js`.
4. Complete backups, migration testing, and production cutover.

Do not add photos or bios beside executive board members. That section is intentionally text-only.

## Assets
- `assets/ld48-emblem.png`, `assets/brand/LD48_Logo_Lockup*.png` , logo lockups.
- `assets/events-data.js`, event feed logic.
- `assets/content/`, CMS-managed endorsements, resolutions, and opportunities.
- `assets/pco-roster.json`, the complete 165-precinct public roster snapshot dated July 31, 2026.
- Google Fonts (Archivo, Libre Franklin) loaded via CDN `<link>`, not bundled.
