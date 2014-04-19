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
 * List of urls in course
 *
 * @package    mod
 * @subpackage url
 * @copyright  2009 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/fuzebox/locallib.php");

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array("id"=>$id),'*',MUST_EXIST);
$fuzemeets = $DB->get_records("fuzebox",array("course"=>$id));
$strmeets = get_string('component','fuzebox');
require_course_login($id);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/fuzebox/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strmeets);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strmeets);

$renderer = $PAGE->get_renderer('mod_fuzebox');
$renderer->viewallcoursemeets($id,$fuzemeets);