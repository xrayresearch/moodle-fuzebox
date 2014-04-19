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
 * Class for reading HTML templates with OB
 *
 * @filesource
 * @package motte
 * @subpackage view
 * @license GPLv2 http://opensource.org/licenses/gpl-license.php GNU Public license
 * @version 2.44
 * @author 	Pedro Gauna (pgauna@gmail.com) /
 * 			Braulio Rios (braulioriosf@gmail.com) /
 * 			Pablo Erartes (pabloeuy@gmail.com)
 */
class fuzeTemplate {

	/**
	 *
	 * @var string
	 * @access private
	 */
	private $_template;

	/**
	 *
	 * @var string
	 * @access private
	 */
	private $_templateDir;

	/**
	 *
	 * @var array
	 * @access private
	 */
	private $_var;

	public function __construct($template = '', $templateDir = 'templates') {
		$this->setTemplateDir($templateDir);
		$this->setTemplate($template);
		$this->clearVars();
	}

	public function setTemplate($name = '') {
		$this->_template = $name;
	}

	public function getTemplate() {
		return $this->_template;
	}

	public function setTemplateDir($dir = '') {
		$this->_templateDir = $dir;
	}

	public function getTemplateDir() {
		return $this->_templateDir;
	}

	public function clearVar($varName) {
		$this->_engine->clear_assign($varName);
	}

	public function clearVars() {
		$this->_var = array();
	}
        
	public function addVar($varName, $varValue) {
		$this->_var[$varName] = $varValue;
	}

	public function setVar($varName, $varValue) {
		$this->addVar($varName, $varValue);
	}
        
	public function setVars($vars) {
                foreach($vars as $k=>$v)
                    $this->setVar($k,$v);
	}

	public function appendVar($varName, $varValue) {
		$this->_var[$varName][] = $varValue;
	}

	public function getVar($varName) {
		return $this->_var[$varName];
	}

	public function getHtml() {
		$html = '';
		$file = $this->getTemplateDir() . '/' . $this->getTemplate();
		if (is_file($file)) {
			preg_match_all("(\\$[\w|\d]+)", file_get_contents($file), $vars);
			if (is_array($vars)) {
				foreach ($vars[0] as $key => $var) {
					$var = substr($var, 1);
					if (!array_key_exists($var, $this->_var)) {
						$this->setVar($var, '');
					}
				}
			}
			extract($this->_var);
			ob_start();
			include($this->getTemplateDir() . '/' . $this->getTemplate());
			$html = ob_get_clean();
		}
		return $html;
	}

	public function showHtml() {
		print $this->getHtml();
	}
}

/**
 * A custom renderer class that extends the plugin_renderer_base and
 * is used by the fuzebox module.
 *
 * @package fuzebox
 * @copyright 2013 Shani Mahadeva
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class mod_fuzebox_renderer extends plugin_renderer_base {
    /**
     * This method is used to generate HTML for a subscriber selection form that
     * uses two user_selector controls
     *
     * @param user_selector_base $existinguc
     * @param user_selector_base $potentialuc
     * @return string
     */
    public function viewmeet($cm,$meeting=0) {
        global $DB;
        $meet = is_number($meeting)?
                        ($DB::get_record("fuzebox",array("meetingid"=>$meeting))):
                        (is_object($meeting)?$meeting:null);
        $this->page->set_title($meet->name);
        $this->page->set_heading($meet->name);
        $output = $this->output->header();
        $this->output->heading("Fuzebox Meeting",2);
        
        $output .= $this->getmeetinghtml($meet, $cm);
        $output .= $this->output->footer();
        echo $output;
        return true;
    }
    
    public function viewallcoursemeets($course,$fuzemeets=null){
        global $DB,$CFG;
        if(!$course){
            notice(get_string('unspecifycourseid', 'error'), $CFG->wwwroot);
            exit;
        }
        $meets = !empty($fuzemeets)?$fuzemeets:($DB->get_records("fuzebox",array("course"=>$course)));
        $strmeets = get_string('component','fuzebox');
        echo $this->output->header();
        if (empty($meets)) {
            notice(get_string('thereareno', 'moodle', $strmeets), "$CFG->wwwroot/course/view.php?id=$course->id");
            exit;
        }
        $this->output->heading("Fuzebox Meeting",2);
        $this->page->set_title("Fuzebox Meeting");
        $this->page->set_heading("Fuzebox Meeting");
        $output = "";
        foreach ($meets as $meet){
            $output .= $this->getmeetinghtml($meet);
        }
        
        $output .= $this->output->footer();
        echo $output;
        return true;
    }
    
    public function getmeetinghtml($meet,$cmod=null){
        global $USER,$CFG;
        $output = "";
        $output .= $this->output->box_start('generalbox wrapfuze');
        
        $dateS = new DateTime();
        $dateS->setTimestamp($meet->starttime);
        $day = $dateS->format("l");
        $date = $dateS->format("D M d Y");
        $time = $dateS->format("H:i A");
        $loading = $CFG->wwwroot."/pix/i/loading_small.gif";
        
        $editable = $USER->id==$meet->user;
        if($editable){
                $cm = !empty($cmod)?$cmod:get_coursemodule_from_instance("fuzebox", $meet->id,$meet->course);
                if(!empty($cm)){
                    if(!$meet->launched){
                        $meet->launchurl = new moodle_url(
                                $CFG->wwwroot."/mod/fuzebox/view.php",
                                array("id"=>$cm->id,"action"=>"launch")
                                );
                        $meet->updateurl = new moodle_url(
                                $CFG->wwwroot."/course/mod.php",
                                array("sesskey"=>$USER->sesskey,"sr"=>0,"update"=>$cm->id));
                    }else{
                        $meet->viewurl = new moodle_url(
                            $CFG->wwwroot."/mod/fuzebox/view.php",
                            array("id"=>$cm->id,"action"=>"view"));
                    }
                    $meet->deleteurl = new moodle_url(
                            $CFG->wwwroot."/course/mod.php",
                            array("sesskey"=>$USER->sesskey,"sr"=>0,"delete"=>$cm->id));
                    
                    $data = array(
                        "url"=>$CFG->wwwroot."/mod/fuzebox/view.php?id=".$cm->id."&action=getinfo",
                        "mid"=>$meet->meetingid
                    );
                    $jsmodule = array(
                        'name'     => 'mod_fuzebox',
                        'fullpath' => '/mod/fuzebox/js/fuze.js',
                    );
                    $this->page->set_url('/mod/fuzebox/view.php', array('id' => $cm->id));
                    $this->page->requires->js('/mod/fuzebox/js/fuze.js');

                    $this->page->requires->js_init_call('M.mod_fuzebox.loadInfo', $data,true,$jsmodule);
                }else{
                    $editable = false;
                }
        }
        $template = new fuzeTemplate('meetings.php',$CFG->dirroot."/mod/fuzebox/templates");
        $template->setVars(array(
            "day"=>$day,
            "date"=>$date,
            "time"=>$time,
            "editable"=>$editable,
            "meet"=>(array)$meet,
            "loading"=>$loading
        ));
        $output .= $template->getHtml();
        $output .= $this->output->box_end();
        return $output;
    }
}