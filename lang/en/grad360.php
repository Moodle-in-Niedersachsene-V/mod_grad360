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
 * English language strings for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['add_hotspot'] = 'Add more hotspots';
$string['allhotspotsvisited'] = 'Congratulations! You have visited all required hotspots.';
$string['autorotate'] = 'Auto-rotate';
$string['autorotate_help'] = 'If enabled, the panorama slowly rotates automatically until the user interacts with it.';
$string['backtoview'] = 'Back to viewer';
$string['completionhotspots'] = 'Require all hotspots visited';
$string['completionhotspots_desc'] = 'Student must visit all {$a} required hotspot(s)';
$string['completionhotspots_help'] = 'If enabled, the activity is marked as complete only when the student has visited all hotspots marked as required.';
$string['error_height_range'] = 'Viewer height must be between 100 and 1200 pixels.';
$string['error_hfov_range'] = 'Horizontal field of view must be between 30 and 150 degrees.';
$string['error_no_image'] = 'Please provide a panorama image URL or upload a panorama image.';
$string['error_no_images'] = 'Please provide at least 2 image URLs for rotation mode.';
$string['grad360:addinstance'] = 'Add a new 360° Viewer activity';
$string['grad360:interact'] = 'Interact with hotspots';
$string['grad360:view'] = 'View 360° Viewer activity';
$string['grad360:viewreport'] = 'View student progress report';
$string['grade_max'] = 'Maximum grade';
$string['grade_max_help'] = 'The maximum grade for this activity. The grade is calculated based on the percentage of required hotspots the student has visited. Set to 0 to disable grading.';
$string['hfov'] = 'Horizontal field of view';
$string['hfov_help'] = 'The horizontal field of view in degrees (30–150). A wider angle shows more of the panorama at once. Default is 100.';
$string['hotspot_icon'] = 'Icon';
$string['hotspot_link'] = 'Link URL';
$string['hotspot_required'] = 'Required';
$string['hotspot_text'] = 'Text';
$string['hotspot_title'] = 'Title';
$string['hotspots'] = 'Hotspots';
$string['hotspots_help'] = 'Define interactive hotspots that students can click in the viewer. Each hotspot has a title, description text, optional link, position (pitch/yaw), icon type, and a flag to mark it as required for completion.';
$string['image_url'] = 'Panorama image URL';
$string['image_url_help'] = 'Enter the URL of an equirectangular panorama image, or upload a file below. If both are provided, the uploaded file takes priority.';
$string['missingparameter'] = 'Missing required parameter.';
$string['mode'] = 'Viewer mode';
$string['mode_help'] = 'Select the viewer mode. "Panorama" displays an equirectangular 360° image. "Object rotation" displays a series of images that can be dragged through to simulate rotating an object.';
$string['mode_panorama'] = 'Panorama (360°)';
$string['mode_rotation'] = 'Object rotation';
$string['modulename'] = '360° Viewer';
$string['modulename_help'] = 'The 360° Viewer activity allows teachers to embed interactive panoramic images or object rotation views with clickable hotspots. Students explore the scene and discover all required hotspots to complete the activity.';
$string['modulenameplural'] = '360° Viewers';
$string['nohotspots'] = 'No hotspots have been defined for this activity.';
$string['noinstances'] = 'There are no 360° Viewer activities in this course.';
$string['nostudents'] = 'No students are enrolled in this course.';
$string['notvisited'] = 'Not visited';
$string['panorama_file'] = 'Panorama image upload';
$string['panorama_file_help'] = 'Upload an equirectangular panorama image. This takes priority over the image URL if both are provided.';
$string['pluginadministration'] = '360° Viewer administration';
$string['pluginname'] = '360° Viewer';
$string['privacy:metadata:grad360_progress'] = 'Records which hotspots a user has visited.';
$string['privacy:metadata:grad360_progress:hotspotid'] = 'The ID of the visited hotspot.';
$string['privacy:metadata:grad360_progress:timecreated'] = 'The time the hotspot was visited.';
$string['privacy:metadata:grad360_progress:userid'] = 'The ID of the user.';
$string['progress'] = 'Progress';
$string['progress_text'] = '{$a->visited} of {$a->total} required hotspots visited';
$string['report'] = 'Progress report';
$string['required'] = 'Required';
$string['required_legend'] = '* = Required hotspot';
$string['rotation_autoplay'] = 'Auto-play rotation';
$string['rotation_images'] = 'Rotation images (URLs)';
$string['rotation_images_help'] = 'Enter the URLs of the rotation images, one URL per line. At least 2 images are required. The images should show the same object from different angles.';
$string['student'] = 'Student';
$string['viewer_height'] = 'Viewer height (pixels)';
$string['viewer_height_help'] = 'The height of the viewer in pixels (100–1200). Default is 450.';
$string['viewersettings'] = '360° Configuration';
$string['viewreport'] = 'View progress report';
$string['visited'] = 'Visited';
