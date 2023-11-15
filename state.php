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
 * State change page in local_extension
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\access\capability_checker;
use local_extension\rule;
use local_extension\state;
use local_extension\utility;

require_once(__DIR__ . '/../../config.php');
global $PAGE, $USER;

require_login(null, false);

$requestid = required_param('id', PARAM_INT);
$courseid = required_param('course', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$stateid = required_param('s', PARAM_INT);

$request = utility::cache_get_request($requestid);

// Item $request->user is an array of $userid=>$userobj associated to this request, eg. those that are subscribed, and the user.
// The list of subscribed users populated each time the request object is generated.
// The request object is invalidated and regenerated after each comment, attachment added, or rule triggered.

// Allow the user to modify their own request.

if ($USER->id != $request->request->userid) {

    // Checking if the current user is not part of the request or does not have the capability to view all requests.
    $context = utility::get_context($requestid, $cmid);
    $canview = capability_checker::can_view_all_requests($context);
    $canmodify = capability_checker::can_force_change_status($courseid);

    if (!$canmodify && !$canview) {

        if (array_key_exists($USER->id, $request->users)) {
            // The user is part of the request, lets check their access.
            $access = $request->get_user_access($USER->id, $request->cms[$cmid]->cm->id);

            if ($access != rule::RULE_ACTION_APPROVE &&
                $access != rule::RULE_ACTION_FORCEAPPROVE
            ) {
                // The $USER belongs to the request user list, but does not have sufficient access.
                print_error('invalidaccess', 'local_extension');
            }

        } else {
            // The user does not have the capability, nor is part of the request user list.
            print_error('invalidaccess', 'local_extension');
        } // End array_key_exists check.

    } // End capability check.
}

$params = array('id' => $requestid, 'course' => $courseid, 'cmid' => $cmid, 's' => $stateid);
$url = new moodle_url('/local/extension/state.php', $params);
$PAGE->set_url($url);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('page_heading_index', 'local_extension'));
$PAGE->requires->css('/local/extension/styles.css');
$PAGE->add_body_class('local_extension');

$renderer = $PAGE->get_renderer('local_extension');

$mod = $request->mods[$cmid];
$course = $mod->course;
$cm = $mod->cm;

$requestuser = core_user::get_user($request->request->userid);

$params = array(
    'request' => $request,
    'cmid' => $cmid,
    'state' => $stateid,
    'user' => $requestuser,
);
if (!is_null($mod->handler)) {
    $params['instance'] = $mod->handler->get_instance($mod);
}

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('breadcrumb_nav_index', 'local_extension'), new moodle_url('/local/extension/index.php'));

$obj = array('id' => $requestid, 'name' => fullname($requestuser));

$pageurl = new moodle_url('/local/extension/status.php', array('id' => $request->requestid));
$PAGE->navbar->add(get_string('breadcrumb_nav_status', 'local_extension', $obj), $pageurl);
$PAGE->navbar->add(get_string('breadcrumb_nav_modify_state', 'local_extension'));

$mform = new \local_extension\form\state(null, $params);

if ($mform->is_cancelled()) {
    $statusurl = new moodle_url('/local/extension/status.php', array('id' => $requestid));
    redirect($statusurl);

} else if ($form = $mform->get_data()) {

    // Use the same time for the attachments and comments.
    $time = time();

    // Parse the form data to see if any accept/deny/reopen/etc buttons have been clicked, and update the state accordingly.
    // If the state has been approved then it will call the handers->submit_extension method to extend the module.
    $notifycontent[] = state::instance()->update_cm_state($request, $USER, $form, $time);

    $comment = $form->commentarea;
    $validationtime = $form->validationtime;
    if (!empty($comment)) {
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

    if (!empty($form->submitbutton_status)) {
        $statusurl = new moodle_url('/local/extension/status.php', array('id' => $requestid));
        redirect($statusurl);
    }

    if (!empty($form->submitbutton_list)) {
        $index = new moodle_url('/local/extension/index.php');
        redirect($index);
    }

} else {
    $data = new stdClass();
    $data->id = $requestid;
    $data->cmid = $cmid;
    $data->userid = $request->request->userid;
    $data->course = $courseid;
    $data->s = $stateid;
    $mform->set_data($data);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
