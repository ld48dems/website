# Content editor status and migration

The browser editor is built, but its current authentication and publishing configuration is specific to the temporary Netlify preview. Netlify is not the intended permanent host.

The preview at `https://ld48dems-preview.netlify.app` deploys from the shared `ld48dems/website` GitHub repository. The editor interface is at `/admin/`, but it cannot publish unless Netlify Identity and Git Gateway are temporarily activated.

## What is already built

- Browser editor for endorsements and their archive.
- Browser editor for resolutions.
- Browser editor for volunteer opportunities.
- Browser editor for the complete PCO roster.
- Version history and rollback through GitHub.
- Automatic Netlify preview deployment after an authenticated editor clicks **Publish**.

Approved future volunteer opportunities use the site's shared event feed. The next 5 appear automatically on the homepage, and the next 8 appear on the Events page.

## Permanent editor decision

Before moving the site to the permanent web host, choose one of these approaches:

1. Keep Decap CMS and add a non-Netlify GitHub OAuth authentication service.
2. Use a host-native content-management tool that writes the required JSON files.
3. Use GitHub's browser editor with review before merging.
4. Assign a technical publisher to make requested updates and deploy releases.
5. Rebuild in the permanent host's preferred CMS if editors need visual control over every page.

The final admin URL, login method, approval workflow, and deployment behavior depend on this decision. Do not promise `https://www.ld48dems.org/admin/` as the permanent editor until migration testing is complete.

## Ownership rules

- Keep at least two LD 48 owners on GitHub, the permanent hosting account, and the domain/DNS account.
- Give editor access only to people authorized to publish for LD 48.
- Remove access when a volunteer leaves the role.
- Keep routine content in the structured JSON files, whether updates happen through Decap CMS, GitHub, or a replacement editor.