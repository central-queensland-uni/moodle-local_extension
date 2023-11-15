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
 * Status comment form
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extension\form;

use html_writer;
use local_extension\access\capability_checker;
use local_extension\base_request;
use local_extension\rule;
use local_extension\state;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * A form to enter in comments for an extension request
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class status extends \moodleform {
    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform    = $this->_form;
        $request  = $this->_customdata['request'];
        /* @var \local_extension_renderer $renderer IDE hinting */
        $renderer = $this->_customdata['renderer'];
        $mods     = $request->mods;

        foreach ($mods as $mod) {
            $this->definition_for_module($mod);
        }

        $html = '';

        if ($html .= $renderer->render_extension_attachments($request)) {
            $html .= html_writer::start_tag('br');
        }

        $html .= html_writer::empty_tag('p');
        $html .= $renderer->render_extension_comments($request);
        $html .= html_writer::start_tag('br');
        $mform->addElement('html', $html);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'validationtime', time());
        $mform->setType('validationtime', PARAM_INT);

        $policy = get_config('local_extension', 'attachmentpolicy');
        // Moodle rich text editor may leave a <br> in an empty editor.
        if (!empty($policy)) {
            $html = html_writer::div($policy, '');
            $mform->addElement('html', $html);
        }

        $mform->addElement('filemanager', 'attachments', '', null, array('subdirs' => 0));

        $mform->addElement('textarea', 'commentarea', '', 'wrap="virtual" rows="5" cols="70"');

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit_comment', 'local_extension'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('back'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    private function definition_for_module($mod) {
        global $USER;

        $state = state::instance();

        if (is_null($mod->handler)) {
            base_request::default_status_definition($mod, $this->_form, null);
        } else {
            $mod->handler->status_definition($mod, $this->_form);
        }

        // Displays a list of state changes, their status and extension length.
        $state->render_state_definition($mod, $this->_form);

        if (capability_checker::can_force_change_status($mod->course->id)) {
            // The user has the required capabilities, allow them to change everything.
            $state->render_force_buttons($this->_form, $mod->localcm->cm->state, $mod->localcm);
        } else if ($USER->id == $mod->localcm->userid) {
            // A student is viewing this component, display the additional request buttons and basic cancellation.
            $state->render_owner_buttons($this->_form, $mod->localcm->cm->state, $mod->localcm);
        } else if (rule::can_approve($mod, $USER->id)) {
            // The user has the approiate level of access to confirm, deny or extend the request.
            $state->render_approve_buttons($this->_form, $mod->localcm->cm->state, $mod->localcm);
        }
    }
}
