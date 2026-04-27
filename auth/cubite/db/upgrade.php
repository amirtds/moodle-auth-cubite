<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Apply the catalog lockdown when an existing install upgrades to >= 1.3.0.
 * Reuses the helper from db/install.php so install and upgrade behave identically.
 */
function xmldb_auth_cubite_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026042706) {
        require_once(__DIR__ . '/install.php');
        auth_cubite_apply_lockdown();
        upgrade_plugin_savepoint(true, 2026042706, 'auth', 'cubite');
    }

    // Reverse the visible=0 sweep applied by 1.3.0. Hidden courses block enrolled
    // students from accessing the content — we rely on the after_config hook to
    // block catalog browsing instead, so visibility itself stays at the default.
    if ($oldversion < 2026042708) {
        $DB->execute("UPDATE {course} SET visible = 1 WHERE id != ?", [SITEID]);
        upgrade_plugin_savepoint(true, 2026042708, 'auth', 'cubite');
    }

    return true;
}
