<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'auth_cubite/sharedsecret',
        get_string('sharedsecret', 'auth_cubite'),
        get_string('sharedsecret_desc', 'auth_cubite'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'auth_cubite/storefronturl',
        get_string('storefronturl', 'auth_cubite'),
        get_string('storefronturl_desc', 'auth_cubite'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'auth_cubite/adminbypasstoken',
        get_string('adminbypasstoken', 'auth_cubite'),
        get_string('adminbypasstoken_desc', 'auth_cubite'),
        '',
        PARAM_ALPHANUM
    ));
}
