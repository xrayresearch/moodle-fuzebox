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
 * fuzebox module admin settings and defaults
 *
 * @package    mod
 * @subpackage fuzebox
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");
    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_NEW);

  //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configselect('fuzebox/displayoptions',
        get_string('displayoptions', 'url'), get_string('configdisplayoptions', 'url'),
        $defaultdisplayoptions, $displayoptions));
    $settings->add(new admin_setting_configduration('fuzebox/fuze_sesstimout',
        get_string('fuze_sesstimeout', 'fuzebox'), get_string('fuze_sesstimeoutnote', 'fuzebox'),
        900, 1));
  //--- Fuze Partner Details -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('fuze_partner_details', get_string('fuze_partner_details', 'fuzebox'), get_string('fuze_partner_details', 'fuzebox')));
    $settings->add(new admin_setting_configtext('fuzebox/url',
        get_string('fuze_partner_url', 'fuzebox'), get_string('fuze_partner_url', 'fuzebox'),"",PARAM_URL,80));
    $settings->add(new admin_setting_configtext('fuzebox/packagename',
        get_string('fuze_packagename', 'fuzebox'), get_string('fuze_packagename', 'fuzebox'),"",PARAM_TEXT,20));
    $settings->add(new admin_setting_configtext('fuzebox/pk',
        get_string('fuze_pk', 'fuzebox'), get_string('fuze_pk', 'fuzebox'),"",PARAM_RAW,20));
    $settings->add(new admin_setting_configtext('fuzebox/ek',
        get_string('fuze_ek', 'fuzebox'), get_string('fuze_ek', 'fuzebox'),"",PARAM_RAW,50));
}
