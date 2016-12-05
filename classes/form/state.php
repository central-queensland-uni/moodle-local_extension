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
 * Modify the cm extension state.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extension\form;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/formslib.php');

/**
 * A form to modify the state of an extension cm.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state extends \moodleform {
    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform    = $this->_form;
        $request  = $this->_customdata['request'];
        $cmid     = $this->_customdata['cmid'];
        $state    = $this->_customdata['state'];
        $modified = $this->_customdata['modified'];
        $mods     = $request->mods;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 's');
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'modified');
        $mform->setType('modified', PARAM_INT);

        $mod = $mods[$cmid];
        $handler = $mod->handler;

        /* @var \local_extension\cm $lcm IDE hinting. */
        $lcm = $mod->localcm;

        $html = \html_writer::tag('h2', 'State change confirmation');
        $mform->addElement('html', $html);

        $handler->status_change_definition($mod, $mform, $this->_customdata);

        if ($modified) {
            $extensionlength = \local_extension\utility::calculate_length($lcm->cm->lengthprev);
        } else {
            $extensionlength = \local_extension\utility::calculate_length($lcm->cm->length);
        }

        $mform->addElement('static', 'extensionlength', 'Extension length', $extensionlength);

        $currentstate = \local_extension\state::instance()->get_state_name($lcm->cm->state);
        $mform->addElement('static', 'currentstate', 'Current state', $currentstate);

        $newstate = \local_extension\state::instance()->get_state_name($state);
        $mform->addElement('static', 'newstate', 'New state', $newstate);

        $mform->addElement('textarea', 'commentarea', get_string('comments'), 'wrap="virtual" rows="5" cols="70"');
        $mform->addElement('html', \html_writer::empty_tag('br'));

        $mform->addElement('submit', 'submitbutton_status', get_string('submit_state_return_status', 'local_extension'));
        $mform->addElement('html', \html_writer::empty_tag('br'));

        $mform->addElement('submit', 'submitbutton_list', get_string('submit_state_return_list', 'local_extension'));
        $mform->addElement('html', \html_writer::empty_tag('br'));

        $mform->addElement('cancel', 'cancel', get_string('submit_state_back', 'local_extension'));
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param array $data An array of form data
     * @param array $files An array of form files
     * @return array of error messages
     */
    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        $mform    = $this->_form;
        $request  = $this->_customdata['request'];
        $cmid     = $this->_customdata['cmid'];
        $mods     = $request->mods;

        $mod = $mods[$cmid];
        $lcm = $mod->localcm;

        $approved = false;

        // Checking if the user has the ability to approve.
        $access = $request->get_user_access($USER->id, $request->cms[$cmid]->cm->id);
        if ($access == \local_extension\rule::RULE_ACTION_APPROVE ||
            $access == \local_extension\rule::RULE_ACTION_FORCEAPPROVE) {

            $approved = true;
        }

        // Checking for capabilities or admin access.
        $context = \context_module::instance($cmid);
        if (has_capability('local/extension:viewallrequests', $context)) {
            $approved = true;
        }

        // Checking to see if the new state is a possible transition.
        $possible = \local_extension\state::instance()->state_is_possible($lcm->cm->state, $data['s'], $approved);

        if (!$possible) {
            $errors['newstate'] = get_string('invalidstate', 'local_extension');
        }

        return $errors;
    }
}
