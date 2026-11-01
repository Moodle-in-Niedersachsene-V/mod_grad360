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
 * Backup structure steps for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete grad360 structure for backup.
 */
class backup_grad360_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure of the backup file.
     *
     * @return backup_nested_element The root element with all nested elements.
     */
    protected function define_structure(): backup_nested_element {

        // Check if we are including user info.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element (table fields minus id, course for the main table).
        $grad360 = new backup_nested_element('grad360', ['id'], [
            'name', 'intro', 'introformat',
            'mode', 'image', 'images',
            'hfov', 'autorotate', 'autoplay', 'speed', 'viewerheight',
            'grade', 'completionhotspots',
            'timecreated', 'timemodified',
        ]);

        $hotspots = new backup_nested_element('hotspots');

        $hotspot = new backup_nested_element('hotspot', ['id'], [
            'grad360id', 'sortorder', 'title', 'content', 'link',
            'pitch', 'yaw', 'icon', 'required',
        ]);

        $progresses = new backup_nested_element('progresses');

        $progress = new backup_nested_element('progress', ['id'], [
            'grad360id', 'userid', 'hotspotid', 'timecreated',
        ]);

        // Build the tree.
        $grad360->add_child($hotspots);
        $hotspots->add_child($hotspot);

        $grad360->add_child($progresses);
        $progresses->add_child($progress);

        // Define sources.
        $grad360->set_source_table('grad360', ['id' => backup::VAR_ACTIVITYID]);
        $hotspot->set_source_table('grad360_hotspots', ['grad360id' => backup::VAR_PARENTID], 'id ASC');

        // Only include progress data if user info is requested.
        if ($userinfo) {
            $progress->set_source_table('grad360_progress', ['grad360id' => backup::VAR_PARENTID], 'id ASC');
        }

        // Define id annotations.
        $progress->annotate_ids('user', 'userid');

        // Define file annotations.
        $grad360->annotate_files('mod_grad360', 'intro', null);
        $grad360->annotate_files('mod_grad360', 'panorama', null);
        $grad360->annotate_files('mod_grad360', 'rotation', null);

        // Return the root element wrapped in standard activity info.
        return $this->prepare_activity_structure($grad360);
    }
}
