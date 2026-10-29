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
 * View page for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$g  = optional_param('g', 0, PARAM_INT);  // Instance ID.

if ($id) {
    $cm = get_coursemodule_from_id('grad360', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $grad360 = $DB->get_record('grad360', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($g) {
    $grad360 = $DB->get_record('grad360', ['id' => $g], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $grad360->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('grad360', $grad360->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparameter');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/grad360:view', $context);

// Trigger module viewed event.
$event = \mod_grad360\event\course_module_viewed::create([
    'objectid' => $grad360->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('grad360', $grad360);
$event->trigger();

// Mark as viewed for completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Page setup.
$PAGE->set_url('/mod/grad360/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($grad360->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_activity_record($grad360);

// Load hotspots from database.
$hotspots = $DB->get_records('grad360_hotspots', ['grad360id' => $grad360->id], 'sortorder ASC');
$hotspotsdata = [];
foreach ($hotspots as $hs) {
    $hotspotsdata[] = [
        'id' => (int)$hs->id,
        'title' => $hs->title,
        'text' => $hs->content,
        'link' => $hs->link,
        'pitch' => (float)$hs->pitch,
        'yaw' => (float)$hs->yaw,
        'icon' => $hs->icon,
        'required' => (bool)$hs->required,
    ];
}

// Load user progress.
$caninteract = has_capability('mod/grad360:interact', $context);
$progress = [];
if ($caninteract) {
    $visited = $DB->get_records('grad360_progress', [
        'grad360id' => $grad360->id,
        'userid' => $USER->id,
    ]);
    foreach ($visited as $v) {
        $progress[] = (int)$v->hotspotid;
    }
}

// Calculate progress stats.
$totalrequired = 0;
$visitedrequired = 0;
foreach ($hotspotsdata as $hs) {
    if ($hs['required']) {
        $totalrequired++;
        if (in_array($hs['id'], $progress)) {
            $visitedrequired++;
        }
    }
}
$progresspercent = $totalrequired > 0 ? round(($visitedrequired / $totalrequired) * 100) : 100;

// Prepare image URL.
$imageurl = $grad360->image;
if ($grad360->mode === 'panorama') {
    // Check for uploaded file.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_grad360', 'panorama', 0, 'sortorder', false);
    if ($files) {
        $file = reset($files);
        $imageurl = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_grad360',
            'panorama',
            0,
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }
}

// Prepare images for rotation mode.
$imagesdata = [];
if ($grad360->mode === 'rotation' && !empty($grad360->images)) {
    $imagesdata = json_decode($grad360->images, true) ?: [];
}

// Prepare JS data.
$viewerconfig = [
    'cmid' => (int)$cm->id,
    'instanceid' => (int)$grad360->id,
    'mode' => $grad360->mode,
    'image' => $imageurl ?? '',
    'images' => $imagesdata,
    'hfov' => (int)$grad360->hfov,
    'autorotate' => (bool)$grad360->autorotate,
    'autoplay' => (bool)$grad360->autoplay,
    'speed' => (float)$grad360->speed,
    'viewerheight' => (int)$grad360->viewerheight,
    'hotspots' => $hotspotsdata,
    'progress' => $progress,
    'caninteract' => $caninteract,
];

// Check if user can manage hotspots (for editor).
$canmanagehotspots = has_capability('mod/grad360:managehotspots', $context);
$viewerconfig['canmanagehotspots'] = $canmanagehotspots;

$PAGE->requires->js_call_amd('mod_grad360/view', 'init', [$viewerconfig]);

// Render.
echo $OUTPUT->header();

// Intro text.
if (trim(strip_tags($grad360->intro))) {
    echo $OUTPUT->box(format_module_intro('grad360', $grad360, $cm->id), 'generalbox', 'intro');
}

// Progress bar (for students).
if ($caninteract && $totalrequired > 0) {
    $data = [
        'percent' => $progresspercent,
        'visited' => $visitedrequired,
        'total' => $totalrequired,
        'complete' => $progresspercent >= 100,
        'progresstext' => get_string('progress_text', 'mod_grad360', [
            'visited' => $visitedrequired,
            'total' => $totalrequired,
        ]),
    ];
    echo $OUTPUT->render_from_template('mod_grad360/progress_bar', $data);
}

// Edit hotspots button (for teachers).
if ($canmanagehotspots && $grad360->mode === 'panorama') {
    echo html_writer::div(
        html_writer::tag('button', get_string('editor_start', 'mod_grad360'), [
            'id' => 'grad360-edit-hotspots-btn',
            'class' => 'btn btn-primary mb-2',
            'type' => 'button',
        ]),
        'grad360-editor-controls'
    );
}

// Viewer container.
echo html_writer::div('', 'grad360-viewer-container', [
    'id' => 'grad360-viewer',
    'style' => 'height:' . $grad360->viewerheight . 'px;',
    'role' => 'application',
    'aria-label' => format_string($grad360->name),
]);

// Hotspot list (for accessibility and overview).
if (!empty($hotspotsdata)) {
    $listdata = [
        'hotspots' => array_map(function ($hs) use ($progress, $caninteract) {
            return [
                'id' => $hs['id'],
                'title' => $hs['title'],
                'text' => $hs['text'],
                'link' => $hs['link'],
                'haslink' => !empty($hs['link']),
                'required' => $hs['required'],
                'visited' => in_array($hs['id'], $progress),
                'caninteract' => $caninteract,
            ];
        }, $hotspotsdata),
        'showstatus' => $caninteract,
    ];
    echo $OUTPUT->render_from_template('mod_grad360/hotspot_list', $listdata);
}

// Teacher report link.
if (has_capability('mod/grad360:viewreport', $context)) {
    $reporturl = new moodle_url('/mod/grad360/report.php', ['id' => $cm->id]);
    echo html_writer::div(
        html_writer::link($reporturl, get_string('viewreport', 'mod_grad360'), ['class' => 'btn btn-secondary mt-3']),
        'grad360-report-link'
    );
}

echo $OUTPUT->footer();
