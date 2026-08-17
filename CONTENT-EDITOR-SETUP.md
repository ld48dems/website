# Content editor setup

This is a one-time owner setup. After it is complete, routine website updates happen at `https://www.ld48dems.org/admin/` and publish automatically.

The preview at `https://ld48dems-preview.netlify.app` already deploys from the shared `ld48dems/website` GitHub repository. It currently belongs to the `cockrellio` Netlify workspace and must be transferred to an LD 48-owned Netlify team before launch. Until Identity and Git Gateway are enabled and editors are invited, the editor login screen may load but editors cannot publish updates.

## What is already built

- Browser editor for endorsements and their archive.
- Browser editor for resolutions.
- Browser editor for volunteer opportunities.
- Browser editor for the complete PCO roster.
- Version history and rollback through GitHub.
- Automatic Netlify deployment after an editor clicks **Publish**.

Approved future volunteer opportunities use the site's shared event feed. The next 5 appear automatically on the homepage, and the next 8 appear on the Events page.

## One-time setup

1. Confirm at least two LD 48 leaders are owners of the `ld48dems/website` GitHub repository.
2. Create or select an LD 48-owned Netlify team with at least two owners.
3. Transfer the `ld48dems-preview` Netlify site from the `cockrellio` workspace to the LD 48-owned team.
4. In Netlify, open **Site configuration** > **Build & deploy** > **Continuous deployment** and confirm the site is linked to `ld48dems/website`, deploys the `main` branch, and uses `.` as the publish directory.
5. Select **Site configuration** > **Identity** > **Enable Identity**.
6. Under **Registration preferences**, select **Invite only**.
7. Under **Services**, enable **Git Gateway**.
8. Open **Identity** > **Invite users** and invite each approved website editor.
9. Confirm an invited editor can sign in at `/admin/`, change a test volunteer opportunity, publish it, and see the Netlify deploy complete.
10. Add the production domain only after ownership, editor publishing, forms, and rollback have been tested.

## Ownership rules

- Keep at least two LD 48 owners on GitHub and Netlify.
- Give editor access only to people authorized to publish for LD 48.
- Remove access when a volunteer leaves the role.
- Use the editor for routine content. Do not edit the HTML pages for endorsements, resolutions, volunteer opportunities, or the PCO roster.