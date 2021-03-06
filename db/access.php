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
 * Capability definitions for this module.
 *
 * @package    local_extension
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\access\capability_checker;

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/extension:viewallrequests' => array(
        'riskbitmask'  => RISK_SPAM | RISK_XSS | RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),

    'local/extension:modifyrequeststatus' => array(
        'riskbitmask'  => RISK_SPAM | RISK_XSS | RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),

    'local/extension:viewhistorydetail' => array(
        'riskbitmask'  => RISK_PERSONAL,
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
    ),

    capability_checker::CAPABILITY_ACCESS_ALL_COURSE_REQUESTS => array(
        'riskbitmask'  => RISK_SPAM | RISK_XSS | RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => array(
            'manager'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ),
    ),
);
