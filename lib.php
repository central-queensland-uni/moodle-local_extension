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
 * lib.php
 *
 * @package    local_extension
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Add the link to start a request, Moodle 2.9+
 *
 * @param global_navigation $nav Navigation
 */
function local_extension_extend_navigation(global_navigation $nav) {
    local_extension_extends_navigation($nav);
}

/**
 * Add the link to start a request, Moodle 2.7
 *
 * @param global_navigation $nav Navigation
 */
function local_extension_extends_navigation(global_navigation $nav) {
    global $PAGE, $USER;

    $context = $PAGE->context;
    $contextlevel = $context->contextlevel;
    $sitecontext = context_system::instance();

    // TODO add perms checks here. Maybe. Allow for students only?

    if (isloggedin() and !isguestuser()) {

        // If there are no triggers setup then we should not show these links at all.
        $triggers = \local_extension\rule::load_all();
        if (count($triggers) == 0) {
            return;
        }

        // This is a map of datatypes and their triggers. Useful to know if a trigger has been set for a datatype later.
        $datatypes = array();
        foreach ($triggers as $trigger) {
            $datatypes[$trigger->datatype][] = $trigger;
        }

        // General link in the navigation menu.
        $url = (new moodle_url('/local/extension/index.php'))->out();
        $nodename = get_string('requestextension_status', 'local_extension');
        $node = $nav->add($nodename, $url, null, null, 'local_extension');
        $node->showinflatnavigation = true;

        if ($contextlevel == CONTEXT_COURSE) {
            // If the user is not enrolled, do not provide an extension request link in the course/mod context.
            if (!is_enrolled($context, $USER->id)) {
                return;
            }

            // Adding a nagivation string nested in the course that provides a count and status of the requests.
            $courseid = optional_param('id', 0, PARAM_INT);
            if (empty($courseid)) {
                return;
            }

            $url = new moodle_url('/local/extension/request.php', array('course' => $courseid));

            $coursenode = $nav->find($courseid, navigation_node::TYPE_COURSE);
            if (!empty($coursenode)) {
                // MOODLE-1519 Workaround.
                $requests = null; // \local_extension\utility::find_course_requests($courseid);

                if (empty($requests)) {
                    // Display the request extension link.
                    $node = $coursenode->add(get_string('nav_request', 'local_extension'), $url);
                } else {

                    $requestcount = \local_extension\utility::count_requests($courseid, $USER->id);

                    if ($requestcount > 1) {
                        $string = get_string('nav_course_request_plural', 'local_extension');
                    } else {
                        $string = get_string('nav_course_request', 'local_extension');
                    }

                    $requeststatus = \local_extension\utility::course_request_status($courseid, $USER->id);

                    $url = new moodle_url('/local/extension/index.php');
                    $node = $coursenode->add($requestcount . ' ' . $string . ' ' . $requeststatus, $url);
                }
            }

        } else if ($contextlevel == CONTEXT_MODULE) {
            // Adding a navigation string nested in the course module that provides a status update.
            $id = optional_param('id', 0, PARAM_INT);

            if (empty($id)) {
                return;
            }

            // If the user is not enrolled, do not provide an extension request link in the course/mod context.
            if (!is_enrolled($context, $USER->id)) {
                return;
            }

            $modulenode = $nav->find($id, navigation_node::TYPE_ACTIVITY);
            if (!empty($modulenode)) {
                $courseid = $PAGE->course->id;

                list($request, $cm) = \local_extension\utility::find_module_requests($courseid, $id);

                $modinfo = get_fast_modinfo($courseid);
                $cmdata = $modinfo->cms[$id];

                // Eg. assign, quiz.
                $modname = $cmdata->modname;

                // No triggers have been defined for this mod type. It will not show a request extension link.
                if (!array_key_exists($modname, $datatypes)) {
                    return;
                }

                if (empty($cm)) {
                    // Display the request extension link.
                    $url = new moodle_url('/local/extension/request.php', array('course' => $courseid, 'cmid' => $id));
                    $node = $modulenode->add(get_string('nav_request', 'local_extension'), $url);
                } else {
                    // Display the request status for this module.
                    $url = new moodle_url('/local/extension/status.php', array('id' => $request->requestid));

                    $localcm = $request->mods[$id]->localcm;

                    $result = \local_extension\state::instance()->get_state_result($localcm->get_stateid());

                    // The function block_nagivation->trim will truncate the navagation item to 25/50 characters.
                    $node = $modulenode->add(get_string('nav_status', 'local_extension', $result), $url);
                }

            }

        }

    }

}

/**
 * Serves the extension files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function local_extension_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_USER) {
        return false;
    }

    // Ensure the filearea is the one used by this plugin.
    if ($filearea !== 'attachments') {
        return false;
    }

    require_login($course, false, $cm);

    // When the file is stored, we use the $item id is the requestid.
    $itemid = array_shift($args);
    $filename = array_pop($args);

    // Lets obtain the cached request.
    $request = \local_extension\request::from_id($itemid);

    // Item $request->user is an array of $userid=>$userobj associated to this request, eg. those that are subscribed, and the user.
    // The list of subscribed users populated each time the request object is generated.
    // The request object is invalidated and regenerated after each comment, attachment added, or rule triggered.
    if (!array_key_exists($USER->id, $request->users)) {

        // If the user does not have the capability to view all requests, return false.
        if (!has_capability('local/extension:viewallrequests', $context)) {
            return false;
        }
    }

    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_extension', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true);
}
