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
 * Edit a rule / trigger.
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

$triggerid = optional_param('id', 0, PARAM_INT);
$datatype = required_param('datatype', PARAM_ALPHANUM);

if (empty($datatype)) {
    throw new coding_exception('required_param() requires $parname and $type to be specified (parameter: datatype)');
}

$PAGE->set_url(new moodle_url('/local/extension/rules/edit.php'));

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_extension'));
$PAGE->set_heading(get_string('rules_page_heading', 'local_extension'));
$PAGE->requires->css('/local/extension/styles.css');
$PAGE->add_body_class('local_extension');

$renderer = $PAGE->get_renderer('local_extension');

$data = null;
$editordata = array(
    'template_notify' => array('text' => get_string('template_notify_content', 'local_extension')),
    'template_user' => array('text' => get_string('template_user_content', 'local_extension')),
);

if (!empty($triggerid) && confirm_sesskey()) {
    $data = rule::from_id($triggerid);

    // Set the saved serialised data as object properties, which will be loaded as default form values.
    // If and only if the form elements have the same name, and they have been saved to the data variable.
    if (!empty($data->data)) {
        foreach ($data->data as $key => $value) {

            if (strpos($key, 'template') === 0) {
                if (!empty($value)) {
                    $editordata[$key] = array('text' => $value);
                }
            }

            $data->$key = $value;
        }
    }
}

$rules = rule::load_all($datatype);
$sorted = utility::rule_tree($rules);

$params = array(
    'ruleid' => $triggerid,
    'rules' => $sorted,
    'datatype' => $datatype,
    'editordata' => $editordata,
);

if (empty($triggerid)) {
    $PAGE->navbar->add(get_string('breadcrumb_nav_rule_new', 'local_extension'));

} else {
    $PAGE->navbar->add(get_string('breadcrumb_nav_rule_edit', 'local_extension', $rules[$triggerid]->name));

}

$mform = new \local_extension\form\rule(null, $params);

$mform->set_data($data);

if ($mform->is_cancelled()) {

    $url = new moodle_url('/local/extension/rules/manage.php');
    redirect($url);
    die;

} else if ($form = $mform->get_data()) {

    $rule = new rule();

    // Also saves template_ form items to the custom data variable.
    $rule->load_from_form($form);

    if (!empty($rule->id)) {
        $DB->update_record('local_extension_triggers', $rule);
        $rule->trigger_update_event();
    } else {
        $rule->id = $DB->insert_record('local_extension_triggers', $rule);
        $rule->trigger_create_event($rule->id);
    }

    $url = new moodle_url('/local/extension/rules/manage.php');
    redirect($url);
    die;

}

echo $OUTPUT->header();
echo $mform->display();
echo $OUTPUT->footer();
