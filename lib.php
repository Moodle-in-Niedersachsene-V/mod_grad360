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
 * Library functions for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Given an object containing all the necessary data,
 * this function will create a new instance and return the id.
 *
 * @param stdClass $data Form data from mod_form.
 * @param mod_grad360_mod_form|null $mform The form.
 * @return int New instance id.
 */
function grad360_add_instance(stdClass $data, ?mod_grad360_mod_form $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    // Encode images array to JSON if present.
    if (!empty($data->images_text)) {
        $urls = array_filter(array_map('trim', explode("\n", $data->images_text)));
        $data->images = json_encode(array_values($urls));
    }

    $data->id = $DB->insert_record('grad360', $data);

    // Save panorama file from draft area.
    $context = context_module::instance($data->coursemodule);
    if (isset($data->panorama_file)) {
        file_save_draft_area_files(
            $data->panorama_file,
            $context->id,
            'mod_grad360',
            'panorama',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }

    // Save hotspots.
    grad360_save_hotspots($data);

    // Update gradebook.
    grad360_grade_item_update($data);

    return $data->id;
}

/**
 * Given an object containing all the necessary data,
 * this function will update an existing instance.
 *
 * @param stdClass $data Form data from mod_form.
 * @param mod_grad360_mod_form|null $mform The form.
 * @return bool Success.
 */
function grad360_update_instance(stdClass $data, ?mod_grad360_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    // Encode images array to JSON if present.
    if (!empty($data->images_text)) {
        $urls = array_filter(array_map('trim', explode("\n", $data->images_text)));
        $data->images = json_encode(array_values($urls));
    }

    $DB->update_record('grad360', $data);

    // Save panorama file from draft area.
    $context = context_module::instance($data->coursemodule);
    if (isset($data->panorama_file)) {
        file_save_draft_area_files(
            $data->panorama_file,
            $context->id,
            'mod_grad360',
            'panorama',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }

    // Update hotspots.
    grad360_save_hotspots($data);

    // Update gradebook.
    grad360_grade_item_update($data);

    return true;
}

/**
 * Given an ID of an instance, this function will permanently
 * delete the instance and associated data.
 *
 * @param int $id Instance id.
 * @return bool Success.
 */
function grad360_delete_instance(int $id): bool {
    global $DB;

    if (!$DB->record_exists('grad360', ['id' => $id])) {
        return false;
    }

    // Delete progress records.
    $DB->delete_records('grad360_progress', ['grad360id' => $id]);

    // Delete hotspots.
    $DB->delete_records('grad360_hotspots', ['grad360id' => $id]);

    // Delete instance.
    $DB->delete_records('grad360', ['id' => $id]);

    // Remove grade item.
    grade_update('mod/grad360', 0, 'mod', 'grad360', $id, 0, null, ['deleted' => 1]);

    return true;
}

/**
 * Save hotspots for an activity instance.
 *
 * Deletes existing hotspots and re-inserts from form data.
 *
 * @param stdClass $data The activity data including hotspot arrays.
 */
function grad360_save_hotspots(stdClass $data): void {
    global $DB;

    // Delete existing hotspots.
    $DB->delete_records('grad360_hotspots', ['grad360id' => $data->id]);

    if (empty($data->hotspot_title)) {
        return;
    }

    foreach ($data->hotspot_title as $i => $title) {
        if (empty(trim($title)) && empty(trim($data->hotspot_content[$i] ?? ''))) {
            continue;
        }
        $hotspot = new stdClass();
        $hotspot->grad360id = $data->id;
        $hotspot->sortorder = $i;
        $hotspot->title = trim($title);
        $hotspot->content = trim($data->hotspot_content[$i] ?? '');
        $hotspot->link = trim($data->hotspot_link[$i] ?? '');
        $hotspot->pitch = floatval($data->hotspot_pitch[$i] ?? 0);
        $hotspot->yaw = floatval($data->hotspot_yaw[$i] ?? 0);
        $hotspot->icon = $data->hotspot_icon[$i] ?? 'info';
        $hotspot->required = !empty($data->hotspot_required[$i]) ? 1 : 0;
        $DB->insert_record('grad360_hotspots', $hotspot);
    }
}

/**
 * Indicates API features that this module supports.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True if supported, null if unknown.
 */
function grad360_supports(string $feature): mixed {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Create/update grade item for the given instance.
 *
 * @param stdClass $data Activity instance (must have ->id, ->course, ->name, ->grade).
 * @param mixed $grades Optional grades array.
 * @return int GRADE_UPDATE_OK etc.
 */
function grad360_grade_item_update(stdClass $data, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = [
        'itemname' => $data->name,
    ];

    if (isset($data->grade) && $data->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = $data->grade;
        $item['grademin'] = 0;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    return grade_update('mod/grad360', $data->course, 'mod', 'grad360', $data->id, 0, $grades, $item);
}

/**
 * Update grades in the gradebook.
 *
 * @param stdClass $data Activity instance.
 * @param int $userid Specific user or 0 for all.
 * @param bool $nullifnone If true, send null grade for users with no progress.
 */
function grad360_update_grades(stdClass $data, int $userid = 0, bool $nullifnone = true): void {
    global $DB;

    $grades = [];
    $hotspots = $DB->get_records('grad360_hotspots', ['grad360id' => $data->id, 'required' => 1]);
    $totalrequired = count($hotspots);

    if ($totalrequired === 0) {
        // No required hotspots — everyone gets full marks.
        grad360_grade_item_update($data);
        return;
    }

    $params = ['grad360id' => $data->id];
    $usersql = '';
    if ($userid) {
        $usersql = ' AND p.userid = :userid';
        $params['userid'] = $userid;
    }

    // Count visited required hotspots per user.
    $sql = "SELECT p.userid, COUNT(DISTINCT p.hotspotid) AS visited
              FROM {grad360_progress} p
              JOIN {grad360_hotspots} h ON h.id = p.hotspotid AND h.required = 1
             WHERE p.grad360id = :grad360id $usersql
          GROUP BY p.userid";

    $records = $DB->get_records_sql($sql, $params);

    foreach ($records as $rec) {
        $grade = new stdClass();
        $grade->userid = $rec->userid;
        $grade->rawgrade = ($rec->visited / $totalrequired) * $data->grade;
        $grade->dategraded = time();
        $grades[$rec->userid] = $grade;
    }

    grad360_grade_item_update($data, $grades);
}

/**
 * Return the list of required custom completion rules.
 *
 * @param cm_info $cm The course module info object.
 * @return array
 */
function grad360_get_completion_active_rule_descriptions($cm): array {
    global $DB;

    $descriptions = [];
    if (!empty($cm->customdata['customcompletionrules']['completionhotspots'])) {
        $grad360 = $DB->get_record('grad360', ['id' => $cm->instance]);
        $requiredcount = $DB->count_records('grad360_hotspots', [
            'grad360id' => $cm->instance,
            'required' => 1,
        ]);
        $descriptions[] = get_string('completionhotspots_desc', 'mod_grad360', $requiredcount);
    }

    return $descriptions;
}

/**
 * Serve pluginfile for the module.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool False if file not found.
 */
function grad360_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);
    require_capability('mod/grad360:view', $context);

    if ($filearea === 'panorama' || $filearea === 'rotation') {
        $itemid = array_shift($args);
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_grad360', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    return false;
}
