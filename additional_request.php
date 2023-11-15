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
 * Display the form for a student to request additional time on an existing extension.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\attachment_processor;

require_once(__DIR__ . '/../../config.php');
global $CFG, $PAGE;

$requestid     = required_param('id', PARAM_INT);
$cmid          = required_param('cmid', PARAM_INT);
$courseid      = required_param('courseid', PARAM_INT);

$PAGE->set_url(new moodle_url('/local/extension/additional_request.php'), [
    'id'       => $requestid,
    'cmid'     => $cmid,
    'courseid' => $courseid,
]);
$selfurl = $PAGE->url;

$cm = get_fast_modinfo($courseid)->get_cm($cmid);
require_login(null, false);

$request = \local_extension\request::from_id($requestid);

$PAGE->set_context(context_system::instance());

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('page_heading_additional_request', 'local_extension'));

$PAGE->requires->css('/local/extension/styles.css');
$PAGE->add_body_class('local_extension');

$PAGE->set_secondary_navigation(false);
$PAGE->activityheader->disable();

$requestuser = core_user::get_user($request->request->userid);
$pageurl = new moodle_url('/local/extension/status.php', ['id' => $request->requestid]);
$obj = ['id' => $requestid, 'name' => fullname($requestuser)];

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('breadcrumb_nav_index', 'local_extension'), new moodle_url('/local/extension/index.php'));
$PAGE->navbar->add(get_string('breadcrumb_nav_status', 'local_extension', $obj), $pageurl);
$PAGE->navbar->add(get_string('breadcrumb_nav_additional', 'local_extension'));

$cm      = $request->mods[$cmid]->localcm;
$event   = $request->mods[$cmid]->event;
$course  = $request->mods[$cmid]->course;
$handler = $request->mods[$cmid]->handler;

$params = [
    'request'  => $request,
    'cmid'     => $cmid,
    'instance' => $handler->get_instance($request->mods[$cmid]),
    'reviewdate' => 0,
];

$statusurl = new moodle_url('/local/extension/status.php', ['id' => $requestid]);
$requestform = new \local_extension\form\additional_request(null, $params);
if ($requestform->is_cancelled()) {
    redirect($statusurl);

} else if ($requestdata = $requestform->get_data()) {

    // Did we submit to review the contents?
    if ($requestdata->review) {
        // Provide the new due date to the review form.
        $due = 'due' . $cmid;
        $params['reviewdate'] = $requestdata->$due;

        // Unset the submit button text. We have changed this.
        unset($requestdata->submitbutton);

        $reviewform = new \local_extension\form\additional_review(null, $params);
        $reviewform->set_data($requestdata);

        // Show the review page before processing the data.
        echo $OUTPUT->header();
        $reviewform->display();
        echo $OUTPUT->footer();
        exit();
    }
}

$reviewform = new \local_extension\form\additional_review(null, $params);
$reviewdata = $reviewform->get_data();

if ($reviewform->is_cancelled()) {
    redirect($PAGE->url);

} else if ($reviewdata = $reviewform->get_data()) {

    // Use the same time for the attachments and comments.
    $time = time();

    // The review has been confirmed, try to submit the extension request!
    $notifycontent = [];

    // Check if this form has already been submitted and just redirect if it already has.
    $validationtime = $reviewdata->validationtime;
    $validationcheck = $validationtime . '_' . $USER->id;
    if ($request->validationcheck_exists($validationcheck)) {
        redirect($statusurl);
    }

    // Update the requested cm length.
    $due = 'due' . $cmid;
    $newdate = $reviewdata->$due;
    $cm->cm->data = $newdate;
    $cm->cm->length = $newdate - $event->timestart;
    $cm->update_data();

    // Update the state of the cm to state::REOPENED.
    $reviewdata->s = \local_extension\state::STATE_REOPENED;
    $notifycontent[] = \local_extension\state::instance()->update_cm_state($request, $USER, $reviewdata, $time);

    // Adding the comment to the notify content.
    $comment = $reviewdata->commentarea;
    if (!empty($comment)) {
        $notifycontent[] = $request->add_comment($USER, $comment, $time, $validationtime);
    }

    // Obtain any new attachments that have been added.
    $draftcontext = context_user::instance($USER->id);
    $usercontext = context_user::instance($request->request->userid);

    $itemid = $requestid;
    $draftitemid = file_get_submitted_draft_itemid('attachments');

    $fs = get_file_storage();
    $draftfiles = $fs->get_area_files($draftcontext->id, 'user', 'draft', $draftitemid, 'id');
    $oldfiles = $fs->get_area_files($usercontext->id, 'local_extension', 'attachments', $itemid, 'id');

    $processor = new attachment_processor($oldfiles);
    $draftfiles = $processor->rename_new_files($draftfiles);

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

        $draftnames = [];
        $oldnames = [];

        foreach ($draftfiles as $file) {
            $draftnames[$file->get_filename()] = $file;
        }

        foreach ($oldfiles as $file) {
            $oldnames[$file->get_filename()] = $file;
        }

        // This diff array will contain all the new files to be attached.
        $diff = array_diff_key($draftnames, $oldnames);
        foreach ($diff as $file) {
            $notifycontent[] = $request->add_attachment_history($file, $time);
        }
    }

    // Cleaning up the array.
    $notifycontent = array_filter($notifycontent, function($obj) {
        return !is_null($obj);
    });

    // Notify the core set of subscribers with updated details.
    $request->notify_subscribers($notifycontent, $USER->id);

    // We've modified the length of the request, too many people may be notified, lets reset the list of subscriptions.
    $request->reset_subscribers($cmid);

    // The state has changed / dates are different. Triggers may associate new users or set other rules.
    $request->process_triggers();

    // Update the lastmod.
    $request->update_lastmod($USER->id, $time);

    // Invalidate the cache for this request. The content has changed.
    $request->get_data_cache()->delete($request->requestid);

    redirect($statusurl);
}

// Output the initial form to request an additional extension.
echo $OUTPUT->header();
$requestform->display();
echo $OUTPUT->footer();
