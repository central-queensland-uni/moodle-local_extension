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
 *  local_extension plugin lang string library
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['admin_settings_general'] = 'Extension settings';
$string['ago'] = 'ago';
$string['and'] = 'and';
$string['attachmentpolicy'] = 'File attachment policy';
$string['attachmentpolicyhelp'] = 'This policy will be displayed above the attachment field.';
$string['attachment_detail'] = '{$a->file} from {$a->user} on {$a->date}';
$string['attachments'] = 'Attached documents';
$string['button_edit_rule'] = 'Add a new Rule';
$string['button_request_extension'] = 'Request an Extension';
$string['breadcrumb_nav_additional'] = 'Request an additional extension';
$string['breadcrumb_nav_index'] = 'Extension Status';
$string['breadcrumb_nav_modify'] = 'Modify extension length';
$string['breadcrumb_nav_modify_state'] = 'Modify state';
$string['breadcrumb_nav_preferences'] = 'Preferences';
$string['breadcrumb_nav_request'] = 'New Extension Request';
$string['breadcrumb_nav_rule_edit'] = 'Edit Rule: {$a}';
$string['breadcrumb_nav_rule_new'] = 'New Rule';
$string['breadcrumb_nav_status'] = 'Extension #{$a->id}: {$a->name}';
$string['cachedef_requests'] = 'A cache of the requests data';
$string['comment'] = 'General comments';
$string['comments'] = 'Comments';
$string['coursecontext'] = 'Allow course context requests';
$string['coursecontexthelp'] = 'Allow multiple activity modules to be selected when making a request in the course context.';
$string['defaultcategory'] = 'Default request category';
$string['defaultcategoryhelp'] = 'This is the default category to filter the request list by.';
$string['duedateoriginal'] = 'Original due date';
$string['duedateextension'] = 'Due date of the current extension';
$string['emaildisable'] = 'Disable Email notifications';
$string['emaildisablehelp'] = 'This prevents the plugin from sending any email notifications.';
$string['email_notification_subject'] = '[{$a->courseshortname}] Extension request #{$a->requestid} for {$a->fullname}.';
$string['emailtemplate'] = 'Email Template';
$string['emailtemplatehelp'] = '';
$string['error_extension_lessthan'] = 'Less than days';
$string['error_no_mods'] = 'The requested date range has no activities that will allow an extension request.
{$a->startrange} - {$a->endrange}';
$string['error_none_selected'] = 'Please select at least one item';
$string['error_same_date'] = 'Please select a different date';
$string['error_over_extension_limit'] = 'Your request of {$a->days} days is too long. Please make a shorter request.';
$string['event_trigger_create'] = 'Trigger created';
$string['event_trigger_create_desc'] = 'The user with the id \'{$a->userid}\' has created a trigger for type \'{$a->datatype}\'.';
$string['event_trigger_disable'] = 'Trigger disabled';
$string['event_trigger_disable_desc'] = 'The user with the id \'{$a->userid}\' has disabled the trigger id \'{$a->objectid}\' for type \'{$a->datatype}\'.';
$string['event_trigger_process'] = 'Processed triggers';
$string['event_trigger_process_desc'] = 'Process the triggers for all active requests';
$string['event_trigger_request'] = 'Request created';
$string['event_trigger_request_desc'] = 'A request has been created';
$string['event_trigger_update'] = 'Trigger updated';
$string['event_trigger_update_desc'] = 'The user with the id \'{$a->userid}\' has updated the trigger id \'{$a->objectid}\' for type \'{$a->datatype}\'.';
$string['export_csv'] = 'Export CSV';
$string['extensionlimitdefault'] = '28';
$string['extensionlimit'] = 'Extension length limit in days';
$string['extensionlimithelp'] = 'This is a hard limit which restricts the length that a student can initially request an extension for.';
$string['extensionlimitwarningdefault'] = 'Your request of {{days}} days is too long. Please make a shorter request.';
$string['extensionlimitwarning'] = 'Warning message for exceeding the limit';
$string['extensionlimitwarninghelp'] = 'Customise the warning message that users will see when they request an extension that is greater than the limit set.<br />Template items are:</br>{{maxdays}} The maximum number of days for a request.<br/>{{maxweeks}} The maximum number of weeks for a request.<br/>{{lengthdays}} The extension length in days.<br/>{{lengthweeks}} The extension length in weeks.';
$string['extensionpolicyrequest'] = 'Extension policy for request';
$string['extensionpolicyrequesthelp'] = 'When making a new request, this policy will be displayed at the top of the page.';
$string['extensionpolicystatus'] = 'Extension policy for status';
$string['extensionpolicystatushelp'] = 'When viewing the status of a request, this policy will be displayed at the top of the page.';
$string['extension:accessallcourserequests'] = 'Allow user to view all extension requests in the course';
$string['extension:exportrequestscsv'] = 'Allow user to export the requests he is subscribed to as CSV';
$string['extension:modifyrequeststatus'] = 'Abilitiy to force a status change in a request';
$string['extension:viewallrequests'] = 'View all extension requests';
$string['extension:viewhistorydetail'] = 'View detailed history in the extension status page';
$string['digest_subject'] = 'Extension status - digest: {$a} message(s)';
$string['externalrules'] = 'Manage Adapter Triggers';
$string['form_modify_original_status'] = 'Original extension request details and status.';
$string['form_modify_request_header'] = 'Please specify the new extension length.';
$string['form_request_availablerequests'] = 'Available Requests';
$string['form_request_requestsinprogress'] = 'Requests in progress';
$string['form_rule_action'] = 'Action';
$string['form_rule_any_value'] = 'any value';
$string['form_rule_continue'] = 'Continue';
$string['form_rule_extension_length_greater'] = 'Length from due date<br />(greater or equal to x days)';
$string['form_rule_extension_length'] = 'Length from due date';
$string['form_rule_extension_length_less'] = 'Length from due date<br />(less than x days)';
$string['form_rule_greater_or_equal'] = 'greater or equal to';
$string['form_rule_header_edit'] = 'Edit Rule';
$string['form_rule_header_extension_length_options'] = 'Extension Length Options';
$string['form_rule_header_general_options'] = 'General Options';
$string['form_rule_label_days_long'] = 'days long';
$string['form_rule_label_weekdays_old'] = 'weekdays old';
$string['form_rule_label_elapsed_length'] = 'And the request is';
$string['form_rule_label_elapsed_length_help'] = 'Elapsed length is the number of weekdays since the request was initially made. Useful for sending follow up notifications.';
$string['form_rule_label_name'] = 'Rule name';
$string['form_rule_label_parent_allways'] = 'Activate this rule with these conditions';
$string['form_rule_label_parent_end'] = 'has triggered';
$string['form_rule_label_parent'] = 'Only activate if';
$string['form_rule_label_parent_help'] = 'It is possible to nest rules in a parent/child relationship. This rule will only try to activate if the parent has triggered previously.';
$string['form_rule_label_priority'] = 'Priority';
$string['form_rule_label_priority_help'] = 'This is a value from 1-10 and determines which rule will trigger first. The lower the number, the higher the priority.';
$string['form_rule_label_request_length'] = 'And the requested length is';
$string['form_rule_label_request_length_help'] = 'When a request is made, the user must specify a length for the request. This will check against the length of the request.';
$string['form_rule_label_set_roles'] = 'Then set all roles equal to';
$string['form_rule_label_set_roles_help'] = 'There are three options possible. If these rules are in a nested parent/child relationship then as the next rule is activated, users that are set to Approve will be downgraded to Subscribe.<br />
<br />
Approve: Can approve / deny the request status.<br />
Subscribe: View and comment on the request only.<br />
Force approve: Maintain the ability to approve / deny the status.';
$string['form_rule_label_template'] = 'And notify that role with';
$string['form_rule_label_template_help'] = 'An email notification be sent to all roles specified with this content. If this is blank, an email will not be sent. These are the possible template items <br />
{{course}} => Course full name<br />
{{module}} => Course module name<br />
{{student}} => Requesting users full name<br />
{{student_first}} => Requesting users first name<br />
{{student_middle}} => Requesting users middle name<br />
{{student_last}} => Requesting users last name<br />
{{student_alternate}} => Requesting users alternate name<br />
{{student_idnumber}} => Requesting users idnumber (Respects $CFG->showuseridentity)<br />
{{student_username}} => Requesting users username (Respects $CFG->showuseridentity)<br />
{{duedate}} => Due date of the course module<br />
{{extensiondate}} => The date requested for length of the extension<br />
{{requeststatusurl}} => URL for the request status page<br />
{{extensionlength}} => The request length in the number of days/hours<br />
{{rulename}} => The name of the rule<br />
{{rolename}} => The name of the role that this rule is for.<br />
{{eventname}} => The name of the event<br />
{{eventdescription}} => The description of the event<br />
{{attachments}} => The list of attachments<br />
{{fullhistory}} => The full comment stream for the request<br />
{{course_role_shortname}} => A list of users that have the shortname role in course context<br />';
$string['form_rule_label_template_request'] = 'Also notify the requesting user with';
$string['form_rule_label_template_request_help'] = 'An email notification be sent to the user that made this request. If this is blank, an email will not be sent. These are the possible template items <br />
{{course}} => Course full name<br />
{{module}} => Course module name<br />
{{student}} => Requesting users full name<br />
{{student_first}} => Requesting users first name<br />
{{student_middle}} => Requesting users middle name<br />
{{student_last}} => Requesting users last name<br />
{{student_alternate}} => Requesting users alternate name<br />
{{student_idnumber}} => Requesting users idnumber (Respects $CFG->showuseridentity)<br />
{{student_username}} => Requesting users username (Respects $CFG->showuseridentity)<br />
{{duedate}} => Due date of the course module<br />
{{extensiondate}} => The date requested for length of the extension<br />
{{requeststatusurl}} => URL for the request status page<br />
{{extensionlength}} => The request length in the number of days/hours<br />
{{rulename}} => The name of the rule<br />
{{rolename}} => The name of the role that this rule is for.<br />
{{eventname}} => The name of the event<br />
{{eventdescription}} => The description of the event<br />
{{attachments}} => The list of attachments<br />
{{fullhistory}} => The full comment stream for the request<br />
{{course_role_shortname}} => A list of users that have the shortname role in course context<br />';
$string['form_rule_label_this_request'] = 'this request';
$string['form_rule_label_to'] = 'to';
$string['form_rule_less_than'] = 'less than';
$string['form_rule_parent'] = 'Parent Rule';
$string['form_rule_roles'] = 'Actionable';
$string['form_rule_select_approve'] = 'Approve';
$string['form_rule_select_forceapprove'] = 'Force Approve';
$string['form_rule_select_subscribe'] = 'Subscribe';
$string['form_rule_template'] = 'template';
$string['form_rule_time_elapsed'] = 'Time elapsed from request date';
$string['form_rule_validate_elapsed'] = 'You must enter a number that is less than the request length';
$string['form_rule_validate_greater_equal_to_zero'] = 'You must enter a number equal or greater than zero here';
$string['form_rule_validate_greater_than_zero'] = 'You must enter a number greater than zero here';
$string['invalidaccess'] = 'Permission denied. Invalid access level.';
$string['invalidstate'] = 'Invalid State';
$string['messageprovider:status'] = 'Activity extension notifications';
$string['modifyextensionlength'] = 'Modify extension length';
$string['modulecontext'] = 'Allow module context requests';
$string['modulecontexthelp'] = 'Allow individual requests to be made in the module context. If this is disabled, no requests can be made individually.';
$string['na'] = 'N/A';
$string['nav_course_request'] = 'extension request';
$string['nav_course_request_plural'] = 'extension requests';
$string['nav_index'] = 'Extension Status';
$string['nav_request'] = 'Request Extension';
$string['nav_status'] = '{$a} Extension';
$string['notification_footer'] = '{$a->content}<hr /><p>Notification update for extension request #{$a->id}: {$a->fullname}.
<a href="{$a->statusurl}">Click here view the full status page for this request.</a></p>';
$string['page_h2_summary'] = 'Extension status list';
$string['page_heading_additional_request'] = 'Request an additional extension';
$string['page_heading_index'] = 'Extension status';
$string['page_heading_manage_delete'] = 'Remove rule';
$string['page_heading_manage'] = 'Extension trigger list';
$string['page_heading_summary'] = 'Extension summary list';
$string['page_index_all'] = 'All';
$string['page_index_categories'] = 'Categories';
$string['page_index_courses'] = 'Unit Code';
$string['page_index_faculties'] = 'Unit Category';
$string['page_index_mycourses'] = 'My Units';
$string['page_index_toomanycourses'] = 'Too many courses, please search or filter by category.';
$string['page_modify_comment'] = '
The extension length has been modified.

{$a->course}, {$a->event}

Requested extension date: {$a->original}
Modified extension date: {$a->new}

Time difference: {$a->diff}
Extension length: {$a->length}';
$string['page_request_notriggersdefined'] = 'No extension triggers have been defined.';
$string['page_request_outofrange'] = 'Please reduce the look ahead range.';
$string['page_request_requestnewlink'] = 'Click here to make a new extension request for {$a}';
$string['page_request_requeststatuslink'] = 'Click here to view the extension status {$a}';
$string['page_request_searchbackward'] = 'Look behind for possible requests in the past';
$string['page_request_searchforward'] = 'Look ahead for possible requests in the future';
$string['permissiondenied'] = 'Permisison denied.';
$string['pluginname'] = 'Activity extensions';
$string['preference_export_csv'] = 'Export CSV';
$string['preference_export_csv_help'] = 'Shows export csv button on extension status page. Will only display if you have extensions that can be exported.';
$string['preference_mail_digest'] = 'Mail digest';
$string['preference_mail_digest_help'] = 'If enabled, instead of sending e-mail notifications immediately they will be grouped into one single message and sent daily.';
$string['preferences'] = 'Preferences';
$string['preferences_title'] = 'Activity extensions preferences';
$string['preferences_saved'] = 'Preferences saved.';

$string['privacy:metadata:local_extension'] = 'local_extension plugin';
$string['privacy:metadata:local_extension_cm:userid'] = 'User id';
$string['privacy:metadata:local_extension_cm:request'] = 'Request id';
$string['privacy:metadata:local_extension_cm:course'] = 'Course';
$string['privacy:metadata:local_extension_cm:name'] = 'Name';
$string['privacy:metadata:local_extension_cm:cmid'] = 'cmid';
$string['privacy:metadata:local_extension_cm:state'] = 'State';
$string['privacy:metadata:local_extension_cm:data'] = 'Data';
$string['privacy:metadata:local_extension_cm:length'] = 'Length';
$string['privacy:metadata:local_extension_cm'] = 'Extension cm table';
$string['privacy:metadata:local_extension_comment:userid'] = 'User id';
$string['privacy:metadata:local_extension_comment:request'] = 'Request id';
$string['privacy:metadata:local_extension_comment:timestamp'] = 'Time stamp';
$string['privacy:metadata:local_extension_comment:message'] = 'Message';
$string['privacy:metadata:local_extension_comment:validationcheck'] = 'Validation check';
$string['privacy:metadata:local_extension_comment'] = 'Extension comment table';
$string['privacy:metadata:local_extension_hist_state:userid'] = 'User id';
$string['privacy:metadata:local_extension_hist_state:localcmid'] = 'Local cmid';
$string['privacy:metadata:local_extension_hist_state:requestid'] = 'Request id';
$string['privacy:metadata:local_extension_hist_state:timestamp'] = 'Time stamp';
$string['privacy:metadata:local_extension_hist_state:state'] = 'State';
$string['privacy:metadata:local_extension_hist_state:extlength'] = 'Extlength';
$string['privacy:metadata:local_extension_hist_state'] = 'Extension historic state table';
$string['privacy:metadata:local_extension_hist_trig:userid'] = 'User id';
$string['privacy:metadata:local_extension_hist_trig:trig'] = 'Trig';
$string['privacy:metadata:local_extension_hist_trig:localcmid'] = 'Local cmid';
$string['privacy:metadata:local_extension_hist_trig:request'] = 'Request id';
$string['privacy:metadata:local_extension_hist_trig:timestamp'] = 'Time stamp';
$string['privacy:metadata:local_extension_hist_trig:state'] = 'State';
$string['privacy:metadata:local_extension_hist_trig'] = 'Extension historic trig table';
$string['privacy:metadata:local_extension_hist_file:userid'] = 'User id';
$string['privacy:metadata:local_extension_hist_file:requestid'] = 'Request id';
$string['privacy:metadata:local_extension_hist_file:timestamp'] = 'Time stamp';
$string['privacy:metadata:local_extension_hist_file:filehash'] = 'File hash';
$string['privacy:metadata:local_extension_hist_file'] = 'Extension historic file table';
$string['privacy:metadata:local_extension_subscription:userid'] = 'User id';
$string['privacy:metadata:local_extension_subscription:localcmid'] = 'Local cmid';
$string['privacy:metadata:local_extension_subscription:trig'] = '';
$string['privacy:metadata:local_extension_subscription:requestid'] = 'Request id';
$string['privacy:metadata:local_extension_subscription:access'] = 'Access';
$string['privacy:metadata:local_extension_subscription:lastmod'] = 'Last modify';
$string['privacy:metadata:local_extension_subscription'] = 'Extension suscription table';
$string['privacy:metadata:local_extension_request:userid'] = 'User id';
$string['privacy:metadata:local_extension_request:searchstart'] = 'Search start';
$string['privacy:metadata:local_extension_request:searchend'] = 'Search end';
$string['privacy:metadata:local_extension_request:timestamp'] = 'Time stamp';
$string['privacy:metadata:local_extension_request:lastmod'] = 'Last modify';
$string['privacy:metadata:local_extension_request:lastmodid'] = 'Last modify id';
$string['privacy:metadata:local_extension_request:messageid'] = 'Message id';
$string['privacy:metadata:local_extension_request'] = 'Extension request table';

$string['renderer_search_text'] = 'Search';
$string['requestextension_status'] = 'Extension Status';
$string['request_page_heading'] = 'Extension Request';
$string['request_state_history_log'] = '{$a->status} extension for {$a->course}, {$a->event}';
$string['requireattachment'] = 'Require attachment with initial request';
$string['requireattachmenthelp'] = 'When making a new request, enabling this setting will enforce that a user attaches supporting documentation.';
$string['rolehelp'] = '';
$string['rolelist'] = 'Roles to notify';
$string['rules_page_heading'] = 'Rules management';
$string['ruleignoredatatype'] = 'Rules ignore datatype';
$string['ruleignoredatatypehelp'] = 'Ignore the datatype restriction and run every rule against each request.';
$string['searchbackhelp'] = 'How many days to search back from today when requesting an extension.';
$string['searchback'] = 'Search backward';
$string['searchforwardhelp'] = 'How many days to search forward from today when requesting an extension.';
$string['searchforward'] = 'Search forward';
$string['searchforwardmaxweekshelp'] = 'The number of weeks allowed to search forward when making a request.';
$string['searchforwardmaxweeks'] = 'Maximum weeks searching forward';
$string['searchbackwardmaxweekshelp'] = 'The number of weeks allowed to search backward when making a request.';
$string['searchbackwardmaxweeks'] = 'Maximum weeks searching backward';
$string['state_approved'] = 'Granted';
$string['state_button_additional_request'] = 'Request Additional Extension';
$string['state_button_approve'] = 'Approve request';
$string['state_button_cancel'] = 'Cancel request';
$string['state_button_deny'] = 'Deny request';
$string['state_button_modfiy_length'] = 'Modify request length';
$string['state_button_reopen'] = 'Reopen request';
$string['state'] = 'State';
$string['state_cancel'] = 'Cancelled';
$string['state_denied'] = 'Denied';
$string['state_internal'] = 'Internal';
$string['state_new'] = 'New';
$string['state_reopened'] = 'Reopened';
$string['state_result_approved'] = 'Approved';
$string['state_result_cancelled'] = 'Cancelled';
$string['state_result_denied'] = 'Denied';
$string['state_result_pending'] = 'Pending';
$string['status_file_attachment'] = 'File attached {$a}';
$string['status_status_line'] = '{$a->status} extension request for {$a->length} until {$a->date}.';
$string['status_status_internal_with_length'] = 'extension overrides the request, the extension is for {$a->length} until {$a->date}.';
$string['status_status_summary_extra_details'] = 'Updated by {$a->user} on {$a->date}.';
$string['status_status_summary_with_length'] = 'extension request for {$a->length} until {$a->date}.';
$string['status_status_summary_without_length'] = 'extension request on {$a->date}.';
$string['strftime_datetime'] = '%d/%m/%Y %H:%M';
$string['submit_additional_request'] = 'Submit additional request';
$string['submit_additional_review'] = 'Review additional request';
$string['submit_comment'] = 'Submit comment';
$string['submit_request'] = 'Submit request';
$string['submit_state_return_status'] = 'Confirm state change and return to comment stream';
$string['submit_state_return_list'] = 'Confirm state change and return to the list of open requests';
$string['submit_state_back'] = 'Cancel state change and go back';
$string['subplugintype_extension'] = 'Extension adapter';
$string['subplugintype_extension_plural'] = 'Extension adapters';
$string['supportusernamedefault'] = 'Extension Request';
$string['supportusernamehelp'] = 'This is the name that extension request emails will be from. If this is empty it will use the name of the user who updated the request.';
$string['supportusername'] = 'Notification support name';
$string['systemcontext'] = 'Allow system context requests';
$string['systemcontexthelp'] = 'Allow multiple activity modules to be selected when making a request in the system context. This spans across the site and allows making a single request that has activities from multiple courses.';
$string['table_header_course'] = 'Course';
$string['table_header_datedue'] = 'Due date';
$string['table_header_dateextension'] = 'Extended until';
$string['table_header_index_activity'] = 'Activity';
$string['table_header_index_course'] = 'Course';
$string['table_header_index_lastmod'] = 'Last updated';
$string['table_header_index_newduedate'] = 'New due date';
$string['table_header_index_requestdate'] = 'Creation date';
$string['table_header_index_requestlength'] = 'Request length';
$string['table_header_index_requestid'] = 'Request';
$string['table_header_index_status'] = 'Status';
$string['table_header_index_user'] = 'User';
$string['table_header_items'] = 'Number of request items';
$string['table_header_lastmod'] = 'Last updated';
$string['table_header_module'] = 'Module';
$string['table_header_requestdate'] = 'Date of request';
$string['table_header_request'] = 'Request';
$string['table_header_rule_actionable'] = 'Actionable';
$string['table_header_rule_action'] = 'Action';
$string['table_header_rule_continue'] = 'Continue';
$string['table_header_rule_data'] = 'Rules';
$string['table_header_rule_datatype'] = 'Type';
$string['table_header_rule_name'] = 'Name';
$string['table_header_rule_parent'] = 'Parent';
$string['table_header_rule_priority'] = 'Priority';
$string['table_header_statushead'] = 'Status';
$string['table_header_statusrow'] = '<a href="{$a}">Click to view request status</a>';
$string['table_header_username'] = 'Requesting User';
$string['task_process'] = 'Process Triggers / Rules';
$string['task_email_digest'] = 'E-mail Digest Processing';
$string['template_notify_content'] = '{{course}} => Course full name<br />
{{module}} => Course module name<br />
{{student}} => Requesting users full name<br />
{{student_first}} => Requesting users first name<br />
{{student_middle}} => Requesting users middle name<br />
{{student_last}} => Requesting users last name<br />
{{student_alternate}} => Requesting users alternate name<br />
{{student_idnumber}} => Requesting users idnumber (Respects $CFG->showuseridentity)<br />
{{student_username}} => Requesting users username (Respects $CFG->showuseridentity)<br />
{{duedate}} => Due date of the course module<br />
{{extensiondate}} => The date requested for length of the extension<br />
{{requeststatusurl}} => URL for the request status page<br />
{{extensionlength}} => The request length in the number of days/hours<br />
{{rulename}} => The name of the rule<br />
{{rolename}} => The name of the role that this rule is for.<br />
{{eventname}} => The name of the event<br />
{{eventdescription}} => The description of the event<br />
{{attachments}} => The list of attachments<br />
{{fullhistory}} => The full comment stream for the request<br />
{{course_role_shortname}} => A list of users that have the shortname role in course context<br />';
$string['template_notify_subject'] = 'DEBUG: {{rulename}} {{rolename}}';
$string['template_user_content'] = '{{course}} => Course full name<br />
{{module}} => Course module name<br />
{{student}} => Requesting users full name<br />
{{student_first}} => Requesting users first name<br />
{{student_middle}} => Requesting users middle name<br />
{{student_last}} => Requesting users last name<br />
{{student_alternate}} => Requesting users alternate name<br />
{{student_idnumber}} => Requesting users idnumber (Respects $CFG->showuseridentity)<br />
{{student_username}} => Requesting users username (Respects $CFG->showuseridentity)<br />
{{duedate}} => Due date of the course module<br />
{{extensiondate}} => The date requested for length of the extension<br />
{{requeststatusurl}} => URL for the request status page<br />
{{extensionlength}} => The request length in the number of days/hours<br />
{{rulename}} => The name of the rule<br />
{{rolename}} => The name of the role that this rule is for.<br />
{{eventname}} => The name of the event<br />
{{eventdescription}} => The description of the event<br />
{{attachments}} => The list of attachments<br />
{{fullhistory}} => The full comment stream for the request<br />
{{course_role_shortname}} => A list of users that have the shortname role in course context<br />';
$string['template_user_subject'] = 'DEBUG {{student}} {{rulename}} {{rolename}}';
$string['week'] = '{$a} week';
$string['week_plural'] = '{$a} weeks';
