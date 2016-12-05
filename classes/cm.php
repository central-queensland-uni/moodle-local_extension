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
 * Course module class.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extension;

defined('MOODLE_INTERNAL') || die();

/**
 * Course module class.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm {
    /** @var int The local_extension_cm id */
    public $cmid = null;

    /** @var int The user id associated with this cm, should be the userid of the request */
    public $userid = null;

    /** @var int The request id associated with this cm */
    public $requestid = null;

    /** @var \stdClass The local_extension_cm database object */
    public $cm = null;

    /**
     * Cm constructor
     *
     * @param int $cmid
     * @param int $userid
     * @param int $requestid
     */
    public function __construct($cmid, $userid, $requestid) {
        $this->cmid = $cmid;
        $this->userid = $userid;
        $this->requestid = $requestid;
    }

    /**
     * Obtain a cm class with the requestid.
     *
     * @param int $cmid
     * @param int $requestid
     * @return cm $cm Local cm.
     */
    public static function from_requestid($cmid, $requestid) {
        global $DB;

        $cm = new cm($cmid, null, $requestid);

        $conditions = array('cmid' => $cm->cmid, 'request' => $cm->requestid);
        $record = $DB->get_record('local_extension_cm', $conditions, 'cmid,course,data,id,request,state,length,lengthprev,userid');

        if (!empty($record)) {
            $cm->userid = $record->userid;
            $cm->cm = $record;
        }

        return $cm;
    }

    /**
     * Obtain a cm class with the userid.
     *
     * @param int $cmid
     * @param int $userid
     * @return cm $localcm Local cm.
     */
    public static function from_userid($cmid, $userid) {
        global $DB;

        $localcm = new cm($cmid, $userid, null);

        $conditions = array('cmid' => $localcm->cmid, 'userid' => $localcm->userid);
        $cm = $DB->get_record('local_extension_cm', $conditions, 'cmid,course,data,id,request,state,length,userid');

        if (!empty($cm)) {
            $localcm->cm = $cm;
            $localcm->requestid = $cm->request;
        }

        return $localcm;
    }

    /**
     * Parses submitted form data and sets the properties of this class to match.
     *
     * @param \stdClass $form
     */
    public function load_from_form($form) {

        foreach ($form as $key => $value) {

            if (property_exists($this, $key)) {
                $this->$key = $form->$key;

            } else {
                if ($key == 'datatype') {
                    $this->cm->data['datatype'] = $form->$key;
                }
            }

        }

        $this->data_save();
    }

    /**
     * Unserialises and base64_decodes the saved custom data.
     * @return array
     */
    public function data_load() {
        return unserialize(base64_decode($this->get_data()));
    }

    /**
     * Saves the custom data, serialising it and then base64_encoding.
     */
    public function data_save() {
        $data = base64_encode(serialize($this->get_data()));
        $this->set_data($data);
    }

    /**
     * Writes an state change entry to local_extension_his_state. Returns the history object.
     *
     * @param \stdClass $mod
     * @param int $state
     * @param int $userid
     * @return \stdClass $history
     */
    public function write_history($mod, $state, $userid) {
        global $DB;

        $localcm = $mod->localcm;

        $history = new \stdClass();
        $history->localcmid = $localcm->cmid;
        $history->requestid = $localcm->requestid;
        $history->timestamp = time();
        $history->state = $state;
        $history->userid = $userid;

        $DB->insert_record('local_extension_his_state', $history);

        return $history;
    }

    /**
     * Returns the cm courseid
     *
     * @return integer
     */
    public function get_courseid() {
        return $this->cm->course;
    }

    /**
     * Returns the cm cmid
     *
     * @return integer
     */
    public function get_cmid() {
        return $this->cm->cmid;
    }

    /**
     * Returns the cm data.
     *
     * @return mixed
     */
    public function get_data() {
        if (!empty($this->cm)) {
            return $this->cm->data;

        } else {
            return null;
        }
    }

    /**
     * Returns the cm previous length
     *
     * @return mixed
     */
    public function get_length_previous() {
        if (!empty($this->cm)) {
            return $this->cm->lengthprev;

        } else {
            return null;
        }
    }

    /**
     * Returns the cm length
     *
     * @return mixed
     */
    public function get_length() {
        if (!empty($this->cm)) {
            return $this->cm->length;

        } else {
            return null;
        }
    }

    /**
     * Returns the cm state id
     *
     * @return integer
     */
    public function get_stateid() {
        return $this->cm->state;
    }

    /**
     * Set the cm state id
     *
     * @param integer $state
     */
    private function set_stateid($state) {
        $this->cm->state = $state;
    }

    /**
     * Sets the state of this request.
     *
     * @param int $state
     * @return bool
     */
    public function set_state($state) {
        global $DB;

        if (empty($state)) {
            return false;
        }

        $this->set_stateid($state);

        $DB->update_record('local_extension_cm', $this->cm);

        utility::cache_invalidate_request($this->requestid);

        return true;
    }

    /**
     * Set the cm data
     *
     * @param mixed $data
     */
    private function set_data($data) {
        $this->cm->data = $data;
    }

    /**
     * Updates the database record of the local cm.
     */
    public function update_data() {
        global $DB;

        $DB->update_record('local_extension_cm', $this->cm);

        utility::cache_invalidate_request($this->requestid);
    }

}
