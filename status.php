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
 * Status page in local_extension
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $PAGE, $USER;

require_login(false);

// TODO add perms checks here.

$requestid = required_param('id', PARAM_INTEGER);

$url = new moodle_url('/local/extension/status.php', array('id' => $requestid));
$PAGE->set_url($url);

// TODO context could be user, course or module.
$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('status_page_heading', 'local_extension'));
$PAGE->requires->css('/local/extension/styles.css');

$request = \local_extension\utility::cache_get_request($requestid);
$fileareaitemid = $request->request->timestamp . $requestid;

$renderer = $PAGE->get_renderer('local_extension');

$params = array(
    'user' => $OUTPUT->user_picture($USER),
    'request' => $request,
    'renderer' => $renderer,
    'history' => $request->state_history()
);

$mform = new \local_extension\form\update(null, $params);

$draftitemid = file_get_submitted_draft_itemid('attachments');

file_prepare_draft_area($draftitemid, $context->id, 'local_extension', 'attachments', $fileareaitemid);

$entry = new stdClass();
$entry->attachments = $draftitemid;
$mform->set_data($entry);

if ($form = $mform->get_data()) {
    $comment = $form->commentarea;

    file_save_draft_area_files($draftitemid, $context->id, 'local_extension', 'attachments', $fileareaitemid);

    // Parse the form data to see if any accept/deny/reopen/etc buttons have been clicked, and update the state accordingly.
    $request->update_cm_state($USER, $form);

    if (!empty($comment)) {
        $request->add_comment($USER, $comment);
    }

    // Invalidate the cache for this request. The content has changed.
    $request->get_data_cache()->delete($request->requestid);

    redirect($url);
} else {
    $data = array('id' => $requestid);
    $mform->set_data($data);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();