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

use local_extension\rule;
use local_extension\state;
use local_extension\utility;

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

    /** @var rule[] $rules */
    public static $rules = null;

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
     * {@inheritDoc}
     * @see \local_extension\base_request::get_triggers()
     */
    public function get_triggers() {
        if (empty(self::$rules)) {
            self::$rules = \local_extension\rule::load_all($this->get_data_type());
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
        // Allow quiz calendar items that have a close date. This means we can extend the due date.
        // Quiz items that have passed the submission date will not show up for extension as calendar items are not present.
        if ($event->eventtype == "close") {
            return true;
        }

        return false;
    }

    /**
     * Define parts of the request for for an event object
     *
     * @param array $mod An array of event details
     * @param \moodleform $mform A moodle form object
     * @return string
     */
    public function request_definition($mod, $mform = null) {
        $event = $mod->event;
        $course = $mod->course;

        $html = \html_writer::start_div('content');
        $coursestring = \html_writer::tag('b', $course->fullname . ' > ' . $event->name, array('class' => 'mod'));
        $str = get_string('dueon', 'extension_assign', userdate($event->timestart + $event->timeduration));
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
     * Define parts of the request for for an event object
     *
     * @param array $mod An array of event details
     * @param \moodleform $mform A moodle form object
     * @return string
     */
    public function status_definition($mod, $mform = null) {
        global $USER, $DB;

        $event = $mod->event;
        $course = $mod->course;
        $localcm = $mod->localcm;
        $cm = $mod->cm;

        $requestid = $localcm->requestid;
        $cmid = $localcm->cmid;
        $quiz =  $quiz = $DB->get_record('quiz', array('id' => $cm->instance), 'timeclose');

        $html = \html_writer::start_div('content');

        $courseurl = new \moodle_url('/course/view.php', array('id' => $course->id));
        $courselink = \html_writer::link($courseurl, $course->fullname);

        $eventurl = new \moodle_url('/mod/' . $event->modulename . '/view.php', array('id' => $cmid));
        $eventlink = \html_writer::link($eventurl, $event->name);

        $coursestring = \html_writer::tag('b', $courselink. ' > ' . $eventlink, array('class' => 'mod'));
        $str = get_string('dueon', 'extension_assign', userdate($quiz->timeclose));
        $html .= \html_writer::tag('p', $coursestring . ' ' . $str);

        $status = state::instance()->get_state_name($localcm->cm->state);

        $obj = new \stdClass();
        $obj->status = $status;
        $obj->date = userdate($localcm->cm->data);
        $obj->length = utility::calculate_length($localcm->cm->length);

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
        $html .= \html_writer::end_div(); // End .content.

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

        $mform->addElement('static', 'timeclose', get_string('duedate', 'assign'), userdate($event->timestart + $event->timeduration));

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
        global $DB;

        $event = $mod->event;
        $course = $mod->course;
        $cm = $mod->cm;

        /* @var \local_extension\cm $lcm IDE hinting. */
        $lcm = $mod->localcm;
        $instance = $customdata['instance'];

        $quiz =  $quiz = $DB->get_record('quiz', array('id' => $cm->instance), 'timeclose');

        $html = \html_writer::start_div('content');
        $coursestring = \html_writer::tag('b', $course->fullname . ' > ' . $event->name, array('class' => 'mod'));
        $html .= \html_writer::tag('p', $coursestring);
        $html .= \html_writer::end_div(); // End .content.

        $mform->addElement('html', \html_writer::tag('p', $html));
        $html .= \html_writer::end_div(); // End .content.

        if ($quiz->timeclose) {
            $mform->addElement('static', 'duedate', get_string('duedate', 'assign'), userdate($quiz->timeclose));
        }

        $extensionlength = utility::calculate_length($lcm->cm->length);
        if ($extensionlength) {
            $mform->addElement('static', 'cutoffdate', 'Current extension length', $extensionlength);
        }

        $this->date_selector($mod, $mform, false);

        return $html;
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param \moodleform $mform A moodle form object
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
     * @param \moodleform $mform A moodle form object
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
     * @param int $quizid
     * @param int $userid
     * @param int $timeclose
     * @return bool
     */
    public function submit_extension($quizid, $userid, $timeclose) {
        global $DB;

        $quiz = $DB->get_record('quiz', array('id' => $quizid));

        $obj = new \stdClass();
        $obj->quiz = $quizid;
        $obj->userid = $userid;
        $obj->timeclose = $timeclose;

        $conditions = array(
            'quiz' => $quizid,
            'userid' => $userid,
            'groupid' => null,
        );

        $params = array(
            'context' => \context_system::instance(), // TODO change to quiz context
            'other' => array(
                'quizid' => $quizid
            )
        );

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


        quiz_update_open_attempts(array('quizid'=>$quizid));
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

    public function get_instance($mod) {
        return false;
    }

}

