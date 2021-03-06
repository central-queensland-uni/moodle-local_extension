<?php
// This file is part of Extension Activity Plugin
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

namespace local_extension\task;

use core\task\scheduled_task;
use local_extension\message\mailer;

defined('MOODLE_INTERNAL') || die();

class email_digest_task extends scheduled_task {
    /** @var mailer */
    private $mailer;

    public function __construct($mailer = null) {
        if (is_null($mailer)) {
            $mailer = new mailer();
        }
        $this->mailer = $mailer;
    }

    public function get_name() {
        return get_string('task_email_digest', 'local_extension');
    }

    public function execute() {
        mtrace('Sending e-mails digest...');
        $this->mailer->email_digest_send();
        mtrace('Deleting old messages from queue...');
        $this->mailer->email_digest_cleanup();
    }
}
