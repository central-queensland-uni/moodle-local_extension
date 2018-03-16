<?php
// This file is part of Moodle Course Rollover Plugin
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
 * @codingStandardsIgnoreFile
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\ExpectationException;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * @package     local_extension
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2018 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(public) Allow as many methods as needed.
 */
class behat_local_extension extends behat_base {
    /**
     * @Given /^the extension manager is configured +\# local_extension$/
     */
    public function theExtensionManagerIsConfiguredLocal_extension() {
        global $DB;
        $DB->insert_record('local_extension_triggers', (object)[
            'context'            => 0,
            'name'               => 'Dummy Trigger',
            'role'               => 0,
            'action'             => 0,
            'priority'           => 0,
            'parent'             => 0,
            'lengthfromduedate'  => 0,
            'lengthtype'         => 0,
            'elapsedfromrequest' => 0,
            'elapsedtype'        => 0,
            'datatype'           => '',
            'data'               => '',
        ]);
    }

    /**
     * @Given /^I am (?:logged in as )?(?:an? )?(\w+) +\# local_extension$/
     */
    public function iAmA($user) {
        global $DB;

        if ($user == 'administrator') {
            $user = 'admin';
        } else {
            if (!$DB->record_exists('user', ['username' => $user])) {
                $generator = new testing_data_generator();
                $generator->create_user(['username' => $user, 'password' => $user]);
            }
        }

        $this->execute('behat_auth::i_log_in_as', [$user]);
    }

    /**
     * @Given /^I (?:am at|go to) the extension preferences page(?: again)? +\# local_extension$/
     */
    public function iAmAtTheExtensionPreferencesPageLocal_extension() {
        $this->visitPath('/local/extension/preferences.php');
    }

    /**
     * @Given /^I (select|deselect|unselect) the checkbox "([^"]*)" +\# local_extension$/
     */
    public function iSelectTheCheckboxLocal_extension($selected, $field) {
        $selected = ($selected == 'select') ? 1 : 0;
        $this->execute('behat_forms::i_set_the_field_to', [$field, $selected]);
    }

    /**
     * @Given /^the checkbox "([^"]*)" should be ((?:un)?selected) +\# local_extension$/
     */
    public function theCheckboxShouldBeSelectedLocal_extension($checkbox, $selectedornot) {
        $selected = ($selectedornot == 'selected');
        $element = $this->find_field($checkbox);

        if ($element->isChecked() != $selected) {
            throw new ExpectationException('"' . $checkbox . '" should be ' . $selectedornot, $this->getSession());
        }
    }

    /**
     * @Given /^the user "([^"]*)" is enrolled into "([^"]*)" as "([^"]*)" +\# local_extension$/
     */
    public function theUserIsEnrolledIntoAsLocal_extension($user, $course, $role) {
        $generator = new testing_data_generator();
        $user = $generator->create_user(['username' => $user, 'password' => $user]);
        $course = $generator->create_course(['shortname' => $course]);

        $userid = $user->id;
        $courseid = $course->id;
        $generator->enrol_user(
            $userid,
            $courseid,
            $role
        );
    }

    /**
     * @When /^I am on course "([^"]*)" page +\# local_extension$/
     */
    public function iAmOnCoursePageLocal_extension($course) {
        $this->visitPath("/course/view.php?name={$course}");
    }
}
