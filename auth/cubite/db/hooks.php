<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => auth_cubite\hook_callbacks::class . '::before_standard_top_of_body_html',
    ],
    [
        'hook' => core\hook\after_config::class,
        'callback' => auth_cubite\hook_callbacks::class . '::after_config',
    ],
];
