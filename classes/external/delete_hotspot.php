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

namespace mod_grad360\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function to delete a hotspot.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_hotspot extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'hotspotid' => new external_value(PARAM_INT, 'Hotspot ID to delete'),
        ]);
    }

    /**
     * Delete a hotspot.
     *
     * @param int $cmid Course module ID.
     * @param int $hotspotid Hotspot ID.
     * @return array
     */
    public static function execute(int $cmid, int $hotspotid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'hotspotid' => $hotspotid,
        ]);

        $cm = get_coursemodule_from_id('grad360', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/grad360:managehotspots', $context);

        $grad360 = $DB->get_record('grad360', ['id' => $cm->instance], '*', MUST_EXIST);

        // Verify hotspot belongs to this instance.
        $hotspot = $DB->get_record('grad360_hotspots', [
            'id' => $params['hotspotid'],
            'grad360id' => $grad360->id,
        ], '*', MUST_EXIST);

        // Delete progress records for this hotspot.
        $DB->delete_records('grad360_progress', [
            'grad360id' => $grad360->id,
            'hotspotid' => $params['hotspotid'],
        ]);

        // Delete the hotspot.
        $DB->delete_records('grad360_hotspots', ['id' => $params['hotspotid']]);

        return [
            'success' => true,
        ];
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
        ]);
    }
}
