<?php

function print_interactions_selector_form(
    $course,
    $selecteduser = 0,
    $selecteddate = 'today',
    $modname = "",
    $modid = 0,
    $modaction = '',
    $selectedgroup = -1,
    $showcourses = 0,
    $showusers = 0,
    $logformat = 'downloadasexcel',
    $interactiontype = ''
) {

    global $USER, $CFG, $DB, $OUTPUT, $SESSION;

    // first check to see if we can override showcourses and showusers
    $numcourses =  $DB->count_records("course");
    if ($numcourses < COURSE_MAX_COURSES_PER_DROPDOWN && !$showcourses) {
        $showcourses = 1;
    }

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    /// Setup for group handling.
    if ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
        $selectedgroup = -1;
        $showgroups = false;
    } else if ($course->groupmode) {
        $showgroups = true;
    } else {
        $selectedgroup = 0;
        $showgroups = false;
    }

    if ($selectedgroup === -1) {
        if (isset($SESSION->currentgroup[$course->id])) {
            $selectedgroup =  $SESSION->currentgroup[$course->id];
        } else {
            $selectedgroup = groups_get_all_groups($course->id, $USER->id);
            if (is_array($selectedgroup)) {
                $selectedgroup = array_shift(array_keys($selectedgroup));
                $SESSION->currentgroup[$course->id] = $selectedgroup;
            } else {
                $selectedgroup = 0;
            }
        }
    }

    // Get all the possible users
    $users = array();

    $courseusers = get_enrolled_users($context, '', $selectedgroup, 'u.id, u.firstname, u.lastname, u.idnumber', 'lastname ASC, firstname ASC');

    if (count($courseusers) < COURSE_MAX_USERS_PER_DROPDOWN && !$showusers) {
        $showusers = 1;
    }

    if ($showusers) {
        if ($courseusers) {
            foreach ($courseusers as $courseuser) {
                $users[$courseuser->id] = fullname($courseuser, has_capability('moodle/site:viewfullnames', $context));
            }
        }
        $users[$CFG->siteguest] = get_string('guestuser');
    }

    if (has_capability('coursereport/log:view', $sitecontext) && $showcourses) {
        if ($ccc = $DB->get_records("course", null, "fullname", "id,fullname,category")) {
            foreach ($ccc as $cc) {
                if ($cc->category) {
                    $courses["$cc->id"] = format_string($cc->fullname);
                } else {
                    $courses["$cc->id"] = format_string($cc->fullname) . ' (Site)';
                }
            }
        }
        asort($courses);
    }

    $activities = array();
    $selectedactivity = "";

    /// Casting $course->modinfo to string prevents one notice when the field is null
    if ($modinfo = unserialize((string)$course->modinfo)) {
        $section = 0;
        $sections = get_all_sections($course->id);
        foreach ($modinfo as $mod) {
            if ($mod->mod == "label") {
                continue;
            }
            if ($mod->section > 0 and $section <> $mod->section) {
                $activities["section/$mod->section"] = '--- ' . get_section_name($course, $sections[$mod->section]) . ' ---';
            }
            $section = $mod->section;
            $mod->name = strip_tags(format_string($mod->name, true));
            if (strlen($mod->name) > 55) {
                $mod->name = substr($mod->name, 0, 50) . "...";
            }
            if (!$mod->visible) {
                $mod->name = "(" . $mod->name . ")";
            }
            $activities["$mod->cm"] = $mod->name;

            if ($mod->cm == $modid) {
                $selectedactivity = "$mod->cm";
            }
        }
    }
    if (has_capability('coursereport/log:view', $sitecontext) && ($course->id == SITEID)) {
        $activities["site_errors"] = get_string("siteerrors");
        if ($modid === "site_errors") {
            $selectedactivity = "site_errors";
        }
    }
    $strftimedate = get_string("strftimedate");
    $strftimedaydate = get_string("strftimedaydate");

    asort($users);

    $interactiontypes = array(
        'alumnoalumno' => "alumno-alumno",
        'alumnoprofesor' => "alumno-profesor",
        'alumnocontenido' => "alumno-contenido",
        'alumnosistema' => "alumno-sistema",
        'transcontenidos' => "transmitir contenidos",
        'interaccionesclase' => "crear interacciones de clase",
        'evaluarestudiantes' => "evaluar estudiantes",
        'evaluarcursoprofes' => "evaluar curso y profesores",
        'activas' => "activas",
        'pasivas' => "pasivas",
        'entrada' => "entrada",
        'salida' => "salida"
    );

    // Get all the possible dates
    // Note that we are keeping track of real (GMT) time and user time
    // User time is only used in displays - all calcs and passing is GMT

    $timenow = time(); // GMT

    // What day is it now for the user, and when is midnight that day (in GMT).
    $timemidnight = $today =
        usergetmidnight($timenow);

    // Put today up the top of the list
    $dates = array("$timemidnight" => get_string("today") . ", " . userdate($timenow, $strftimedate));

    if (!$course->startdate or ($course->startdate > $timenow)) {
        $course->startdate = $course->timecreated;
    }

    $numdates = 1;
    while ($timemidnight > $course->startdate and $numdates < 365) {
        $timemidnight = $timemidnight - 86400;
        $timenow = $timenow - 86400;
        $dates["$timemidnight"] = userdate($timenow, $strftimedaydate);
        $numdates++;
    }

    if ($selecteddate == "today") {
        $selecteddate = $today;
    }

    echo "<form class=\"logselectform\" action=\"$CFG->wwwroot/course/report/interactions/index.php\" method=\"get\">\n";
    echo "<div>\n";
    echo "<input type=\"hidden\" name=\"chooselog\" value=\"1\" />\n";
    echo "<input type=\"hidden\" name=\"showusers\" value=\"$showusers\" />\n";
    echo "<input type=\"hidden\" name=\"showcourses\" value=\"$showcourses\" />\n";
    if (has_capability('coursereport/log:view', $sitecontext) && $showcourses) {
        echo html_writer::select($courses, "id", $course->id, false);
    } else {
        //        echo '<input type="hidden" name="id" value="'.$course->id.'" />';
        $courses = array();
        $courses[$course->id] = $course->fullname . (($course->id == SITEID) ? ' (' . get_string('site') . ') ' : '');
        echo html_writer::select($courses, "id", $course->id, false);
        if (has_capability('coursereport/log:view', $sitecontext)) {
            $a = new stdClass();
            $a->url = "$CFG->wwwroot/course/report/interactions/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
                . "&id=$course->id&date=$selecteddate&modid=$selectedactivity&showcourses=1&showusers=$showusers";
            print_string('logtoomanycourses', 'moodle', $a);
        }
    }

    if ($showgroups) {
        if ($cgroups = groups_get_all_groups($course->id)) {
            foreach ($cgroups as $cgroup) {
                $groups[$cgroup->id] = $cgroup->name;
            }
        } else {
            $groups = array();
        }
        echo html_writer::select($groups, "group", $selectedgroup, get_string("allgroups"));
    }

    if ($showusers) {
        echo html_writer::select($users, "user", $selecteduser, get_string("allparticipants"));
    } else {
        $users = array();
        if (!empty($selecteduser)) {
            $user = $DB->get_record('user', array('id' => $selecteduser));
            $users[$selecteduser] = fullname($user);
        } else {
            $users[0] = get_string('allparticipants');
        }
        echo html_writer::select($users, "user", $selecteduser, false);
        $a = new stdClass();
        $a->url = "$CFG->wwwroot/course/report/interactions/index.php?chooselog=0&group=$selectedgroup&user=$selecteduser"
            . "&id=$course->id&date=$selecteddate&modid=$selectedactivity&showusers=1&showcourses=$showcourses";
        print_string('logtoomanyusers', 'moodle', $a);
    }
    echo html_writer::select($dates, "date", $selecteddate, get_string("alldays"));


    $interaction = $log->module . $log->action;
    echo html_writer::select($interactiontypes, 'interactiontype', $interactiontype, "All interactions");


    $logformats = array('downloadasexcel' => get_string('downloadexcel'));
    //echo html_writer::select($activities, "modid", $selectedactivity, get_string("allactivities"));
    echo html_writer::select($logformats, 'logformat', $logformat, false);
    echo '<input type="submit" value="' . "Get these interactions" . '" />';
    echo '</div>';
    echo '</form>';
}


function print_interactions_xls(
    $course,
    $user,
    $date,
    $order = 'l.time DESC',
    $modname,
    $modid,
    $modaction,
    $groupid,
    $interactiontype
) {

    global $CFG, $DB;
    require_once("$CFG->libdir/excellib.class.php");

    //$order="l.userid ASC";

    if (!$logs = build_logs_array(
        $course,
        $user,
        $date,
        $order,
        '',
        '',
        $modname,
        $modid,
        $modaction,
        $groupid
    )) {
        return false;
    }

    $courses = array();

    if ($course->id == SITEID) {
        $courses[0] = '';
        if ($ccc = get_courses('all', 'c.id ASC', 'c.id,c.shortname')) {
            foreach ($ccc as $cc) {
                $courses[$cc->id] = $cc->shortname;
            }
        }
    } else {
        $courses[$course->id] = $course->shortname;
    }

    $count = 0;
    $ldcache = array();
    $tt = getdate(time());
    $today = mktime(0, 0, 0, $tt["mon"], $tt["mday"], $tt["year"]);

    $strftimedatetime = get_string("strftimedatetime");

    $nroPages = ceil(count($logs) / (EXCELROWS - FIRSTUSEDEXCELROW + 1));
    $filename = 'interactions_' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
    $filename .= '.xls';

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = array();
    $headers = array(
        get_string('course'), get_string('time'), get_string('ip_address'),
        "ID Usuario", get_string('action'), get_string('info'), "Agentes", "Finalidad", "Acci�n", "E/S", "Rol [From]", "Info", "Recurso", "Path"
    );

    // Creating worksheets
    for ($wsnumber = 1; $wsnumber <= $nroPages; $wsnumber++) {
        $sheettitle = get_string('logs') . ' ' . $wsnumber . '-' . $nroPages;
        $worksheet[$wsnumber] = &$workbook->add_worksheet($sheettitle);
        $worksheet[$wsnumber]->set_column(1, 1, 30);
        $worksheet[$wsnumber]->write_string(0, 0, get_string('savedat') .
            userdate(time(), $strftimedatetime));
        $col = 0;
        foreach ($headers as $item) {
            $worksheet[$wsnumber]->write(FIRSTUSEDEXCELROW - 1, $col, $item, '');
            $col++;
        }
    }

    $worksheet[$nroPages + 1] = &$workbook->add_worksheet("Interactions");

    if (empty($logs['logs'])) {
        $workbook->close();
        return true;
    }

    $formatDate = &$workbook->add_format();
    $formatDate->set_num_format(get_string('log_excel_date_format'));

    $row = FIRSTUSEDEXCELROW;

    $myxls = &$worksheet[$wsnumber - 1];
    $numbera = array();
    $numberb = array();
    $numberc = array();
    $numberd = array();
    $numbere = array();
    $numberf = array();
    $numberg = array();
    $numberh = array();
    $numberi = array();
    $numberj = array();
    $numberk = array();
    $numberl = array();
    $fullnames = array();

    foreach ($logs['logs'] as $log) {
        $linf = $log->info;
        $path = make_log_url($log->module, $log->url);
        $cmid = $log->cmid;
        if (isset($ldcache[$log->module][$log->action])) {
            $ld = $ldcache[$log->module][$log->action];
        } else {
            $ld = $DB->get_record('log_display', array('module' => $log->module, 'action' => $log->action));
            $ldcache[$log->module][$log->action] = $ld;
        }
        if ($ld && !empty($log->info)) {
            // ugly hack to make sure fullname is shown correctly
            if (($ld->mtable == 'user') and ($ld->field == $DB->sql_concat('firstname', "' '", 'lastname'))) {
                $log->info = fullname($DB->get_record($ld->mtable, array('id' => $log->info)), true);
            } else {
                $log->info = $DB->get_field($ld->mtable, $ld->field, array('id' => $log->info));
            }
        }

        // Filter log->info
        $log->info = format_string($log->info);
        $log->info = strip_tags(urldecode($log->info));  // Some XSS protection

        if ($nroPages > 1) {
            if ($row > EXCELROWS) {
                $wsnumber++;
                $myxls = &$worksheet[$wsnumber];
                $row = FIRSTUSEDEXCELROW;
            }
        }

        $interactions = array(
            "userview", "courseuser report", "courseview", "courseupdate", "courseenrol", "courseunenrol", "coursereport interactions", "coursereport log", "coursereport live", "coursereport outline",
            "coursereport participation", "coursereport stats", "messagewrite", "messageread", "messageadd contact", "messageremove contact", "messageblock contact", "messageunblock contact", "groupview", "tagupdate",
            "assignmentview", "assignmentadd", "assignmentupdate", "assignmentview submission", "assignmentupload", "chatview", "chatadd", "chatupdate", "chatreport", "chattalk",
            "choiceview", "choiceupdate", "choiceadd", "choicereport", "choicechoose", "choicechoose again", "dataview", "dataadd", "dataupdate", "datarecord delete",
            "datafields add", "datafields update", "datatemplates saved", "datatemplates def", "feedbackstartcomplete", "feedbacksubmit", "feedbackdelete", "feedbackview", "feedbackview all", "folderview",
            "folderview all", "folderupdate", "folderadd", "forumadd", "forumupdate", "forumadd discussion", "forumadd post", "forumupdate post", "forumuser report", "forummove discussion",
            "forumview suscribers", "forumview discussion", "forumview forum", "forumsubscribe", "forumunsubscribe", "glossaryadd", "glossaryupdate", "glossaryview", "glossaryview all", "glossaryadd entry",
            "glossaryupdate entry", "glossaryadd category", "glossaryupdate category", "glossarydelete category", "glossaryapprove entry", "glossaryview entry", "imscpview", "imscpview all", "imscpupdate", "imscpadd",
            "labeladd", "labelupdate", "lessonstart", "lessonend", "lessonview", "pageview", "pageviewall", "pageupdate", "pageadd", "quizadd",
            "quizupdate", "quizview", "quizreport", "quizattempt", "quizsubmit", "quizreview", "quizeditquestions", "quizpreview", "quizstart attempt", "quizclose attempt",
            "quizcontinue attempt", "quizedit override", "quizdelete override", "resourcesview", "resourcesview all", "resourcesupdate", "resourcesadd", "scormview", "scormreview", "scormupdate",
            "scormadd", "surveyadd", "surveyupdate", "surveydownload", "surveyview form", "surveyview graph", "surveyview report", "surveysubmit", "urlview", "urlview all",
            "urlupdate", "urladd", "workshopadd", "workshopupdate", "workshopview", "workshopview all", "workshopadd submission", "workshopupdate submission", "workshopview submission", "workshopadd assessment",
            "workshopupdate assessment", "workshopadd example", "workshopupdate example", "workshopview example", "workshopadd reference assessment", "workshopupdate reference assessment", "workshopadd example assessment", "workshopupdate example assessment", "workshopupdate aggregate grades", "workshopupdate clear aggregated grades",
            "workshopupdate clear assessments", "userlogin", "userlogout"
        );

        $interaction = $log->module . $log->action;

        // $fullname = fullname($log, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
        $fullname = $log->userid;

        $context = get_context_instance(CONTEXT_COURSE, $log->course);

        // Determinaci�n de roles (del usuario y de con qui�n interact�a)
        $roles = get_user_roles($context, $log->userid);
        $role = key($roles);
        $ownrole_id = $roles[$role]->roleid;

        $roleto = get_user_roles($context, $log->info);
        $roletokey = key($roleto);
        $torole_id = $roleto[$roletokey]->roleid;

        switch ($interactiontype) {
            case "alumnoalumno":
                if ($interaction == $interactions[29] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction == $interactions[61] || $interaction == $interactions[124]) {
                    $interactiontype1 = "SS";
                    $numbera[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    // $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    $myxls->write($row, 1, $log->time, ''); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 6, $interactiontype1, '');
                    $row++;
                } elseif ($interaction == $interactions[12] || $interaction == $interactions[13]) { // TODO: this condition might be reached
                    // $roleto = get_user_roles($context, $log->info);
                    // if ($roles->roleid == "5" || $roles->roleid == "9") {
                    // if ($roleto == "5" || $roleto == "9") {
                    if ($ownrole_id == "5" || $ownrole_id == "9") {
                        if ($torole_id == "5" || $torole_id == "9") {
                            $interactiontype1 = "SS";
                            $numbera[$fullname]++;
                        } else {
                            $interactiontype1 = "";
                        }
                    } else {
                        $interactiontype1 = "";
                    }
                } else {
                    $interactiontype1 = "";
                }
                break;
            case "alumnoprofesor":
                if ($interaction == $interactions[12] || $interaction == $interactions[13] || $interaction == $interactions[24] || $interaction == $interactions[29] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction == $interactions[61] || $interaction == $interactions[111] || $interaction == $interactions[112] || $interaction == $interactions[117] || $interaction == $interactions[124]) {
                    $interactiontype1 = "ST";
                    $numberb[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 6, $interactiontype1, '');
                    $row++;
                } elseif ($interaction == $interactions[12] || $interaction == $interactions[13]) { // TODO: this condition is never reached and kind of redundant with previous
                    // $roleto = get_user_roles($context, $log->info);
                    if ($ownrole_id == "5" || $ownrole_id == "9") {
                        if ($torole_id == "1" || $torole_id == "2" || $torole_id == "3" || $torole_id == "4" || $torole_id == "10") {
                            $interactiontype1 = "ST";
                            $numberb[$fullname]++;
                        } else {
                            $interactiontype1 = "";
                        }
                    } elseif ($ownrole_id == "1" || $ownrole_id == "2" || $ownrole_id == "3" || $ownrole_id == "4" || $ownrole_id == "10") {
                        if ($torole_id == "5" || $torole_id == "9") {
                            $interactiontype1 = "ST";
                            $numberb[$fullname]++;
                        } else {
                            $interactiontype1 = "";
                        }
                    } else {
                        $interactiontype1 = "";
                    }
                } else {
                    $interactiontype1 = "";
                }
                break;
            case "alumnocontenido":
                if ($interaction == $interactions[19] || $interaction == $interactions[20] || $interaction == $interactions[23] || $interaction == $interactions[26] || $interaction == $interactions[36] || $interaction == $interactions[37] || $interaction == $interactions[38] || $interaction == $interactions[39] || $interaction == $interactions[40] || $interaction == $interactions[41] || $interaction == $interactions[47] || $interaction == $interactions[48] || $interaction == $interactions[49] || $interaction == $interactions[50] || $interaction == $interactions[51] || $interaction == $interactions[52] || $interaction == $interactions[53] || $interaction == $interactions[59] || $interaction == $interactions[62] || $interaction == $interactions[65] || $interaction == $interactions[66] || $interaction == $interactions[67] || $interaction == $interactions[68] || $interaction == $interactions[69] || $interaction == $interactions[70] || $interaction == $interactions[71] || $interaction == $interactions[72] || $interaction == $interactions[73] || $interaction == $interactions[74] || $interaction == $interactions[75] || $interaction == $interactions[76] || $interaction == $interactions[77] || $interaction == $interactions[78] || $interaction == $interactions[79] || $interaction == $interactions[80] || $interaction == $interactions[81] || $interaction == $interactions[82] || $interaction == $interactions[83] || $interaction == $interactions[84] || $interaction == $interactions[85] || $interaction == $interactions[86] || $interaction == $interactions[87] || $interaction == $interactions[88] || $interaction == $interactions[95] || $interaction == $interactions[97] || $interaction == $interactions[103] || $interaction == $interactions[104] || $interaction == $interactions[105] || $interaction == $interactions[106] || $interaction == $interactions[107] || $interaction == $interactions[108] || $interaction == $interactions[109] || $interaction == $interactions[110] || $interaction == $interactions[118] || $interaction == $interactions[119] || $interaction == $interactions[120] || $interaction == $interactions[121] || $interaction == $interactions[125]) {
                    $interactiontype1 = "SC";
                    $numberc[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 6, $interactiontype1, '');
                    $row++;
                } else {
                    $interactiontype1 = "";
                }
                break;
            case "alumnosistema":
                if ($interaction == $interactions[0] || $interaction == $interactions[1] || $interaction == $interactions[2] || $interaction == $interactions[3] || $interaction == $interactions[4] || $interaction == $interactions[5] || $interaction == $interactions[6] || $interaction == $interactions[7] || $interaction == $interactions[8] || $interaction == $interactions[9] || $interaction == $interactions[10] || $interaction == $interactions[11] || $interaction == $interactions[14] || $interaction == $interactions[15] || $interaction == $interactions[16] || $interaction == $interactions[17] || $interaction == $interactions[18] || $interaction == $interactions[25] || $interaction == $interactions[27] || $interaction == $interactions[28] || $interaction == $interactions[30] || $interaction == $interactions[31] || $interaction == $interactions[32] || $interaction == $interactions[33] || $interaction == $interactions[34] || $interaction == $interactions[35] || $interaction == $interactions[42] || $interaction == $interactions[43] || $interaction == $interactions[44] || $interaction == $interactions[45] || $interaction == $interactions[46] || $interaction == $interactions[54] || $interaction == $interactions[58] || $interaction == $interactions[60] || $interaction == $interactions[63] || $interaction == $interactions[64] || $interaction == $interactions[89] || $interaction == $interactions[90] || $interaction == $interactions[91] || $interaction == $interactions[92] || $interaction == $interactions[93] || $interaction == $interactions[94] || $interaction == $interactions[96] || $interaction == $interactions[98] || $interaction == $interactions[99] || $interaction == $interactions[100] || $interaction == $interactions[101] || $interaction == $interactions[102] || $interaction == $interactions[113] || $interaction == $interactions[114] || $interaction == $interactions[115] || $interaction == $interactions[116] || $interaction == $interactions[122] || $interaction == $interactions[123] || $interaction == $interactions[124] || $interaction == $interactions[125] || $interaction == $interactions[126] || $interaction == $interactions[127] || $interaction == $interactions[128] || $interaction == $interactions[129] || $interaction == $interactions[130] || $interaction == $interactions[131] || $interaction == $interactions[132] || $interaction == $interactions[133] || $interaction == $interactions[134] || $interaction == $interactions[135] || $interaction == $interactions[136] || $interaction == $interactions[137] || $interaction == $interactions[138]) {
                    $interactiontype1 = "SSY";
                    $numberd[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 6, $interactiontype1, '');
                    $row++;
                } else {
                    $interactiontype1 = "";
                }
                break;
            case "transcontenidos":
                if ($interaction == $interactions[19] || $interaction == $interactions[36] || $interaction == $interactions[37] || $interaction == $interactions[38] || $interaction == $interactions[39] || $interaction == $interactions[40] || $interaction == $interactions[41] || $interaction == $interactions[42] || $interaction == $interactions[43] || $interaction == $interactions[44] || $interaction == $interactions[45] || $interaction == $interactions[46] || $interaction == $interactions[47] || $interaction == $interactions[48] || $interaction == $interactions[49] || $interaction == $interactions[50] || $interaction == $interactions[51] || $interaction == $interactions[52] || $interaction == $interactions[65] || $interaction == $interactions[66] || $interaction == $interactions[67] || $interaction == $interactions[68] || $interaction == $interactions[69] || $interaction == $interactions[70] || $interaction == $interactions[71] || $interaction == $interactions[72] || $interaction == $interactions[73] || $interaction == $interactions[74] || $interaction == $interactions[75] || $interaction == $interactions[76] || $interaction == $interactions[77] || $interaction == $interactions[78] || $interaction == $interactions[79] || $interaction == $interactions[80] || $interaction == $interactions[81] || $interaction == $interactions[82] || $interaction == $interactions[83] || $interaction == $interactions[84] || $interaction == $interactions[85] || $interaction == $interactions[86] || $interaction == $interactions[87] || $interaction == $interactions[88] || $interaction == $interactions[103] || $interaction == $interactions[104] || $interaction == $interactions[105] || $interaction == $interactions[106] || $interaction == $interactions[107] || $interaction == $interactions[108] || $interaction == $interactions[109] || $interaction == $interactions[110] || $interaction == $interactions[118] || $interaction == $interactions[119] || $interaction == $interactions[120] || $interaction == $interactions[121]) {
                    $interactiontype2 = "TC";
                    $numbere[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 7, $interactiontype2, '');
                    $row++;
                } else {
                    $interactiontype2 = "";
                }
                break;
            case "interaccionesclase":
                if ($interaction == $interactions[12] || $interaction == $interactions[13] || $interaction == $interactions[14] || $interaction == $interactions[15] || $interaction == $interactions[16] || $interaction == $interactions[17] || $interaction == $interactions[25] || $interaction == $interactions[26] || $interaction == $interactions[27] || $interaction == $interactions[28] || $interaction == $interactions[29] || $interaction == $interactions[53] || $interaction == $interactions[54] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction == $interactions[58] || $interaction == $interactions[59] || $interaction == $interactions[60] || $interaction == $interactions[61] || $interaction == $interactions[62] || $interaction == $interactions[63] || $interaction == $interactions[64]) {
                    $interactiontype2 = "CCI";
                    $numberf[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 7, $interactiontype2, '');
                    $row++;
                } else {
                    $interactiontype2 = "";
                }
                break;
            case "evaluarestudiantes":
                if ($interaction == $interactions[20] || $interaction == $interactions[21] || $interaction == $interactions[22] || $interaction == $interactions[23] || $interaction == $interactions[24] || $interaction == $interactions[30] || $interaction == $interactions[31] || $interaction == $interactions[32] || $interaction == $interactions[33] || $interaction == $interactions[34] || $interaction == $interactions[35] || $interaction == $interactions[89] || $interaction == $interactions[90] || $interaction == $interactions[91] || $interaction == $interactions[92] || $interaction == $interactions[93] || $interaction == $interactions[94] || $interaction == $interactions[95] || $interaction == $interactions[96] || $interaction == $interactions[97] || $interaction == $interactions[98] || $interaction == $interactions[99] || $interaction == $interactions[100] || $interaction == $interactions[101] || $interaction == $interactions[102] || $interaction == $interactions[122] || $interaction == $interactions[123] || $interaction == $interactions[124] || $interaction == $interactions[125] || $interaction == $interactions[126] || $interaction == $interactions[127] || $interaction == $interactions[128] || $interaction == $interactions[129] || $interaction == $interactions[130] || $interaction == $interactions[131] || $interaction == $interactions[132] || $interaction == $interactions[133] || $interaction == $interactions[134] || $interaction == $interactions[135] || $interaction == $interactions[136] || $interaction == $interactions[137] || $interaction == $interactions[138] || $interaction == $interactions[139] || $interaction == $interactions[140]) {
                    $interactiontype2 = "ES";
                    $numberg[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 7, $interactiontype2, '');
                    $row++;
                } else {
                    $interactiontype2 = "";
                }
                break;
            case "evaluarcursoprofes":
                if ($interaction == $interactions[111] || $interaction == $interactions[112] || $interaction == $interactions[113] || $interaction == $interactions[114] || $interaction == $interactions[115] || $interaction == $interactions[116] || $interaction == $interactions[117]) {
                    $interactiontype2 = "ECT";
                    $numberh[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 7, $interactiontype2, '');
                    $row++;
                } else {
                    $interactiontype2 = "";
                }
                break;
            case "activas":
                if ($interaction == $interactions[3] || $interaction == $interactions[4] || $interaction == $interactions[5] || $interaction == $interactions[12] || $interaction == $interactions[14] || $interaction == $interactions[15] || $interaction == $interactions[16] || $interaction == $interactions[17] || $interaction == $interactions[19] || $interaction == $interactions[21] || $interaction == $interactions[22] || $interaction == $interactions[24] || $interaction == $interactions[26] || $interaction == $interactions[27] || $interaction == $interactions[29] || $interaction == $interactions[31] || $interaction == $interactions[32] || $interaction == $interactions[34] || $interaction == $interactions[35] || $interaction == $interactions[37] || $interaction == $interactions[38] || $interaction == $interactions[39] || $interaction == $interactions[40] || $interaction == $interactions[41] || $interaction == $interactions[42] || $interaction == $interactions[43] || $interaction == $interactions[44] || $interaction == $interactions[45] || $interaction == $interactions[46] || $interaction == $interactions[51] || $interaction == $interactions[52] || $interaction == $interactions[53] || $interaction == $interactions[54] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction == $interactions[58] || $interaction == $interactions[59] || $interaction == $interactions[63] || $interaction == $interactions[64] || $interaction == $interactions[65] || $interaction == $interactions[66] || $interaction == $interactions[69] || $interaction == $interactions[70] || $interaction == $interactions[71] || $interaction == $interactions[72] || $interaction == $interactions[73] || $interaction == $interactions[74] || $interaction == $interactions[78] || $interaction == $interactions[79] || $interaction == $interactions[80] || $interaction == $interactions[81] || $interaction == $interactions[82] || $interaction == $interactions[83] || $interaction == $interactions[87] || $interaction == $interactions[88] || $interaction == $interactions[89] || $interaction == $interactions[90] || $interaction == $interactions[93] || $interaction == $interactions[94] || $interaction == $interactions[96] || $interaction == $interactions[98] || $interaction == $interactions[99] || $interaction == $interactions[100] || $interaction == $interactions[101] || $interaction == $interactions[102] || $interaction == $interactions[105] || $interaction == $interactions[106] || $interaction == $interactions[109] || $interaction == $interactions[110] || $interaction == $interactions[111] || $interaction == $interactions[112] || $interaction == $interactions[113] || $interaction == $interactions[117] || $interaction == $interactions[120] || $interaction == $interactions[121] || $interaction == $interactions[122] || $interaction == $interactions[123] || $interaction == $interactions[126] || $interaction == $interactions[127] || $interaction == $interactions[129] || $interaction == $interactions[130] || $interaction == $interactions[131] || $interaction == $interactions[132] || $interaction == $interactions[134] || $interaction == $interactions[135] || $interaction == $interactions[136] || $interaction == $interactions[137] || $interaction == $interactions[138] || $interaction == $interactions[139] || $interaction == $interactions[140]) {
                    $interactiontype3 = "ACT";
                    $numberi[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 8, $interactiontype3, '');
                    $row++;
                } else {
                    $interactiontype3 = "";
                }
                break;
            case "pasivas":
                if ($interaction == $interactions[0] || $interaction == $interactions[1] || $interaction == $interactions[2] || $interaction == $interactions[6] || $interaction == $interactions[7] || $interaction == $interactions[8] || $interaction == $interactions[9] || $interaction == $interactions[10] || $interaction == $interactions[11] || $interaction == $interactions[13] || $interaction == $interactions[18] || $interaction == $interactions[20] || $interaction == $interactions[23] || $interaction == $interactions[25] || $interaction == $interactions[28] || $interaction == $interactions[30] || $interaction == $interactions[33] || $interaction == $interactions[36] || $interaction == $interactions[47] || $interaction == $interactions[48] || $interaction == $interactions[49] || $interaction == $interactions[50] || $interaction == $interactions[58] || $interaction == $interactions[60] || $interaction == $interactions[61] || $interaction == $interactions[62] || $interaction == $interactions[67] || $interaction == $interactions[68] || $interaction == $interactions[75] || $interaction == $interactions[76] || $interaction == $interactions[77] || $interaction == $interactions[84] || $interaction == $interactions[85] || $interaction == $interactions[86] || $interaction == $interactions[91] || $interaction == $interactions[92] || $interaction == $interactions[95] || $interaction == $interactions[97] || $interaction == $interactions[103] || $interaction == $interactions[104] || $interaction == $interactions[107] || $interaction == $interactions[108] || $interaction == $interactions[113] || $interaction == $interactions[114] || $interaction == $interactions[115] || $interaction == $interactions[116] || $interaction == $interactions[118] || $interaction == $interactions[119] || $interaction == $interactions[124] || $interaction == $interactions[125] || $interaction == $interactions[128] || $interaction == $interactions[133]) {
                    $interactiontype3 = "PAS";
                    $numberj[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 8, $interactiontype3, '');
                    $row++;
                } else {
                    $interactiontype3 = "";
                }
                break;
            case "entrada":
                if ($interaction == $interactions[141]) {
                    $interactiontype4 = "IN";
                    $numberk[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 9, $interactiontype4, '');
                    $row++;
                } else {
                    $interactiontype4 = "";
                }
            case "salida":
                if ($interaction == $interactions[142]) {
                    $interactiontype4 = "OUT";
                    $numberl[$fullname]++;
                    $myxls->write($row, 0, $courses[$log->course], '');
                    $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                    // $myxls->write($row, 2, $log->ip, '');
                    $myxls->write($row, 2, 'IP oculta', '');
                    //$fullnames[$fullname] = $fullname;
                    //$myxls->write($row, 3, $fullnames[$fullname], '');
                    $fullnames[$fullname] = $log->userid;
                    $myxls->write($row, 3, $log->userid, '');
                    $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                    $myxls->write($row, 5, $log->info, '');
                    $myxls->write($row, 9, $interactiontype4, '');
                    $row++;
                } else {
                    $interactiontype4 = "";
                }
            default:
                if ($interaction == $interactions[29] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction == $interactions[61] || $interaction ==             $interactions[124]) {
                    $interactiontype1 = "SS";
                    $numbera[$fullname]++;
                } elseif ($interaction == $interactions[24] || $interaction == $interactions[29] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction ==         $interactions[61] || $interaction == $interactions[111] || $interaction == $interactions[112] || $interaction == $interactions[117] || $interaction == $interactions[124]) {
                    $interactiontype1 = "ST";
                    $numberb[$fullname]++;
                } elseif ($interaction == $interactions[12] || $interaction == $interactions[13]) {
                    // $roleto = get_user_roles($context, $log->info);
                    if ($ownrole_id == "5" || $ownrole_id == "9") {
                        if ($torole_id == "1" || $torole_id == "2" || $torole_id == "3" || $torole_id == "4" || $torole_id == "10") {
                            $interactiontype1 = "ST";
                            $numberb[$fullname]++;
                        } elseif ($torole_id == "5" || $torole_id == "9") {
                            $interactiontype1 = "SS";
                            $numbera[$fullname]++;
                        } else {
                            $interactiontype1 = "";
                        }
                    } elseif ($ownrole_id == "1" || $ownrole_id == "2" || $ownrole_id == "3" || $ownrole_id == "4" || $ownrole_id == "10") {
                        if ($torole_id == "5" || $torole_id == "9") {
                            $interactiontype1 = "ST";
                            $numberb[$fullname]++;
                        } else {
                            $interactiontype1 = "";
                        }
                    } else {
                        $interactiontype1 = "";
                    }
                } elseif ($interaction == $interactions[19] || $interaction == $interactions[20] || $interaction == $interactions[23] || $interaction == $interactions[26] || $interaction == $interactions[36] || $interaction ==     $interactions[37] || $interaction == $interactions[38] || $interaction == $interactions[39] || $interaction == $interactions[40] || $interaction == $interactions[41] || $interaction == $interactions[47] || $interaction == $interactions[48] || $interaction == $interactions[49] || $interaction == $interactions[50] || $interaction == $interactions[51] || $interaction == $interactions[52] || $interaction == $interactions[53] || $interaction == $interactions[59] || $interaction == $interactions[62] || $interaction == $interactions[65] || $interaction == $interactions[66] || $interaction == $interactions[67] || $interaction == $interactions[68] || $interaction == $interactions[69] || $interaction == $interactions[70] || $interaction == $interactions[71] || $interaction == $interactions[72] || $interaction == $interactions[73] || $interaction == $interactions[74] || $interaction == $interactions[75] || $interaction == $interactions[76] || $interaction == $interactions[77] || $interaction == $interactions[78] || $interaction == $interactions[79] || $interaction == $interactions[80] || $interaction == $interactions[81] || $interaction == $interactions[82] || $interaction == $interactions[83] || $interaction == $interactions[84] || $interaction == $interactions[85] || $interaction == $interactions[86] || $interaction == $interactions[87] || $interaction == $interactions[88] || $interaction == $interactions[95] || $interaction == $interactions[97] || $interaction == $interactions[103] || $interaction == $interactions[104] || $interaction == $interactions[105] || $interaction == $interactions[106] || $interaction == $interactions[107] || $interaction == $interactions[108] || $interaction == $interactions[109] || $interaction == $interactions[110] || $interaction == $interactions[118] || $interaction == $interactions[119] || $interaction == $interactions[120] || $interaction == $interactions[121] || $interaction == $interactions[125]) {
                    $interactiontype1 = "SC";
                    $numberc[$fullname]++;
                } elseif ($interaction == $interactions[0] || $interaction == $interactions[1] || $interaction == $interactions[2] || $interaction == $interactions[3] || $interaction == $interactions[4] || $interaction == $interactions[5] || $interaction == $interactions[6] || $interaction == $interactions[7] || $interaction == $interactions[8] || $interaction == $interactions[9] || $interaction == $interactions[10] || $interaction == $interactions[11] || $interaction == $interactions[14] || $interaction == $interactions[15] || $interaction == $interactions[16] || $interaction == $interactions[17] || $interaction == $interactions[18] || $interaction == $interactions[25] || $interaction == $interactions[27] || $interaction == $interactions[28] || $interaction == $interactions[30] || $interaction == $interactions[31] || $interaction == $interactions[32] || $interaction == $interactions[33] || $interaction == $interactions[34] || $interaction == $interactions[35] || $interaction == $interactions[42] || $interaction == $interactions[43] || $interaction == $interactions[44] || $interaction == $interactions[45] || $interaction == $interactions[46] || $interaction == $interactions[54] || $interaction == $interactions[58] || $interaction == $interactions[60] || $interaction == $interactions[63] || $interaction == $interactions[64] || $interaction == $interactions[89] || $interaction == $interactions[90] || $interaction == $interactions[91] || $interaction == $interactions[92] || $interaction == $interactions[93] || $interaction == $interactions[94] || $interaction == $interactions[96] || $interaction == $interactions[98] || $interaction == $interactions[99] || $interaction == $interactions[100] || $interaction == $interactions[101] || $interaction == $interactions[102] || $interaction == $interactions[113] || $interaction == $interactions[114] || $interaction == $interactions[115] || $interaction == $interactions[116] || $interaction == $interactions[122] || $interaction == $interactions[123] || $interaction == $interactions[124] || $interaction == $interactions[125] || $interaction == $interactions[126] || $interaction == $interactions[127] || $interaction == $interactions[128] || $interaction == $interactions[129] || $interaction == $interactions[130] || $interaction == $interactions[131] || $interaction == $interactions[132] || $interaction == $interactions[133] || $interaction == $interactions[134] || $interaction == $interactions[135] || $interaction == $interactions[136] || $interaction == $interactions[137] || $interaction == $interactions[138]) {
                    $interactiontype1 = "SSY";
                    $numberd[$fullname]++;
                } else {
                    $interactiontype1 = "ND";
                }

                if ($interaction == $interactions[19] || $interaction == $interactions[36] || $interaction == $interactions[37] || $interaction == $interactions[38] || $interaction == $interactions[39] || $interaction == $interactions[40] || $interaction == $interactions[41] || $interaction == $interactions[42] || $interaction == $interactions[43] || $interaction == $interactions[44] || $interaction == $interactions[45] || $interaction == $interactions[46] || $interaction == $interactions[47] || $interaction == $interactions[48] || $interaction == $interactions[49] || $interaction == $interactions[50] || $interaction == $interactions[51] || $interaction == $interactions[52] || $interaction == $interactions[65] || $interaction == $interactions[66] || $interaction == $interactions[67] || $interaction == $interactions[68] || $interaction == $interactions[69] || $interaction == $interactions[70] || $interaction == $interactions[71] || $interaction == $interactions[72] || $interaction == $interactions[73] || $interaction == $interactions[74] || $interaction == $interactions[75] || $interaction == $interactions[76] || $interaction == $interactions[77] || $interaction == $interactions[78] || $interaction == $interactions[79] || $interaction == $interactions[80] || $interaction == $interactions[81] || $interaction == $interactions[82] || $interaction == $interactions[83] || $interaction == $interactions[84] || $interaction == $interactions[85] || $interaction == $interactions[86] || $interaction == $interactions[87] || $interaction == $interactions[88] || $interaction == $interactions[103] || $interaction == $interactions[104] || $interaction == $interactions[105] || $interaction == $interactions[106] || $interaction == $interactions[107] || $interaction == $interactions[108] || $interaction == $interactions[109] || $interaction == $interactions[110] || $interaction == $interactions[118] || $interaction == $interactions[119] || $interaction == $interactions[120] || $interaction == $interactions[121]) {
                    $interactiontype2 = "TC";
                    $numbere[$fullname]++;
                } elseif ($interaction == $interactions[12] || $interaction == $interactions[13] || $interaction == $interactions[14] || $interaction == $interactions[15] || $interaction == $interactions[16] || $interaction == $interactions[17] || $interaction == $interactions[25] || $interaction == $interactions[26] || $interaction == $interactions[27] || $interaction == $interactions[28] || $interaction == $interactions[29] || $interaction == $interactions[53] || $interaction == $interactions[54] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction == $interactions[58] || $interaction == $interactions[59] || $interaction == $interactions[60] || $interaction == $interactions[61] || $interaction == $interactions[62] || $interaction == $interactions[63] || $interaction == $interactions[64]) {
                    $interactiontype2 = "CCI";
                    $numberf[$fullname]++;
                } elseif ($interaction == $interactions[20] || $interaction == $interactions[21] || $interaction == $interactions[22] || $interaction == $interactions[23] || $interaction == $interactions[24] || $interaction == $interactions[30] || $interaction == $interactions[31] || $interaction == $interactions[32] || $interaction == $interactions[33] || $interaction == $interactions[34] || $interaction == $interactions[35] || $interaction == $interactions[89] || $interaction == $interactions[90] || $interaction == $interactions[91] || $interaction == $interactions[92] || $interaction == $interactions[93] || $interaction == $interactions[94] || $interaction == $interactions[95] || $interaction == $interactions[96] || $interaction == $interactions[97] || $interaction == $interactions[98] || $interaction == $interactions[99] || $interaction == $interactions[100] || $interaction == $interactions[101] || $interaction == $interactions[102] || $interaction == $interactions[122] || $interaction == $interactions[123] || $interaction == $interactions[124] || $interaction == $interactions[125] || $interaction == $interactions[126] || $interaction == $interactions[127] || $interaction == $interactions[128] || $interaction == $interactions[129] || $interaction == $interactions[130] || $interaction == $interactions[131] || $interaction == $interactions[132] || $interaction == $interactions[133] || $interaction == $interactions[134] || $interaction == $interactions[135] || $interaction == $interactions[136] || $interaction == $interactions[137] || $interaction == $interactions[138] || $interaction == $interactions[139] || $interaction == $interactions[140]) {
                    $interactiontype2 = "ES";
                    $numberg[$fullname]++;
                } elseif ($interaction == $interactions[111] || $interaction == $interactions[112] || $interaction == $interactions[113] || $interaction == $interactions[114] || $interaction == $interactions[115] || $interaction == $interactions[116] || $interaction == $interactions[117]) {
                    $interactiontype2 = "ECT";
                    $numberh[$fullname]++;
                } else {
                    $interactiontype2 = "ND";
                }

                if ($interaction == $interactions[3] || $interaction == $interactions[4] || $interaction == $interactions[5] || $interaction == $interactions[12] || $interaction == $interactions[14] || $interaction == $interactions[15] || $interaction == $interactions[16] || $interaction == $interactions[17] || $interaction == $interactions[19] || $interaction == $interactions[21] || $interaction == $interactions[22] || $interaction == $interactions[24] || $interaction == $interactions[26] || $interaction == $interactions[27] || $interaction == $interactions[29] || $interaction == $interactions[31] || $interaction == $interactions[32] || $interaction == $interactions[34] || $interaction == $interactions[35] || $interaction == $interactions[37] || $interaction == $interactions[38] || $interaction == $interactions[39] || $interaction == $interactions[40] || $interaction == $interactions[41] || $interaction == $interactions[42] || $interaction == $interactions[43] || $interaction == $interactions[44] || $interaction == $interactions[45] || $interaction == $interactions[46] || $interaction == $interactions[51] || $interaction == $interactions[52] || $interaction == $interactions[53] || $interaction == $interactions[54] || $interaction == $interactions[55] || $interaction == $interactions[56] || $interaction == $interactions[57] || $interaction == $interactions[58] || $interaction == $interactions[59] || $interaction == $interactions[63] || $interaction == $interactions[64] || $interaction == $interactions[65] || $interaction == $interactions[66] || $interaction == $interactions[69] || $interaction == $interactions[70] || $interaction == $interactions[71] || $interaction == $interactions[72] || $interaction == $interactions[73] || $interaction == $interactions[74] || $interaction == $interactions[78] || $interaction == $interactions[79] || $interaction == $interactions[80] || $interaction == $interactions[81] || $interaction == $interactions[82] || $interaction == $interactions[83] || $interaction == $interactions[87] || $interaction == $interactions[88] || $interaction == $interactions[89] || $interaction == $interactions[90] || $interaction == $interactions[93] || $interaction == $interactions[94] || $interaction == $interactions[96] || $interaction == $interactions[98] || $interaction == $interactions[99] || $interaction == $interactions[100] || $interaction == $interactions[101] || $interaction == $interactions[102] || $interaction == $interactions[105] || $interaction == $interactions[106] || $interaction == $interactions[109] || $interaction == $interactions[110] || $interaction == $interactions[111] || $interaction == $interactions[112] || $interaction == $interactions[113] || $interaction == $interactions[117] || $interaction == $interactions[120] || $interaction == $interactions[121] || $interaction == $interactions[122] || $interaction == $interactions[123] || $interaction == $interactions[126] || $interaction == $interactions[127] || $interaction == $interactions[129] || $interaction == $interactions[130] || $interaction == $interactions[131] || $interaction == $interactions[132] || $interaction == $interactions[134] || $interaction == $interactions[135] || $interaction == $interactions[136] || $interaction == $interactions[137] || $interaction == $interactions[138] || $interaction == $interactions[139] || $interaction == $interactions[140]) {
                    $interactiontype3 = "ACT";
                    $numberi[$fullname]++;
                } elseif ($interaction == $interactions[0] || $interaction == $interactions[1] || $interaction == $interactions[2] || $interaction == $interactions[6] || $interaction == $interactions[7] || $interaction == $interactions[8] || $interaction == $interactions[9] || $interaction == $interactions[10] || $interaction == $interactions[11] || $interaction == $interactions[13] || $interaction == $interactions[18] || $interaction == $interactions[20] || $interaction == $interactions[23] || $interaction == $interactions[25] || $interaction == $interactions[28] || $interaction == $interactions[30] || $interaction == $interactions[33] || $interaction == $interactions[36] || $interaction == $interactions[47] || $interaction == $interactions[48] || $interaction == $interactions[49] || $interaction == $interactions[50] || $interaction == $interactions[58] || $interaction == $interactions[60] || $interaction == $interactions[61] || $interaction == $interactions[62] || $interaction == $interactions[67] || $interaction == $interactions[68] || $interaction == $interactions[75] || $interaction == $interactions[76] || $interaction == $interactions[77] || $interaction == $interactions[84] || $interaction == $interactions[85] || $interaction == $interactions[86] || $interaction == $interactions[91] || $interaction == $interactions[92] || $interaction == $interactions[95] || $interaction == $interactions[97] || $interaction == $interactions[103] || $interaction == $interactions[104] || $interaction == $interactions[107] || $interaction == $interactions[108] || $interaction == $interactions[113] || $interaction == $interactions[114] || $interaction == $interactions[115] || $interaction == $interactions[116] || $interaction == $interactions[118] || $interaction == $interactions[119] || $interaction == $interactions[124] || $interaction == $interactions[125] || $interaction == $interactions[128] || $interaction == $interactions[133]) {
                    $interactiontype3 = "PAS";
                    $numberj[$fullname]++;
                } else {
                    $interactiontype3 = "ND";
                }

                if ($interaction == $interactions[141]) {
                    $interactiontype4 = "IN";
                    $numberk[$fullname]++;
                } elseif ($interaction == $interactions[142]) {
                    $interactiontype4 = "OUT";
                    $numberl[$fullname]++;
                } else {
                    $interactiontype4 = "ND";
                }

                $myxls->write($row, 0, $courses[$log->course], '');
                $myxls->write_date($row, 1, $log->time, $formatDate); // write_date() does conversion/timezone support. MDL-14934
                // $myxls->write($row, 2, $log->ip, '');
                //$fullnames[$fullname] = $fullname;
                //$myxls->write($row, 3, $fullnames[$fullname], '');
                $myxls->write($row, 2, 'IP oculta', '');
                $fullnames[$fullname] = $log->userid;
                $myxls->write($row, 3, $log->userid, '');
                $myxls->write($row, 4, $log->module . ' ' . $log->action, '');
                $myxls->write($row, 5, $log->info, '');
                $myxls->write($row, 6, $interactiontype1, '');
                $myxls->write($row, 7, $interactiontype2, '');
                $myxls->write($row, 8, $interactiontype3, '');
                $myxls->write($row, 9, $interactiontype4, '');
                $myxls->write($row, 10, $ownrole_id, '');
                $myxls->write($row, 11, $linf, '');
                $myxls->write($row, 12, $cmid, '');
                $myxls->write($row, 13, $path, '');
                $row++;
        }


        //        $myxls->write($row, 12, $log->course, '');



        //$roles = get_user_roles($context, $log->userid);
        //foreach ($roles as $role) {
        //$myxls->write($row, 10, $role->roleid, '');
        //$myxls->write($row, 11, $role->name, '');
        //}


    }

    $myxls = &$worksheet[$wsnumber];

    //$headers2 = array("Usuario", "Interacciones segun los agentes que intervienen", "Numero", "Interacciones segun su finalidad", "Numero", "Interacciones segun la accion", "Numero", "Interacciones entrada/salida", "Numero");
    $headers2 = array(
        "Usuario", "Alumno-Alumno", "Alumno-Profesor", "Alumno-Contenido", "Alumno-Sistema", "Transmitir contenidos", "Crear interacciones de clase",
        "Evaluar estudiantes", "Evaluar curso y profesores", "Activas", "Pasivas", "Entrada", "Salida"
    );
    foreach ($headers2 as $item) {
        $worksheet[$wsnumber]->write(0, $col2, $item, '');
        $col2++;
    }
    $row2 = 1;



    foreach ($fullnames as $value) {
        // var_dump($fullnames);
        // var_dump($numbera);
        // var_dump($numberb);
        // var_dump($numberc);
        // var_dump($numberd);
        // die();
        $myxls->write($row2, 0, $value, '');
        //$myxls->write($row2, 1, $courses[$log->course], '');
        $myxls->write($row2, 1, is_null($numbera[$value]) ? '0' : $numbera[$value], '');
        $myxls->write($row2, 2, is_null($numberb[$value]) ? '0' : $numberb[$value], '');
        $myxls->write($row2, 3, is_null($numberc[$value]) ? '0' : $numberc[$value], '');
        $myxls->write($row2, 4, is_null($numberd[$value]) ? '0' : $numberd[$value], '');
        $myxls->write($row2, 5, is_null($numbere[$value]) ? '0' : $numbere[$value], '');
        $myxls->write($row2, 6, is_null($numberf[$value]) ? '0' : $numberf[$value], '');
        $myxls->write($row2, 7, is_null($numberg[$value]) ? '0' : $numberg[$value], '');
        $myxls->write($row2, 8, is_null($numberh[$value]) ? '0' : $numberh[$value], '');
        $myxls->write($row2, 9, is_null($numberi[$value]) ? '0' : $numberi[$value], '');
        $myxls->write($row2, 10, is_null($numberj[$value]) ? '0' : $numberj[$value], '');
        $myxls->write($row2, 11, is_null($numberk[$value]) ? '0' : $numberk[$value], '');
        $myxls->write($row2, 12, is_null($numberl[$value]) ? '0' : $numberl[$value], '');
        $row2++;
        // $myxls->write($row2, 0, $value, '');
        // //$myxls->write($row2, 1, $courses[$log->course], '');
        // $myxls->write($row2, 1, "alumno-alumno", '');
        // $myxls->write($row2, 2, $numbera[$value], '');
        // $myxls->write($row2, 3, "transmitir contenidos", '');
        // $myxls->write($row2, 4, $numbere[$value], '');
        // $myxls->write($row2, 5, "activas", '');
        // $myxls->write($row2, 6, $numberi[$value], '');
        // $myxls->write($row2, 7, "entrada", '');
        // $myxls->write($row2, 8, $numberk[$value], '');
        // $row2++;
        // $myxls->write($row2, 1, "alumno-profesor", '');
        // $myxls->write($row2, 2, $numberb[$value], '');
        // $myxls->write($row2, 3, "crear interacciones de clase", '');
        // $myxls->write($row2, 4, $numberf[$value], '');
        // $myxls->write($row2, 5, "pasivas", '');
        // $myxls->write($row2, 6, $numberj[$value], '');
        // $myxls->write($row2, 7, "salida", '');
        // $myxls->write($row2, 8, $numberl[$value], '');
        // $row2++;
        // $myxls->write($row2, 1, "alumno-contenido", '');
        // $myxls->write($row2, 2, $numberc[$value], '');
        // $myxls->write($row2, 3, "evaluar estudiantes", '');
        // $myxls->write($row2, 4, $numberg[$value], '');
        // $row2++;
        // $myxls->write($row2, 1, "alumno-sistema", '');
        // $myxls->write($row2, 2, $numberd[$value], '');
        // $myxls->write($row2, 3, "evaluar curso y profesores", '');
        // $myxls->write($row2, 4, $numberh[$value], '');
        // $row2++;

    }
    $useridto = $DB->get_records_sql("select useridto from mdl_message where timecreated = $log->time");
    $useridfrom = $DB->get_records_sql("select useridfrom from mdl_message where timecreated = $log->time");

    $workbook->close();
    return true;
}
