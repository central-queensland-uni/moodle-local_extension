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
 * Extension quiz request class.
 *
 * @package    extension_quiz
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace extension_quiz;

use html_writer;
use local_extension\mod_data;
use local_extension\rule;
use local_extension\utility;
use moodleform;
use MoodleQuickForm;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Extension quiz request class.
 *
 * @package    extension_quiz
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
        return 'Quiz';
    }

    /**
     * {@inheritDoc}
     * @see \local_extension\base_request::get_data_type()
     */
    public function get_data_type() {
        return 'quiz';
    }

    /**
     * Is a calendar event something we can handle?
     *
     * @param event $event A calendar event object
     * @param coursemodule $cm A course module
     * @return boolean True if should be handled
     */
    public function is_candidate($event, $cm) {
        global $DB;
        // Allow quiz calendar items that have a close date. This means we can extend the due date.
        // Quiz items that have passed the submission date will not show up for extension as calendar items are not present.
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*');
        $event->timeclose = $quiz->timeclose;

        /*
         * It is possible that when obtaining the list of events, if an extension has been granted it will return
         * the override instead. The events timestart will be of the overrides, not the quiz timeclose.
         *
         * We replace this so the starting time of the close of the event is now the closing time of the quiz.
         */
        $event->timestart = $quiz->timeclose;

        if ($event->eventtype == "close") {
            return true;
        }

        if ($event->eventtype == "open" && $event->timeduration > 0) {
            return true;
        }

        // Look for a possible end date.

        return false;
    }

    /**
     * Define parts of the request for for an event object
     *
     * @param mod_data $mod Local mod_data object with event details
     * @param moodleform $mform A moodle form object
     * @return string
     */
    public function request_definition($mod, $mform = null) {
        $event = $mod->event;
        $course = $mod->course;

        $html = html_writer::start_div('content');
        $coursestring = html_writer::tag('b', $course->fullname . ' > ' . $mod->cm->name, array('class' => 'mod'));
        $str = get_string('dueon', 'extension_assign', userdate($event->timeclose));
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

    public function get_due_date($mod) {
        return $mod->event->timeclose;
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

        $user = $customdata['user'];

        $html = html_writer::start_div('content');
        $coursestring = html_writer::tag('b', $course->fullname . ' > ' . $mod->cm->name, array('class' => 'mod'));
        $html .= html_writer::tag('p', $coursestring);
        $html .= html_writer::end_div(); // End .content.

        $mform->addElement('html', html_writer::tag('p', $html));
        $html .= html_writer::end_div(); // End .content.

        $mform->addElement('static', 'cutoffdate', 'Requested by', fullname($user));

        $mform->addElement('static', 'timeclose', get_string('duedate', 'assign'), userdate($event->timeclose));
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
     * Renders the modify extension details in a form with a date selector.
     *
     * @param mod_data $mod Local mod_data object with event details
     * @param MoodleQuickForm $mform A moodle form object
     * @param array $customdata mform customdata
     * @return string
     */
    public function modify_definition($mod, $mform, $customdata) {
        global $DB;

        $event = $mod->event;
        $course = $mod->course;
        $cm = $mod->cm;
        $lcm = $mod->localcm;

        $instance = $customdata['instance'];

        $suppressdate = null;
        if (isset($customdata['suppressdate'])) {
            $suppressdate = $customdata['suppressdate'];
        }

        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], 'timeclose');

        $html = html_writer::start_div('content');
        $coursestring = html_writer::tag('b', $course->fullname . ' > ' . $mod->cm->name, array('class' => 'mod'));
        $html .= html_writer::tag('p', $coursestring);
        $html .= html_writer::end_div(); // End .content.

        $mform->addElement('html', html_writer::tag('p', $html));
        $html .= html_writer::end_div(); // End .content.

        if ($quiz->timeclose) {
            $mform->addElement('static', 'duedate', get_string('duedate', 'assign'), userdate($quiz->timeclose));
        }

        $mform->addElement('static', 'extensionduedate', get_string('duedateextension', 'local_extension'), userdate($lcm->get_data()));

        $extensionlength = utility::calculate_length($lcm->cm->length);
        if ($extensionlength) {
            $mform->addElement('static', 'cutoffdate', 'Current extension length', $extensionlength);
        }

        if (!$suppressdate) {
            $this->date_selector($mod, $mform, false);
        }

        return $html;
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param moodleform $mform A moodle form object
     * @param mod_data $mod Local mod_data object with event details
     * @param array $data An array of form data
     * @return array of error messages
     */
    public function request_validation($mform, $mod, $data) {

        $errors = array();
        $event = $mod->event;
        $cm = $mod->cm;
        $formid = 'due' . $cm->id;

        $due = $event->timeclose;

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
     * @param moodleform $mform A moodle form object
     * @param mod_data $mod An array of event details
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
     * @param int $quizid
     * @param int $userid
     * @param int $timeclose
     * @return bool
     */
    public function submit_extension($quizid, $userid, $timeclose) {
        global $DB;

        $quiz = $DB->get_record('quiz', array('id' => $quizid));

        $obj = new stdClass();
        $obj->quiz = $quizid;
        $obj->userid = $userid;
        $obj->timeclose = $timeclose;

        $conditions = [
            'quiz' => $quizid,
            'userid' => $userid,
            'groupid' => null,
        ];

        $params = [
            'context' => \context_system::instance(), // TODO change to quiz context
            'other' => ['quizid' => $quizid]
        ];

        $oldoverride = $DB->get_record('quiz_overrides', $conditions);
        if ($oldoverride) {
            $params['relateduserid'] = $userid;
            $params['objectid'] = $oldoverride->id;
            $obj->id = $oldoverride->id;
            $DB->update_record('quiz_overrides', $obj);
            $event = \mod_quiz\event\user_override_updated::create($params);
        } else {
            $params['relateduserid'] = $userid;
            $params['objectid'] = $quizid;
            $DB->insert_record('quiz_overrides', $obj);
            $event = \mod_quiz\event\user_override_created::create($params);
        }

        $event->trigger();

        quiz_update_open_attempts(['quizid' => $quizid]);
        quiz_update_events($quiz, $obj);
    }

    /**
     * If an extension was granted, but it was a mistake then this will revoke the extension length.
     *
     * @param int $quizid
     * @param int $userid
     * @return bool
     */
    public function cancel_extension($quizid, $userid) {
        // TODO: Discussion regarding how and when an extension will be removed if a date has been set.
        // Set the course module id before calling quiz_delete_override().
        // overrideedit.php
        // $quiz->cmid = $cm->id;
        // quiz_delete_override($quiz, $oldoverride->id);
    }

    /**
     * Obtains an instance of the mod.
     *
     * Unused.
     *
     * @param array $mod
     * @return bool
     */
    public function get_instance($mod) {
        return false;
    }

    /**
     * Obtains the timestamp date of a potential request.
     *
     * @param mod_data $mod Local mod_data object with event details
     * @return int|bool
     */
    public function get_current_extension($mod) {
        global $DB;

        // There is no interface to obtain the quiz overrides.
        // This is extracted from quiz/overrides.php and slightly modified.
        list($sort, $params) = users_order_by_sql('u');

        $namefields = \core_user\fields::get_name_fields();
        $nameselect = 'u.' . implode(', u.', $namefields);
        $sql = 'SELECT o.*, ' . $nameselect . '
                  FROM {quiz_overrides} o
                  JOIN {user} u ON o.userid = u.id
                 WHERE o.quiz = :quizid
                   AND o.userid = :userid
              ORDER BY ' . $sort;

        $params['quizid'] = $mod->event->instance;
        $params['userid'] = $mod->localcm->userid;

        $override = $DB->get_record_sql($sql, $params);

        if ($override) {
            return $override->timeclose;
        } else {
            return false;
        }
    }

}

