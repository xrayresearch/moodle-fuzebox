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
 * Mandatory public API of fuzebox module
 *
 * @package    mod
 * @subpackage fuzebox
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot.'/mod/fuzebox/locallib.php');
require_once($CFG->dirroot.'/mod/fuzebox/FuzeApi/Fuze/Client.php');
/**
 * List of features supported in fuzebox module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function fuzebox_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_ASSIGNMENT;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function fuzebox_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function fuzebox_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function fuzebox_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions
 * @return array
 */
function fuzebox_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add fuzebox meeting instance.
 * @param object $data
 * @param object $mform
 * @return int new fuzebox meeting instance id
 */
function fuzebox_add_instance($data, $mform) {
    global $DB,$USER,$CFG;
    $client = fuze_getclient($data->course);
    
    $dateS = new DateTime();
    $dateS->setTimestamp($data->starttime);
    $dateE = new DateTime();
    $dateE->setTimestamp($data->endtime);
    
    
    $datafuze = array(
        "subject"=>$data->subject,
        "invitees"=>fuze_emailsclean($data->attendees),
        "sendemail"=>"All",
        "includetollfree"=>isset($data->includetollfree)&&$data->includetollfree,
        "includeinternationaldial" => isset($data->includeinternationaldial)&&$data->includeinternationaldial,
        "autorecording" => isset($data->autorecording)&&$data->autorecording,
        "webinar" => isset($data->webinar)&&$data->webinar,
        "invitationtext" => $data->intro,
        "starttime" => $dateS->format(DateTime::RFC2822),
        "endtime" => $dateE->format(DateTime::RFC2822)
    );
    try{
        $response = $client->scheduleMeeting($datafuze);
    }  catch (Exception $e){
        print_error("fuze_generalerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$data->course,$e->getMessage());
   }
    if($response->code>=200&$response->code<300){
        fuze_setsesstime();
        $fuzeinstance = array(
            "user"=>$USER->id,
            "course"=>$data->course,
            "name"=>"Fuzemeet: ".$datafuze['subject'],
            "intro"=>$datafuze['invitationtext'],
            "introformat"=>FORMAT_PLAIN,
            "meetingid"=>$response->meetingid,
            "starttime"=>$data->starttime,
            "attendurl"=>$response->meetingurl,
            "launchurl"=>$response->meeting->launchmeetingurl,
            "timemodified"=>time()
        );
        $datafuze['id'] = $DB->insert_record('fuzebox', $fuzeinstance);
        return $datafuze['id'];
    }else{
        print_error("fuze_scheduleerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$data->course,$response->code.", ".$response->message);
    }
}

/**
 * Update fuzebox meeting instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function fuzebox_update_instance($data, $mform) {
    global $DB,$USER;
    $fuzebox = $DB->get_record('fuzebox', array('id'=>$data->instance));
    if(!$fuzebox){
        print_error("recordnotfound","fuzebox");
        return false;
    }
        
    if($fuzebox->user!=$USER->id){
        print_error("cantmodifyothersmeeting","fuzebox");
        return false;
    }
    $client = fuze_getclient($data->course);
    
    $dateS = new DateTime();
    $dateS->setTimestamp($data->starttime);
    $dateE = new DateTime();
    $dateE->setTimestamp($data->endtime);
    
    $datafuze = array(
        "meetingid"=> $fuzebox->meetingid,
        "subject" => $data->subject,
        "sendemail" => "All",
        "invitees" => fuze_emailsclean($data->attendees),
        "replace_invitees"=> false,
        "includetollfree" => isset($data->includetollfree)&&$data->includetollfree,
        "includeinternationaldial" => isset($data->includeinternationaldial)&&$data->includeinternationaldial,
        "autorecording" => isset($data->autorecording)&&$data->autorecording,
        "webinar" => isset($data->webinar)&&$data->webinar,
        "invitationtext" => $data->intro,
        "starttime" => $dateS->format(DateTime::RFC2822),
        "endtime" => $dateE->format(DateTime::RFC2822)
    );
   
   try{
       $response = $client->updateMeeting($datafuze);
   }  catch (Exception $e){
       print_error("fuze_generalerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$data->course,$e->getMessage());
   }
    if($response->code>=200&$response->code<300){
        fuze_setsesstime();
        $fuzeinstance = array(
            "id"=>$data->instance,
            "name"=>"Fuzemeet: ".$datafuze["subject"],
            "intro"=>$datafuze["invitationtext"],
            "starttime"=>$data->starttime,
            "timemodified"=>time()
        );
        $datafuze["id"] = $DB->update_record('fuzebox', $fuzeinstance);
        return $datafuze["id"];
    }else{
        print_error("fuze_updateerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$data->course,$response->code);
    }
}

/**
 * Delete fuzebox meeting instance.
 * @param int $id
 * @return bool true
 */
function fuzebox_delete_instance($id) {
    global $DB;

    if (!$fuzebox = $DB->get_record('fuzebox', array('id'=>$id))) {
        return false;
    }
    $client = fuze_getclient($fuzebox->course);
    try{
        $result = $client->call("meeting/delete", array('meetingid'=>$fuzebox->meetingid));
    }  catch (Exception $e){
        print_error("fuze_generalerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$fuzebox->course,$e->getMessage());
    }
    if($result->code>=200&&$result->code<300){
        fuze_setsesstime();
        $DB->delete_records('fuzebox', array('id'=>$fuzebox->id));
        return true;
    }else{
        print_error("fuze_deleteerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$fuzebox->course,$result->code);
        return false;
    }
    // note: all context files are deleted automatically
}
/**
 * 
 * @param type $data Event data from user logout moodle event
 */
function fuze_user_logout($data){
    fuze_logout();        
}
/**
 * 
 * @param type $data Event data from user deleted moodle event
 */
function fuze_user_delete($data){
    if(!isset($data->id))
        return false;
    fuze_delete($data);
}