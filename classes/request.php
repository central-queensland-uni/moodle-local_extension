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
 * Request class
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extension;

/**
 * Request class.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request {

    /** @var $request The local_extension_request database object */
    public $requestid = null;

    /** @var $request The local_extension_request database object */
    public $request = array();

    /** @var $cms */
    public $cms = array();

    /** @var $comments An array of comment objects from the request id */
    public $comments = array();

    /** @var $users An array of user objects with the available fields user_picture::fields  */
    public $users = array();

    /** @var $files An array of attached files that exist for this request id */
    public $files = array();

    /**
     * Request object constructor.
     * @param int $reqid An optional variable to identify the request.
     */
    public function __construct($requestid = null) {
        $this->requestid = $requestid;
    }

    /**
     * Loads data into the object
     */
    public function load() {
        global $DB;

        if (empty($this->requestid)) {
            throw coding_exception('No request id');
        }

        $reqid = $this->requestid;

        $this->request  = $DB->get_record('local_extension_request', array('id' => $reqid));
        $this->cms      = $DB->get_records('local_extension_cm', array('request' => $reqid));
        $this->comments = $DB->get_records('local_extension_comment', array('request' => $reqid));

        $userids     = array();
        $userrecords = array();

        // TODO need to sort cms by date and comments by date.
        // Obtain a unique list of userids that have been commenting.
        foreach ($this->comments as $comment) {
            $userids[$comment->userid] = $comment->userid;
        }

        // Fetch the users.
        // TODO change this to single call using get_in_or_equal .
        foreach ($userids as $uid) {
            $userrecords[$uid] = $DB->get_record('user', array('id' => $uid), \user_picture::fields());
        }

        $this->users = $userrecords;

        $this->files = $this->fetch_attachments($reqid);
    }

    /**
     * Obtain request data for the renderer.
     *
     * @param int $reqid An id for a request.
     * @return request $req A request data object.
     */
    public static function from_id($reqid) {
        $request = new request($reqid);
        $request->load();
        return $request;
    }

    /**
     * Fetch the list of attached files for the request id.
     *
     * @param int $reqid An id for a request.
     * @return
     */
    public function fetch_attachments($reqid) {
        global $USER;

        $context = \context_user::instance($USER->id);

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_extension', 'attachments', $reqid);

        return $files;
    }

    public function add_comment($from, $comment, $format) {

    }

}
