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
 * Extension assignment request class.
 *
 * @package    extension_assign
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace extension_assign;

use local_extension\rule;
use local_extension\state;
use local_extension\utility;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Extension assignment request class.
 *
 * @package    extension_assign
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request extends \local_extension\base_request {

    /** @var rule[] $rules */
    public static $rules = null;

    /**
     * {@inheritDoc}
     * @see \local_extension\base_request::get_name()
     */
    public function get_name() {
        return 'Assignment';
    }

    /**
     * {@inheritDoc}
     * @see \local_extension\base_request::get_data_type()
     */
    public function get_data_type() {
        return 'assign';
    }

    /**
     * {@inheritDoc}
     * @see \local_extension\base_request::get_triggers()
     */
    public function get_triggers() {
        if (empty(self::$rules)) {
            self::$rules = rule::load_all($this->get_data_type());
        }
        return self::$rules;
    }

    /**
     * Is a calendar event something we can handle?
     *
     * @param event $event A calendar event object
     * @param coursemodule $cm A course module
     * @return boolean True if should be handled
     */
    public function is_candidate($event, $cm) {
        if ($event->eventtype == "due") {
            return true;
        }

        return false;
    }

    /**
     * Renders the request details in a form with a date selector.
     *
     * @param array $mod An array of event details
     * @param \MoodleQuickForm $mform A moodle form object
     * @return string
     */
    public function request_definition($mod, $mform = null) {
        $event = $mod->event;
        $course = $mod->course;

        $html = \html_writer::start_div('content');
        $coursestring = \html_writer::tag('b', $course->fullname . ' > ' . $event->name, array('class' => 'mod'));
        $str = get_string('dueon', 'extension_assign', userdate($event->timestart));
        $html .= \html_writer::tag('p', $coursestring . ' ' . $str);

        // Setup the mform due element id.
        if (!empty($mform)) {
            $html .= \html_writer::end_div(); // End .content.
            $mform->addElement('html', \html_writer::tag('p', $html));

            $this->date_selector($mod, $mform);
        }

        $html .= \html_writer::end_div(); // End .content.

        return $html;
    }

    /**
     * Renders the modify extension details in a form with a date selector.
     *
     * @param array $mod An array of event details
     * @param \MoodleQuickForm $mform A moodle form object
     * @param array $customdata mform customdata
     * @return string
     */
    public function modify_definition($mod, $mform, $customdata) {
        $event = $mod->event;
        $course = $mod->course;

        /* @var \local_extension\cm $lcm IDE hinting. */
        $lcm = $mod->localcm;
        $instance = $customdata['instance'];

        $html = \html_writer::start_div('content');
        $coursestring = \html_writer::tag('b', $course->fullname . ' > ' . $event->name, array('class' => 'mod'));
        $html .= \html_writer::tag('p', $coursestring);
        $html .= \html_writer::end_div(); // End .content.

        $mform->addElement('html', \html_writer::tag('p', $html));
        $html .= \html_writer::end_div(); // End .content.

        if ($instance->allowsubmissionsfromdate) {
            $mform->addElement('static', 'allowsubmissionsfromdate', get_string('allowsubmissionsfromdate', 'assign'),
                userdate($instance->allowsubmissionsfromdate));
        }
        if ($instance->duedate) {
            $mform->addElement('static', 'duedate', get_string('duedate', 'assign'), userdate($instance->duedate));
            $finaldate = $instance->duedate;
        }
        if ($instance->cutoffdate) {
            $mform->addElement('static', 'cutoffdate', get_string('cutoffdate', 'assign'), userdate($instance->cutoffdate));
            $finaldate = $instance->cutoffdate;
        }

        $extensionlength = utility::calculate_length($lcm->cm->length);
        if ($extensionlength) {
            $mform->addElement('static', 'cutoffdate', 'Requested extension length', $extensionlength);
        }

        $this->date_selector($mod, $mform, false);

        return $html;
    }

    /**
     * Renders the request status in a form with indicators of the request state.
     *
     * @param array $mod An array of event details
     * @param \MoodleQuickForm $mform A moodle form object
     * @return string
     */
    public function status_definition($mod, $mform = null) {
        global $USER, $DB;

        $event = $mod->event;
        $course = $mod->course;
        $localcm = $mod->localcm;
        $cm = $mod->cm;
        $state = state::instance();

        $requestid = $localcm->requestid;
        $cmid = $localcm->cmid;

        $html = \html_writer::start_div('content');

        $courseurl = new \moodle_url('/course/view.php', array('id' => $course->id));
        $courselink = \html_writer::link($courseurl, $course->fullname);

        $eventurl = new \moodle_url('/mod/' . $event->modulename . '/view.php', array('id' => $cmid));
        $eventlink = \html_writer::link($eventurl, $event->name);

        $coursestring = \html_writer::tag('b', $courselink. ' > ' . $eventlink, array('class' => 'mod'));
        $str = get_string('dueon', 'extension_assign', userdate($event->timestart));
        $html .= \html_writer::tag('p', $coursestring . ' ' . $str);

        $status = state::instance()->get_state_name($localcm->cm->state);

        $obj = new \stdClass();
        $obj->status = $status;
        $obj->length = utility::calculate_length($localcm->cm->length);
        $obj->date = userdate($localcm->cm->data);

        $status  = \html_writer::start_tag('p', array('class' => 'time'));
        $status .= get_string('status_status_line', 'local_extension', $obj);

        // If the users access is either approve or force, then they can modify the request length.
        $context = \context_course::instance($course->id, MUST_EXIST);
        $forcestatus = has_capability('local/extension:modifyrequeststatus', $context);
        $approve = (rule::RULE_ACTION_APPROVE | rule::RULE_ACTION_FORCEAPPROVE);
        $access = rule::get_access($mod, $USER->id);
        if ($forcestatus || $access & $approve) {
            $params = array(
                'id' => $requestid,
                'course' => $course->id,
                'cmid' => $cmid,
            );

            $modifyurl = new \moodle_url('/local/extension/modify.php', $params);
            $modlink = \html_writer::link($modifyurl, get_string('modifyextensionlength', 'local_extension'));
            $status .= ' ' . $modlink;
        }

        $status .= \html_writer::end_tag('p');
        $html .= $status;
        if (!empty($mform)) {
            $mform->addElement('html', $html);

            // If the users access is either approve or force, then they can see the approval buttons.
            if ($forcestatus) {
                $state->render_force_buttons($mform, $localcm->get_stateid(), $localcm->cmid);

            } else if ($USER->id == $localcm->userid) {
                $state->render_owner_buttons($mform, $localcm->get_stateid(), $localcm->cmid);

            } else if ($access & $approve) {
                $state->render_approve_buttons($mform, $localcm->get_stateid(), $localcm->cmid);
            }
        }

        // Check to see if the current assign has the extensionduedate set.
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, $course);
        $flags = $assign->get_user_flags($localcm->userid, false);

        // Extensionduedate is set, an extension must have been approved!
        if ($flags && !empty($flags->extensionduedate)) {
            $existing = \html_writer::start_tag('p', array('class' => 'time'));
            $obj = new \stdClass();
            $obj->status = $state->get_state_name(state::STATE_APPROVED);

            // Existing due date minus event time start is the previous existing extension length.
            $previouslength = $flags->extensionduedate - $event->timestart;
            $obj->length = utility::calculate_length($previouslength);
            $obj->date = userdate($flags->extensionduedate);
            $existing .= get_string('status_status_line', 'local_extension', $obj);
            $existing .= \html_writer::end_tag('p');

            // Only display a previously granted extension if the length values do not match.
            if($previouslength != $localcm->cm->length) {
                $mform->addElement('html', $existing);

                // If the users access is either approve or force, then they can see the approval buttons.
                if ($forcestatus) {
                    $state->render_force_buttons($mform, state::STATE_APPROVED, $localcm->cmid);

                } else if ($USER->id == $localcm->userid) {
                    $state->render_owner_buttons($mform, state::STATE_APPROVED, $localcm->cmid);

                } else if ($access & $approve) {
                    $state->render_approve_buttons($mform, state::STATE_APPROVED, $localcm->cmid);
                }
            }
        }

        $html = \html_writer::end_div(); // End .content.

        if (!empty($mform)) {
            $mform->addElement('html', $html);
        }

        return $html;
    }

    /**
     * Renders the request status in a form with indicators of the request state.
     *
     * @param array $mod An array of event details
     * @param \MoodleQuickForm $mform A moodle form object
     * @param array $customdata
     * @return string
     */
    public function status_change_definition($mod, $mform, $customdata) {
        global $CFG;

        $event = $mod->event;
        $course = $mod->course;
        $localcm = $mod->localcm;

        $instance = $customdata['instance'];
        $modified = $customdata['modified'];
        $user = $customdata['user'];

        $html = \html_writer::start_div('content');
        $coursestring = \html_writer::tag('b', $course->fullname . ' > ' . $event->name, array('class' => 'mod'));
        $html .= \html_writer::tag('p', $coursestring);
        $html .= \html_writer::end_div(); // End .content.

        $mform->addElement('html', \html_writer::tag('p', $html));
        $html .= \html_writer::end_div(); // End .content.

        $mform->addElement('static', 'cutoffdate', 'Requested by', fullname($user));

        $showuseridentityfields = explode(',', $CFG->showuseridentity);
        if (in_array('idnumber', $showuseridentityfields)) {
            $mform->addElement('static', 'cutoffdate', 'ID number', $user->idnumber);
        }

        $mform->addElement('static', 'duedate', get_string('duedate', 'assign'), userdate($instance->duedate));

        if ($modified) {
            $mform->addElement('static', 'duedate', get_string('extensionuntil', 'extension_assign'), userdate($instance->duedate + $localcm->get_length_previous()));
        } else {
            $mform->addElement('static', 'duedate', get_string('extensionuntil', 'extension_assign'), userdate($instance->duedate + $localcm->get_length()));
        }

        return $html;
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param \MoodleQuickForm $mform A moodle form object
     * @param array $mod An array of event details
     * @param array $data An array of form data
     * @return array of error messages
     */
    public function request_validation($mform, $mod, $data) {

        $errors = array();
        $event = $mod->event;
        $cm = $mod->cm;
        $formid = 'due' . $cm->id;
        $now = time();

        $due = $event->timestart;

        if (!array_key_exists($formid, $data)) {
            // Data not set.
            return $errors;
        }

        $request = $data[$formid];
        if ($request == 0) {
            // Didn't ask for extension.
            return $errors;
        }

        $toosoon = 24;

        if ($request <= $due + $toosoon * 60 * 6) {
            $errors[$formid] = get_string('dueerrortoosoon', 'extension_assign', $toosoon);
        } else if ($request <= $now) {
            $errors[$formid] = get_string('dueerrorinpast', 'extension_assign');
        }

        return $errors;
    }

    /**
     * Return data to be stored for the request
     *
     * @param \MoodleQuickForm $mform A moodle form object
     * @param array $mod An array of event details
     * @param array $data An array of form data
     * @return string|bool The data to be stored
     */
    public function request_data($mform, $mod, $data) {
        $cm = $mod->cm;
        $formid = 'due' . $cm->id;
        if (!empty($data->$formid)) {
            return $data->$formid;
        }

        return false;
    }

    /**
     * Grants an extension.
     *
     * @param int $assignmentinstance The instance id of the current assignment.
     * @param int $userid
     * @param int $duedate
     *
     * @return bool
     */
    public function submit_extension($assignmentinstance, $userid, $duedate) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $cm = get_coursemodule_from_instance('assign', $assignmentinstance, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $assignment = new \assign($context, $cm, null);

        if (!$assignment->save_user_extension($userid, $duedate)) {
            return false;
        }

        return true;
    }

    public function get_instance($mod) {
        $cm = $mod->cm;
        $course = $cm->course;
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, $course);

        return $assign->get_instance();
    }

    /**
     * If an extension was granted, but it was a mistake then this will revoke the extension length.
     *
     * @param int $assignmentinstance
     * @param int $userid
     * @return bool
     */
    public function cancel_extension($assignmentinstance, $userid) {
        // TODO: Discussion regarding how and when an extension will be removed if a date has been set.
        // Requires 'mod/assign:grantextension'.
        // $this->submit_extension($assignmentinstance, $userid, 0);
    }
}
