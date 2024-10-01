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
 * Defines all the backup steps that will be used by the backup_local_extension_task.
 *
 * @package    local_extension
 * @copyright  2024 Scott Verbeek <scottverbeek@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_extension_plugin extends backup_plugin {
    /**
     * Adds extension information to the module element.
     */
    public function define_plugin_structure($connectionpoint) {
        global $DB;

        if ($connectionpoint != 'module' || !$this->task->get_setting('userinfo')) {
            parent::define_plugin_structure($connectionpoint);
            return;
        }

        // This node is important for the restore. It makes it easy to add the paths back.
        $module = new backup_nested_element('plugin_local_extension_module');

        $triggers = new backup_nested_element('triggers');
        $trigger = new backup_nested_element('trigger', ['id', 'context'], [ // The context is always system.
            'name',
            'role',
            'action',
            'priority',
            'parent',
            'lengthfromduedate',
            'lengthtype',
            'elapsedfromrequest',
            'elapsedtype',
            'datatype',
            'data',
        ]);
        $triggers->add_child($trigger);

        // Define each element separated.
        $requests = new backup_nested_element('requests');
        $request = new backup_nested_element('request', ['id'], [
            'userid',
            'lastmod',
            'lastmodid',
            'searchstart',
            'searchend',
            'timestamp',
            'messageid',
        ]);
        $requests->add_child($request);

        $comments = new backup_nested_element('comments');
        $comment = new backup_nested_element('comment', ['id'], [
            'userid', // Annotation.
            'timestamp',
            'message',
            'validationcheck',
        ]);
        $comments->add_child($comment);

        $cm = new backup_nested_element('cm', ['id'], [
            'userid', // Annotation.
            'course', // Not needed?
            'cmid', // Not needed?
            'name',
            'state',
            'data',
            'length',
        ]);

        $histfiles = new backup_nested_element('hist_files');
        $histfile = new backup_nested_element('hist_file', ['id'], [
            'userid', // Annotation.
            'timestamp',
            'filehash', // This is the pathnamehash which will need recalculation.
            'olditemid',
            'oldcontextid',
            'filepath',
            'filename',
        ]);
        $histfiles->add_child($histfile);

        $histstates = new backup_nested_element('hist_states');
        $histstate = new backup_nested_element('hist_state', ['id'], [
            'userid', // Annotation.
            'timestamp',
            'state',
            'extlength',
        ]);
        $histstates->add_child($histstate);

        $subscriptions = new backup_nested_element('subscriptions');
        $subscription = new backup_nested_element('subscription', ['id'], [
            'userid', // Annotation.
            'trig', // Annotation.
            'access',
            'lastmod',
        ]);
        $subscriptions->add_child($subscription);

        $histtriggers = new backup_nested_element('hist_trigs');
        $histtrigger = new backup_nested_element('hist_trig', ['id'], [
            'userid', // Annotation.
            'trig', // Annotation.
            'state',
            'timestamp',
        ]);
        $histtriggers->add_child($histtrigger);

        // Build the tree.
        $activity = $this->get_plugin_element();
        $activity->add_child($module);
        $module->add_child($triggers);
        $module->add_child($requests);
        $request->add_child($comments);
        $request->add_child($cm);
        $request->add_child($histfiles);
        $request->add_child($histstates);
        $cm->add_child($histtriggers);
        $cm->add_child($subscriptions);

        // Define the source of $trigger, which should only include the ones from subscriptions and triggers.
        // The restore will decide to restore or not since there may be a lot of duplicated.
        $trigger->set_source_sql('SELECT *
                                    FROM mdl_local_extension_triggers
                                   WHERE id IN (
                                          SELECT trig as id
                                            FROM mdl_local_extension_subscription
                                            JOIN mdl_local_extension_cm cm ON cm.id = localcmid
                                           WHERE cmid = :moduleid1
                                             AND course = :courseid1
                                           UNION
                                          SELECT trig as id
                                            FROM mdl_local_extension_hist_trig
                                            JOIN mdl_local_extension_cm cm ON cm.id = localcmid
                                           WHERE cmid = :moduleid2
                                             AND course = :courseid2
                                         )',
            [
                'courseid1' => backup::VAR_COURSEID,
                'courseid2' => backup::VAR_COURSEID,
                'moduleid1' => backup::VAR_PARENTID,
                'moduleid2' => backup::VAR_PARENTID,
            ]
        );

        // Define the source of $request.
        $request->set_source_sql(
            'SELECT request.*
               FROM {local_extension_request} request
               JOIN {local_extension_cm} localcm ON request.id = localcm.request
              WHERE localcm.cmid = :cm
                AND localcm.course = :course',
            ['cm' => backup::VAR_PARENTID, 'course' => backup::VAR_COURSEID]
        );

        // Define the sources of the children of $request.
        $comment->set_source_table('local_extension_comment',
            ['request' => '../../id']);
        $histstate->set_source_table('local_extension_hist_state',
            ['requestid' => '../../id']);
        $cm->set_source_table('local_extension_cm',
            ['request' => backup::VAR_PARENTID]);

        // For the hist_file we need to add some items for restoring files, specifically the files old itemid and contextid.
        // Also we require the filepath and filename to recalculate the pathnamehash.
        $histfile->set_source_sql('SELECT hf.*, f.itemid as olditemid, f.contextid as oldcontextid, f.filepath, f.filename
                                     FROM {local_extension_hist_file} hf
                                     JOIN {files} f ON hf.filehash = f.pathnamehash
                                    WHERE hf.requestid = :request',
            ['request' => '../../id']);

        // Define the sources of the children of $cm.
        $histtrigger->set_source_table('local_extension_hist_trig',
            ['requestid' => '../../../id', 'localcmid' => '../../id']);
        $subscription->set_source_table('local_extension_subscription',
            ['requestid' => '../../../id', 'localcmid' => '../../id']);

        // Define id annotations.
        $request->annotate_ids('user', 'userid');
        $request->annotate_ids('user', 'lastmodid');
        $comment->annotate_ids('user', 'userid');
        $cm->annotate_ids('user', 'userid');
        $histstate->annotate_ids('user', 'userid');
        $subscription->annotate_ids('user', 'userid');
        $histfile->annotate_ids('user', 'userid');
        $histtrigger->annotate_ids('user', 'userid');

        // The following the annotate files, in a less ordinary way.
        // One request can span more than one activities and more than one courses. The context of a file is context level of user.
        // This context level causes a couple issues which requires us to manually annotate files.
        $statement = "SELECT DISTINCT f.userid as id, f.userid, f.requestid as itemid
                        FROM {local_extension_cm} localcm
                        JOIN {local_extension_hist_file} f ON f.requestid = localcm.request
                       WHERE localcm.cmid = :cm
                         AND localcm.course = :course";
        $params = [
            'cm' => $this->task->get_moduleid(),
            'course' => $this->task->get_courseid(),
        ];
        $rs = $DB->get_recordset_sql($statement, $params);

        $backupid  = $this->task->get_backupid();
        $component = 'local_extension';
        $filearea  = 'attachments';

        foreach ($rs as $record) {
            $userid = $record->userid;
            $context = context_user::instance($userid, IGNORE_MISSING);

            if (!$context) {
                continue; // User has not context, sure it's a deleted user, so cannot have files.
            }

            backup_structure_dbops::annotate_files($backupid, $context->id, $component, $filearea, $record->itemid);
        }
        $rs->close();

        parent::define_plugin_structure($connectionpoint);
    }
}
