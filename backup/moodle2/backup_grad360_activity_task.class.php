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
 * Backup task for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/grad360/backup/moodle2/backup_grad360_stepslib.php');

/**
 * Backup task for the grad360 activity module.
 */
class backup_grad360_activity_task extends backup_activity_task {

    /**
     * No particular settings for this activity.
     */
    protected function define_my_settings(): void {
        // No particular settings for this activity.
    }

    /**
     * Define the backup structure steps.
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_grad360_activity_structure_step('grad360_structure', 'grad360.xml'));
    }

    /**
     * Encode content links in the activity.
     *
     * @param string $content The content to encode.
     * @return string The encoded content.
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of grad360 activities.
        $search = '/(' . $base . '\/mod\/grad360\/index\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@GRAD360INDEX*$2@$', $content);

        // Link to a specific grad360 view page.
        $search = '/(' . $base . '\/mod\/grad360\/view\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@GRAD360VIEWBYID*$2@$', $content);

        return $content;
    }
}
