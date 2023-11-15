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
 * Requests page in local_extension
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\request;
use local_extension\rule;
use local_extension\state;
use local_extension\utility;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/calendar/lib.php');
global $CFG, $PAGE;

$courseid      = optional_param('course', 0, PARAM_INT);
$cmid          = optional_param('cmid', 0, PARAM_INT);
$searchback    = optional_param('back', get_config('local_extension', 'searchback'), PARAM_INT);
$searchforward = optional_param('forward', get_config('local_extension', 'searchforward'), PARAM_INT);

$PAGE->set_url(new moodle_url('/local/extension/request.php'), array(
    'back'    => $searchback,
    'forward' => $searchforward,
    'course'  => $courseid,
    'cmid'    => $cmid,
));

require_login(null, false);

if (!empty($cmid)) {
    $context = context_module::instance($cmid);

    // When selecting an individual module, someone has sought to click the link. This will hopefully display the request form.
    $maxweeks = get_config('local_extension', 'searchforwardmaxweeks');
    $searchforward = $maxweeks * 7;

    $maxweeks = get_config('local_extension', 'searchbackwardmaxweeks');
    $searchback = $maxweeks * 7;
} else if (!empty($courseid)) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('request_page_heading', 'local_extension'));
$PAGE->requires->css('/local/extension/styles.css');
$PAGE->add_body_class('local_extension');
$PAGE->set_secondary_navigation(false);
$PAGE->activityheader->disable();

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('breadcrumb_nav_index', 'local_extension'), new moodle_url('/local/extension/index.php'));
$PAGE->navbar->add(get_string('breadcrumb_nav_request', 'local_extension'));

$user = $USER->id;
$start = time() - $searchback * 24 * 60 * 60;
$end = time() + $searchforward * 24 * 60 * 60;

$renderer = $PAGE->get_renderer('local_extension');

$options = array(
    'courseid' => $courseid,
    'moduleid' => $cmid,
    'requestid' => 0
);

$triggers = rule::load_all();
if (count($triggers) == 0) {
    // Check if admin capability and send to trigger config page.
    echo $OUTPUT->header();
    echo html_writer::tag('p', get_string('page_request_notriggersdefined', 'local_extension'));
    echo $OUTPUT->footer();
    die();
}

$activities = utility::get_activities($user, $start, $end, $options);

$available = array();
$inprogress = array();

foreach ($activities as $mod) {
    // If a local cm object does not exist, then we can make a request for this module.
    if (empty($mod->localcm->cm)) {
        $available[] = $mod;
    } else {
        $inprogress[] = $mod;
    }
}

$params = array(
    'available' => $available,
    'inprogress' => $inprogress,
    'course' => $courseid,
    'cmid' => $cmid,
    'context' => $context,
    'searchforward' => $searchforward,
);

$mform = new \local_extension\form\request(null, $params);

$usercontext = context_user::instance($USER->id);

if ($mform->is_cancelled()) {
    $indexurl = new moodle_url('/local/extension/index.php');
    redirect($indexurl);

} else if ($form = $mform->get_data()) {

    $now = time();

    $request = array(
        'userid' => $USER->id,
        'lastmodid' => $USER->id,
        'searchstart' => $start,
        'searchend' => $end,
        'timestamp' => $now,
        'lastmod' => $now,
        'messageid' => 0,
    );
    $request['id'] = $DB->insert_record('local_extension_request', $request);

    $comment = array(
        'request' => $request['id'],
        'userid' => $USER->id,
        'timestamp' => $now,
        'message' => $form->comment,
        'validationcheck' => $now . '_' . $USER->id,
    );
    $comment['id'] = $DB->insert_record('local_extension_comment', $comment);

    foreach ($activities as $cmid => $mod) {

        $course = $mod->course;
        $handler = $mod->handler;
        $event = $mod->event;

        $data = $handler->request_data($mform, $mod, $form);

        // If no data is present then an extension request date has not been specified.
        if (empty($data)) {
            continue;
        }

        $cm = array(
            'request' => $request['id'],
            'userid' => $USER->id,
            'course' => $course->id,
            'timestamp' => $now,
            'name' => isset($mod->cm->name) ? $mod->cm->name : $event->name,
            'cmid' => $cmid,
            'state' => state::STATE_NEW,
            'data' => $data,
            'length' => $data - $event->timestart,
        );
        $cm['id'] = $DB->insert_record('local_extension_cm', $cm);

        // Construct the initial history state length and status 'new'.
        $history = new stdClass();
        $history->localcmid = $cm['cmid'];
        $history->requestid = $cm['request'];
        $history->timestamp = time();
        $history->state = \local_extension\state::STATE_NEW;
        $history->userid = $USER->id;
        $history->extlength = $cm['length'];
        $DB->insert_record('local_extension_hist_state', $history);

        // Setup the default subscription for the user making the request.
        $sub = new stdClass();
        $sub->userid = $USER->id;
        $sub->localcmid = $cm['id'];
        $sub->requestid = $cm['request'];
        $sub->lastmod = time();
        $sub->trig = null;
        $sub->access = rule::RULE_ACTION_DEFAULT;

        $DB->insert_record('local_extension_subscription', $sub);
    }

    $draftitemid = file_get_submitted_draft_itemid('attachments');
    file_save_draft_area_files($draftitemid, $usercontext->id, 'local_extension', 'attachments', $request['id']);

    $fs = get_file_storage();
    $files = $fs->get_area_files($usercontext->id, 'local_extension', 'attachments', $request['id']);

    $now = time();

    foreach ($files as $file) {
        if ($file->is_directory()) {
            continue;
        }

        $data = array(
            'requestid' => $request['id'],
            'timestamp' => $now,
            'filehash' => $file->get_pathnamehash(),
            'userid' => $file->get_userid(),
        );

        $DB->insert_record('local_extension_hist_file', $data);
    }

    // Initiate the trigger/rule logic notifications and subscriptions, file attachment history.
    $req = request::from_id($request['id']);
    $req->process_triggers();

    $url = new moodle_url('/local/extension/status.php', array('id' => $req->requestid));
    redirect($url);
    die();
} else {
    $draftitemid = 0;
    file_prepare_draft_area($draftitemid, $usercontext->id, 'local_extension', 'attachments', 0);
}

echo $OUTPUT->header();

if (count($activities) == 0) {
    $obj = new stdClass();
    $obj->startrange = userdate($start);
    $obj->endrange = userdate($end);

    echo html_writer::tag('p', get_string('error_no_mods', 'local_extension', $obj));
    echo $renderer->render_request_search_controls($courseid, $cmid, $searchback, $searchforward);
    echo $OUTPUT->footer();
    die();
}

echo $renderer->render_policy();

$contextlevels = array(
    CONTEXT_SYSTEM => "systemcontext",
    CONTEXT_COURSE => "coursecontext",
    CONTEXT_MODULE => "modulecontext",
);

$config = get_config('local_extension');
foreach ($contextlevels as $contextlevel => $cfg) {
    if ($context->contextlevel == $contextlevel) {

        // Only display the search ahead when in the category or course context.
        if (empty($cmid)) {
            echo $renderer->render_request_search_controls($courseid, $cmid, $searchback, $searchforward);
        }

        // If the configuration contexts are enabled then print the overall form to make multiple requests.
        if (!empty($config->$cfg)) {

            $mform->display();

        } else {

            // If the configuration contexts are disabled then provide links to make individual requests.
            foreach ($inprogress as $mod) {
                echo $mod->handler->status_definition($mod);

                $requestid = $mod->localcm->requestid;

                $params = array('id' => $requestid);
                $url = new moodle_url('/local/extension/status.php', $params);

                $name = isset($mod->cm->name) ? $mod->cm->name : $mod->event->name;
                $text = get_string('page_request_requeststatuslink', 'local_extension', $name);
                $link = html_writer::link($url, $text);

                echo html_writer::tag('p', $link);
            }

            if (!empty($available)) {
                echo html_writer::empty_tag('hr');
            }

            foreach ($available as $mod) {
                echo $mod->handler->request_definition($mod);

                $course = $mod->cm->course;
                $cmid = $mod->cm->id;

                $params = array('course' => $course, 'cmid' => $cmid);
                $url = new moodle_url('/local/extension/request.php', $params);

                $name = isset($mod->cm->name) ? $mod->cm->name : $mod->event->name;
                $text = get_string('page_request_requestnewlink', 'local_extension', $name);
                $link = html_writer::link($url, $text);

                echo html_writer::tag('p', $link);
            }

        }
    }
}

echo $OUTPUT->footer();
