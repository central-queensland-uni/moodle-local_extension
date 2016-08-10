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
        $user     = $this->_customdata['user'];
        $request  = $this->_customdata['request'];
        $renderer = $this->_customdata['renderer'];
        $history  = $this->_customdata['history'];
        $mods     = $request->mods;

        foreach ($mods as $id => $mod) {
            $handler = $mod['handler'];
            $handler->status_definition($mform, $mod, $USER->id);

            $access = \local_extension\rule::check_access($mod, $USER->id);
            if ($access == \local_extension\rule::RULE_ACTION_APPROVE) {
                $handler->status_modification($mform, $mod, $USER->id);
            }
        }

        // TODO replace <br /> with css padding/margins, or does that impact the html->text email output.
        if ($html = $renderer->render_extension_attachments($request)) {
            $html .= \html_writer::start_tag('br');
            $mform->addElement('html', $html);
        }

        $html = $renderer->render_extension_comments($request, $history);
        $html .= \html_writer::start_tag('br');
        $mform->addElement('html', $html);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // To identify the current user, $user equals $OUTPUT->user_picture($USER).
        $mform->addElement('html', $user);
        $mform->addElement('textarea', 'commentarea', '', 'wrap="virtual" rows="5" cols="70"');

        $mform->addElement('submit', 'submitcomment', get_string('submit_comment', 'local_extension'));
    }

    /**
     * This is used to update the $mform comment list after a post.
     * definition_after_data() is not suitable for this.
     *
     * $mform->_definition_finalized is set to true on the first page load.
     * After $mform->get_data() the definition_after_data() function will not be called.
     */
    public function update_comments() {
        $mform    = $this->_form;
        $request  = $this->_customdata['request'];
        $renderer = $this->_customdata['renderer'];

        $commentidx = $mform->_elementIndex['comments'];
        $mform->_elements[$commentidx]->_text = $renderer->render_extension_comments($request) . \html_writer::start_tag('br');
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

        return $errors;
    }
}