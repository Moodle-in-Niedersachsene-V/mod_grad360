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
 * External function to save (create or update) a hotspot.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_hotspot extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'hotspotid' => new external_value(PARAM_INT, 'Hotspot ID (0 for new)', VALUE_DEFAULT, 0),
            'title' => new external_value(PARAM_TEXT, 'Hotspot title'),
            'content' => new external_value(PARAM_TEXT, 'Hotspot content text', VALUE_DEFAULT, ''),
            'link' => new external_value(PARAM_URL, 'Link URL', VALUE_DEFAULT, ''),
            'pitch' => new external_value(PARAM_FLOAT, 'Pitch coordinate in degrees'),
            'yaw' => new external_value(PARAM_FLOAT, 'Yaw coordinate in degrees'),
            'icon' => new external_value(PARAM_ALPHA, 'Icon type (info, link, arrow)', VALUE_DEFAULT, 'info'),
            'required' => new external_value(PARAM_BOOL, 'Whether hotspot is required', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Save a hotspot.
     *
     * @param int $cmid Course module ID.
     * @param int $hotspotid Hotspot ID (0 for new).
     * @param string $title Hotspot title.
     * @param string $content Hotspot content.
     * @param string $link Link URL.
     * @param float $pitch Pitch coordinate.
     * @param float $yaw Yaw coordinate.
     * @param string $icon Icon type.
     * @param bool $required Whether required.
     * @return array
     */
    public static function execute(
        int $cmid,
        int $hotspotid,
        string $title,
        string $content,
        string $link,
        float $pitch,
        float $yaw,
        string $icon,
        bool $required
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'hotspotid' => $hotspotid,
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'pitch' => $pitch,
            'yaw' => $yaw,
            'icon' => $icon,
            'required' => $required,
        ]);

        $cm = get_coursemodule_from_id('grad360', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/grad360:managehotspots', $context);

        $grad360 = $DB->get_record('grad360', ['id' => $cm->instance], '*', MUST_EXIST);

        // Validate icon value.
        $allowedicons = ['info', 'link', 'arrow'];
        $iconval = in_array($params['icon'], $allowedicons) ? $params['icon'] : 'info';

        if ($params['hotspotid'] > 0) {
            // Update existing hotspot.
            $hotspot = $DB->get_record('grad360_hotspots', [
                'id' => $params['hotspotid'],
                'grad360id' => $grad360->id,
            ], '*', MUST_EXIST);

            $hotspot->title = $params['title'];
            $hotspot->content = $params['content'];
            $hotspot->link = $params['link'];
            $hotspot->pitch = $params['pitch'];
            $hotspot->yaw = $params['yaw'];
            $hotspot->icon = $iconval;
            $hotspot->required = $params['required'] ? 1 : 0;
            $DB->update_record('grad360_hotspots', $hotspot);

            $returnid = (int)$hotspot->id;
        } else {
            // Create new hotspot.
            $maxsort = $DB->get_field_sql(
                "SELECT MAX(sortorder) FROM {grad360_hotspots} WHERE grad360id = ?",
                [$grad360->id]
            );

            $hotspot = new \stdClass();
            $hotspot->grad360id = $grad360->id;
            $hotspot->sortorder = ($maxsort !== false ? $maxsort + 1 : 0);
            $hotspot->title = $params['title'];
            $hotspot->content = $params['content'];
            $hotspot->link = $params['link'];
            $hotspot->pitch = $params['pitch'];
            $hotspot->yaw = $params['yaw'];
            $hotspot->icon = $iconval;
            $hotspot->required = $params['required'] ? 1 : 0;
            $returnid = (int)$DB->insert_record('grad360_hotspots', $hotspot);
        }

        return [
            'success' => true,
            'hotspotid' => $returnid,
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
            'hotspotid' => new external_value(PARAM_INT, 'The saved hotspot ID'),
        ]);
    }
}
