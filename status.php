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

use local_extension\access\capability_checker;
use local_extension\attachment_processor;
use local_extension\state;
use local_extension\utility;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

global $PAGE, $USER;

require_login(null, false);

$requestid = required_param('id', PARAM_INT);
$request = utility::cache_get_request($requestid);

// Item $request->user is an array of $userid=>$userobj associated to this request, eg. those that are subscribed, and the user.
// The list of subscribed users populated each time the request object is generated.
// The request object is invalidated and regenerated after each comment, attachment added, or rule triggered.

if (!capability_checker::can_view_request($request)) {
    print_error('permissiondenied', 'local_extension');
}

$url = new moodle_url('/local/extension/status.php', array('id' => $requestid));
$PAGE->set_url($url);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('page_heading_index', 'local_extension'));
$PAGE->requires->css('/local/extension/styles.css');
$PAGE->add_body_class('local_extension');
$PAGE->add_body_class('local_extension_status');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('breadcrumb_nav_index', 'local_extension'), new moodle_url('/local/extension/index.php'));

$request = utility::cache_get_request($requestid);
$fileareaitemid = $request->request->timestamp . $requestid;

$renderer = $PAGE->get_renderer('local_extension');

$params = array(
    'request' => $request,
    'renderer' => $renderer,
);

$requestuser = core_user::get_user($request->request->userid);
$obj = array(
    'id' => $requestid,
    'name' => fullname($requestuser)
);

$pageurl = new moodle_url('/local/extension/status.php', array('id' => $request->requestid));
$PAGE->navbar->add(get_string('breadcrumb_nav_status', 'local_extension', $obj), $pageurl);

$mform = new \local_extension\form\status(null, $params);

if ($mform->is_cancelled()) {
    $indexurl = new moodle_url('/local/extension/index.php');
    redirect($indexurl);

} else if ($form = $mform->get_data()) {

    // Use the same time for the attachments and comments.
    $time = time();

    // If the state has changed, redirect to an intermediate page.
    state::instance()->has_submitted_state_change($form, $request);

    // If the individual clicks on a button to request an additional extension, redirect to an itermediate page.
    state::instance()->has_submititted_additional_request($form, $request);

    $comment = $form->commentarea;

    $draftcontext = context_user::instance($USER->id);
    $usercontext = context_user::instance($request->request->userid);

    $itemid = $requestid;
    $draftitemid = file_get_submitted_draft_itemid('attachments');

    $fs = get_file_storage();
    $draftfiles = $fs->get_area_files($draftcontext->id, 'user', 'draft', $draftitemid, 'id');
    $oldfiles = $fs->get_area_files($usercontext->id, 'local_extension', 'attachments', $itemid, 'id');

    $processor = new attachment_processor($oldfiles);
    $draftfiles = $processor->rename_new_files($draftfiles);

    $notifycontent = array();

    // File count must be greater that 1, as an item is the directory '.'.
    if (count($draftfiles) > 1) {
        // We need to add the existing files to the draft area so they are saved and merged with the new area.
        foreach ($oldfiles as $oldfile) {
            $filerecord = new stdClass();
            $filerecord->contextid = $draftcontext->id;
            $filerecord->component = 'user';
            $filerecord->filearea = 'draft';
            $filerecord->itemid = $draftitemid;

            // Check if see if the pathname hash exists before adding the file.
            $hash = $fs->get_pathname_hash(
                $usercontext->id,
                'user',
                'draft',
                $draftitemid,
                $oldfile->get_filepath(),
                $oldfile->get_filename()
            );

            // We do not delete / update / modify the old file. Ideally we will not reach this state due to the previous validation.
            if (!array_key_exists($hash, $draftfiles)) {
                $fs->create_file_from_storedfile($filerecord, $oldfile);
            }
        }

        file_save_draft_area_files($draftitemid, $usercontext->id, 'local_extension', 'attachments', $itemid);

        // Add the new files attached to the attachment history.
        $files = $fs->get_area_files($usercontext->id, 'local_extension', 'attachments', $itemid, 'id');
        $files = $processor->diff_new_files($files);
        foreach ($files as $file) {
            $notifycontent[] = $request->add_attachment_history($file, $time);
        }
    }

    if (!empty($comment)) {
        $validationtime = $form->validationtime;
        $notifycontent[] = $request->add_comment($USER, $comment, $time, $validationtime);
    }

    // Cleaning up the array.
    $notifycontent = array_filter($notifycontent, function($obj) {
        return !is_null($obj);
    });

    $request->notify_subscribers($notifycontent, $USER->id);

    // Update the lastmod.
    $request->update_lastmod($USER->id, $time);

    // Invalidate the cache for this request. The content has changed.
    $request->get_data_cache()->delete($request->requestid);

    redirect($url);
} else {
    $usercontext = context_user::instance($USER->id);

    $draftitemid = 0;
    file_prepare_draft_area($draftitemid, $usercontext->id, 'local_extension', 'attachments', null);

    $entry = new stdClass();
    $entry->attachments = $draftitemid;
    $mform->set_data($entry);

    $data = array('id' => $requestid);
    $mform->set_data($data);
}

echo $OUTPUT->header();

echo $renderer->render_status_policy();

$mform->display();

echo $OUTPUT->footer();
