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

use local_extension\test\extension_testcase;
use local_extension\utility;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_extension_utility_test extends extension_testcase {
    public function provider_for_test_it_calculates_the_number_of_weekdays() {
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
     * @dataProvider provider_for_test_it_calculates_the_number_of_weekdays
     */
    public function test_it_calculates_the_number_of_weekdays($from, $until, $expected) {
        $message = "{$from} ~ {$until}";
        $from = $this->create_timestamp($from);
        $until = $this->create_timestamp($until);
        $actual = utility::calculate_weekdays_elapsed($from, $until);
        self::assertSame($expected, $actual, $message);
    }

    /**
     * Test for utility::create_request_mod_data().
     */
    public function test_create_request_mod_data() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assignment = $assignmentgenerator->create_instance(['course' => $course->id, 'duedate' => time()]);
        $assigncm = get_coursemodule_from_instance('assign', $assignment->id);
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id, 'timeclose' => time()]);
        $quizcm = get_coursemodule_from_instance('quiz', $quiz->id);

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
        $assignlocalcm = clone $localcm;
        $assignlocalcm->cmid = $assigncm->id;
        $assignlocalcm->id = $DB->insert_record('local_extension_cm', $assignlocalcm);
        $quizlocalcm = clone $assignlocalcm;
        $quizlocalcm->id = null;
        $quizlocalcm->cmid = $quizcm->id;
        $quizlocalcm->id = $DB->insert_record('local_extension_cm', $quizlocalcm);

        // Delete all events.
        $DB->delete_records('event');

        // Test a fake event is created correctly from the assign cm data.
        $data = utility::create_request_mod_data($assignlocalcm, $user->id);
        $event = $data->event;
        $this->assertEquals($assignment->name, $event->name);
        $this->assertEquals('assign', actual: $event->modulename);
        $this->assertEquals($assignment->id, actual: $event->instance);
        $this->assertEquals($assignment->duedate, actual: $event->timestart);

        // Test a fake event is created correctly from the quiz cm data.
        $data = utility::create_request_mod_data($quizlocalcm, $user->id);
        $event = $data->event;
        $this->assertEquals($quiz->name, $event->name);
        $this->assertEquals('quiz', actual: $event->modulename);
        $this->assertEquals($quiz->id, actual: $event->instance);
        $this->assertEquals($quiz->timeclose, actual: $event->timestart);
    }
}
