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
 * Restore task for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/grad360/backup/moodle2/restore_grad360_stepslib.php');

/**
 * Restore task for the grad360 activity module.
 */
class restore_grad360_activity_task extends restore_activity_task {

    /**
     * No particular settings for this activity.
     */
    protected function define_my_settings(): void {
        // No particular settings for this activity.
    }

    /**
     * Define the restore structure steps.
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_grad360_activity_structure_step('grad360_structure', 'grad360.xml'));
    }

    /**
     * Define the decode contents for this activity.
     *
     * @return restore_decode_content[] Array of decode content objects.
     */
    public static function define_decode_contents(): array {
        $contents = [];

        $contents[] = new restore_decode_content('grad360', ['intro'], 'grad360');

        return $contents;
    }

    /**
     * Define the decode rules for this activity.
     *
     * @return restore_decode_rule[] Array of decode rules.
     */
    public static function define_decode_rules(): array {
        $rules = [];

        $rules[] = new restore_decode_rule('GRAD360INDEX', '/mod/grad360/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('GRAD360VIEWBYID', '/mod/grad360/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules for this activity.
     *
     * @return restore_log_rule[] Array of log rules.
     */
    public static function define_restore_log_rules(): array {
        $rules = [];

        $rules[] = new restore_log_rule('grad360', 'add', 'view.php?id={course_module}', '{grad360}');
        $rules[] = new restore_log_rule('grad360', 'update', 'view.php?id={course_module}', '{grad360}');
        $rules[] = new restore_log_rule('grad360', 'view', 'view.php?id={course_module}', '{grad360}');

        return $rules;
    }

    /**
     * Define the restore log rules for the course level.
     *
     * @return restore_log_rule[] Array of course-level log rules.
     */
    public static function define_restore_log_rules_for_course(): array {
        $rules = [];

        $rules[] = new restore_log_rule('grad360', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
