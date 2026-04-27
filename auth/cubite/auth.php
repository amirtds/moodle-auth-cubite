<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

class auth_plugin_cubite extends auth_plugin_base {

    public function __construct() {
        $this->authtype = 'cubite';
        $this->config = get_config('auth_cubite');
    }

    public function user_login($username, $password) {
        // Login is handled by login.php, not by username/password.
        return false;
    }

    public function is_internal() {
        return false;
    }

    public function can_change_password() {
        return false;
    }

    public function can_reset_password() {
        return false;
    }

    public function can_signup() {
        return false;
    }

    public function can_confirm() {
        return false;
    }

    public function can_be_manually_set() {
        return true;
    }

    /**
     * Indicates user data is synced from an external source (Cubite).
     * This makes Moodle render firstname/lastname/email as read-only on the profile page.
     */
    public function is_synchronised_with_external() {
        return true;
    }

    /**
     * Returns fields that are locked (read-only) for cubite-auth users.
     * Moodle uses this to disable editing on the profile form.
     */
    public function get_userinfo_lock_fields() {
        return ['firstname', 'lastname', 'email'];
    }

    /**
     * Prevent local password storage — Cubite is the password authority.
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Redirect Moodle's login page to the Cubite storefront login.
     * Admins can bypass this with ?bypass={token} matching the configured token.
     */
    public function loginpage_hook() {
        $config = get_config('auth_cubite');
        $storefronturl = trim($config->storefronturl ?? '');
        if (empty($storefronturl)) {
            return;
        }

        $bypasstoken = trim($config->adminbypasstoken ?? '');
        $bypass = optional_param('bypass', '', PARAM_ALPHANUM);
        if (!empty($bypasstoken) && $bypass === $bypasstoken) {
            return;
        }

        // Don't loop if we're already on a Moodle URL coming back from Cubite.
        if (optional_param('token', '', PARAM_RAW)) {
            return;
        }

        redirect($storefronturl . '/auth/signin');
    }

    /**
     * Redirect signup attempts to the Cubite storefront signup.
     * $user/$notify required by parent signature but unused here.
     */
    public function user_signup($user, $notify = true) {
        $storefronturl = trim(get_config('auth_cubite', 'storefronturl') ?? '');
        if (!empty($storefronturl)) {
            redirect($storefronturl . '/auth/signup');
        }
        return false;
    }
}
