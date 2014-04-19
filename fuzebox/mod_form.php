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
 * fuzebox configuration form
 *
 * @package    mod
 * @subpackage fuzebox
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//TODO Backup code,cron job to delete inactive account, admin page to delete accounts
//Admin page with all functionalities, ability to change user's passwords,view info etc
defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/fuzebox/locallib.php');

class mod_fuzebox_mod_form extends moodleform_mod {
    function definition() {
        global $PAGE,$CFG,$DB;
        if(!fuze_isloggedin()){
            $fuser = fuze_signin($this->current->course);
            if(!$fuser)
                print_error("fuze_signinerror", "fuzebox",$CFG->wwwroot."/course/view.php?id=".$this->current->course,"unknown");
        }
        $PAGE->requires->js('/mod/fuzebox/js/fuze.js');
        
        $mform = $this->_form;
        
        $context = get_context_instance(CONTEXT_COURSE, $this->current->course);
        $roles = array(1,2,3,4,5);
        $users = array();
        foreach($roles as $r){
            $users = $users + get_role_users(array($r),$context,false,"u.id",'ra.roleid');
        }
        $attendees = "";
        foreach($users as $u){
            $user = $DB->get_record('user',array("id"=>$u->id));
            $attendee = array($user->firstname,$user->lastname,"<".trim($user->email).">");
            $attendees .= implode(" ",$attendee).",\n";
        }
        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'subject', get_string('subject', "fuzebox"), array('size'=>'48'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addElement('textarea','intro',get_string("invitationtext", "fuzebox"),array("rows"=>2,"cols"=>46));
        $mform->addElement('textarea', 'attendees', get_string('attendees', "fuzebox"),array("rows"=>10,"cols"=>46));
        $mform->setDefault("attendees", $attendees);
        $mform->addRule('subject', get_string('requiredelement','form'), 'required', '', 'client', false, false);
        $mform->addRule('attendees', get_string('badattendeeformat','fuzebox'), 'callback',"M.mod_fuzebox.attendeesvalidate", 'client', false, false);
        //-------------------------------------------------------
        $mform->addElement('header', 'time', get_string('time', 'form'));
        $opts = array(
            'startyear'=> ((int)date("Y")) - 1,
            'stopyear'=> ((int)date("Y"))+1,
            'step' => 15,
            'optional' => false
        );
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', "fuzebox"),$opts);
        $mform->addElement('date_time_selector', 'endtime', get_string('endtime', "fuzebox"),$opts);
//        $mform->addElement('hidden','timezone','');
        //-------------------------------------------------------
        $mform->addElement('header', 'optional', get_string('optional', 'form'));
        $mform->addElement('checkbox', 'sendemail', get_string('sendemail', "fuzebox"),"",array("checked"=>true));
        $mform->addElement('checkbox', 'autorecording', get_string('autorecording', "fuzebox"));
        $mform->addElement('checkbox', 'webinar', get_string('webinar', "fuzebox"));
        $mform->addElement('checkbox', 'includetollfree', get_string('includetollfree', "fuzebox"),"",array("checked"=>true));
        $mform->addElement('checkbox', 'includeinternationaldial', get_string('includeinternationaldial', "fuzebox"), "",array("checked"=>true));
        //-------------------------------------------------------
        
        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons(true,"Schedule and View","Schedule");
    }

    function data_preprocessing(&$default_values) {
        
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
