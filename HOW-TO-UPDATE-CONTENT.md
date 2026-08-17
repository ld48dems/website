# How to update the LD 48 website

## How events work

The homepage and Events page use the same event list. You do not add an event separately to the homepage.

- The homepage automatically shows the next 5 approved events in date order.
- The Events page automatically shows the next 8 approved events in date order.
- An approved event appears in both places if it falls within those limits.
- When an event's start time passes, it disappears automatically.
- The monthly general meeting is generated automatically as the second Wednesday at 6:00 PM on Zoom until the Google Calendar connection is enabled.

Example: If seven future events are approved, the first five appear on the homepage and all seven appear on the Events page.

## Before editors can publish

The browser editor is built in the temporary Netlify preview, but its current authentication and publishing method will not move automatically to the permanent web host. See `CONTENT-EDITOR-SETUP.md` for the migration options. Until a permanent editor workflow is selected and configured, `/admin/` may show a login button but cannot publish production changes.

## Sign in

1. During preview, go to `https://ld48dems-preview.netlify.app/admin/`.
2. Sign in only if temporary Netlify Identity access has been activated.
3. Open **Website content**.

## Update endorsements

1. Select **Endorsements**.
2. Update the election cycle if needed.
3. Add, edit, reorder, or remove entries under the correct category.
4. Add past endorsement documents to the archive after an election cycle closes.
5. Select **Publish**.

## Add a resolution

1. Select **Resolutions**.
2. Select **Add adopted resolutions**.
3. Enter the year, adoption date, exact adopted title, and public Google document URL.
4. Put newest resolutions first.
5. Select **Publish**.

## Add an event or volunteer opportunity

1. Select **Volunteer opportunities**.
2. Select **Add current opportunities**.
3. Enter a unique internal ID, such as `action-2026-09-12-voter-registration`.
4. Enter the public event title.
5. Enter the start date and time.
6. Enter the location or `Online`.
7. Enter the source organization, such as `LD 48 Democrats`.
8. Enter the public details or signup URL.
9. Enter the button text, such as `Register`, `RSVP`, or `Details`.
10. Turn on **Approved to publish** only after the event has been reviewed.
11. Select **Publish**.

After Netlify finishes publishing, the event is automatically considered for both the homepage and Events page. There is no separate homepage setting.

Expired opportunities disappear from the public site automatically after their start time. Delete old records during a monthly cleanup.

## Events from Google Calendar

After the Google Calendar connection is enabled, future events from `48thdems@gmail.com` automatically join the same shared feed. Add or update the event in Google Calendar, and the website reflects the calendar data without a second website entry.

The calendar connection is not enabled yet because the Google Calendar API key is still pending. Until then, use **Volunteer opportunities** for individual events. The standing monthly meeting continues to appear automatically.

## Update the PCO roster

1. Select **PCO roster**.
2. Change the **Last updated** date.
3. Find the precinct and update the name and public email.
4. Use `VACANT` as the name for an open precinct.
5. Select **Publish**.

## Confirm the update

1. Wait for the Netlify notification that the site published successfully.
2. Open the updated public page in a new browser tab.
3. Confirm the new text and links are correct.