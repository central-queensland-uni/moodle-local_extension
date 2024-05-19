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
 * local_extension plugin settings
 *
 * @package    local_extension
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_extension', get_string('pluginname', 'local_extension')));

    $settings = new admin_settingpage('local_extension_settings_general', get_string('admin_settings_general', 'local_extension'));
    $ADMIN->add('local_extension', $settings);

    $manageurl = new moodle_url("$CFG->wwwroot/local/extension/rules/manage.php");
    $manage = new admin_externalpage('local_extension_settings_rules', get_string('externalrules', 'local_extension'), $manageurl);
    $ADMIN->add('local_extension', $manage);

    $days = array();
    for ($c = 1; $c <= 21; $c++) {
        $days[$c] = new lang_string('numdays', '', $c);
    }

    $weeks = array();
    for ($c = 1; $c <= 52; $c++) {
        if ($c == 1) {
            $weeks[$c] = get_string('week', 'local_extension', $c);
        } else {
            $weeks[$c] = get_string('week_plural', 'local_extension', $c);
        }
    }

    $settings->add(new admin_setting_configselect('local_extension/searchback',
        new lang_string('searchback',             'local_extension'),
        new lang_string('searchbackhelp',         'local_extension'), 7, $days));

    $settings->add(new admin_setting_configselect('local_extension/searchforward',
        new lang_string('searchforward',          'local_extension'),
        new lang_string('searchforwardhelp',      'local_extension'), 14, $days));

    $settings->add(new admin_setting_configselect('local_extension/searchbackwardmaxweeks',
        new lang_string('searchbackwardmaxweeks',     'local_extension'),
        new lang_string('searchbackwardmaxweekshelp', 'local_extension'), 2, $weeks));

    $settings->add(new admin_setting_configselect('local_extension/searchforwardmaxweeks',
        new lang_string('searchforwardmaxweeks',     'local_extension'),
        new lang_string('searchforwardmaxweekshelp', 'local_extension'), 2, $weeks));

    $settings->add(new admin_settings_coursecat_select('local_extension/defaultcategory',
        new lang_string('defaultcategory',         'local_extension'),
        new lang_string('defaultcategoryhelp',     'local_extension'), 1));

    $settings->add(new admin_setting_configcheckbox('local_extension/emaildisable',
        new lang_string('emaildisable',             'local_extension'),
        new lang_string('emaildisablehelp',         'local_extension'), false));

    $settings->add(new admin_setting_configtext('local_extension/supportusername',
        new lang_string('supportusername',        'local_extension'),
        new lang_string('supportusernamehelp',    'local_extension'),
        new lang_string('supportusernamedefault', 'local_extension'), PARAM_TEXT, 100));

    $settings->add(new admin_setting_confightmleditor('local_extension/extensionpolicyrequest',
        new lang_string('extensionpolicyrequest',        'local_extension'),
        new lang_string('extensionpolicyrequesthelp',    'local_extension'),
        ''));

    $settings->add(new admin_setting_confightmleditor('local_extension/extensionpolicystatus',
        new lang_string('extensionpolicystatus',        'local_extension'),
        new lang_string('extensionpolicystatushelp',    'local_extension'),
        ''));

    $settings->add(new admin_setting_confightmleditor('local_extension/attachmentpolicy',
        new lang_string('attachmentpolicy',           'local_extension'),
        new lang_string('attachmentpolicyhelp',       'local_extension'),
        ''));

    $options = array(
        0 => get_string('no'),
        1 => get_string('yes')
    );

    $settings->add(new admin_setting_configselect('local_extension/systemcontext',
        new lang_string('systemcontext',          'local_extension'),
        new lang_string('systemcontexthelp',      'local_extension'), 1, $options));

    $settings->add(new admin_setting_configselect('local_extension/coursecontext',
        new lang_string('coursecontext',          'local_extension'),
        new lang_string('coursecontexthelp',      'local_extension'), 1, $options));

    $settings->add(new admin_setting_configselect('local_extension/modulecontext',
        new lang_string('modulecontext',          'local_extension'),
        new lang_string('modulecontexthelp',      'local_extension'), 1, $options));

    $settings->add(new admin_setting_configtext('local_extension/extensionlimit',
        new lang_string('extensionlimit',        'local_extension'),
        new lang_string('extensionlimithelp',    'local_extension'),
        new lang_string('extensionlimitdefault', 'local_extension'), PARAM_INT));

    $settings->add(new admin_setting_configtext('local_extension/extensionlimitwanring',
        new lang_string('extensionlimitwarning',        'local_extension'),
        new lang_string('extensionlimitwarninghelp',    'local_extension'),
        new lang_string('extensionlimitwarningdefault', 'local_extension'), PARAM_RAW, 100));

    $settings->add(new admin_setting_configselect('local_extension/requireattachment',
        new lang_string('requireattachment',      'local_extension'),
        new lang_string('requireattachmenthelp',  'local_extension'), 1, $options));

    $settings->add(new admin_setting_configselect('local_extension/ruleignoredatatype',
        new lang_string('ruleignoredatatype',     'local_extension'),
        new lang_string('ruleignoredatatypehelp', 'local_extension'), 0, $options));
}
