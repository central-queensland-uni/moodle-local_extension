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

use local_extension\rule;
use local_extension\state;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/formslib.php');

/**
 * A form to enter in comments for an extension request
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update extends \moodleform {
    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        global $USER;

        $mform    = $this->_form;
        $request  = $this->_customdata['request'];
        /* @var \local_extension_renderer $renderer IDE hinting */
        $renderer = $this->_customdata['renderer'];
        $mods     = $request->mods;

        $state = state::instance();

        foreach ($mods as $id => $mod) {
            /* @var \local_extension\cm $localcm IDE hinting */
            $localcm = $mod->localcm;
            $course = $mod->course;
            /* @var \local_extension\base_request $handler IDE hinting */
            $handler = $mod->handler;
            $id = $localcm->cmid;
            $stateid = $localcm->cm->state;
            $userid = $localcm->userid;

            // The capability 'local/extension:modifyrequeststatus' allows a user to force change the status.
            $context = \context_course::instance($course->id, MUST_EXIST);
            $forcestatus = has_capability('local/extension:modifyrequeststatus', $context);

            // If the users access is either approve or force, then they can see the approval buttons.
            $approve = (rule::RULE_ACTION_APPROVE | rule::RULE_ACTION_FORCEAPPROVE);
            $access = rule::get_access($mod, $USER->id);

            $handler->status_definition($mod, $mform);

            if ($forcestatus) {
                $state->render_force_buttons($mform, $stateid, $id);

            } else if ($USER->id == $userid) {
                $state->render_owner_buttons($mform, $stateid, $id);

            } else if ($access & $approve) {
                $state->render_approve_buttons($mform, $stateid, $id);

            }

        }

        $html = '';

        if ($html .= $renderer->render_extension_attachments($request)) {
            $html .= \html_writer::start_tag('br');
        }

        $html .= \html_writer::empty_tag('p');
        $html .= $renderer->render_extension_comments($request);
        $html .= \html_writer::start_tag('br');
        $mform->addElement('html', $html);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $policy = get_config('local_extension', 'attachmentpolicy');
        // Moodle rich text editor may leave a <br> in an empty editor.
        if (!empty($policy)) {
            $html = \html_writer::div($policy, '');
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
}
