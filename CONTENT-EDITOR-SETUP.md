# Content editor setup

This is a one-time owner setup. After it is complete, routine website updates happen at `https://www.ld48dems.org/admin/` and publish automatically.

Until this setup is complete, the editor login screen may load, but editors cannot publish updates. The current Netlify preview was deployed directly and is not yet backed by the shared GitHub publishing workflow.

## What is already built

- Browser editor for endorsements and their archive.
- Browser editor for resolutions.
- Browser editor for volunteer opportunities.
- Browser editor for the complete PCO roster.
- Version history and rollback through GitHub.
- Automatic Netlify deployment after an editor clicks **Publish**.

Approved future volunteer opportunities use the site's shared event feed. The next 5 appear automatically on the homepage, and the next 8 appear on the Events page.

## One-time setup

1. Create a shared GitHub organization or repository owned by LD 48, not an individual volunteer.
2. Put the contents of this `Netlify-preview` folder in that repository's `main` branch.
3. In Netlify, open the LD 48 site.
4. Select **Site configuration** > **Build & deploy** > **Continuous deployment**.
5. Select **Link repository**, choose the LD 48 repository, and set the publish directory to `.`.
6. Select **Site configuration** > **Identity** > **Enable Identity**.
7. Under **Registration preferences**, select **Invite only**.
8. Under **Services**, enable **Git Gateway**.
9. Open **Identity** > **Invite users** and invite each approved website editor.
10. Confirm an invited editor can sign in at `/admin/`, change a test volunteer opportunity, publish it, and see the Netlify deploy complete.

## Ownership rules

- Keep at least two LD 48 owners on GitHub and Netlify.
- Give editor access only to people authorized to publish for LD 48.
- Remove access when a volunteer leaves the role.
- Use the editor for routine content. Do not edit the HTML pages for endorsements, resolutions, volunteer opportunities, or the PCO roster.