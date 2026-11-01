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
 * Restore structure steps for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one grad360 activity.
 */
class restore_grad360_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the restore structure.
     *
     * @return restore_path_element[] Array of restore path elements.
     */
    protected function define_structure(): array {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('grad360', '/activity/grad360');
        $paths[] = new restore_path_element('grad360_hotspot', '/activity/grad360/hotspots/hotspot');

        if ($userinfo) {
            $paths[] = new restore_path_element('grad360_progress', '/activity/grad360/progresses/progress');
        }

        // Return the paths wrapped in standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the grad360 element (main instance).
     *
     * @param stdClass|array $data The data from backup.
     */
    protected function process_grad360($data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // nosemgrep: moodle-fehlender-sesskey
        $newitemid = $DB->insert_record('grad360', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a hotspot element.
     *
     * @param stdClass|array $data The data from backup.
     */
    protected function process_grad360_hotspot($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->grad360id = $this->get_new_parentid('grad360');

        // nosemgrep: moodle-fehlender-sesskey
        $newitemid = $DB->insert_record('grad360_hotspots', $data);
        $this->set_mapping('grad360_hotspot', $oldid, $newitemid);
    }

    /**
     * Process a progress element.
     *
     * @param stdClass|array $data The data from backup.
     */
    protected function process_grad360_progress($data): void {
        global $DB;

        $data = (object)$data;

        $data->grad360id = $this->get_new_parentid('grad360');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->hotspotid = $this->get_mappingid('grad360_hotspot', $data->hotspotid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        // nosemgrep: moodle-fehlender-sesskey
        $DB->insert_record('grad360_progress', $data);
    }

    /**
     * Post-execution actions: restore files.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_grad360', 'intro', null);
        $this->add_related_files('mod_grad360', 'panorama', null);
        $this->add_related_files('mod_grad360', 'rotation', null);
    }
}
