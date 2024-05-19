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
 * Handles the restore of a backup file that includes local_extension data.
 *
 * @package    local_extension
 * @copyright  2024 Scott Verbeek <scottverbeek@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_extension_plugin extends restore_local_plugin {
    /**
     * Defines the extension paths in module element.
     *
     * @return array
     */
    public function define_module_plugin_structure() {
        $paths = [];

        if (!$this->task->get_setting('userinfo')) {
            return $paths;
        }

        $paths[] = new restore_path_element('trigger', $this->get_pathfor('triggers/trigger'));
        $paths[] = new restore_path_element('request', $this->get_pathfor('requests/request'));
        $paths[] = new restore_path_element('comment', $this->get_pathfor('requests/request/comments/comment'));
        $paths[] = new restore_path_element('cm', $this->get_pathfor('requests/request/cm'));
        $paths[] = new restore_path_element('hist_file', $this->get_pathfor('requests/request/hist_files/hist_file'));
        $paths[] = new restore_path_element('hist_state', $this->get_pathfor('requests/request/cm/hist_states/hist_state'));
        $paths[] = new restore_path_element('subscription', $this->get_pathfor('requests/request/cm/subscriptions/subscription'));
        $paths[] = new restore_path_element('hist_trig', $this->get_pathfor('requests/request/cm/hist_trigs/hist_trig'));

        return $paths;
    }

    /**
     * Processes the data for triggers. Performs a checks to prevent duplicate records before restore.
     *
     * @param array $data Extension trigger information.
     */
    public function process_trigger($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // First check if the trigger already exists in the database.
        $nameclause = ' AND ' . $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name');
        $datatypeclause = ' AND ' . $DB->sql_compare_text('datatype') . ' = ' . $DB->sql_compare_text(':datatype');
        $dataclause = ' AND ' . $DB->sql_compare_text('data') . ' = ' . $DB->sql_compare_text(':data');

        $statement = "SELECT *
                        FROM {local_extension_triggers}
                       WHERE role = :role
                         AND action = :action
                         AND priority = :priority
                         AND parent = :parent
                         AND lengthfromduedate = :lengthfromduedate
                         AND lengthtype = :lengthtype
                         AND elapsedfromrequest = :elapsedfromrequest
                         AND elapsedtype = :elapsedtype
                         $nameclause
                         $datatypeclause
                         $dataclause";

        $params = [
            'name' => $data->name,
            'role' => $data->role,
            'action' => $data->action,
            'priority' => $data->priority,
            'parent' => $data->parent,
            'lengthfromduedate' => $data->lengthfromduedate,
            'lengthtype' => $data->lengthtype,
            'elapsedfromrequest' => $data->elapsedfromrequest,
            'elapsedtype' => $data->elapsedtype,
            'datatype' => $data->datatype,
            'data' => $data->data,
        ];

        $existingrecord = $DB->get_record_sql($statement, $params);

        if ($existingrecord) {
            // A record already exists with the same data. Add this records id to the mapping.
            $newitemid = $existingrecord->id;
        } else {
            // Update the context and insert into database.
            $systemctx = context_system::instance();
            $data->context = $systemctx->id;

            $newitemid = $DB->insert_record('local_extension_triggers', $data);
        }

        $this->set_mapping('trigger', $oldid, $newitemid);
    }

    /**
     * Processes the data for request.
     *
     * @param array $data Extension request information.
     */
    public function process_request($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->lastmodid = $this->get_mappingid('user', $data->lastmodid);

        $newitemid = $DB->insert_record('local_extension_request', $data);
        $this->set_mapping('request', $oldid, $newitemid);
    }

    /**
     * Processes the data for comments on a request.
     *
     * @param array $data Extension comment information.
     */
    public function process_comment($data) {
        global $DB;

        $data = (object)$data;
        $data->request = $this->get_new_parentid('request');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('local_extension_comment', $data);
    }

    /**
     * Processes the data for extension local cm of a request.
     *
     * @param array $data Extension cm information.
     */
    public function process_cm($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->request = $this->get_new_parentid('request');
        $data->course = $this->task->get_courseid();
        $data->cmid   = $this->task->get_moduleid();

        $newitemid = $DB->insert_record('local_extension_cm', $data);
        $this->set_mapping('cm', $oldid, $newitemid);
    }

    /**
     * Processes the data for hist file on a request. Also manages the restoration of the annotated files.
     *
     * @param array $data Extension file information.
     */
    public function process_hist_file($data) {
        global $DB;

        $data = (object)$data;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->requestid = $this->get_new_parentid('request');

        // We need a new item id, we get that from the request, so set this as itemname.
        $itemname = 'request';
        $skipparentitemidctxmatch = true;

        // Some other items we need to restore the file.
        $newcontext = context_user::instance($data->userid);
        $newitemid = $data->requestid;
        $component = 'local_extension';
        $filearea = 'attachments';

        // Add file to restore.
        restore_dbops::send_files_to_pool(
            $this->task->get_basepath(),
            $this->task->get_restoreid(),
            $component,
            $filearea,
            $data->oldcontextid,
            $data->userid,
            $itemname,
            $data->olditemid,
            $newcontext->id,
            $skipparentitemidctxmatch
        );

        // Update the filehash to the new values.
        $data->filehash = file_storage::get_pathname_hash(
            $newcontext->id,
            $component,
            $filearea,
            $newitemid,
            $data->filepath,
            $data->filename
        );

        $DB->insert_record('local_extension_hist_file', $data);
    }

    /**
     * Processes the data for histortical state of a request.
     *
     * @param array $data Extension historical state information.
     */
    public function process_hist_state($data) {
        global $DB;

        $data = (object)$data;
        $data->requestid = $this->get_new_parentid('request');
        $data->localcmid = $this->get_new_parentid('cm');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('local_extension_hist_state', $data);
    }

    /**
     * Processes the data for subscriptions on a request.
     *
     * @param array $data Extension subscription information.
     */
    public function process_subscription($data) {
        global $DB;

        $data = (object)$data;
        $data->requestid = $this->get_new_parentid('request');
        $data->localcmid = $this->get_new_parentid('cm');
        $data->trig = $this->get_mappingid('trigger', $data->trig);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('local_extension_subscription', $data);
    }

    /**
     * Processes the data for historical triggers on a request.
     *
     * @param array $data Extension trigger information.
     */
    public function process_hist_trig($data) {
        global $DB;

        $data = (object)$data;
        $data->requestid = $this->get_new_parentid('request');
        $data->localcmid = $this->get_new_parentid('cm');
        $data->trig = $this->get_mappingid('trigger', $data->trig);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('local_extension_hist_trig', $data);
    }
}
