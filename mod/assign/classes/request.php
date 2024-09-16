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

use html_writer;
use local_extension\mod_data;
use local_extension\rule;
use local_extension\utility;
use MoodleQuickForm;

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
     * @param mod_data $mod Local mod_data object with event details
     * @param MoodleQuickForm $mform A moodle form object
     * @return string
     */
    public function request_definition($mod, $mform = null) {
        $event = $mod->event;
        $course = $mod->course;

        $html = html_writer::start_div('content');
        $coursestring = html_writer::tag('b', $course->fullname . ' > ' . $mod->cm->name, array('class' => 'mod'));
        $str = get_string('dueon', 'extension_assign', userdate($event->timestart));
        $html .= html_writer::tag('p', $coursestring . ' ' . $str);

        // Setup the mform due element id.
        if (!empty($mform)) {
            $html .= html_writer::end_div(); // End .content.
            $mform->addElement('html', html_writer::tag('p', $html));

            $this->date_selector($mod, $mform);
        }

        $html .= html_writer::end_div(); // End .content.

        return $html;
    }

    /**
     * Renders the modify extension details in a form with a date selector.
     *
     * @param mod_data $mod Local mod_data object with event details
     * @param MoodleQuickForm $mform A moodle form object
     * @param array $customdata mform customdata
     * @return string
     */
    public function modify_definition($mod, $mform, $customdata) {
        $event = $mod->event;
        $course = $mod->course;
        $lcm = $mod->localcm;

        $instance = $customdata['instance'];

        $suppressdate = null;
        if (isset($customdata['suppressdate'])) {
            $suppressdate = $customdata['suppressdate'];
        }

        $html = html_writer::start_div('content');
        $coursestring = html_writer::tag('b', $course->fullname . ' > ' . $mod->cm->name, array('class' => 'mod'));
        $html .= html_writer::tag('p', $coursestring);
        $html .= html_writer::end_div(); // End .content.

        $mform->addElement('html', html_writer::tag('p', $html));
        $html .= html_writer::end_div(); // End .content.

        if ($instance && $instance->allowsubmissionsfromdate) {
            $mform->addElement('static', 'allowsubmissionsfromdate', get_string('allowsubmissionsfromdate', 'assign'),
                userdate($instance->allowsubmissionsfromdate));
        }

        if ($instance && $instance->cutoffdate) {
            $mform->addElement('static', 'cutoffdate', get_string('cutoffdate', 'assign'), userdate($instance->cutoffdate));
            $finaldate = $instance->cutoffdate;
        }

        if ($instance && $instance->duedate) {
            $mform->addElement('static', 'duedate', get_string('duedateoriginal', 'local_extension'), userdate($instance->duedate));
            $finaldate = $instance->duedate;
        }

        $mform->addElement('static', 'extensionduedate', get_string('duedateextension', 'local_extension'), userdate($lcm->get_data()));

        $extensionlength = utility::calculate_length($lcm->cm->length);
        if ($extensionlength) {
            $mform->addElement('static', 'currentlength', 'Current extension length', $extensionlength);
        }

        if (!$suppressdate) {
            $this->date_selector($mod, $mform, false);
        }

        return $html;
    }

    public function get_due_date($mod) {
        if (is_null($mod->event) || !isset($mod->event->timestart)) {
            [$course, $cm] = get_course_and_cm_from_cmid($mod->cm->id);
            return $cm->customdata['duedate'] ?? 0;
        }
        return $mod->event->timestart;
    }

    /**
     * Renders the request status in a form with indicators of the request state.
     *
     * @param mod_data $mod Local mod_data object with event details
     * @param MoodleQuickForm $mform A moodle form object
     * @param array $customdata
     * @return string
     */
    public function status_change_definition($mod, $mform, $customdata) {
        global $CFG;

        $event = $mod->event;
        $course = $mod->course;
        $lcm = $mod->localcm;

        $instance = $customdata['instance'];
        $user = $customdata['user'];

        $html = html_writer::start_div('content');
        $modulename = isset($mod->cm->name) ? $mod->cm->name : $event->name;
        $coursestring = html_writer::tag('b', $course->fullname . ' > ' . $modulename, array('class' => 'mod'));
        $html .= html_writer::tag('p', $coursestring);
        $html .= html_writer::end_div(); // End .content.

        $mform->addElement('html', html_writer::tag('p', $html));
        $html .= html_writer::end_div(); // End .content.

        $mform->addElement('static', 'cutoffdate', 'Requested by', fullname($user));

        $mform->addElement('static', 'duedate', get_string('duedate', 'assign'), userdate($instance->duedate));
        $mform->addElement('static', 'extensionduedate', get_string('duedateextension', 'local_extension'), userdate($lcm->get_data()));

        $extensionlength = utility::calculate_length($lcm->cm->length);
        if ($extensionlength) {
            $mform->addElement('static', 'currentlength', 'Current extension length', $extensionlength);
        }

        $showuseridentityfields = explode(',', $CFG->showuseridentity);
        if (in_array('idnumber', $showuseridentityfields)) {
            $mform->addElement('static', 'cutoffdate', 'ID number', $user->idnumber);
        }

        return $html;
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param MoodleQuickForm $mform A moodle form object
     * @param mod_data $mod Local mod_data object with event details
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
        }

        return $errors;
    }

    /**
     * Return data to be stored for the request
     *
     * @param MoodleQuickForm $mform A moodle form object
     * @param mod_data $mod Local mod_data object with event details
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

    /**
     * Obtains an instance of the mod.
     *
     * @param mod_data $mod Local mod_data object with event details
     * @return bool
     */
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

    /**
     * Obtains the timestamp date of a potential request.
     *
     * @param mod_data $mod Local mod_data object with event details
     * @return int|bool
     */
    public function get_current_extension($mod) {
        $context = \context_module::instance($mod->cm->id);
        $assign = new \assign($context, $mod->cm, $mod->course);
        $flags = $assign->get_user_flags($mod->localcm->userid, false);

        // No flags? No set extension exists.
        if ($flags === false) {
            return false;
        }

        // No extensionduedate, no
        if ($flags->extensionduedate <= 0) {
            return false;
        }

        // The date of the current extension.
        return $flags->extensionduedate;
    }

}
