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
 * Strings for component 'fuzebox', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    mod
 * @subpackage fuzebox
 * @copyright  2013 Shani Mahadeva  {@link http://satyadev.in}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['recordnotfound'] = 'Record not found';
$string['cantmodifyothersmeeting'] = "You can't modify this meeting";
$string["fuze_signinerror"] = 'Error in fuzebox sign-in - code {$a}';
$string["fuze_nocourseincreate"] = 'Automatic Fuzebox account creation failed because - No course selected';
$string["fuze_signuperror"] = 'Error in signup with Fuzebox- code {$a}';
$string["fuze_signedupbutnotstored"] = 'User signed-up with fuzebox but not stored in module table, please note these details and check module installation - {$a}';
$string['fuze_networkerror'] = 'Error in network while connecting to Fuzebox site';
$string['fuze_generalerror'] = 'Error with fuzebox client while connecting to Fuzebox site: {$a}';
$string['fuze_bademailformat'] = 'Error due to bad email format in {$a}';
$string["fuze_usertablenotexist"] = "Table fuzebox_user does not exist";
$string["fuze_scheduleerror"] = 'Error in scheduling the meeting at fuzebox : code {$a}';
$string["fuze_updateerror"] = 'Error in updating the meeting at fuzebox : code {$a}';
$string["fuze_deleteerror"] = 'Error in deleting the meeting at fuzebox : code {$a}';
$string["fuze_meetingget"] = 'Error in getting meeting information - code {$a}';
$string["fuze_nomeetingid"] = 'Error - No meeting id provided';
$string['configsecretphrase'] = 'This secret phrase is used to produce encrypted code value that can be sent to some servers as a parameter.  The encrypted code is produced by an md5 value of the current user IP address concatenated with your secret phrase. ie code = md5(IP.secretphrase). Please note that this is not reliable because IP address may change and is often shared by different computers.';
$string['contentheader'] = 'Content';
$string['createfuzebox'] = 'Create a fuzebox meeting';
$string['modulename'] = 'Fuzebox Meet';
$string['modulename_link'] = 'mod/fuzebox/view';
$string['modulenameplural'] = 'Fuzebox';
$string['page-mod-fuzebox-x'] = 'Any fuzebox module page';
$string['pluginname'] = 'Fuzebox';
$string['fuze_sesstimeout'] = "Fuzebox session timout in seconds";
$string['fuze_sesstimeoutnote']="This is the session timeout duration for sign-in at Fuzebox site, change it if this value is changed by Fuzebox company";
$string['printintro'] = 'Display fuzebox description';
$string['printintroexplain'] = 'Display fuzebox description below content? Some display types may not display description even if enabled.';
$string['fuzebox:addinstance'] = 'Add a new fuzebox resource';
$string['fuzebox:view'] = 'View fuzebox';
$string['subject'] = 'Subject';
$string['invitationtext'] = 'Invitation Message';
$string['attendees'] = 'Meeting Attendees';
$string['includecoursemembers'] = 'Include course members automatically';
$string['time'] = 'Time';
$string['starttime'] = 'Meeting start time';
$string['endtime'] = 'Meeting end time';
$string['timezone'] = 'Timezone';
$string['sendemail'] = 'Send email to everyone or only attendees';
$string['autorecording'] = 'Auto Recording';
$string['webinar'] = 'Webinar mode';
$string['includeinternationaldial'] = 'Include International Dial Numbers';
$string['includetollfree'] = 'Include toll free numbers';
$string['badattendeeformat'] = 'Please check the format of invitees list - Firstname Lastname <email>,...';
$string['pluginadministration'] = 'PLugin Administration';
$string['component'] = 'Fuzebox Meeting';
$string['fuze_partner_details'] = 'Fuzebox Partner Details';
$string['fuze_partner_url'] = 'Fuzebox Partner API Url';
$string["fuze_packagename"] = "Fuzebox partner package name";
$string['fuze_pk'] = 'Partner Key';
$string['fuze_ek'] = 'Encryption Key';