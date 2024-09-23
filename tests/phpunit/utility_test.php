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

namespace local_extension;

use local_extension\rule;
use local_extension\test\extension_testcase;
use local_extension\utility;

/**
 * Unit tests for local_extension\utility
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_extension\utility
 */
class utility_test extends extension_testcase {

    /**
     * Data provider for test_it_calculates_the_number_of_weekdays().
     *
     * @return array
     */
    public function provider_for_test_it_calculates_the_number_of_weekdays(): array {
        return [
            ['Monday, 2018-03-05', 'Monday, 2018-03-05', 0],
            ['Monday, 2018-03-05', 'Tuesday, 2018-03-06', 1],
            ['Monday, 2018-03-05', 'Friday, 2018-03-09', 4],
            ['Monday, 2018-03-05', 'Saturday, 2018-03-10', 4],
            ['Monday, 2018-03-05', 'Sunday, 2018-03-11', 4],
            ['Monday, 2018-03-05', 'Monday, 2018-03-12', 5],
            ['Monday, 2018-03-05', 'Monday, 2018-03-19', 10],
            ['Saturday, 2018-03-03', 'Sunday, 2018-03-04', 0],
            ['Saturday, 2018-03-03', 'Monday, 2018-03-05', 0],
            ['Saturday, 2018-03-03', 'Tuesday, 2018-03-06', 1],
            ['Saturday, 2018-03-03', 'Wednesday, 2018-03-07', 2],
            ['Saturday, 2018-03-03', 'Thursday, 2018-03-08', 3],
            ['Saturday, 2018-03-03', 'Friday, 2018-03-09', 4],
            ['Saturday, 2018-03-03', 'Saturday, 2018-03-10', 4],
            ['Saturday, 2018-03-03', 'Monday, 2018-03-12', 5],
            ['Friday, 2018-03-02', 'Saturday, 2018-03-03', 0],
            ['Thursday, 2018-02-01', 'Thursday, 2018-02-08', 5],
            ['Thursday, 2018-02-01', 'Wednesday, 2018-02-07', 4],
        ];
    }

    /**
     * Test for utility::calculate_weekdays_elapsed()
     *
     * @dataProvider provider_for_test_it_calculates_the_number_of_weekdays
     * @covers ::calculate_weekdays_elapsed
     */
    public function test_it_calculates_the_number_of_weekdays($from, $until, $expected) {
        $message = "{$from} ~ {$until}";
        $from = $this->create_timestamp($from);
        $until = $this->create_timestamp($until);
        $actual = utility::calculate_weekdays_elapsed($from, $until);
        self::assertSame($expected, $actual, $message);
    }

    /**
     * Test for utility::get_activities() with hidden course.
     *
     * @covers ::get_activities
     */
    public function test_get_activities_course_visibility() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assignment = $assignmentgenerator->create_instance(['course' => $course->id, 'duedate' => time()]);
        $assigncm = get_coursemodule_from_instance('assign', $assignment->id);

        $role = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $rule = new rule();
        $rule->load_from_form((object)[
            'context'            => 1,
            'datatype'           => 'assign',
            'name'               => 'Test rule',
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

        $start = time() - 86400;
        $end = time() + 86400;

        // The assignment should be returned.
        $activities = utility::get_activities($user->id, $start, $end);
        $this->assertCount(1, $activities);
        $this->assertEquals($assigncm->id, array_key_first($activities));

        // Set course visibility to hidden.
        $course->visible = 0;
        update_course($course);

        // We shouldn't have the assignment returned.
        $activities = utility::get_activities($user->id, $start, $end);
        $this->assertCount(0, $activities);
    }

    /**
     * Test for utility::create_request_mod_data().
     *
     * @covers ::create_request_mod_data
     */
    public function test_create_request_mod_data() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create assignments and quizes with and without due dates.
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assignment1 = $assignmentgenerator->create_instance(['course' => $course->id, 'duedate' => time()]);
        $assign1cm = get_coursemodule_from_instance('assign', $assignment1->id);
        $assignment2 = $assignmentgenerator->create_instance(['course' => $course->id]);
        $assign2cm = get_coursemodule_from_instance('assign', $assignment2->id);
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz1 = $quizgenerator->create_instance(['course' => $course->id, 'timeclose' => time()]);
        $quiz1cm = get_coursemodule_from_instance('quiz', $quiz1->id);
        $quiz2 = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz2cm = get_coursemodule_from_instance('quiz', $quiz2->id);

        // Test an existing request with a missing event.
        $extensionrequest = $this->create_request($user->id);
        $extensionrequest->update_timestamp($this->create_timestamp('Thursday, 2018-02-01'));
        $localcm = (object)[
            'request' => $extensionrequest->requestid,
            'userid'  => $user->id,
            'course'  => $course->id,
            'name'    => $course->fullname,
            'data'    => '',
            'length'  => 0,
        ];
        $assign1localcm = clone $localcm;
        $assign1localcm->cmid = $assign1cm->id;
        $assign1localcm->id = $DB->insert_record('local_extension_cm', $assign1localcm);
        $assign2localcm = clone $localcm;
        $assign2localcm->cmid = $assign2cm->id;
        $assign2localcm->id = $DB->insert_record('local_extension_cm', $assign2localcm);
        $quiz1localcm = clone $localcm;
        $quiz1localcm->cmid = $quiz1cm->id;
        $quiz1localcm->id = $DB->insert_record('local_extension_cm', $quiz1localcm);
        $quiz2localcm = clone $localcm;
        $quiz2localcm->cmid = $quiz2cm->id;
        $quiz2localcm->id = $DB->insert_record('local_extension_cm', $quiz2localcm);

        // Delete all events.
        $DB->delete_records('event');

        // Test a fake event is created correctly from the assign1 cm data.
        $data = utility::create_request_mod_data($assign1localcm, $user->id);
        $event = $data->event;
        $this->assertEquals($assignment1->name, $event->name);
        $this->assertEquals('assign', actual: $event->modulename);
        $this->assertEquals($assignment1->id, actual: $event->instance);
        $this->assertEquals($assignment1->duedate, actual: $event->timestart);

        // Test a fake event is created correctly from the assign2 cm data (assign with no due date).
        $data = utility::create_request_mod_data($assign2localcm, $user->id);
        $event = $data->event;
        $this->assertEquals($assignment2->name, $event->name);
        $this->assertEquals('assign', actual: $event->modulename);
        $this->assertEquals($assignment2->id, actual: $event->instance);
        $this->assertEquals($assignment2->duedate, actual: $event->timestart);

        // Test a fake event is created correctly from the quiz1 cm data.
        $data = utility::create_request_mod_data($quiz1localcm, $user->id);
        $event = $data->event;
        $this->assertEquals($quiz1->name, $event->name);
        $this->assertEquals('quiz', actual: $event->modulename);
        $this->assertEquals($quiz1->id, actual: $event->instance);
        $this->assertEquals($quiz1->timeclose, actual: $event->timestart);

        // Test a fake event is created correctly from the quiz2 cm data (quiz with no time close).
        $data = utility::create_request_mod_data($quiz2localcm, $user->id);
        $event = $data->event;
        $this->assertEquals($quiz2->name, $event->name);
        $this->assertEquals('quiz', actual: $event->modulename);
        $this->assertEquals($quiz2->id, actual: $event->instance);
        $this->assertEquals($quiz2->timeclose, actual: $event->timestart);
    }
}
