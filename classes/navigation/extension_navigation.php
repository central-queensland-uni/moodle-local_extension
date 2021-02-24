<?php
// This file is part of Extension Plugin
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
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extension\navigation;

use global_navigation;
use local_extension\rule;
use local_extension\state;
use local_extension\utility;
use moodle_url;
use navigation_node;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extension_navigation {
    public static function is_valid_logged_user() {
        if (!isloggedin()) {
            return false;
        }

        if (isguestuser()) {
            return false;
        }

        return true;
    }

    public static function apply(global_navigation $globalnavigation) {
        $extensionnavigation = new extension_navigation($globalnavigation);
        $extensionnavigation->add_nodes();
    }

    /** @var global_navigation */
    private $globalnavigation;

    public function __construct(global_navigation $globalnavigation) {
        $this->globalnavigation = $globalnavigation;
    }

    private function add_nodes() {
        global $PAGE;

        if (!self::is_valid_logged_user()) {
            return;
        }

        if (!rule::has_rules()) {
            return;
        }

        $this->add_node_in_main_navigation();

        switch ($PAGE->context->contextlevel) {
            case CONTEXT_COURSE:
                $this->add_node_in_course();
                break;
            case CONTEXT_MODULE:
                $this->add_node_in_module();
                break;
        }
    }

    private function add_node_in_course() {
        global $PAGE, $USER, $COURSE;

        if (!is_enrolled($PAGE->context, $USER->id)) {
            return;
        }

        $parentnode = $this->globalnavigation->find($COURSE->id, navigation_node::TYPE_COURSE);
        if (empty($parentnode)) {
            debugging("Cannot find course node for course id: {$COURSE->id}");
            return;
        }

        $url = new moodle_url('/local/extension/request.php', ['course' => $COURSE->id]);
        $label = get_string('nav_request', 'local_extension');
        $parentnode->add($label, $url, global_navigation::TYPE_COURSE, null, "local_extension-course");
    }

    private function add_node_in_module() {
        global $CFG, $COURSE, $PAGE, $USER;

        if (!is_enrolled($PAGE->context, $USER->id)) {
            return;
        }

        $cmid = $PAGE->context->instanceid;

        // Moodle 32+ does not show activities in the menu, so show it inside the course.
        if ($CFG->branch < 32) {
            $parentnode = $this->globalnavigation->find($cmid, navigation_node::TYPE_ACTIVITY);
        } else {
            $parentnode = $this->globalnavigation->find($COURSE->id, navigation_node::TYPE_COURSE);
        }

        if (empty($parentnode)) {
            debugging("Cannot find module node for course cmid: {$cmid}");
            return;
        }

        $moduletype = get_fast_modinfo($PAGE->course)->cms[$cmid]->modname;
        if (!rule::has_rules($moduletype)) {
            return;
        }

        list($request, $cm) = utility::find_module_requests($PAGE->course->id, $cmid);
        if (empty($cm)) {
            $url = new moodle_url('/local/extension/request.php', ['course' => $PAGE->course->id, 'cmid' => $cmid]);
            $label = get_string('nav_request', 'local_extension');
        } else {
            $url = new moodle_url('/local/extension/status.php', ['id' => $request->requestid]);
            $localcm = $request->mods[$cmid]->localcm;
            $result = state::instance()->get_state_result($localcm->get_stateid());
            $label = get_string('nav_status', 'local_extension', $result);
        }
        $parentnode->add($label, $url, global_navigation::TYPE_COURSE, null, "local_extension-module");
    }

    private function add_node_in_main_navigation() {
        $url = (new moodle_url('/local/extension/index.php'))->out();
        $nodename = get_string('requestextension_status', 'local_extension');
        $node = $this->globalnavigation->add($nodename, $url, global_navigation::TYPE_COURSE, null, "local_extension");
        $node->showinflatnavigation = true;
    }
}
