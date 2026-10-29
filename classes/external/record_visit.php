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
 * External function to record a hotspot visit.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class record_visit extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'hotspotid' => new external_value(PARAM_INT, 'Hotspot ID'),
        ]);
    }

    /**
     * Record a hotspot visit.
     *
     * @param int $cmid Course module ID.
     * @param int $hotspotid Hotspot ID.
     * @return array
     */
    public static function execute(int $cmid, int $hotspotid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'hotspotid' => $hotspotid,
        ]);

        $cm = get_coursemodule_from_id('grad360', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/grad360:interact', $context);

        $grad360 = $DB->get_record('grad360', ['id' => $cm->instance], '*', MUST_EXIST);

        // Verify hotspot belongs to this instance.
        $hotspot = $DB->get_record('grad360_hotspots', [
            'id' => $params['hotspotid'],
            'grad360id' => $grad360->id,
        ], '*', MUST_EXIST);

        // Check if already visited.
        $existing = $DB->get_record('grad360_progress', [
            'grad360id' => $grad360->id,
            'userid' => $USER->id,
            'hotspotid' => $params['hotspotid'],
        ]);

        $alreadyvisited = false;
        if (!$existing) {
            $record = new \stdClass();
            $record->grad360id = $grad360->id;
            $record->userid = $USER->id;
            $record->hotspotid = $params['hotspotid'];
            $record->timecreated = time();
            $DB->insert_record('grad360_progress', $record);
        } else {
            $alreadyvisited = true;
        }

        // Calculate current progress.
        $requiredhotspots = $DB->get_records('grad360_hotspots', [
            'grad360id' => $grad360->id,
            'required' => 1,
        ]);
        $totalrequired = count($requiredhotspots);

        $visitedrecords = $DB->get_records('grad360_progress', [
            'grad360id' => $grad360->id,
            'userid' => $USER->id,
        ]);
        $visitedids = array_map(function ($r) {
            return (int)$r->hotspotid;
        }, $visitedrecords);

        $visitedrequired = 0;
        foreach ($requiredhotspots as $rhs) {
            if (in_array((int)$rhs->id, $visitedids)) {
                $visitedrequired++;
            }
        }

        $progresspercent = $totalrequired > 0 ? round(($visitedrequired / $totalrequired) * 100) : 100;
        $complete = $progresspercent >= 100;

        // Update grades.
        require_once(__DIR__ . '/../../lib.php');
        grad360_update_grades($grad360, $USER->id);

        // Trigger completion update if all required hotspots visited.
        if ($complete && !empty($grad360->completionhotspots)) {
            $course = $DB->get_record('course', ['id' => $grad360->course], '*', MUST_EXIST);
            $completion = new \completion_info($course);
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
            }
        }

        return [
            'success' => true,
            'alreadyvisited' => $alreadyvisited,
            'progresspercent' => $progresspercent,
            'visitedrequired' => $visitedrequired,
            'totalrequired' => $totalrequired,
            'complete' => $complete,
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
            'alreadyvisited' => new external_value(PARAM_BOOL, 'Whether this hotspot was already visited'),
            'progresspercent' => new external_value(PARAM_INT, 'Current progress percentage'),
            'visitedrequired' => new external_value(PARAM_INT, 'Number of required hotspots visited'),
            'totalrequired' => new external_value(PARAM_INT, 'Total number of required hotspots'),
            'complete' => new external_value(PARAM_BOOL, 'Whether all required hotspots are visited'),
        ]);
    }
}
