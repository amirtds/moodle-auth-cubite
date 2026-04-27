<?php
require_once(__DIR__ . '/../../config.php');

$token = required_param('token', PARAM_RAW);

// Decode JWT (header.payload.signature)
$parts = explode('.', $token);
if (count($parts) !== 3) {
    throw new moodle_exception('invalidtoken', 'auth_cubite');
}

list($headerB64, $payloadB64, $signatureB64) = $parts;

// Validate signature
$secret = get_config('auth_cubite', 'sharedsecret');
if (empty($secret)) {
    throw new moodle_exception('invalidtoken', 'auth_cubite');
}

$expectedSig = rtrim(strtr(base64_encode(
    hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true)
), '+/', '-_'), '=');

if (!hash_equals($expectedSig, $signatureB64)) {
    throw new moodle_exception('invalidtoken', 'auth_cubite');
}

// Decode payload
$payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);
if (!$payload) {
    throw new moodle_exception('invalidtoken', 'auth_cubite');
}

// Validate expiration
if (isset($payload['exp']) && $payload['exp'] < time()) {
    throw new moodle_exception('invalidtoken', 'auth_cubite');
}

$email = $payload['email'] ?? '';
$firstname = $payload['firstName'] ?? '';
$lastname = $payload['lastName'] ?? '';
$courseid = $payload['courseId'] ?? null;
$siteurl = $payload['siteUrl'] ?? '';
$adminbypasstoken = $payload['adminBypassToken'] ?? '';

// Auto-configure plugin settings from JWT payload — no manual setup needed.
// Skip the set_config write when the value is unchanged: set_config busts the
// config cache, so doing it on every SSO would needlessly invalidate the cache.
if (!empty($siteurl) && get_config('auth_cubite', 'storefronturl') !== $siteurl) {
    set_config('storefronturl', $siteurl, 'auth_cubite');
}
if (!empty($adminbypasstoken) && get_config('auth_cubite', 'adminbypasstoken') !== $adminbypasstoken) {
    set_config('adminbypasstoken', $adminbypasstoken, 'auth_cubite');
}

if (empty($email)) {
    throw new moodle_exception('invalidtoken', 'auth_cubite');
}

// Find or create user
$user = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);

if (!$user) {
    // Create new user
    $username = strtolower(preg_replace('/[^a-z0-9._-]/i', '', explode('@', $email)[0]));

    // Ensure unique username
    $baseusername = $username;
    $counter = 1;
    while ($DB->record_exists('user', ['username' => $username])) {
        $username = $baseusername . $counter;
        $counter++;
    }

    $user = create_user_record($username, '', 'cubite');
    $user->email = $email;
    $user->firstname = $firstname ?: 'User';
    $user->lastname = $lastname ?: '';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $DB->update_record('user', $user);
    $user = $DB->get_record('user', ['id' => $user->id]);
} else {
    // Refresh profile data from Cubite on every SSO and pin auth to 'cubite' so the
    // profile-lock + sync behaviour applies. Existing users created via the Moodle
    // webservice API land here with auth='manual'; this is where they get migrated.
    $needsupdate = false;
    if ($user->auth !== 'cubite') {
        $user->auth = 'cubite';
        $needsupdate = true;
    }
    if ($firstname && $user->firstname !== $firstname) {
        $user->firstname = $firstname;
        $needsupdate = true;
    }
    if ($lastname && $user->lastname !== $lastname) {
        $user->lastname = $lastname;
        $needsupdate = true;
    }
    if ($needsupdate) {
        $DB->update_record('user', $user);
        $user = $DB->get_record('user', ['id' => $user->id]);
    }
}

complete_user_login($user);

if ($courseid) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else {
    redirect(new moodle_url('/my/'));
}
