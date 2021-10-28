<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportinteractions', 'Interactions', "$CFG->wwwroot/course/report/interactions/index.php?id=".SITEID, 'coursereport/log:view'));

