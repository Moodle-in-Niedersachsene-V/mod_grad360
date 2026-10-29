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
 * List of all grad360 instances in a course.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_login($course);

$PAGE->set_url('/mod/grad360/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_grad360'));

$instances = get_all_instances_in_course('grad360', $course);

if (empty($instances)) {
    notice(
        get_string('noinstances', 'mod_grad360'),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
}

$usesections = course_format_uses_sections($course->format);
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head = [$strsectionname, get_string('name'), get_string('description')];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head = [get_string('name'), get_string('description')];
    $table->align = ['left', 'left'];
}

foreach ($instances as $instance) {
    $attributes = [];
    if (!$instance->visible) {
        $attributes['class'] = 'dimmed';
    }

    $link = html_writer::link(
        new moodle_url('/mod/grad360/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name),
        $attributes
    );

    $description = format_module_intro('grad360', $instance, $instance->coursemodule);

    if ($usesections) {
        $table->data[] = [
            get_section_name($course, $instance->section),
            $link,
            $description,
        ];
    } else {
        $table->data[] = [$link, $description];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
