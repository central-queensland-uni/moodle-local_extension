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

namespace local_extension;

use backup;
use stdClass;
use restore_dbops;
use backup_controller;
use advanced_testcase;
use restore_controller;

/**
 * Functional tests for local_extension plugin backup and restore.
 *
 * @package   local_extension
 * @copyright 2025 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_extension_backup_restore_test extends advanced_testcase {

    /**
     * Setup function before each test.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Test the backup and restore of triggers and requests.
     * @covers ::backup
     * @covers ::restore
     */
    public function test_backup_and_restore_triggers_requests() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Create an assignment module.
        $assign = $this->getDataGenerator()->create_module('assign', [
                    'course' => $course->id,
                    'name' => 'Test Assignment',
                    'duedate' => time(),
        ]);

        $this->setup_initial_data($course, $user, $assign);

        $backupid = $this->backup($course);

        $newcourseid = $this->restore($backupid, $course, '');

        $restoreassign = $DB->get_record('assign', ['course' => $newcourseid]);

        $restorecmid = get_coursemodule_from_instance('assign', $restoreassign->id)->id;

        $requestrestoreddata = $DB->get_record_sql(
            "SELECT * FROM {local_extension_request} WHERE userid = ? ORDER BY id DESC LIMIT 1",
            [$user->id]);

        $this->assertEquals(2, count($DB->get_records('local_extension_request', ['userid' => $user->id])));
        $this->assertNotEmpty( $DB->get_records('local_extension_comment', ['request' => $requestrestoreddata->id]));
        $this->assertNotEmpty($DB->get_records('local_extension_subscription', ['requestid' => $requestrestoreddata->id ]));
        $this->assertNotEmpty($DB->get_records('local_extension_hist_state', ['localcmid' => $restorecmid]));
        $this->assertNotEmpty($DB->get_records('local_extension_cm', ['course' => $newcourseid]));

    }

    /**
     * Sets up initial data for testing.
     *
     * @param stdClass $course Course object to backup
     * @param stdClass $user Moodle user object
     * @param stdClass $assign Assignment module
     */
    protected function setup_initial_data($course, $user, $assign) {
        global $DB;

        $userid = $user->id;
        $now = time();
        $cmid = get_coursemodule_from_instance('assign', $assign->id)->id;

        $request = [
            'userid'      => $userid,
            'lastmodid'   => $userid,
            'searchstart' => $now - 2 * DAYSECS,
            'searchend'   => $now + 2 * DAYSECS,
            'timestamp'   => $now,
            'lastmod'     => $now,
            'messageid'   => 0,
        ];
        $requestid = $DB->insert_record('local_extension_request', (object)$request);

        $comment = [
            'request'   => $requestid,
            'userid'    => $userid,
            'timestamp' => $now,
            'message'   => 'Please accept my request.',
        ];
        $DB->insert_record('local_extension_comment', (object)$comment);

        $data = $now + 3 * DAYSECS;
        $cm = [
            'request'   => $requestid,
            'userid'    => $userid,
            'course'    => $course->id,
            'timestamp' => $now,
            'name'      => $assign->name,
            'cmid'      => $cmid,
            'state'     => state::STATE_NEW,
            'data'      => $data,
            'length'    => $data,
        ];
        $cm['id'] = $DB->insert_record('local_extension_cm', (object)$cm);

        $history = new stdClass();
        $history->localcmid = $cm['cmid'];
        $history->requestid = $cm['request'];
        $history->timestamp = time();
        $history->state = state::STATE_NEW;
        $history->userid = $userid;
        $history->extlength = $cm['length'];
        $DB->insert_record('local_extension_hist_state', $history);

        $sub = new stdClass();
        $sub->userid = $userid;
        $sub->localcmid = $cm['id'];
        $sub->requestid = $cm['request'];
        $sub->lastmod = time();
        $sub->trig = null;
        $sub->access = rule::RULE_ACTION_DEFAULT;

        $DB->insert_record('local_extension_subscription', $sub);
    }

    /**
     * Backs up a course to a temp directory.
     *
     * @param stdClass $course Course object to backup
     * @return string ID of backup
     */
    protected function backup(stdClass $course): string {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );

        $bc->get_plan()->get_setting('users')->set_status(\backup_setting::NOT_LOCKED);
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->get_plan()->get_setting('logs')->set_value(true);
        $backupid = $bc->get_backupid();

        $bc->execute_plan();
        $bc->destroy();

        return $backupid;
    }

    /**
     * Restores a course from a temp directory.
     *
     * @param string $backupid Backup ID
     * @param stdClass $course Original course object
     * @param string $suffix Suffix to add after original course shortname and fullname
     * @return int New course ID
     */
    protected function restore(string $backupid, stdClass $course, string $suffix): int {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Restore to a new course with default settings.
        $newcourseid = restore_dbops::create_new_course(
            $course->fullname . $suffix,
            $course->shortname . $suffix,
            $course->category
        );

        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        $rc->get_plan()->get_setting('users')->set_value(true);
        $rc->get_plan()->get_setting('logs')->set_value(true);

        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }
}
