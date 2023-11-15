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
 * Privacy provider.
 *
 * @package   local_extension
 * @author    Guillermo Gomez (guillermogomez@catalyst-au.net)
 * @copyright 2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extension\privacy;


use coding_exception;
use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use dml_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy class for requesting user data.
 *
 * @copyright 2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'local_extension_cm',
            [
                'userid' => 'privacy:metadata:local_extension_cm:userid',
                'request' => 'privacy:metadata:local_extension_cm:request',
                'course' => 'privacy:metadata:local_extension_cm:course',
                'name' => 'privacy:metadata:local_extension_cm:name',
                'cmid' => 'privacy:metadata:local_extension_cm:cmid',
                'state' => 'privacy:metadata:local_extension_cm:state',
                'data' => 'privacy:metadata:local_extension_cm:data',
                'length' => 'privacy:metadata:local_extension_cm:length'
            ],
            'privacy:metadata:local_extension_cm'
        );

        $collection->add_database_table(
            'local_extension_comment',
            [
                'userid' => 'privacy:metadata:local_extension_comment:userid',
                'request' => 'privacy:metadata:local_extension_comment:request',
                'timestamp' => 'privacy:metadata:local_extension_comment:timestamp',
                'message' => 'privacy:metadata:local_extension_comment:message',
                'validationcheck' => 'privacy:metadata:local_extension_comment:validationcheck'
            ],
            'privacy:metadata:local_extension_comment'
        );

        $collection->add_database_table(
            'local_extension_hist_state',
            [
                'userid' => 'privacy:metadata:local_extension_hist_state:userid',
                'localcmid' => 'privacy:metadata:local_extension_hist_state:localcmid',
                'requestid' => 'privacy:metadata:local_extension_hist_state:requestid',
                'timestamp' => 'privacy:metadata:local_extension_hist_state:timestamp',
                'state' => 'privacy:metadata:local_extension_hist_state:state',
                'extlength' => 'privacy:metadata:local_extension_hist_state:extlength'
            ],
            'privacy:metadata:local_extension_hist_state'
        );

        $collection->add_database_table(
            'local_extension_hist_trig',
            [
                'userid' => 'privacy:metadata:local_extension_hist_trig:userid',
                'trig' => 'privacy:metadata:local_extension_hist_trig:trig',
                'localcmid' => 'privacy:metadata:local_extension_hist_trig:localcmid',
                'requestid' => 'privacy:metadata:local_extension_hist_trig:request',
                'timestamp' => 'privacy:metadata:local_extension_hist_trig:timestamp',
                'state' => 'privacy:metadata:local_extension_hist_trig:state'
            ],
            'privacy:metadata:local_extension_hist_trig'
        );

        $collection->add_database_table(
            'local_extension_hist_file',
            [
                'userid' => 'privacy:metadata:local_extension_hist_file:userid',
                'requestid' => 'privacy:metadata:local_extension_hist_file:requestid',
                'timestamp' => 'privacy:metadata:local_extension_hist_file:timestamp',
                'filehash' => 'privacy:metadata:local_extension_hist_file:filehash'
            ],
            'privacy:metadata:local_extension_hist_file'
        );

        $collection->add_database_table(
            'local_extension_subscription',
            [
                'userid' => 'privacy:metadata:local_extension_subscription:userid',
                'localcmid' => 'privacy:metadata:local_extension_subscription:localcmid',
                'trig' => 'privacy:metadata:local_extension_subscription:trig',
                'requestid' => 'privacy:metadata:local_extension_subscription:requestid',
                'access' => 'privacy:metadata:local_extension_subscription:access',
                'lastmod' => 'privacy:metadata:local_extension_subscription:lastmod'
            ],
            'privacy:metadata:local_extension_subscription'
        );

        $collection->add_database_table(
            'local_extension_request',
            [
                'userid' => 'privacy:metadata:local_extension_request:userid',
                'searchstart' => 'privacy:metadata:local_extension_request:searchstart',
                'searchend' => 'privacy:metadata:local_extension_request:searchend',
                'timestamp' => 'privacy:metadata:local_extension_request:timestamp',
                'lastmod' => 'privacy:metadata:local_extension_request:lastmod',
                'lastmodid' => 'privacy:metadata:local_extension_request:lastmodid',
                'messageid' => 'privacy:metadata:local_extension_request:messageid'
            ],
            'privacy:metadata:local_extension_request'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     * @throws dml_exception
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        if (self::user_has_extension_data($userid)) {
            $contextlist->add_user_context($userid);
        }
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     * @throws dml_exception
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }

        // If the user exists in any extension table, add the user context and return it.
        if (self::user_has_extension_data($context->instanceid)) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user()->id;
        $context = \context_user::instance($contextlist->get_user()->id);
        $tables = static::get_table_user_map($user);
        foreach ($tables as $table => $filterparams) {
            $records = $DB->get_recordset($table, $filterparams);
            foreach ($records as $record) {
                writer::with_context($context)->export_data([
                    get_string('privacy:metadata:local_extension', 'local_extension'),
                    get_string('privacy:metadata:'.$table, 'local_extension')
                ], $record);
            }
        }
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     * @throws dml_exception
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        if ($context->contextlevel == CONTEXT_USER) {
            self::delete_user_data($context->instanceid);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER) {
                self::delete_user_data($context->instanceid);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            self::delete_user_data($context->instanceid);
        }
    }

    /**
     * This does the deletion of user data given a userid.
     *
     * @param int $userid The user ID
     * @throws dml_exception
     */
    private static function delete_user_data(int $userid) {
        global $DB;

        $tables = self::get_table_user_map($userid);
        foreach ($tables as $table => $filterparams) {
            $DB->delete_records($table, $filterparams);
        }
    }

    /**
     * Return true if the specified userid has data in any extension tables.
     *
     * @param int $userid The user to check for.
     * @return boolean
     * @throws dml_exception
     */
    private static function user_has_extension_data(int $userid) {
        global $DB;

        $tables = self::get_table_user_map($userid);
        foreach ($tables as $table => $filterparams) {
            if ($DB->count_records($table, $filterparams) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a map of database tables that contain user data, and the filters to get records for a user.
     *
     * @param int $userid
     * @return array The table user map.
     */
    protected static function get_table_user_map(int $userid): array {
        $tables = [
            'local_extension_request' => ['userid' => $userid],
            'local_extension_cm' => ['userid' => $userid],
            'local_extension_comment' => ['userid' => $userid],
            'local_extension_hist_state' => ['userid' => $userid],
            'local_extension_hist_trig' => ['userid' => $userid],
            'local_extension_hist_file' => ['userid' => $userid],
            'local_extension_subscription' => ['userid' => $userid],
        ];

        return $tables;
    }


}