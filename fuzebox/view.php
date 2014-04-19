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
 * URL module main user interface
 *
 * @package    mod
 * @subpackage url
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/fuzebox/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

global $PAGE,$DB,$USER,$OUTPUT;
$id       = required_param("id", PARAM_INT);        // Course module ID
$action      = optional_param("action", "", PARAM_TEXT);

$cm = get_coursemodule_from_id('fuzebox', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/fuzebox:view', $context);
$fuzemeet = $DB->get_record("fuzebox", array("id"=>$cm->instance));
add_to_log($course->id, 'fuzebox', 'view', 'view.php?id='.$cm->id, $cm->id, $cm->id);


$PAGE->set_url('/mod/fuzebox/view.php', array('id' => $cm->id));


if($action=="getinfo"){
    if($fuzemeet->user==$USER->id){
        $result = fuze_getmeetinfo($fuzemeet->meetingid);
        @header("application/json");
        echo json_encode($result);
    }else{
        @header("application/json");
        return false;
    }
    die();
}else if($action=="launch"){
    fuze_launchmeeting($fuzemeet);
}else if($action=="view"){
    
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_url('/mod/fuzebox/index.php', array('id' => $course->id));
    
    if(!($fuzemeet->user==$USER->id)){
    //this is a hack, fuzebox allows only the creator of meeting to view meeting info
        $wassignedin = fuze_isloggedin();
        fuze_logout();
        $user = $DB->get_record("user",array("id"=>$fuzemeet->user));
        fuze_signin($fuzemeet->course, $user);
        $info = fuze_getmeetinfo($fuzemeet->id);
        fuze_logout();
        if($wassignedin) fuze_signin();
    }else{
        try{
            $info = fuze_getmeetinfo($fuzemeet->meetingid);
        }catch(Exception $e){
            
        }
    }
    if(isset($info->meeting->details->viewurl)){
        if($info->meeting->details->viewable)
            redirect($info->meeting->details->viewurl);
        else{
            echo $OUTPUT->header();
            notice ("Meeting is not viewable");
            echo $OUTPUT->footer();
        }
    }
        
    
}else{
    $renderer = $PAGE->get_renderer('mod_fuzebox');
    $renderer->viewmeet($cm,$fuzemeet);
}