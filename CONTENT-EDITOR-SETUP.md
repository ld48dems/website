# Content editor and production publishing

The browser editor uses Netlify Identity and Git Gateway at `https://ld48dems-preview.netlify.app/admin/`. Published edits commit structured content to GitHub `main`.

The GitHub workflow `.github/workflows/deploy-mochahost.yml` deploys validated changes from `main` to Mochahost's `/public_html/susan/` directory. The production `/susan/admin/` route redirects authorized editors to the Netlify-hosted editor. Complete the one-time GitHub environment-secret setup in `MOCHAHOST-DEPLOYMENT.md` before relying on automatic publishing.

## What is already built

- Browser editor for endorsements and their archive.
- Browser editor for resolutions.
- Browser editor for volunteer opportunities.
- Browser editor for the complete PCO roster.
- Version history and rollback through GitHub.
- Automatic Netlify preview deployment after an authenticated editor clicks **Publish**.
- Automatic Mochahost staging deployment from GitHub after the deployment environment is configured.

Approved future volunteer opportunities use the site's shared event feed. The next 5 appear automatically on the homepage, and the next 8 appear on the Events page.

## Publishing flow

1. An authorized editor selects **Publish** in Decap CMS.
2. Git Gateway commits the JSON change to GitHub `main`.
3. Netlify updates the preview.
4. GitHub Actions validates the site and deploys public files to Mochahost staging.
5. The editor verifies the change at `https://ld48dems.org/susan/`.

If the Netlify preview is retired later, replace authentication before removing it. The structured JSON files and GitHub deployment workflow can remain unchanged.

## Ownership rules

- Keep at least two LD 48 owners on GitHub, the permanent hosting account, and the domain/DNS account.
- Give editor access only to people authorized to publish for LD 48.
- Remove access when a volunteer leaves the role.
- Keep routine content in the structured JSON files, whether updates happen through Decap CMS, GitHub, or a replacement editor.