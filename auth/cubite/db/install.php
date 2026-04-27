<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Plugin install hook — locks Moodle down so it acts purely as a learning delivery
 * engine, with Cubite as the storefront/commerce source of truth.
 *
 * Disables self/guest enrollment globally so students can't bypass the storefront.
 * Catalog pages (/course/index.php etc) are blocked at runtime by the after_config
 * hook in classes/hook_callbacks.php — courses stay visible=1 so enrolled students
 * can access them via direct URL.
 */
function xmldb_auth_cubite_install() {
    auth_cubite_apply_lockdown();
    return true;
}

function auth_cubite_apply_lockdown() {
    global $CFG;

    $enabled = explode(',', $CFG->enrol_plugins_enabled ?? '');
    $enabled = array_filter($enabled, function($p) {
        return !in_array(trim($p), ['self', 'guest'], true);
    });
    set_config('enrol_plugins_enabled', implode(',', $enabled));
}
