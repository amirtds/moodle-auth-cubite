<?php
namespace auth_cubite;

use core\hook\output\before_standard_top_of_body_html_generation;
use core\hook\after_config;

class hook_callbacks {

    /**
     * Redirect non-admins away from Moodle's course catalog pages back to the Cubite
     * storefront. This enforces Cubite as the only course discovery surface — students
     * can't browse Moodle's catalog or self-enroll bypassing payment.
     */
    public static function after_config(after_config $hook): void {
        if (CLI_SCRIPT || AJAX_SCRIPT || WS_SERVER) {
            return;
        }

        // Cheap path check first — most requests are not catalog pages, so we can
        // bail before doing any DB work (is_siteadmin, get_config).
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $blocked = [
            '/course/index.php' => true,
            '/course/search.php' => true,
            '/course/index_category.php' => true,
        ];
        $matched = false;
        foreach ($blocked as $path => $_) {
            if (substr($script, -strlen($path)) === $path) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return;
        }

        if (is_siteadmin()) {
            return;
        }

        $storefronturl = trim(get_config('auth_cubite', 'storefronturl') ?? '');
        if (!empty($storefronturl)) {
            redirect($storefronturl . '/courses');
        }
    }

    public static function before_standard_top_of_body_html(before_standard_top_of_body_html_generation $hook): void {
        // Admins keep Moodle's native "home" link; everyone else gets sent back to
        // the Cubite storefront catalog when they click the navbar brand.
        if (!isloggedin() || is_siteadmin()) {
            return;
        }

        $storefronturl = trim(get_config('auth_cubite', 'storefronturl') ?? '');
        if (empty($storefronturl)) {
            return;
        }

        $url = json_encode($storefronturl);

        // Rewrite the navbar brand (site name/logo) to link back to the Cubite storefront,
        // so the existing "home" link takes the student back to the catalog they came from.
        $script = <<<HTML
<script>
(function() {
    var url = $url;
    function rewriteBrand() {
        var brands = document.querySelectorAll('a.navbar-brand');
        brands.forEach(function(b) { b.setAttribute('href', url); });
        return brands.length > 0;
    }
    if (rewriteBrand()) { return; }
    document.addEventListener('DOMContentLoaded', function() {
        if (rewriteBrand()) { return; }
        // Boost theme can re-render the header after initial load; observe just the
        // header subtree (not the whole document) to keep this cheap.
        var header = document.querySelector('header') || document.body;
        var obs = new MutationObserver(function() {
            if (rewriteBrand()) { obs.disconnect(); }
        });
        obs.observe(header, { childList: true, subtree: true });
        setTimeout(function() { obs.disconnect(); }, 3000);
    });
})();
</script>
HTML;

        $hook->add_html($script);
    }
}
