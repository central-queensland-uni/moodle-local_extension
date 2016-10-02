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
 * Modify the extension length.
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
 * A form to modify the length of an extension.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modify extends \moodleform {
    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform    = $this->_form;
        $request  = $this->_customdata['request'];
        $cmid     = $this->_customdata['cmid'];
        $mods     = $request->mods;

        $mod = $mods[$cmid];

        $handler = $mod['handler'];

        $html = '<h2>Original extension request details and status.</h2>';
        $mform->addElement('html', $html);

        $handler->status_definition($mod, $mform);

        $html = '<hr>';
        $mform->addElement('html', $html);

        $html = '<h2>Please specify the new extension length.</h2>';
        $mform->addElement('html', $html);

        $handler->request_definition($mod, $mform);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitmodification', 'Update extension');
        $buttonarray[] = &$mform->createElement('submit', 'cancel', 'Cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

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
        $user     = $this->_customdata['user'];
        $request  = $this->_customdata['request'];
        $cmid     = $this->_customdata['cmid'];
        $mods     = $request->mods;

        $mod = $mods[$cmid];
        $handler = $mod['handler'];

        $cm = $mod['cm'];
        $event = $mod['event'];
        $formid = 'due' . $cm->id;

        $due[$formid] = $data[$formid];

        $errors += $handler->request_validation($mform, $mod, $data);

        // The date selector has a checkbox. Ensure this is ticked.
        if (empty($data[$formid])) {
            $errors[$formid] = get_string('error_none_selected', 'local_extension');
        }

        return $errors;
    }
}