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
 * A form to enter in details to request an extension on the existing time limit.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extension\form;

use html_writer;
use moodleform;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/formslib.php');

/**
 * A form to enter in details to request an extension on the existing time limit.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class additional_request extends moodleform {

    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        $request = $this->_customdata['request'];
        $cmid = $this->_customdata['cmid'];

        $mod = $request->mods[$cmid];

        $localcm = $mod->localcm;
        $course = $mod->course;
        $handler = $mod->handler;
        $stateid = $localcm->cm->state;
        $userid = $localcm->userid;

        // These hidden elements are used when clicking the 'cancel' button.
        // We request that cmid, reqid and courseid are required parameters to view the additional extension page.
        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'id', $request->requestid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'review', 1);
        $mform->setType('review', PARAM_INT);

        $mform->addElement('hidden', 'validationtime', time());
        $mform->setType('validationtime', PARAM_INT);

        $html = html_writer::tag('h2', get_string('form_modify_request_header', 'local_extension'));
        $mform->addElement('html', $html);

        // Utilise the same visual modify definition that admins/coordinators view.
        $handler->modify_definition($mod, $mform, $this->_customdata);

        $mform->addElement('textarea', 'commentarea', get_string('comment', 'local_extension'), 'rows="5" cols="70"');
        $mform->addRule('commentarea', 'Required', 'required', null, 'client');

        $policy = get_config('local_extension', 'attachmentpolicy');
        // Moodle rich text editor may leave a <br> in an empty editor.
        if (!empty($policy)) {
            $html = html_writer::div($policy, '');
            $mform->addElement('static', 'policy', '', $html);
        }

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'local_extension'), null, array(
            'subdirs' => 0,
        ));

        if (get_config('local_extension', 'requireattachment')) {
            $mform->addRule('attachments', 'Required', 'required', null, 'client');;
        }

        $this->add_action_buttons(true, get_string('submit_additional_review', 'local_extension'));
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param array $data An array of form data
     * @param array $files An array of form files
     * @return array of error messages
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        $mform = $this->_form;
        $request = $this->_customdata['request'];
        $cmid = $this->_customdata['cmid'];
        $mod = $request->mods[$cmid];

        $formid = 'due' . $cmid;

        $handler = $mod->handler;
        $event = $mod->event;

        // Default request_validation checks for dates within one day of the request.
        $errors += $handler->request_validation($mform, $mod, $data);

        // Validation checking for maximum request length.
        $extensionlimit = get_config('local_extension', 'extensionlimit');

        $timestart = $event->timestart;
        $requestuntil = $data[$formid];
        if (!empty($requestuntil)) {
            $requestlength = $requestuntil - $timestart;

            $days = $requestlength / (3600 * 24);

            if ($days > $extensionlimit) {

                $templatevars = array(
                    '/{{maxweeks}}/' => floor($extensionlimit / 7),
                    '/{{lengthweeks}}/' => floor($days / 7),
                    '/{{maxdays}}/' => $extensionlimit,
                    '/{{lengthdays}}/' => $days,
                );

                $patterns = array_keys($templatevars);
                $replacements = array_values($templatevars);

                $warningstring = get_config('local_extension', 'extensionlimitwanring');
                if (empty($warningstring)) {
                    $obj = (object) ['days' => intval($days)];
                    $resultstr = get_string('error_over_extension_limit', 'local_extension', $obj);

                } else {
                    $resultstr = preg_replace($patterns, $replacements, $warningstring);
                }
                $errors[$formid] = $resultstr;
            }
        }
        return $errors;
    }
}

