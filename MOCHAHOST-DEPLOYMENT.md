# Mochahost staging deployment

The browser editor remains at <https://ld48dems-preview.netlify.app/admin/>. Authorized editor changes commit structured content to GitHub `main`. The workflow in `.github/workflows/deploy-mochahost.yml` then validates and deploys the public website to `/public_html/susan/` over FTPS.

## One-time GitHub setup

Create a GitHub environment named `mochahost-staging`. Add these environment secrets:

- `MOCHAHOST_FTP_SERVER`: the Mochahost FTP hostname
- `MOCHAHOST_FTP_USERNAME`: the dedicated FTP user rooted at the staging website
- `MOCHAHOST_FTP_PASSWORD`: that FTP user's password

The configured `ld48-deploy` FTP account is restricted to `/public_html/susan/`, and the workflow deploys to that account's `./` root. Do not broaden the FTP account's directory access.

Require reviewer approval on the environment if content changes should be reviewed before production deployment.

## Files intentionally kept outside deployment

The workflow never uploads `private-config.template.php`. The real form configuration remains at `/home/lddemsor/ld48-private/config.php`, outside `public_html`, with mode `0600`.

Netlify functions and settings remain available only to the temporary preview. The same HTML files select Netlify form handling on `*.netlify.app` and Mochahost's `./api/forms.php` everywhere else.

## Verification after setup

1. Run **Deploy to Mochahost staging** from the GitHub Actions tab.
2. Confirm the workflow succeeds.
3. Open <https://ld48dems.org/susan/home.dc.html> and confirm the current meeting strip appears.
4. Open <https://ld48dems.org/susan/admin/> and confirm it redirects to the editor.
5. Publish a harmless reviewed content change in the editor.
6. Confirm the GitHub workflow runs and the change appears under `/susan/`.
7. Revert the test change through the editor if it should not remain public.
