<?php

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }

    require_once($CFG->dirroot.'/course/lib.php');

    if (has_capability('moodle/site:viewreports', $context)) {
        echo $OUTPUT->heading(get_string('interactions') .':');

        print_interactions_selector_form($course);
    }