<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 *  local_extension plugin renderer
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Extension renderer class.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_extension_renderer extends plugin_renderer_base {

    /**
     * Extension comment renderer.
     *
     * @param \local_extension\request $req The extension request object.
     * @return string $out The html output.
     */
    public function render_extension_comments(\local_extension\request $req) {
        $out = '';

        $out .= html_writer::start_tag('div', ['class' => 'comments']);

        // Fetch the comments, state changes and file attachments.
        $indexed = [];

        $history = $req->get_history();
        foreach ($history as $comment) {
            $indexed[$comment->timestamp][$comment->userid][] = $comment;
        }

        if (!empty($indexed)) {
            $out .= html_writer::tag('div', get_string('comments', 'local_extension'), ['class' => 'statusheader']);
        }

        // Initial loop for items that have the same timestamp.
        foreach ($indexed as $timestamp => $userid) {
            // First inner loop for items that have the same userid.
            foreach ($userid as $id => $items) {

                $req->sort_history($items);

                $comment = new stdClass();
                $message = '';
                // Second inner loop for collating the message content into one status item.
                foreach ($items as $item) {
                    $message .= html_writer::tag('p', $item->message);
                }

                $comment->timestamp = $items[0]->timestamp;
                $comment->userid = $items[0]->userid;
                $comment->message = $message;
                $out .= $this->render_single_comment($req, $comment);
            }
        }

        $out .= html_writer::end_div(); // End .comments.

        return $out;
    }

    /**
     * Helper function to render a single comment. Also used in email notifications.
     *
     * @param \local_extension\request $req
     * @param stdClass $comment
     * @return string $out
     */
    public function render_single_comment(\local_extension\request $req, $comment) {
        $class = 'content';
        $out = '';

        if (array_key_exists($comment->userid, $req->users)) {
            $user = $req->users[$comment->userid];
        } else {
            $user = \core_user::get_user($comment->userid);
        }

        $out .= html_writer::start_tag('div', ['class' => 'comment']);

        $out .= html_writer::start_tag('div', ['class' => 'avatar']);
        $out .= $this->output->user_picture($user, ['size' => 50]);
        $out .= html_writer::end_div(); // End .avatar.

        $out .= html_writer::start_tag('div', ['class' => $class]);
        $out .= html_writer::tag('span', fullname($user), ['class' => 'name']);
        if (!empty($user->idnumber)) {
            $out .= html_writer::tag('span', " ({$user->idnumber})", ['class' => 'useridnumber']);
        }
        $out .= html_writer::tag('span', ' - ' . $this->render_role($req, $user->id), ['class' => 'role']);
        $out .= html_writer::tag('span', ' - ' . $this->render_time($comment->timestamp), ['class' => 'time']);

        $out .= html_writer::start_tag('div', ['class' => 'message']);
        $out .= html_writer::div(format_text(trim($comment->message), FORMAT_MOODLE), 'comment');
        $out .= html_writer::end_div(); // End .message.

        $out .= html_writer::end_div(); // End .content.

        $out .= html_writer::end_div(); // End .comment.

        return $out;
    }

    /**
     * Renders role information
     *
     * @param \local_extension\request $req
     * @param integer $userid
     * @return string The html output.
     */
    public function render_role($req, $userid) {
        $details = '';
        $rolename = '';
        $roles = [];

        // Roles are scoped to the enrollment status in courses.
        foreach ($req->mods as $cmid => $mod) {
            $course = $mod->course;
            $context = \context_course::instance($course->id);
            $roles = get_user_roles($context, $userid, true);

            foreach ($roles as $role) {
                $rolename = role_get_name($role, $context);
                if (!empty($rolename)) {
                    $details .= "{$rolename} - {$course->fullname}\n";
                }
            }
        }

        return html_writer::tag('abbr', $rolename, ['title' => $details]);

    }

    /**
     * Render nice times
     *
     * @param integer $time The time to show
     * @param boolean $showdate If this is set, then print the full date instead of 'time ago'.
     * @return string The html output.
     */
    public function render_time($time, $showdate = false) {
        $delta = time() - $time;

        // The nice delta.

        // Just show the biggest time unit instead of 2.
        $show = format_time($delta);
        $num = strtok($show, ' ');
        $unit = strtok(' ');
        $show = "$num $unit ".get_string('ago', 'local_extension');

        // The full date.
        $fulldate = userdate($time, get_string('strftime_datetime', 'local_extension'));

        return "{$fulldate} ($show)";
    }

    /**
     * Extension attachment file renderer.
     *
     * @param \local_extension\request $req The extension request object.
     * @return string $out The html output.
     */
    public function render_extension_attachments(\local_extension\request $req) {
        list($fs, $files) = $req->fetch_attachments();

        $out  = html_writer::start_tag('div', ['class' => 'attachments']);
        $out .= html_writer::tag('div', get_string('attachments', 'local_extension'), ['class' => 'statusheader']);
        $out .= html_writer::start_tag('ul');

        foreach ($files as $file) {
            /* @var stored_file $file IDE hinting */

            $f = $fs->get_file(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );

            if (!$f || $f->is_directory()) {
                continue;
            }

            $fileurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );

            $user = core_user::get_user($file->get_userid());

            $obj = new stdClass();
            $obj->file = $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file)) .
                ' ' . html_writer::link($fileurl, $f->get_filename());
            $obj->user = fullname($user);
            $obj->date = userdate($file->get_timecreated());

            $out .= html_writer::start_tag('li', ['class' => 'attachment']);
            $out .= get_string('attachment_detail', 'local_extension', $obj);
            $out .= html_writer::end_tag('li'); // End .attachment.

        }

        $out .= html_writer::end_tag('ul'); // End .attachment.
        $out .= html_writer::end_div(); // End .attachments.

        // The first file will be '.'
        if (count($files) > 1) {
            return $out;
        }
    }

    /**
     * Extension status email renderer.
     *
     * @param request $req The extension request object.
     * @return string $out The html output.
     */
    public function render_extension_email(\local_extension\request $req) {
    }

    /**
     * Render a summary of all requests in a table.
     *
     * @param flexible_table $table
     * @param array $requests
     */
    public function render_extension_summary_table($table, $requests) {

        if (!empty($requests)) {

            foreach ($requests as $request) {
                $statusurl = new moodle_url("/local/extension/status.php", ['id' => $request->id]);
                $status = get_string("table_header_statusrow", "local_extension", $statusurl->out());

                $values = [
                    $request->id,
                    $request->count,
                    userdate($request->timestamp),
                    userdate($request->lastmod),
                    $status,
                    fullname(\core_user::get_user($request->userid)),
                ];
                $table->add_data($values);

            }
        }

        return $table->finish_output();
    }

    /**
     * Adapter trigger renderer for status management page.
     *
     * @param \local_extension\rule $rule
     * @param string $parentstr The name of the parent trigger.
     * @return string $html The html output.
     */
    public function render_trigger_rule_text($rule, $parentstr) {
        $html  = html_writer::start_tag('div');

        $html .= html_writer::tag('p', $rule->name);

        if (empty($parentstr)) {
            $activate = get_string('form_rule_label_parent_allways', 'local_extension');
            $html .= html_writer::tag('p', $activate);
        } else {
            $activate = [
                get_string('form_rule_label_parent', 'local_extension'),
                $parentstr,
                get_string('form_rule_label_parent_end', 'local_extension'),
            ];
            $html .= html_writer::tag('p', implode(' ', $activate));
        }

        $lengthtype = $rule->rule_type($rule->lengthtype);

        $reqlength = [
            get_string('form_rule_label_request_length', 'local_extension'),
            $lengthtype,
            $rule->lengthfromduedate,
            get_string('form_rule_label_days_long', 'local_extension'),
        ];
        $html .= html_writer::tag('p', implode(' ', $reqlength));

        $elapsedtype = $rule->rule_type($rule->elapsedtype);

        $elapsedlength = [
            get_string('form_rule_label_elapsed_length', 'local_extension'),
            $elapsedtype,
            $rule->elapsedfromrequest,
            get_string('form_rule_label_weekdays_old', 'local_extension'),
        ];
        $html .= html_writer::tag('p', implode(' ', $elapsedlength));

        $setroles = [
            get_string('form_rule_label_set_roles', 'local_extension'),
            $rule->get_role_name(),
            get_string('form_rule_label_to', 'local_extension'),
            $rule->get_action_name(),
            get_string('form_rule_label_this_request', 'local_extension'),
        ];
        $html .= html_writer::tag('p', implode(' ', $setroles));

        $html .= html_writer::end_div();

        return $html;
    }


    /**
     * Renders a dropdown select box with the available rule type handlers.
     *
     * @param array $mods
     * @param moodle_url $url
     * @return string $html
     */
    public function render_manage_new_rule($mods, $url) {
        $stredit = get_string('button_edit_rule', 'local_extension');

        $options = [];

        foreach ($mods as $mod) {
            $options[$mod->get_data_type()] = $mod->get_name();
        }

        $html = $this->single_select($url, 'datatype', $options, '', ['' => $stredit], 'newfieldform');

        return $html;
    }

    /**
     * Prints the list of rules, and child rules that may be deleted on manage.php
     *
     * @param \local_extension\rule $branch
     * @param string $html
     * @return string
     */
    public function render_delete_rules($branch, $html = null) {
        if (empty($html)) {
            $html = '';
        }

        foreach ($branch as $rule) {
            if (!empty($rule->parentrule->name)) {
                $parentstr = $rule->parentrule->name;
            } else {
                $parentstr = '';
            }

            $html .= html_writer::start_div('manageruleitem');
            $html .= $this->render_trigger_rule_text($rule, $parentstr);
            $html .= html_writer::end_div();
            $html .= html_writer::empty_tag('br');

            if (!empty($rule->children)) {
                $html = $this->render_delete_rules($rule->children, $html);
            }
        }

        return $html;
    }

    /**
     * Renders the request policy that has been defined in the administration configuration.
     *
     * @return null|string
     */
    public function render_policy() {

        $policy = get_config('local_extension', 'extensionpolicyrequest');

        // Moodle rich text editor may leave a <br> in an empty editor.

        if (!empty($policy)) {
            return html_writer::div($policy, 'policy');
        }

        return null;
    }

    /**
     * Renders the request policy that has been defined in the administration configuration.
     *
     * @return null|string
     */
    public function render_status_policy() {

        $policy = get_config('local_extension', 'extensionpolicystatus');

        // Moodle rich text editor may leave a <br> in an empty editor.

        if (!empty($policy)) {
            return html_writer::div($policy, 'policy');
        }

        return null;
    }


    /**
     * Renders a 'weeks' selection box allowing someone to search for requests in the future.
     *
     * @param int $courseid
     * @param int $cmid
     * @param int $searchback
     * @param int $searchforward
     * @return string
     */
    public function render_request_search_controls($courseid, $cmid, $searchback, $searchforward) {
        $maxweeksbackward = get_config('local_extension', 'searchbackwardmaxweeks');
        $maxweeksforward = get_config('local_extension', 'searchforwardmaxweeks');
        $searchforwarddefault = get_config('local_extension', 'searchforward');
        $searchbackwarddefault = get_config('local_extension', 'searchback');

        // Return with a warning message that the look ahead length has exceeded the configured setting.
        if ($searchforward > $maxweeksforward * 7) {
            return html_writer::span(get_string('page_request_outofrange', 'local_extension'));
        }

        // Return with a warning message that the look ahead length has exceeded the configured setting.
        if ($searchback > $maxweeksbackward * 7) {
            return html_writer::span(get_string('page_request_outofrange', 'local_extension'));
        }

        $controlstable = new html_table();
        $controlstable->attributes['class'] = 'controls';
        $controlstable->cellspacing = 0;
        $controlstable->data[] = new html_table_row();

        $popupurl = new moodle_url('/local/extension/request.php', [
            'course'  => $courseid,
            'cmid'    => $cmid,
            'forward' => $searchforward,
        ]);

        // Searching backward.
        $backwardlist = [];
        for ($i = 1; $i <= $maxweeksbackward; $i++) {

            // Dont add 'weeks' that are less than the default search length.
            $defaultweeks = $searchbackwarddefault / 7;

            if ($i < $defaultweeks) {
                continue;
            }

            $week  = get_string('week', 'local_extension', $i);
            $weeks = get_string('week_plural', 'local_extension', $i);

            $value = 7 * $i;

            if ($i == 1) {
                $backwardlist[$value] = $week;
            } else {
                $backwardlist[$value] = $weeks;
            }
        }

        $select = new single_select($popupurl, 'back', $backwardlist, $searchback, null, 'backform');
        $select->set_label(get_string('page_request_searchbackward', 'local_extension'));

        $html = $this->render($select);

        $searchforwardcell = new html_table_cell();
        $searchforwardcell->attributes['class'] = 'right';
        $searchforwardcell->text = $html;

        $controlstable->data[0]->cells[] = $searchforwardcell;

        // Searcahing forward.
        $popupurl = new moodle_url('/local/extension/request.php', [
            'course'  => $courseid,
            'cmid'    => $cmid,
            'back'    => $searchback,
        ]);

        $forwardlist = [];

        for ($i = 1; $i <= $maxweeksforward; $i++) {

            // Dont add 'weeks' that are less than the default searchforward length.
            $defaultweeks = $searchforwarddefault / 7;

            if ($i < $defaultweeks) {
                continue;
            }

            $week  = get_string('week', 'local_extension', $i);
            $weeks = get_string('week_plural', 'local_extension', $i);

            $value = 7 * $i;

            if ($i == 1) {
                $forwardlist[$value] = $week;
            } else {
                $forwardlist[$value] = $weeks;
            }
        }

        $select = new single_select($popupurl, 'forward', $forwardlist, $searchforward, null, 'forwardform');
        $select->set_label(get_string('page_request_searchforward', 'local_extension'));

        $html = $this->render($select);

        $searchforwardcell = new html_table_cell();
        $searchforwardcell->attributes['class'] = 'right';
        $searchforwardcell->text = $html;

        $controlstable->data[0]->cells[] = $searchforwardcell;

        return html_writer::table($controlstable);

    }

    /**
     * Renders a search item to help assist filtering requests.
     *
     * @param \context $context
     * @param int $categoryid
     * @param int $courseid
     * @param int $stateid
     * @param \moodle_url $baseurl
     * @param string $search
     * @param string $faculty
     * @return string
     */
    public function render_index_search_controls($context, $categoryid, $courseid, $stateid, $baseurl, $search, $faculty) {
        global $CFG;

        // $courseid = empty($courseid) ? 1 : $courseid;
        $courselist = [];
        $hascapability = false;

        if (has_capability('local/extension:viewallrequests', $context)) {
            $hascapability = true;
        }

        if (has_capability('local/extension:modifyrequeststatus', $context)) {
            $hascapability = true;
        }

        if ($CFG->branch < 36) {
            include_once($CFG->libdir . '/coursecatlib.php');

            $categorylist = \core_course_category::make_categories_list('local/extension:viewallrequests');
            if (!empty($categoryid)) {
                $courselist = \core_course_category::get($categoryid)->get_courses();
            } else {
                $courselist = \core_course_category::get(0)->get_courses(['recursive' => true]);
            }
        } else {
            $categorylist = \core_course_category::make_categories_list('local/extension:viewallrequests');
            if (!empty($categoryid)) {
                $courselist = \core_course_category::get($categoryid)->get_courses();
            } else {
                $courselist = \core_course_category::get(0)->get_courses(['recursive' => true]);
            }
        }

        $mycourses = enrol_get_my_courses();

        // Check if the courseid exists in the list of options, we may have changed the faculty filter.
        if (!array_key_exists($courseid, $courselist)) {
            $baseurl->remove_params('id');
            $this->page->set_url($baseurl);
        }

        $searchelements = [];

        // Categories.
        $categoryselect = $this->render_category_select($categorylist, $categoryid, $baseurl, $hascapability);
        if ($categoryselect) {
            $strcategories = get_string('page_index_categories', 'local_extension');
            $searchelements[$strcategories] = $categoryselect;
        }

        // Faculties.
        $facultyselect = $this->render_faculty_select($categorylist, $courselist, $faculty, $baseurl, $hascapability);
        if ($facultyselect) {
            $strfaculties = get_string('page_index_faculties', 'local_extension');
            $searchelements[$strfaculties] = $facultyselect;
        }

        // Courses.
        $courseselect = $this->render_all_courses_select($categorylist, $faculty, $courselist, $courseid, $baseurl, $hascapability);
        if ($courseselect) {
            $strcourses = get_string('page_index_courses', 'local_extension');
            $searchelements[$strcourses] = $courseselect;
        }

        // The users enrolled courses.
        if ($mycourses) {
            $mycoursesselect = $this->render_my_enrolled_courses_select($categorylist, $courselist, $mycourses, $courseid, $faculty, $baseurl);
            $strmycourses = get_string('page_index_mycourses', 'local_extension');
            $searchelements[$strmycourses] = $mycoursesselect;
        }

        // State select.
        $stateselect = $this->render_state_select($stateid, $baseurl);
        $strstate = get_string('state', 'local_extension');
        $searchelements[$strstate] = $stateselect;

        // Search field.
        $searchfield = $this->render_search_course($search, $baseurl);
        $strsearch = get_string('renderer_search_text', 'local_extension');
        $searchelements[$strsearch] = $searchfield;

        // Prints the resulting filter row as a table.
        $controlstable = new html_table();
        $controlstable->attributes['class'] = 'controls';
        $controlstable->data[] = new html_table_row();

        foreach ($searchelements as $string => $select) {
            $html  = html_writer::span($string, '', ['id' => $string]);
            $html .= $select;
            $categorycell = new html_table_cell();
            $categorycell->attributes['class'] = 'right';
            $categorycell->text = $html;
            $controlstable->data[0]->cells[] = $categorycell;
        }

        return html_writer::table($controlstable);
    }

    /**
     * Renders the category select.
     *
     * @param array $categorylist
     * @param integer $categoryid
     * @param \moodle_url $baseurl
     * @param boolean $hascapability
     * @return string
     */
    public function render_category_select($categorylist, $categoryid, $baseurl, $hascapability) {
        // Display a list of categories only with requirements.
        // 1. Has the capability local/extension:viewallrequests and 'categories' exist.
        // 2. OR has the capability local/extension:viewallrequests.
        // 3. OR has the capability local/extension:modifyrequeststatus.
        if (!empty($categorylist) || $hascapability) {

            $cats = [];
            global $CFG;

            // Add the top level 'Top' element.
            if ($CFG->branch < 35) {
                $cats[0] = \core_course_category::get(0)->get_formatted_name();
            } else {
                $cats[0] = \core_course_category::get(0)->get_formatted_name();
            }

            $cats += $categorylist;

            $newurl = clone $baseurl;
            $newurl->remove_params('id');

            $select = new single_select($newurl, 'catid', $cats, $categoryid, null, 'catform');

            return $this->render($select);
        }
    }

    /**
     * Renders the faculty select.
     *
     * @param array $categorylist
     * @param array $courselist
     * @param string $faculty
     * @param \moodle_url $baseurl
     * @param boolean $hascapability
     * @return string
     */
    public function render_faculty_select($categorylist, $courselist, $faculty, $baseurl, $hascapability) {
        // Display a list of faculties to filter by.
        if (!empty($categorylist) || $hascapability) {

            $options = [];

            // TODO add regex as configuration item.
            $re = "/^([A-Z]+)[0-9]+_[0-9]+/i";

            // Create an array of faculties that should match the regular expression.
            // This ends up distinct as we overwrite keys.
            foreach ($courselist as $course) {

                if (preg_match($re, $course->shortname, $matches)) {

                    $options[$matches[1]] = $matches[1];
                }
            }

            $newurl = clone $baseurl;
            $newurl->remove_params('id');

            // If the faculty has changed, remove it from the post parameters.
            if (!array_key_exists($faculty, $options)) {
                $newurl->remove_params('faculty');
            }

            asort($options);

            $options = ['0' => get_string('page_index_all', 'local_extension')] + $options;

            $select = new single_select($newurl, 'faculty', $options, $faculty, null, 'facultyform');

            return $this->render($select);
        }
    }

    /**
     * Renders the list of all courses.
     *
     * @param array $categorylist
     * @param string $faculty
     * @param array $courselist
     * @param integer $courseid
     * @param \moodle_url $baseurl
     * @param boolean $hascapability
     * @return string
     */
    public function render_all_courses_select($categorylist, $faculty, $courselist, $courseid, $baseurl, $hascapability) {
        // Display a list of all courses to filter by
        if (!empty($categorylist) || $hascapability) {

            $options = [];

            foreach ($courselist as $course) {

                // If a faculty has been set then we will filter the results.
                if (!empty($faculty)) {

                    $re = "/^$faculty/i";

                    if (preg_match($re, $course->shortname)) {

                        $options[$course->id] = $course->fullname;
                    }

                } else {

                    $options[$course->id] = $course->fullname;
                }
            }

            asort($options);

            $options = ['1' => get_string('page_index_all', 'local_extension')] + $options;

            $select = new single_select($baseurl, 'id', $options, $courseid, null, 'courseform');

            return $this->render($select);
        }
    }

    /**
     * Renders the list of the users current enrolled courses.
     *
     * @param array $categorylist
     * @param array $courselist
     * @param array $mycourses
     * @param integer $courseid
     * @param string $faculty
     * @param \moodle_url $baseurl
     * @return string
     */
    public function render_my_enrolled_courses_select($categorylist, $courselist, $mycourses, $courseid, $faculty, $baseurl) {
        // global $SITE;

        if ($mycourses) {

            // $systemcontext = context_system::instance();

            $mycourselist = [];

            $mycourselist['1'] = get_string('page_index_all', 'local_extension');

            foreach ($mycourses as $mycourse) {

                $coursecontext = context_course::instance($mycourse->id);

                $mycourselist[$mycourse->id] = format_string($mycourse->fullname, true, ['context' => $coursecontext]);
            }

            /*
            if (has_capability('moodle/site:viewparticipants', $systemcontext)) {

                unset($courselist[SITEID]);

                $obj = ['context' => $systemcontext];

                $courselist = [SITEID => format_string($SITE->fullname, true, $obj)] + $courselist;
            }
            */

            $facultylist = [];
            foreach ($courselist as $course) {

                // If a faculty has been set then we will filter the results.
                if (!empty($faculty)) {

                    $re = "/^$faculty/i";

                    if (preg_match($re, $course->shortname)) {

                        $facultylist[$course->id] = $course->fullname;
                    }

                } else {

                    $facultylist[$course->id] = $course->fullname;
                }
            }

            $params = [
                'faculty' => $facultylist,
                'catid' => $categorylist,
            ];

            $newurl = clone $baseurl;
            foreach ($params as $param => $list) {
                $key = $baseurl->get_param($param);
                if (!array_key_exists($key, $list)) {
                    $newurl->param($param, $key);
                }
            }

            $newurl->remove_params('id');
            $newurl->remove_params('catid');

            $select = new single_select($newurl, 'id', $mycourselist, $courseid, null, 'mycourseform');

            return $this->render($select);
        }
    }

    /**
     * Renders the state selection.
     *
     * @param integer $stateid
     * @param \moodle_url $baseurl
     * @return string
     */
    public function render_state_select($stateid, $baseurl) {
        // Display a search filter for the status.
        $state = \local_extension\state::instance();

        $statelist = [];

        $statelist[0] = get_string('page_index_all', 'local_extension');

        foreach ($state->statearray as $sid => $name) {

            // Do not add the internal state to the list.
            if ($sid === \local_extension\state::STATE_INTERNAL) {
                continue;
            }

            $statelist[$sid] = $state->get_state_name($sid);
        }

        $select = new single_select($baseurl, 'state', $statelist, $stateid, null, 'stateform');

        return $this->render($select);
    }

    /**
     * Renders a search input form element that is used with filtering the requests based on course.
     *
     * @param string $search
     * @param \moodle_url $baseurl
     * @return string
     */
    public function render_search_course($search, $baseurl) {
        $id = html_writer::random_id('search_course_f');

        $inputattributes = [
            'type' => 'text',
            'id' => 'search',
            'name' => 'search',
            'value' => s($search),
        ];

        $formattributes = [
            'method' => 'get',
            'action' => $baseurl,
            'id' => $id,
            'class' => 'searchform'
        ];

        $html  = html_writer::input_hidden_params($baseurl, ['search']);

        $html .= html_writer::tag('input', null, $inputattributes);

        $form = html_writer::tag('form', $html, $formattributes);

        $output = html_writer::div($form, 'coursesearch');

        return $output;
    }

}
