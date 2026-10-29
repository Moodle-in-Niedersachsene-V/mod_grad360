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
 * External services for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_grad360_record_visit' => [
        'classname' => 'mod_grad360\external\record_visit',
        'description' => 'Record that a user has visited a hotspot.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/grad360:interact',
    ],
    'mod_grad360_get_progress' => [
        'classname' => 'mod_grad360\external\get_progress',
        'description' => 'Get the current user\'s hotspot visit progress.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/grad360:view',
    ],
];
