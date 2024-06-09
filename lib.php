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
 * This function extends the course navigation with an activity extension link.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_extension_extend_navigation_course(\navigation_node $navigation, \stdClass $course, \context $context) {
    global $PAGE, $USER;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    $label = get_string('nav_request', 'local_extension');
    $url = new moodle_url('/local/extension/index.php', []);

    $caps = ['local/extension:viewallrequests', 'local/extension:accessallcourserequests'];
    $hascap = has_any_capability($caps, $context);
    if ($hascap) {
        $label = get_string('nav_index', 'local_extension');
    }

    if (!is_enrolled($context, $USER->id) && !$hascap) {
        return;
    }

    $navigation->add(
        $label,
        $url,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/edit', '')
    );
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
