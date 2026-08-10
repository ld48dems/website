# Handoff: LD 48 Democrats website

## What this is

This is the deployable LD 48 Democrats website, not a design mockup. It is a static site built from `.dc.html` pages, structured JSON content, and the custom `support.js` runtime. Netlify hosts the public site, processes five forms, and will publish browser-based content edits after the one-time owner setup in `CONTENT-EDITOR-SETUP.md`.

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
| `admin/index.html` | `/admin/` | Decap CMS browser editor; owner activation is still required |

## Design system
- Colors: navy `#011D3E` (primary dark), blue `#0267BD` (CTA/links), light blue `#0BA5E9` (hover/accent), green `#63A434` (secondary accent), light panel `#E1EFF8`.
- Fonts: **Archivo** (headings, weight 500–900) and **Libre Franklin** (body, weight 400–700), both loaded from Google Fonts in each page's `<helmet>`.
- All styling is inline (`style="..."`) by convention in this file format , no external stylesheet.
- Accessibility: skip-to-content link + `:focus-visible` outlines added in `nav.dc.html`/`footer.dc.html` and each page's own `<style>` block; all interactive elements are ≥44px tall.

## Managed content

Routine editors use `/admin/`. The CMS updates these files:

- `assets/content/endorsements.json`
- `assets/content/resolutions.json`
- `assets/content/opportunities.json`
- `assets/pco-roster.json`

Each public page retains embedded fallback data where appropriate. Keep fallback arrays synchronized when changing page architecture. Routine editors should not edit HTML or JSON directly.

## Events system (`assets/events-data.js`)

The shared module supplies events to the homepage, events page, and volunteer page:

1. Live Google Calendar events when `GOOGLE_API_KEY` is configured.
2. A standing second-Wednesday meeting at 6:00 PM on Zoom when live calendar access is unavailable.
3. Approved opportunities from `assets/content/opportunities.json`.

Generated meeting dates are labeled as standing-schedule dates until live calendar access is connected.

## Forms
All forms (membership, SMS opt-in, contact, volunteer, home-page email signup) are wired for **Netlify Forms**:
- Each `<form>` has `data-netlify="true"`, a `name`, a hidden `form-name` input, and a honeypot field (`bot-field`) for spam.
- On submit, JS does `fetch('/', { method: 'POST', body: new URLSearchParams(new FormData(form)) })` to Netlify's form-handling endpoint, then shows a success state.
- **This only works once deployed on Netlify** (or you swap in a different form backend). Netlify auto-detects these forms from the static HTML at deploy time , no server code needed. Submissions + CSV export live in the Netlify site dashboard → Forms tab; email notifications per-form are configured there too (Forms → Form notifications).

## Remaining owner actions

1. Obtain Google Calendar access from Katie and configure a restricted API key in `assets/events-data.js`.
2. Complete the shared GitHub repository, Netlify continuous deployment, Identity, Git Gateway, and editor invitations in `CONTENT-EDITOR-SETUP.md`.
3. Confirm Netlify form-notification recipients in **Forms** > **Form notifications**.

Do not add photos or bios beside executive board members. That section is intentionally text-only.

## Assets
- `assets/ld48-emblem.png`, `assets/brand/LD48_Logo_Lockup*.png` , logo lockups.
- `assets/events-data.js`, event feed logic.
- `assets/content/`, CMS-managed endorsements, resolutions, and opportunities.
- `assets/pco-roster.json`, the complete 165-precinct public roster snapshot dated July 31, 2026.
- Google Fonts (Archivo, Libre Franklin) loaded via CDN `<link>`, not bundled.
