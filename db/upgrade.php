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
 * Upgrade script for local_extension.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_extension_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017051600) {

        // Rename field trigger on table local_extension_history_trig to trig.
        $table = new xmldb_table('local_extension_history_trig');
        $field = new xmldb_field('trigger', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        // Launch rename field trig.
        $dbman->rename_field($table, $field, 'trig');

        // Rename field trigger on table local_extension_subscription to trig.
        $table = new xmldb_table('local_extension_subscription');
        $field = new xmldb_field('trigger', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        // Launch rename field trig.
        $dbman->rename_field($table, $field, 'trig');

        // Define table local_extension_history_trig to be renamed to local_extension_hist_trig.
        $table = new xmldb_table('local_extension_history_trig');
        // Launch rename table for local_extension_history_trig.
        $dbman->rename_table($table, 'local_extension_hist_trig');

        // Define table local_extension_history_file to be renamed to local_extension_hist_file.
        $table = new xmldb_table('local_extension_history_file');
        // Launch rename table for local_extension_history_file.
        $dbman->rename_table($table, 'local_extension_hist_file');

        // Define table local_extension_his_state to be renamed to local_extension_hist_state.
        $table = new xmldb_table('local_extension_his_state');
        // Launch rename table for local_extension_his_state.
        $dbman->rename_table($table, 'local_extension_hist_state');

        // Extension savepoint reached.
        upgrade_plugin_savepoint(true, 2017051600, 'local', 'extension');
    }

    if ($oldversion < 2017062200) {

        // Define field extlength to be added to local_extension_hist_state.
        $table = new xmldb_table('local_extension_hist_state');
        $field = new xmldb_field('extlength', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Conditionally launch add field timestamp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Obtains the first comment for the request. Will be used when setting the initial history state and length.
        $sql = "SELECT lcm.*,
                       com.*
                  FROM {local_extension_cm} lcm
            INNER JOIN {local_extension_comment} com
                    ON lcm.request = com.request
            INNER JOIN
                      (
                      SELECT cc.request, MIN(cc.timestamp) as timestamp
                        FROM {local_extension_comment} cc
                    GROUP BY cc.request
                      ) cc ON lcm.request = cc.request
                          AND com.timestamp = cc.timestamp
              ORDER BY lcm.request ASC";

        $records = $DB->get_records_sql($sql);

        // Create 'New' states for each request.
        foreach ($records as $record) {

            $sh = new stdClass();
            $sh->localcmid = $record->cmid;
            $sh->requestid = $record->request;
            $sh->timestamp = $record->timestamp;
            $sh->state = \local_extension\state::STATE_NEW;
            $sh->userid = $record->userid;

            $DB->insert_record('local_extension_hist_state', $sh, false, true);
        }

        // Obtains length value for cms that match a possible history state. Updates the history with the length of the cm.
        $sql = "SELECT hst.*,
                       lcm.length AS extlength
                  FROM {local_extension_cm} lcm
             LEFT JOIN {local_extension_hist_state} hst
                    ON lcm.request = hst.requestid
                   AND lcm.state = hst.state
              ORDER BY hst.timestamp ASC";

        $records = $DB->get_records_sql($sql);

        $history = [];

        // Create a map of the requestids and each history item.
        foreach ($records as $record) {
            if (!empty($record->id)) {
                $history[$record->requestid][] = $record;
            }
        }

        foreach ($history as $rid => $items) {

            // Only update the last history item, as we will know the extension length for this based on the cm object.
            $record = array_pop($items);

            if ($record) {
                $DB->update_record('local_extension_hist_state', $record, true);
            }
        }

        upgrade_plugin_savepoint(true, 2017062200, 'local', 'extension');
    }

    if ($oldversion < 2018030705) {

        // Define table local_extension_digest_queue to be created.
        $table = new xmldb_table('local_extension_digest_queue');

        // Adding fields to table local_extension_digest_queue.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'invalid');
        $table->add_field('added', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('runid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userto', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('headers', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('contents', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_extension_digest_queue.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_extension_digest_queue.
        $table->add_index('in_extension_status_added', XMLDB_INDEX_NOTUNIQUE, ['status', 'added']);

        // Conditionally launch create table for local_extension_digest_queue.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_extension_digest_runs to be created.
        $table = new xmldb_table('local_extension_digest_runs');

        // Adding fields to table local_extension_digest_runs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('whenran', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_extension_digest_runs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_extension_digest_runs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Extension savepoint reached.
        upgrade_plugin_savepoint(true, 2018030705, 'local', 'extension');
    }


    if ($oldversion < 2023111501) {

        // Define field extlength to be added to local_extension_comment.
        $table = new xmldb_table('local_extension_comment');
        $field = new xmldb_field('validationcheck', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');

        // Conditionally launch add field timestamp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update existing comments.
        $comments = $DB->get_records('local_extension_comment');
        foreach ($comments as $comment) {
            $comment->validationcheck = $comment->timestamp . '_' . $comment->userid;
            $DB->update_record('local_extension_comment', $comment);
        }

        // Extension savepoint reached.
        upgrade_plugin_savepoint(true, 2023111501, 'local', 'extension');
    }

    return true;
}
