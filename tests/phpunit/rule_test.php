<?php
// This file is part of Moodle Assignment Extension Plugin
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
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\rule;
use local_extension\test\extension_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_extension_rule_test extends extension_testcase {
    public function test_it_uses_working_days_instead_of_calendar_days() {
        global $DB;
        $this->resetAfterTest();
        self::setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assignment = $assignmentgenerator->create_instance(['course' => $course->id, 'duedate' => time()]);
        $cmid = get_coursemodule_from_instance('assign', $assignment->id)->id;

        $extensionrequest = $this->create_request($user->id);
        $extensionrequest->update_timestamp($this->create_timestamp('Thursday, 2018-02-01'));
        $DB->insert_record('local_extension_cm', (object)[
            'request' => $extensionrequest->requestid,
            'userid'  => $user->id,
            'course'  => $course->id,
            'name'    => $course->fullname,
            'cmid'    => $cmid,
            'data'    => time() + 86400,
            'length'  => 0,
        ]);

        $role = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $rule = new rule();
        $rule->load_from_form((object)[
            'context'            => 1,
            'datatype'           => 'assign',
            'name'               => 'Working Days Test',
            'priority'           => 0,
            'parent'             => 0,
            'lengthtype'         => rule::RULE_CONDITION_ANY,
            'lengthfromduedate'  => 0,
            'elapsedtype'        => rule::RULE_CONDITION_GE,
            'elapsedfromrequest' => 5,
            'role'               => $role,
            'action'             => rule::RULE_ACTION_APPROVE,
            'template_notify'    => ['text' => ''],
            'template_user'      => ['text' => ''],
        ]);
        $rule->id = $DB->insert_record('local_extension_triggers', $rule);

        // Reload with course modules and rules.
        $extensionrequest->load();

        // 7 calendar days, 5 weekdays.
        $today = $this->create_timestamp('Thursday, 2018-02-08');
        $triggered = $extensionrequest->process_triggers($today);
        self::assertTrue($triggered, 'After 5 weekdays it should trigger.');

        // 6 calendar days, 4 weekdays.
        $today = $this->create_timestamp('Wednesday, 2018-02-07');
        $triggered = $extensionrequest->process_triggers($today);
        self::assertFalse($triggered);
    }

    public function test_it_checks_if_has_rules() {
        global $DB;
        $this->resetAfterTest();

        self::assertFalse(rule::has_rules());
        self::assertFalse(rule::has_rules('assign'));
        self::assertFalse(rule::has_rules('forum'));

        $rule = new rule();
        $rule->load_from_form((object)[
            'context'            => 1,
            'datatype'           => 'assign',
            'name'               => 'Test',
            'priority'           => 0,
            'parent'             => 0,
            'lengthtype'         => rule::RULE_CONDITION_ANY,
            'lengthfromduedate'  => 0,
            'elapsedtype'        => rule::RULE_CONDITION_GE,
            'elapsedfromrequest' => 5,
            'role'               => $DB->get_field('role', 'id', ['shortname' => 'manager']),
            'action'             => rule::RULE_ACTION_APPROVE,
            'template_notify'    => ['text' => ''],
            'template_user'      => ['text' => ''],
        ]);
        $rule->id = $DB->insert_record('local_extension_triggers', $rule);

        self::assertTrue(rule::has_rules());
        self::assertTrue(rule::has_rules('assign'));
        self::assertFalse(rule::has_rules('forum'));
    }
}
