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
 * Delete a rule.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extension\rule;
use local_extension\utility;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extension_settings_rules');

$delete = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   // MD5 confirmation hash.

$url = new moodle_url('/local/extension/rules/delete.php');
$PAGE->set_url($url);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('rules_page_heading', 'local_extension'));
$PAGE->requires->css('/local/extension/styles.css');
$PAGE->add_body_class('local_extension');

/* @var local_extension_renderer $renderer IDE hinting */
$renderer = $PAGE->get_renderer('local_extension');

// TODO replace with load_branch from ruleid.
$rules = rule::load_all();
$ordered = utility::rule_tree($rules);
if ($delete && confirm_sesskey()) {

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        echo html_writer::tag('h2', get_string('page_heading_manage_delete', 'local_extension'));

        $params = array(
            'id' => $delete,
            'confirm' => md5($delete),
            'sesskey' => sesskey()
        );
        $deleteurl = new moodle_url($url, $params);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

        $branch = utility::rule_tree_branch($ordered, $delete);

        echo $renderer->render_delete_rules(array($branch));

        $url = new moodle_url('/local/extension/rules/manage.php');
        echo $OUTPUT->confirm('', $deletebutton, $url);
        echo $OUTPUT->footer();

        exit();

    } else if (data_submitted()) {
        // Select all child rules for id.
        $sql = "SELECT id
                  FROM {local_extension_triggers}
                 WHERE parent = ?";
        $params = array($delete);

        // List of rule ids associated to this $delete id.
        $items = $DB->get_fieldset_sql($sql, $params);

        // Add the $ruleid.
        $items[] = $delete;

        // Remove all rules, including the children.
        $DB->delete_records_list('local_extension_triggers', 'id', $items);

        foreach ($items as $deleteid) {
            $rules[$deleteid]->trigger_disable_event();
        }

        redirect(new moodle_url('/local/extension/rules/manage.php'));

    }

}
