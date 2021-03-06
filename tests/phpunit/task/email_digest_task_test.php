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
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\task\email_digest_task;
use local_extension\test\extension_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_extension_email_digest_task_test extends extension_testcase {
    public function test_it_sends_daily() {
        // In preference_mail_digest_help we say it is sent daily, that must be the default.
        $tasks = null;
        require(__DIR__ . '/../../../db/tasks.php');
        self::assertNotNull($tasks);
        foreach ($tasks as $task) {
            if ($task['classname'] == email_digest_task::class) {
                self::assertNotSame('*', $task['minute'], 'minute');
                self::assertNotSame('*', $task['hour'], 'hour');
                self::assertSame('*', $task['day'], 'day');
                self::assertSame('*', $task['dayofweek'], 'dayofweek');
                self::assertSame('*', $task['month'], 'month');
                return;
            }
        }
        self::fail('Not found: ' . email_digest_task::class);
    }

    public function test_it_sends_and_cleans_up() {
        if (method_exists(self::class, 'createMock')) {
            $mock = self::createMock(\local_extension\message\mailer::class);
        } else {
            $mock = $this->getMock(\local_extension\message\mailer::class);
        }
        $mock->expects($this->once())->method('email_digest_send');
        $mock->expects($this->once())->method('email_digest_cleanup');

        ob_start();
        $task = new email_digest_task($mock);
        $task->execute();
        $output = ob_get_clean();

        self::assert_string_contains('Sending', $output, 'sending');
        self::assert_string_contains('Deleting', $output, 'deleting');
    }
}
