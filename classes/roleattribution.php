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
 * Entities Class to display list of entity records.
 *
 * @package local_roleattribution
 * @author Georg Maißer
 * @copyright 2023 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_roleattribution;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/accesslib.php');

/**
 * Class roleattribution
 *
 * @author Georg Maißer
 * @copyright 2023 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class roleattribution {

    /**
     * This function
     * @param object $user
     */
    public static function sync_roles($user) {

        // Get all roles vie user fields separated by commas.
        $affilations = array_merge(
            explode(',', $user->department),
            explode(',', $user->institution)
        );


        // IAM course creator role.
        $roletohandle = 12;
        // IDs for access to global secondaire and fondamental folders.
        $esestid = self::get_contextid_by_catname("ES et EST");

        $efid = self::get_contextid_by_catname("Enseignement fondamental");

        // if ($user->username == "karst801"){
        // $saml_courses = array("LTAM-TEACHER","LAM-TEACHER","LAM-OTHER");
        // }

        if (!$user->id) {
            return;
        }

        // Array of roles that user should have in a context.
        $aclshouldhave = [];
        foreach ($affilations as $affilation) {
            if (preg_match("/(.*)-TEACHER/i", $affilation, $school)) {
                $contextid = self::get_contextid_by_catname($school[1]);
                if ($school[1] == 'EP-ALL') {
                    if ($efid != 0 && !in_array($efid, $aclshouldhave)) {
                        $aclshouldhave[] = $efid;
                    }
                } else {
                    if ($esestid != 0 && !in_array($esestid, $aclshouldhave)) {
                        $aclshouldhave[] = $esestid;
                    }
                }
                if ($contextid) {
                    $aclshouldhave[] = $contextid;
                }
            }
        }

        $aclhas = [];

        $uacl = get_user_roles_sitewide_accessdata($user->id);
        foreach ($uacl['ra'] as $key => $value) {
            if (reset($value) == 12) {
                $contextid = self::get_contextid_by_path($key);
                if ($contextid) {
                    $aclhas[] = $contextid;
                }
            }
        }

        $toassign = array_diff($aclshouldhave, $aclhas);
        foreach ($toassign as $key => $contextid) {
            role_assign($roletohandle, $user->id, $contextid);
        }

        $tounassign = array_diff($aclhas, $aclshouldhave);
        foreach ($tounassign as $key => $contextid) {
            role_unassign($roletohandle, $user->id, $contextid);
        }
    }

    public static function get_contextid_by_catname($school) {
        global $DB;
        $params = array($school);
        $sql = "SELECT con.id
                FROM {context} con
            LEFT JOIN {course_categories} cat ON (con.instanceid = cat.id)
                WHERE con.contextlevel = 40
                AND cat.name like ? ";

        $records = $DB->get_records_sql($sql, $params);
        $record = reset($records);
        return $record->id ?? 0;
    }

    public static function get_contextid_by_path($path) {
        global $DB;
        $params = array($path);
        $sql = "SELECT id
                FROM {context}
                WHERE contextlevel = 40
                AND path like ?";

        $records = $DB->get_records_sql($sql, $params);
        $record = reset($records);
        return $record->id ?? 0;
    }
}
