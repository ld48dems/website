<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

function field(string $name, int $maxLength = 5000): string
{
    $value = $_POST[$name] ?? '';
    if (is_array($value)) {
        return '';
    }

    return trim(substr((string) $value, 0, $maxLength));
}

function listField(string $name, int $maxItems = 20): array
{
    $value = $_POST[$name] ?? [];
    $items = is_array($value) ? $value : [$value];

    return array_values(array_filter(array_map(
        static fn($item): string => trim(substr((string) $item, 0, 200)),
        array_slice($items, 0, $maxItems)
    )));
}

function validEmail(string $email): bool
{
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function requireSameOrigin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($origin === '' || $host === '') {
        return;
    }

    $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
    if ($originHost !== $host) {
        respond(403, ['error' => 'This form must be submitted from the LD 48 website.']);
    }
}

function enforceRateLimit(): void
{
    $client = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . 'ld48-form-' . hash('sha256', $client) . '.json';
    $now = time();
    $windowStart = $now - 600;
    $timestamps = [];

    $handle = @fopen($path, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return;
    }

    $raw = stream_get_contents($handle);
    $stored = json_decode($raw ?: '[]', true);
    if (is_array($stored)) {
        $timestamps = array_values(array_filter($stored, static fn($stamp): bool => is_int($stamp) && $stamp >= $windowStart));
    }

    if (count($timestamps) >= 12) {
        flock($handle, LOCK_UN);
        fclose($handle);
        respond(429, ['error' => 'Too many submissions. Please wait a few minutes and try again.']);
    }

    $timestamps[] = $now;
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($timestamps));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function loadConfig(): array
{
    $configuredPath = getenv('LD48_CONFIG_PATH');
    $documentRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
    $defaultPath = dirname($documentRoot) . DIRECTORY_SEPARATOR . 'ld48-private' . DIRECTORY_SEPARATOR . 'config.php';
    $path = $configuredPath !== false && $configuredPath !== '' ? $configuredPath : $defaultPath;

    if (!is_file($path)) {
        error_log('LD48 forms configuration file was not found.');
        respond(503, ['error' => 'Form service is not configured yet. Please email 48thdems@gmail.com.']);
    }

    $config = require $path;
    if (!is_array($config)) {
        error_log('LD48 forms configuration file did not return an array.');
        respond(503, ['error' => 'Form service is temporarily unavailable. Please email 48thdems@gmail.com.']);
    }

    return $config;
}

function configValue(array $config, array $path): string
{
    $value = $config;
    foreach ($path as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return '';
        }
        $value = $value[$key];
    }

    return is_scalar($value) ? trim((string) $value) : '';
}

function addIfPresent(array &$target, string $key, string $value): void
{
    if ($value !== '') {
        $target[$key] = $value;
    }
}

function mailchimpUpsert(array $config, string $email, array $mergeFields = []): void
{
    $apiKey = configValue($config, ['mailchimp', 'api_key']);
    $audienceId = configValue($config, ['mailchimp', 'audience_id']);
    $serverPrefix = configValue($config, ['mailchimp', 'server_prefix']);

    if ($apiKey === '' || $audienceId === '' || !preg_match('/^[a-z0-9-]+$/i', $serverPrefix)) {
        error_log('LD48 Mailchimp configuration is incomplete.');
        respond(503, ['error' => 'Mailing-list signup is temporarily unavailable. Please email 48thdems@gmail.com.']);
    }
    if (!function_exists('curl_init')) {
        error_log('LD48 forms require the PHP cURL extension.');
        respond(503, ['error' => 'Mailing-list signup is temporarily unavailable.']);
    }

    $subscriberHash = md5(strtolower($email));
    $url = sprintf(
        'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s',
        rawurlencode($serverPrefix),
        rawurlencode($audienceId),
        $subscriberHash
    );
    $payload = [
        'email_address' => $email,
        'status_if_new' => 'subscribed',
    ];
    if ($mergeFields !== []) {
        $payload['merge_fields'] = $mergeFields;
    }

    $request = curl_init($url);
    curl_setopt_array($request, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode('ld48:' . $apiKey),
            'Content-Type: application/json',
            'User-Agent: LD48-Website/1.0',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $responseBody = curl_exec($request);
    $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($request);
    curl_close($request);

    if ($responseBody === false || $status < 200 || $status >= 300) {
        $mailchimpError = json_decode(is_string($responseBody) ? $responseBody : '', true);
        $detail = is_array($mailchimpError) && isset($mailchimpError['detail'])
            ? trim(substr((string) $mailchimpError['detail'], 0, 500))
            : '';
        $fieldErrors = is_array($mailchimpError) && isset($mailchimpError['errors']) && is_array($mailchimpError['errors'])
            ? json_encode($mailchimpError['errors'], JSON_UNESCAPED_SLASHES)
            : '[]';
        error_log(sprintf('LD48 Mailchimp request failed with status %d: %s %s fields=%s', $status, $curlError, $detail, $fieldErrors));
        respond(502, ['error' => $detail !== '' ? $detail : 'Mailchimp could not accept the signup. Please try again.']);
    }
}

function notificationBody(string $title, array $fields): string
{
    $lines = [$title, str_repeat('=', strlen($title)), ''];
    foreach ($fields as $label => $value) {
        $display = is_array($value) ? implode(', ', $value) : trim((string) $value);
        $lines[] = $label . ': ' . ($display !== '' ? $display : '(not provided)');
    }
    $lines[] = '';
    $lines[] = 'Submitted: ' . gmdate('Y-m-d H:i:s') . ' UTC';
    $lines[] = 'Source: ' . substr((string) ($_SERVER['HTTP_REFERER'] ?? 'LD48 website'), 0, 500);

    return implode("\n", $lines);
}

function sendNotification(array $config, string $recipientKey, string $subject, string $body, string $replyTo): void
{
    $to = configValue($config, ['mail', $recipientKey]);
    $from = configValue($config, ['mail', 'from_email']);
    if (!validEmail($to) || !validEmail($from)) {
        error_log('LD48 email notification configuration is incomplete.');
        respond(503, ['error' => 'This form is temporarily unavailable. Please email 48thdems@gmail.com.']);
    }

    $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
    $headers = [
        'From: LD48 Website <' . $from . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: LD48 Website',
    ];
    if (validEmail($replyTo)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    if (!mail($to, $safeSubject, $body, implode("\r\n", $headers), '-f' . $from)) {
        error_log('LD48 form notification could not be handed to the hosting mail service.');
        respond(502, ['error' => 'We could not send your submission. Please email 48thdems@gmail.com.']);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['error' => 'Method not allowed.']);
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 100000) {
    respond(413, ['error' => 'Submission is too large.']);
}
if (field('bot-field', 200) !== '') {
    respond(200, ['ok' => true]);
}

requireSameOrigin();
enforceRateLimit();

$formName = field('form-name', 80);
$allowedForms = ['home-email-signup', 'membership', 'contact', 'volunteer', 'sms-optin'];
if (!in_array($formName, $allowedForms, true)) {
    respond(400, ['error' => 'Unknown form.']);
}

$config = loadConfig();

if ($formName === 'home-email-signup') {
    $email = strtolower(field('email', 320));
    $firstName = field('first_name', 100);
    $lastName = field('last_name', 100);
    if (!validEmail($email)) {
        respond(400, ['error' => 'Please enter a valid email address.']);
    }
    if ($firstName === '' || $lastName === '') {
        respond(400, ['error' => 'Please enter your first and last name.']);
    }
    mailchimpUpsert($config, $email, ['FNAME' => $firstName, 'LNAME' => $lastName]);
    respond(200, ['ok' => true]);
}

if ($formName === 'membership') {
    $email = strtolower(field('email', 320));
    $firstName = field('first_name', 100);
    $lastName = field('last_name', 100);
    if (!validEmail($email)) {
        respond(400, ['error' => 'Please enter a valid email address.']);
    }
    if ($firstName === '' || $lastName === '') {
        respond(400, ['error' => 'Please enter your first and last name.']);
    }

    $congressionalDistricts = [
        'cd1' => 'CD 1 (Representative Suzan DelBene)',
        'cd9' => 'CD 9 (Representative Adam Smith)',
        'not-ld48' => "Don't live in LD 48",
    ];
    $membershipTypes = [
        'voting' => 'Voting member of LD 48 as I am a resident.',
        'guest-nonresident' => 'Guest at LD 48 meetings as I am not an LD 48 resident.',
        'nonvoting-guest' => 'Non-Voting Guest at LD 48 meetings.',
    ];
    $membershipType = $membershipTypes[field('affirmation', 80)] ?? '';
    if ($membershipType === '') {
        respond(400, ['error' => 'Please select a membership type.']);
    }

    $mergeFields = [];
    addIfPresent($mergeFields, 'FNAME', $firstName);
    addIfPresent($mergeFields, 'LNAME', $lastName);
    addIfPresent($mergeFields, 'PHONE', field('phone', 60));
    addIfPresent($mergeFields, 'MMERGE5', $congressionalDistricts[field('congressional_district', 40)] ?? '');
    addIfPresent($mergeFields, 'MMERGE6', field('precinct', 100));
    addIfPresent($mergeFields, 'MMERGE7', $membershipType);
    addIfPresent($mergeFields, 'MMERGE8', field('referred_by', 200));

    $address = [
        'addr1' => field('address1', 200),
        'addr2' => field('address2', 200),
        'city' => field('city', 100),
        'state' => field('state', 100),
        'zip' => field('postal', 40),
        'country' => field('country', 100) === 'United States of America' ? 'US' : field('country', 100),
    ];
    if ($address['addr1'] !== '' && $address['city'] !== '' && $address['state'] !== '' && $address['zip'] !== '' && $address['country'] !== '') {
        $mergeFields['ADDRESS'] = $address;
    }

    mailchimpUpsert($config, $email, $mergeFields);
    respond(200, ['ok' => true]);
}

if ($formName === 'contact') {
    $name = field('name', 200);
    $email = strtolower(field('email', 320));
    $message = field('message', 10000);
    if ($name === '' || !validEmail($email) || $message === '') {
        respond(400, ['error' => 'Please provide your name, a valid email address, and a message.']);
    }
    $subject = field('subject', 200);
    sendNotification(
        $config,
        'contact_to',
        '[LD48 website] ' . ($subject !== '' ? $subject : 'New contact message'),
        notificationBody('New LD48 website contact message', [
            'Name' => $name,
            'Email' => $email,
            'Subject' => $subject,
            'Message' => $message,
        ]),
        $email
    );
    respond(200, ['ok' => true]);
}

if ($formName === 'volunteer') {
    $name = field('name', 200);
    $email = strtolower(field('email', 320));
    if ($name === '' || !validEmail($email)) {
        respond(400, ['error' => 'Please provide your name and a valid email address.']);
    }
    sendNotification(
        $config,
        'volunteer_to',
        '[LD48 website] New volunteer signup',
        notificationBody('New LD48 volunteer signup', [
            'Name' => $name,
            'Email' => $email,
            'Phone' => field('phone', 60),
            'Interests' => listField('interest'),
        ]),
        $email
    );
    respond(200, ['ok' => true]);
}

$smsEmail = strtolower(field('sms_email', 320));
$smsPhone = field('sms_phone', 60);
if ($smsPhone === '' || field('sms_optin', 80) === '') {
    respond(400, ['error' => 'Please provide a phone number and confirm SMS consent.']);
}
if ($smsEmail !== '' && !validEmail($smsEmail)) {
    respond(400, ['error' => 'Please enter a valid email address or leave it blank.']);
}
sendNotification(
    $config,
    'sms_to',
    '[LD48 website] New SMS opt-in',
    notificationBody('New LD48 SMS opt-in', [
        'Name' => field('sms_name', 200),
        'Email' => $smsEmail,
        'Phone' => $smsPhone,
        'Consent' => field('sms_optin', 80),
    ]),
    $smsEmail
);
respond(200, ['ok' => true]);
