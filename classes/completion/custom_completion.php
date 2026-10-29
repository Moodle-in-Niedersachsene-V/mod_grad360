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

namespace mod_grad360\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Check the completion state for a custom rule.
     *
     * @param string $rule The completion rule.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $grad360id = $this->cm->instance;
        $userid = $this->userid;

        if ($rule === 'completionhotspots') {
            // Count required hotspots.
            $totalrequired = $DB->count_records('grad360_hotspots', [
                'grad360id' => $grad360id,
                'required' => 1,
            ]);

            if ($totalrequired === 0) {
                return COMPLETION_COMPLETE;
            }

            // Count visited required hotspots.
            $sql = "SELECT COUNT(DISTINCT p.hotspotid)
                      FROM {grad360_progress} p
                      JOIN {grad360_hotspots} h ON h.id = p.hotspotid AND h.required = 1
                     WHERE p.grad360id = :grad360id AND p.userid = :userid";

            $visited = $DB->count_records_sql($sql, [
                'grad360id' => $grad360id,
                'userid' => $userid,
            ]);

            return ($visited >= $totalrequired) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }

        return COMPLETION_INCOMPLETE;
    }

    /**
     * Get the list of custom completion rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionhotspots'];
    }

    /**
     * Get the descriptions for custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $requiredcount = $DB->count_records('grad360_hotspots', [
            'grad360id' => $this->cm->instance,
            'required' => 1,
        ]);

        return [
            'completionhotspots' => get_string('completionhotspots_desc', 'mod_grad360', $requiredcount),
        ];
    }

    /**
     * Get the sort order for custom completion rules.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionhotspots',
        ];
    }
}
