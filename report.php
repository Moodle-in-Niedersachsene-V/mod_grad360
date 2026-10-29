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
 * Teacher report page for mod_grad360.
 *
 * Shows progress of all students across hotspots.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.

$cm = get_coursemodule_from_id('grad360', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$grad360 = $DB->get_record('grad360', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/grad360:viewreport', $context);

$PAGE->set_url('/mod/grad360/report.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($grad360->name) . ' - ' . get_string('report', 'mod_grad360'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'mod_grad360'));

// Load hotspots.
$hotspots = $DB->get_records('grad360_hotspots', ['grad360id' => $grad360->id], 'sortorder ASC');

if (empty($hotspots)) {
    echo $OUTPUT->notification(get_string('nohotspots', 'mod_grad360'), 'info');
    echo $OUTPUT->footer();
    die();
}

// Get enrolled students with interact capability.
$students = get_enrolled_users(
    $context,
    'mod/grad360:interact',
    0,
    'u.id, u.firstname, u.lastname, u.email',
    'u.lastname ASC, u.firstname ASC'
);

if (empty($students)) {
    echo $OUTPUT->notification(get_string('nostudents', 'mod_grad360'), 'info');
    echo $OUTPUT->footer();
    die();
}

// Load all progress records for this activity.
$progressrecords = $DB->get_records('grad360_progress', ['grad360id' => $grad360->id]);
$progressmap = []; // Userid => [hotspotid => timecreated].
foreach ($progressrecords as $pr) {
    if (!isset($progressmap[$pr->userid])) {
        $progressmap[$pr->userid] = [];
    }
    $progressmap[$pr->userid][$pr->hotspotid] = $pr->timecreated;
}

// Count required hotspots.
$requiredhotspots = array_filter($hotspots, function ($hs) {
    return (bool) $hs->required;
});
$totalrequired = count($requiredhotspots);

// Build table.
$table = new html_table();
$table->attributes['class'] = 'generaltable grad360-report-table';

// Header row.
$table->head = [get_string('student', 'mod_grad360')];
foreach ($hotspots as $hs) {
    $label = format_string($hs->title);
    if ($hs->required) {
        $label .= ' *';
    }
    $table->head[] = $label;
}
$table->head[] = get_string('progress', 'mod_grad360');

// Data rows.
foreach ($students as $student) {
    $row = [];
    $row[] = fullname($student);

    $visitedreq = 0;
    foreach ($hotspots as $hs) {
        $visited = isset($progressmap[$student->id][$hs->id]);
        if ($visited) {
            $icon = $OUTPUT->pix_icon('i/valid', get_string('visited', 'mod_grad360'));
            if ($hs->required) {
                $visitedreq++;
            }
        } else {
            $icon = $OUTPUT->pix_icon('i/invalid', get_string('notvisited', 'mod_grad360'));
        }
        $row[] = $icon;
    }

    // Progress percentage.
    if ($totalrequired > 0) {
        $percent = round(($visitedreq / $totalrequired) * 100);
        $row[] = $percent . '%';
    } else {
        $row[] = '100%';
    }

    $table->data[] = $row;
}

echo html_writer::table($table);

if (!empty($requiredhotspots)) {
    echo html_writer::tag('p', get_string('required_legend', 'mod_grad360'), ['class' => 'text-muted small mt-2']);
}

// Back to view link.
$viewurl = new moodle_url('/mod/grad360/view.php', ['id' => $cm->id]);
echo html_writer::div(
    html_writer::link($viewurl, get_string('backtoview', 'mod_grad360'), ['class' => 'btn btn-secondary mt-3']),
    'grad360-back-link'
);

echo $OUTPUT->footer();
