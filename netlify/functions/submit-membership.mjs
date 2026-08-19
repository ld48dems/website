import { createHash } from 'node:crypto';

const congressionalDistricts = {
  cd1: 'CD 1 (Representative Suzan Delbene)',
  cd9: 'CD 9 (Representative Adam Smith)',
  'not-ld48': "Don't live in LD 48",
};

const membershipTypes = {
  voting: 'Voting member of LD 48 as I am a resident.',
  'guest-nonresident': 'Guest at LD 48 meetings as I am not an LD 48 resident.',
  'nonvoting-guest': 'Non-Voting Guest at LD 48 meetings.',
};

function jsonResponse(status, body) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

function addIfPresent(target, key, value) {
  const normalized = String(value || '').trim();
  if (normalized) target[key] = normalized;
}

export default async function submitMembership(request) {
  if (request.method !== 'POST') {
    return jsonResponse(405, { error: 'Method not allowed.' });
  }

  const apiKey = Netlify.env.get('MAILCHIMP_API_KEY');
  const audienceId = Netlify.env.get('MAILCHIMP_AUDIENCE_ID');
  const serverPrefix = Netlify.env.get('MAILCHIMP_SERVER_PREFIX');

  if (!apiKey || !audienceId || !serverPrefix) {
    console.error('Mailchimp environment variables are not configured.');
    return jsonResponse(503, { error: 'Membership signup is temporarily unavailable.' });
  }

  const form = await request.formData();
  if (form.get('bot-field')) {
    return jsonResponse(200, { ok: true });
  }

  const email = String(form.get('email') || '').trim().toLowerCase();
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(email)) {
    return jsonResponse(400, { error: 'Please enter a valid email address.' });
  }

  const congressionalDistrict = congressionalDistricts[form.get('congressional_district')];
  const membershipType = membershipTypes[form.get('affirmation')];
  if (!membershipType) {
    return jsonResponse(400, { error: 'Please select a membership type.' });
  }

  const mergeFields = {};
  addIfPresent(mergeFields, 'FNAME', form.get('first_name'));
  addIfPresent(mergeFields, 'LNAME', form.get('last_name'));
  addIfPresent(mergeFields, 'PHONE', form.get('phone'));
  addIfPresent(mergeFields, 'MMERGE5', congressionalDistrict);
  addIfPresent(mergeFields, 'MMERGE6', form.get('precinct'));
  addIfPresent(mergeFields, 'MMERGE7', membershipType);
  addIfPresent(mergeFields, 'MMERGE8', form.get('referred_by'));

  const address = {
    addr1: String(form.get('address1') || '').trim(),
    addr2: String(form.get('address2') || '').trim(),
    city: String(form.get('city') || '').trim(),
    state: String(form.get('state') || '').trim(),
    zip: String(form.get('postal') || '').trim(),
    country: form.get('country') === 'United States of America'
      ? 'US'
      : String(form.get('country') || '').trim(),
  };

  if (address.addr1 && address.city && address.state && address.zip && address.country) {
    mergeFields.ADDRESS = address;
  }

  const subscriberHash = createHash('md5').update(email).digest('hex');
  const url = `https://${serverPrefix}.api.mailchimp.com/3.0/lists/${audienceId}/members/${subscriberHash}`;
  const response = await fetch(url, {
    method: 'PUT',
    headers: {
      Authorization: `Basic ${Buffer.from(`ld48:${apiKey}`).toString('base64')}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      email_address: email,
      status_if_new: 'subscribed',
      merge_fields: mergeFields,
    }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    console.error('Mailchimp membership submission failed.', {
      status: response.status,
      title: error.title,
      detail: error.detail,
    });
    return jsonResponse(502, { error: 'We could not complete your membership signup. Please try again.' });
  }

  return jsonResponse(200, { ok: true });
}

export const config = {
  path: '/api/submit-membership',
};