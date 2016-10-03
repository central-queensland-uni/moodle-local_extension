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

use local_extension\utility;

require_once(__DIR__ . '/../../config.php');
global $PAGE, $USER;

require_login(true);

$requestid = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

$request = utility::cache_get_request($requestid);

// Item $request->user is an array of $userid=>$userobj associated to this request, eg. those that are subscribed, and the user.
// The list of subscribed users populated each time the request object is generated.
// The request object is invalidated and regenerated after each comment, attachment added, or rule triggered.

// Permissions checking
/*
if (!array_key_exists($USER->id, $request->users)) {
    // TODO What should we print here?
    die();
}
*/
$url = new moodle_url('/local/extension/modify.php', array('id' => $requestid, 'cmid' => $cmid));
$PAGE->set_url($url);

// TODO context could be user, course or module.
$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('page_heading_index', 'local_extension'));
$PAGE->requires->css('/local/extension/styles.css');
$PAGE->add_body_class('local_extension');

$renderer = $PAGE->get_renderer('local_extension');

$params = array(
    'user' => $OUTPUT->user_picture($USER),
    'request' => $request,
    'cmid' => $cmid,
    'renderer' => $renderer,
);

$requestuser = core_user::get_user($request->request->userid);

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('breadcrumb_nav_index', 'local_extension'), new moodle_url('/local/extension/index.php'));
$PAGE->navbar->add(get_string('breadcrumb_nav_modify', 'local_extension'), $url);

$obj = array('id' => $requestid, 'name' => \fullname($requestuser));

$pageurl = new moodle_url('/local/extension/status.php', array('id' => $request->requestid));
$PAGE->navbar->add(get_string('breadcrumb_nav_status', 'local_extension', $obj), $pageurl);

$mform = new \local_extension\form\modify(null, $params);

if ($form = $mform->get_data()) {

    // TODO Edge cases with lowering the length beyond set triggers. Deal with changes / triggers.
    $cm = $request->cms[$cmid];
    $event = $request->mods[$cmid]['event'];
    $course  = $request->mods[$cmid]['course'];

    $due = 'due' . $cmid;

    $originaldate = $cm->cm->data;
    $newdate = $form->$due;

    $delta = $newdate - $originaldate;

    $show = format_time($delta);
    $num = strtok($show, ' ');
    $unit = strtok(' ');
    $show = "$num $unit";

    // Prepend -+ signs to indicate a difference in length.
    $sign = $delta < 0 ? '-' : '+';

    $obj = (object) [
        'course' => $course->fullname,
        'event' => $event->name,
        'original' => userdate($originaldate),
        'new' => userdate($newdate),
        'diff' => $sign . $show,
    ];

    $datestring = get_string('page_modify_comment', 'local_extension', $obj);

    $cm->cm->data = $form->$due;
    $cm->cm->length = $form->$due - $event->timestart;
    $cm->update_data();

    $notifycontent = array();
    $notifycontent[] = $request->add_comment($USER, $datestring);
    $request->notify_subscribers($notifycontent, $USER->id);

    $request->get_data_cache()->delete($request->requestid);

    $statusurl = new moodle_url('/local/extension/status.php', array('id' => $requestid));

    // TODO Run triggers, update subscriptions.
    redirect($statusurl);

} else {
    $data = new stdClass();
    $data->id = $requestid;
    $data->cmid = $cmid;
    $mform->set_data($data);
}

echo $OUTPUT->header();

// TODO echo $renderer->display_modify_heading();

$mform->display();

echo $OUTPUT->footer();
