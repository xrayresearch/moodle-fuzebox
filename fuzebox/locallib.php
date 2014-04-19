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
 * Private fuzebox module utility functions
 *
 * @package    mod
 * @subpackage fuzebox
 * @copyright  2013 Shani Mahadeva  {@link http://satyadev.in}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$config = get_config('fuzebox');
$sess = isset($config->fuze_sesstimeout)?($config->fuze_sesstimeout)*1000:900000;
defined('MOODLE_INTERNAL') || die;
define("FUZE_SESS_TIMEOUT", $sess);
require_once("$CFG->dirroot/mod/fuzebox/FuzeApi/Fuze/MoodleFuzeClient.php");

/**
 * Checks if given user has addinstance capability and if he is registered
 * to fuzebox. If not registered, creates account. User will not be signed up if he can't
 * do mod/fuzebox:addinstance. Change here to control who is eligible for a fuzebox account.
 * @global type $USER
 * @param type $users user for which to create fuzebox account
 */
function fuze_createAccount($courseid,$user){
    global $USER,$DB,$CFG;
    if(is_null($user)||empty($user)){
        $user = $USER;
    }
    $returnurl = $CFG->wwwroot."/course/view.php?id=".$courseid;
    $fuzeuser = $DB->get_record("fuzebox_user",array("user"=>$user->id));
    if(!empty($fuzeuser)&&!empty($fuzeuser->id))
        return $fuzeuser;
    if(!preg_match("#.*@.*\.#", $user->email)){
        print_error("fuze_bademailformat","fuzebox",$returnurl," user's email");
    }
            
    
    $cxt = get_context_instance(CONTEXT_COURSE, $courseid);
    require_capability("mod/fuzebox:addinstance", $cxt);
    $client = new MoodleFuzeClient();
    //Add more packages here if applicable
    $packagename = get_config('fuzebox','packagename');
    $params = array(
        'firstname'=>$user->firstname,
        'lastname'=>$user->lastname,
        'email'=>$user->email,
        'password'=>  fuze_rand_string(8),
        'packages'=>array($packagename)
    );
    //Check if table fuzebox_user exists before signup
    $dbman = $DB->get_manager();
    if($dbman->table_exists("fuzebox_user")){
    //Signup
        try{
            $response = $client->signup($params);
        }  catch (Exception $e){
            print_error("fuze_generalerror","fuzebox",$returnurl,$e->getMessage());
        }
        if(!$response)
            print_error ("fuze_networkerror","fuzebox",$returnurl);
    }
    else{
        print_error("fuze_usertablenotexist","fuzebox",$returnurl);
        return false;
    }
    $newfuzeuser = array(
        'user'=>$user->id,
        'password'=>$params['password'],
        'signedon'=>time(),
        'package'=>1
    );
    //If signed up successfully -
    if($response->code>=200&&$response->code<300){
        
        $fuserid = $DB->insert_record("fuzebox_user",$newfuzeuser);
        
        if($fuserid){
            $result = $client->signin(array("email"=>$user->email,"password"=>$newfuzeuser["password"]));
            if($result&&isset($result->token)){
                fuze_settoken($client->getUserSession());
            }else{
                print_error("fuze_networkerror","fuzebox",$returnurl);
                return false;
            }
            return $fuserid;
        }
        else{
            print_error("fuze_signedupbutnotstored","fuzebox",$returnurl,"user: ".$user->email.", password: ".$fuzeuser["password"]);
            return false;
        }
    }
    else{
        if($response->code==451){
            //account exists at Fuzebox site but is deleted here, try to add and reset password
            $fuserid = $DB->insert_record("fuzebox_user",$newfuzeuser);
            return fuze_resetpass($client, $user, $returnurl, $newfuzeuser["password"]);
        }
        print_error("fuze_signuperror","fuzebox",$returnurl,$response->code.", ".$response->message);
        return false;
    }
}
/**
 * Sign-in current or given user if record exists in fuzebox_user
 * or create account if user has mod/fuze:addinstance capability for course $courseid.
 * Set session token if signin succeeds or try reset password once if it fails.
 * 
 * @global type $USER
 * @global type $DB
 * @global type $CFG
 * @param type $courseid
 * @param type $user
 * @return boolean
 */
function fuze_signin($courseid=0,$user=null){
    global $USER,$DB,$CFG;
    if(fuze_isloggedin())
        return true;
    if(is_null($user)||!$user->id){
        $user = $USER;
    }
    $fuzeuser = $DB->get_record("fuzebox_user",array('user'=>$user->id));
    $returnurl = $CFG->wwwroot;
    if(empty($fuzeuser)||!$fuzeuser->id){
        if(!$courseid)
            print_error ("fuze_nocourseincreate","fuzebox",$returnurl);
        return fuze_createAccount($courseid,$user);
    }else{
        $fclient = new MoodleFuzeClient();
        try{
            $result = $fclient->signin(array("email"=>$user->email,"password"=>$fuzeuser->password));
        
            if($result->code>=200&&$result->code<300&&isset($result->token)){
                fuze_settoken($fclient->getUserSession(),$fuzeuser);
                return true;
            }else{
                if($result->code==400){
                    //Password error, try to reset password and login once
                    return fuze_resetpass($fclient, $user, $returnurl);
                }else{
                    print_error("fuze_signinerror","fuzebox",$returnurl,$result->code);
                    return false;
                }
            }
        }  catch (Exception $e){
            print_error("fuze_generalerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$courseid,$e->getMessage());
        }
    }
}
/**
 * 
 * @global type $DB
 * @param type $meeting The meeting record from {fuzebox} table, not just the ID
 */
function fuze_launchmeeting($meeting){
    global $DB;
    if(empty($meeting))
        print_error("fuze_nomeetingid","fuzebox");
    $client = fuze_getclient($meeting->course);
    try{
        $reallaunchurl = $client->getSignedLaunchURL($meeting->meetingid, $meeting->launchurl);
        if(!empty($reallaunchurl))
            $DB->update_record("fuzebox",array("id"=>$meeting->id,"launched"=>1));
    }catch(Exception $e){
        print_error("fuze_generalerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$meeting->course,$e->getMessage());
    }
    redirect($reallaunchurl);
}

function fuze_getmeetinfo($meetingid){
    global $DB,$CFG;
    $fuzemeet = $DB->get_record("fuzebox",array("meetingid"=>$meetingid)) ;
    if(!empty($fuzemeet)){
        $client = fuze_getclient($fuzemeet->course);
        try{
            $response =  $client->call("meeting/get", array("meetingid"=>$meetingid));
        }  catch (Exception $e){
            print_error("fuze_generalerror","fuzebox",$CFG->wwwroot."/course/view.php?id=".$fuzemeet->course,$e->getMessage());
        }
        if(!$response)
            print_error ("fuze_networkerror","fuzebox");
        if($response->code>=200&&$response->code<300){
            fuze_setsesstime();
            return $response;
        }
        else{
            print_error("fuze_meetingget","fuzebox",$CFG->wwwroot."/course/view.php?id=".$fuzemeet->course,$response->code.", ".$response->message);
            return false;
        }
    }
}
function fuze_resetpass($fclient,$user,$returnurl,$pass){
    global $DB;
    $password = !empty($pass)?$pass:fuze_rand_string(8);
    $response = $fclient->resetPassword(array("email"=>$user->email,"password"=>  $password));
    if($response->code>=200&&$response->code<300){
        $fuzeuser = $DB->get_record("fuzebox_user",array('user'=>$user->id));
        $DB->update_record("fuzebox_user",array("id"=>$fuzeuser->id,"password"=>$password));
        $result = $fclient->signin(array("email"=>$user->email,"password"=>$password));
        if($result->code>=200&&$result->code<300&&isset($result->token)){
            fuze_settoken($fclient->getUserSession());
            return true;
        }else{
            print_error("fuze_signinerror","fuzebox",$returnurl,$response->code);
            return false;
        }
    }else{
        print_error("fuze_signinerror","fuzebox",$returnurl,$response->code." while trying to reset psasword.".$response->message);
        return false;
    }
}

function fuze_delete($user){
    global $CFG,$DB;
    fuze_logout();
    $row = $DB->get_record("fuzebox_user",array("user"=>$user->id));
    if(!empty($row));{
        fuze_signin(0, $user);
        $client = fuze_getclient(0);
        $DB->delete_records("fuzebox_user",array("id"=>$row->id));
        try{
            $client->cancelAccount();
        }catch(Exception $e){
            print_error("fuze_generalerror","fuzebox",$CFG->wwwroot,$e->getMessage());
        }
    }
}

function fuze_logout($user=null){
    global $USER;
    if(is_null($user))
        $user = $USER;
    if(fuze_isloggedin()){
        fuze_settoken(null);
    }
}
/**
 * Get signed in client with token set in client
 * @param type $courseid Can be 0 if no course selected
 * @return \MoodleFuzeClient|null
 */
function fuze_getclient($courseid){
    if(fuze_signin($courseid)){
        return new MoodleFuzeClient(fuze_gettoken());
    }else{
        return null;
    }
}
function fuze_setsesstime($time=0){
    $_SESSION["fuzesesstime"] = $time?$time:time();
}
function fuze_getsesstime(){
    return $_SESSION["fuzesesstime"];
}
function fuze_settoken($token=null,$fuser=null){
    global $USER,$DB;
    if(!is_null($token)){
        $_SESSION["fuzetoken"] = $token;
        $fuzeuser = !empty($fuser)?$fuser:$DB->get_record("fuzebox_user",array("user"=>$USER->id));
        if(!empty($fuzeuser)){
            $DB->update_record("fuzebox_user",array("id"=>$fuzeuser->id,"lastsignin"=>time()));
        }
        fuze_setsesstime();
    }else{
        unset($_SESSION["fuzetoken"]);
    }
}
function fuze_gettoken(){
    return isset($_SESSION["fuzetoken"])?$_SESSION["fuzetoken"]:false;
}
/**
 * If valid token was retrieved less than "FUZE_SESS_TIMEOUT" seconds ago,
 * then user is signed in.
 * @return bool
 */
function fuze_isloggedin(){
    return (isset($_SESSION["fuzetoken"])&&( time() - fuze_getsesstime() ) < FUZE_SESS_TIMEOUT);
}
/**
 * Generate password string
 * @param type $length of password string to generate
 * @return type password string of $length
 */
function fuze_rand_string( $length ) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars),0,$length);
}
/**
 * 
 * @param type $stringvalue list of invitees
 * @return type sanitized string for fuzebox list if invitees
 */
function fuze_emailsclean($stringvalue){
    $values = explode(">", $stringvalue);
    $valid = array();
    foreach($values as $k=>$v){
        $temp = trim(preg_replace("#(,|\n)#","", $v)).">";
        if(preg_match("#[A-z]+ [A-z]+ <[A-z0-9._%+-]+@[A-z0-9.-]+\.[A-z]{2,4}>#",$temp))
                $valid[$k] = $temp;
    }
    return implode(",", $valid);
}
