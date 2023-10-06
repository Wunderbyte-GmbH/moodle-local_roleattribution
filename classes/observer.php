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
 * Event observers.
 *
 * @package local_roleattribution
 * @copyright 2023 Georg Maißer <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\event\base;
use local_roleattribution\roleattribution;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->dirroot.'/user/lib.php');

/**
 * Event observer for local_roleattribution.
 */
class local_roleattribution_observer {

    /**
     * Observer for the update_catscale event
     *
     * @param base $event
     */
    public static function userlogin(base $event) {

        $userid = $event->userid;

        $users = user_get_users_by_id([$userid]);
        $user = reset($users);

        roleattribution::sync_roles($user);

    }

}
