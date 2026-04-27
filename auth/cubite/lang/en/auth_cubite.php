<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Cubite SSO';
$string['auth_cubitedescription'] = 'Enables seamless single sign-on from Cubite storefronts. Students are automatically logged in when they click "Go to Course" on their Cubite storefront.';
$string['sharedsecret'] = 'Shared Secret';
$string['sharedsecret_desc'] = 'The shared secret used to verify login tokens from Cubite. Copy this from your Cubite site settings (Moodle tab > SSO section).';
$string['invalidtoken'] = 'Invalid or expired login token.';
$string['missingtoken'] = 'No login token provided.';
$string['backto'] = 'Back to {$a}';
$string['storefronturl'] = 'Storefront URL';
$string['storefronturl_desc'] = 'The URL of your Cubite storefront (e.g. https://academy.example.com). Users hitting Moodle\'s login or signup pages will be redirected here.';
$string['adminbypasstoken'] = 'Admin bypass token';
$string['adminbypasstoken_desc'] = 'A random secret token that allows admins to log into Moodle directly, bypassing the Cubite SSO redirect. Generate this in your Cubite site settings — admins use the URL: {moodleurl}/login/index.php?bypass={token}';
