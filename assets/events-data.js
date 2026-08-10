// Shared event feed for LD 48 pages: home, get-involved, volunteer, events.
//
// LAYER 1 , your own meetings, automatically computed from the standing schedule below.
// LAYER 2 , your own calendar, live, once you add a Google Calendar API key (optional).
// LAYER 3 , approved events and partner actions from assets/content/opportunities.json.
//
// To turn on live Google Calendar sync:
//   1. Google Calendar > Settings > [your calendar] > Access permissions > "Make available to public"
//   2. Google Cloud Console > APIs & Services > Credentials > Create API key,
//      restrict it to "Calendar API" and to your Netlify domain (HTTP referrers).
//   3. Paste the calendar ID and key below. Until then, the standing meeting rule
//      (computed live) covers meetings, and opportunities.json covers everything else.
export const GOOGLE_CALENDAR_ID = '48thdems@gmail.com';
export const GOOGLE_API_KEY = '';

const MEETING_RULE = {
  weekday: 3, // 0=Sun ... 3=Wed
  nth: 2,     // 2nd of the month
  hour: 18,
  minute: 0,
  title: 'Monthly general meeting',
  where: 'Zoom (link emailed to members)',
  cta: 'Get the Zoom link',
  href: 'become-member.dc.html',
};

function nthWeekdayOfMonth(year, month, weekday, nth) {
  const first = new Date(year, month, 1);
  const offset = (7 + weekday - first.getDay()) % 7;
  return new Date(year, month, 1 + offset + 7 * (nth - 1), MEETING_RULE.hour, MEETING_RULE.minute);
}

export function upcomingMeetings(count = 6) {
  const out = [];
  const now = new Date();
  let y = now.getFullYear(), m = now.getMonth();
  let guard = 0;
  while (out.length < count && guard < 36) {
    const d = nthWeekdayOfMonth(y, m, MEETING_RULE.weekday, MEETING_RULE.nth);
    if (d > now) {
      out.push({
        id: 'meeting-' + d.toISOString(),
        type: 'meeting',
        title: MEETING_RULE.title,
        start: d,
        where: MEETING_RULE.where,
        cta: MEETING_RULE.cta,
        href: MEETING_RULE.href,
        source: 'LD 48 Democrats',
      });
    }
    m++; if (m > 11) { m = 0; y++; }
    guard++;
  }
  return out;
}

export async function fetchCuratedActions() {
  try {
    const response = await fetch('./assets/content/opportunities.json');
    if (!response.ok) return [];
    const data = await response.json();
    return (data.opportunities || []).map((item) => ({
      ...item,
      type: 'action',
      start: new Date(item.start),
    }));
  } catch {
    return [];
  }
}

export async function fetchOwnCalendarEvents(count = 8) {
  if (!GOOGLE_API_KEY) return [];
  try {
    const params = new URLSearchParams({
      key: GOOGLE_API_KEY,
      singleEvents: 'true',
      orderBy: 'startTime',
      timeMin: new Date().toISOString(),
      maxResults: String(count),
    });
    const res = await fetch(`https://www.googleapis.com/calendar/v3/calendars/${encodeURIComponent(GOOGLE_CALENDAR_ID)}/events?${params}`);
    if (!res.ok) return [];
    const data = await res.json();
    return (data.items || []).map((ev) => ({
      id: ev.id,
      type: /meeting/i.test(ev.summary || '') ? 'meeting' : 'shift',
      title: ev.summary || 'Untitled event',
      start: new Date((ev.start && (ev.start.dateTime || ev.start.date)) || Date.now()),
      where: ev.location || '',
      href: ev.htmlLink || '#',
      cta: 'Details',
      source: 'LD 48 Democrats',
    }));
  } catch {
    return [];
  }
}

// Merged, sorted, upcoming-only feed. Falls back gracefully at every layer:
// live calendar -> computed meeting rule, always plus approved partner actions.
export async function getAllEvents({ count = 8 } = {}) {
  const now = new Date();
  const own = await fetchOwnCalendarEvents(count);
  const meetings = own.length ? own : upcomingMeetings(3);
  const curated = await fetchCuratedActions();
  const actions = curated.filter((action) => action.approved && action.start > now);
  return [...meetings, ...actions].sort((a, b) => a.start - b.start).slice(0, count);
}

export function nextMeeting(events) {
  return events.find((e) => e.type === 'meeting') || upcomingMeetings(1)[0];
}

export function fmtDate(d) {
  return { mon: d.toLocaleString('en-US', { month: 'short' }), day: String(d.getDate()).padStart(2, '0') };
}

export function fmtWhen(d) {
  return d.toLocaleString('en-US', { weekday: 'long', month: 'short', day: 'numeric', hour: 'numeric', minute: d.getMinutes() ? '2-digit' : undefined });
}
