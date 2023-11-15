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
class additional_review extends moodleform {

    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        $request = $this->_customdata['request'];
        $cmid = $this->_customdata['cmid'];
        $reviewdate = $this->_customdata['reviewdate'];

        // Suppress the date selector
        $this->_customdata['suppressdate'] = true;

        $mod = $request->mods[$cmid];
        $handler = $mod->handler;
        $lcm = $mod->localcm;

        // These hidden elements are set with the $form->set_data() function.
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'attachments');
        $mform->setType('attachments', PARAM_INT);

        $mform->addElement('hidden', 'commentarea');
        $mform->setType('commentarea', PARAM_TEXT);

        $mform->addElement('hidden', 'due'. $cmid);
        $mform->setType('due'. $cmid, PARAM_INT);

        $mform->addElement('hidden', 'validationtime', time());
        $mform->setType('validationtime', PARAM_INT);

        // Print a basic defintion.
        $handler->modify_definition($mod, $mform, $this->_customdata);

        // Display the new extension request length.
        $newlength = ($reviewdate - $lcm->cm->data) + $lcm->cm->length;
        $extensionlength = \local_extension\utility::calculate_length($newlength);
        $mform->addElement('static', 'cutoffdate', 'New extension length', $extensionlength);

        $this->add_action_buttons(true, get_string('submit_additional_request', 'local_extension'));
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

        return $errors;
    }
}
