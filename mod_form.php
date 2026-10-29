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
 * Activity settings form for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 */
class mod_grad360_mod_form extends moodleform_mod {
    /**
     * Define the form elements.
     */
    public function definition(): void {
        global $DB;

        $mform = $this->_form;

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Activity name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Standard intro editor.
        $this->standard_intro_elements();

        // 360° Configuration.
        $mform->addElement('header', 'viewersettings', get_string('viewersettings', 'mod_grad360'));

        // Mode selection.
        $modes = [
            'panorama' => get_string('mode_panorama', 'mod_grad360'),
            'rotation' => get_string('mode_rotation', 'mod_grad360'),
        ];
        $mform->addElement('select', 'mode', get_string('mode', 'mod_grad360'), $modes);
        $mform->addHelpButton('mode', 'mode', 'mod_grad360');
        $mform->setDefault('mode', 'panorama');

        // Panorama image URL.
        $mform->addElement('text', 'image', get_string('image_url', 'mod_grad360'), ['size' => '80']);
        $mform->setType('image', PARAM_URL);
        $mform->addHelpButton('image', 'image_url', 'mod_grad360');
        $mform->hideIf('image', 'mode', 'eq', 'rotation');

        // Panorama file picker.
        $mform->addElement(
            'filemanager',
            'panorama_file',
            get_string('panorama_file', 'mod_grad360'),
            null,
            [
                'subdirs' => 0,
                'maxfiles' => 1,
                'accepted_types' => ['image'],
            ]
        );
        $mform->addHelpButton('panorama_file', 'panorama_file', 'mod_grad360');
        $mform->hideIf('panorama_file', 'mode', 'eq', 'rotation');

        // Horizontal field of view.
        $mform->addElement('text', 'hfov', get_string('hfov', 'mod_grad360'), ['size' => '5']);
        $mform->setType('hfov', PARAM_INT);
        $mform->setDefault('hfov', 100);
        $mform->addHelpButton('hfov', 'hfov', 'mod_grad360');
        $mform->hideIf('hfov', 'mode', 'eq', 'rotation');

        // Auto-rotate.
        $mform->addElement('advcheckbox', 'autorotate', get_string('autorotate', 'mod_grad360'));
        $mform->addHelpButton('autorotate', 'autorotate', 'mod_grad360');
        $mform->hideIf('autorotate', 'mode', 'eq', 'rotation');

        // Rotation images (one URL per line).
        $mform->addElement('textarea', 'images_text', get_string('rotation_images', 'mod_grad360'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('images_text', PARAM_RAW);
        $mform->addHelpButton('images_text', 'rotation_images', 'mod_grad360');
        $mform->hideIf('images_text', 'mode', 'eq', 'panorama');

        // Auto-play rotation.
        $mform->addElement('advcheckbox', 'autoplay', get_string('rotation_autoplay', 'mod_grad360'));
        $mform->hideIf('autoplay', 'mode', 'eq', 'panorama');

        // Viewer height.
        $mform->addElement('text', 'viewerheight', get_string('viewer_height', 'mod_grad360'), ['size' => '5']);
        $mform->setType('viewerheight', PARAM_INT);
        $mform->setDefault('viewerheight', 450);
        $mform->addHelpButton('viewerheight', 'viewer_height', 'mod_grad360');

        // Hotspots.
        $mform->addElement('header', 'hotspotssection', get_string('hotspots', 'mod_grad360'));
        $mform->addHelpButton('hotspotssection', 'hotspots', 'mod_grad360');

        // Load existing hotspots when editing.
        $hotspots = [];
        if (!empty($this->current->id)) {
            $hotspots = $DB->get_records('grad360_hotspots', ['grad360id' => $this->current->id], 'sortorder ASC');
            $hotspots = array_values($hotspots);
        }

        // We provide up to 20 hotspot slots; start with existing count + 3.
        $repeatcount = max(count($hotspots) + 3, 5);

        $hotspotgroup = [];
        $hotspotgroup[] = $mform->createElement(
            'text', 'hotspot_title',
            get_string('hotspot_title', 'mod_grad360'),
            ['size' => '30']
        );
        $hotspotgroup[] = $mform->createElement(
            'text', 'hotspot_content',
            get_string('hotspot_text', 'mod_grad360'),
            ['size' => '40']
        );
        $hotspotgroup[] = $mform->createElement(
            'text', 'hotspot_link',
            get_string('hotspot_link', 'mod_grad360'),
            ['size' => '20']
        );
        $hotspotgroup[] = $mform->createElement('text', 'hotspot_pitch', 'Pitch', ['size' => '5']);
        $hotspotgroup[] = $mform->createElement('text', 'hotspot_yaw', 'Yaw', ['size' => '5']);
        $hotspotgroup[] = $mform->createElement('select', 'hotspot_icon', get_string('hotspot_icon', 'mod_grad360'), [
            'info' => 'ℹ',
            'link' => '🔗',
            'arrow' => '➡',
        ]);
        $hotspotgroup[] = $mform->createElement(
            'advcheckbox',
            'hotspot_required',
            get_string('hotspot_required', 'mod_grad360')
        );

        $this->repeat_elements(
            $hotspotgroup,
            $repeatcount,
            [
                'hotspot_title' => ['type' => PARAM_TEXT],
                'hotspot_content' => ['type' => PARAM_TEXT],
                'hotspot_link' => ['type' => PARAM_URL],
                'hotspot_pitch' => ['type' => PARAM_FLOAT, 'default' => 0],
                'hotspot_yaw' => ['type' => PARAM_FLOAT, 'default' => 0],
                'hotspot_icon' => ['type' => PARAM_ALPHA, 'default' => 'info'],
                'hotspot_required' => ['type' => PARAM_INT, 'default' => 1],
            ],
            'hotspot_repeats',
            get_string('add_hotspot', 'mod_grad360'),
            3,
            get_string('add_hotspot', 'mod_grad360'),
            true
        );

        // Grade.
        $mform->addElement('header', 'gradesection', get_string('grade'));

        $mform->addElement('text', 'grade', get_string('grade_max', 'mod_grad360'), ['size' => '5']);
        $mform->setType('grade', PARAM_INT);
        $mform->setDefault('grade', 100);
        $mform->addHelpButton('grade', 'grade_max', 'mod_grad360');

        // Completion.
        $mform->addElement('header', 'completionsection', get_string('completion', 'completion'));

        $mform->addElement(
            'advcheckbox',
            'completionhotspots',
            get_string('completionhotspots', 'mod_grad360')
        );
        $mform->addHelpButton('completionhotspots', 'completionhotspots', 'mod_grad360');

        // Standard course module elements.
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Pre-process form data for editing an existing instance.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues): void {
        global $DB;

        // Decode images JSON back to textarea format.
        if (!empty($defaultvalues['images'])) {
            $imgs = json_decode($defaultvalues['images'], true);
            if (is_array($imgs)) {
                $defaultvalues['images_text'] = implode("\n", $imgs);
            }
        }

        // Load hotspot data into form arrays.
        if (!empty($defaultvalues['id'])) {
            $hotspots = $DB->get_records(
                'grad360_hotspots',
                ['grad360id' => $defaultvalues['id']],
                'sortorder ASC'
            );
            $i = 0;
            foreach ($hotspots as $hs) {
                $defaultvalues["hotspot_title[$i]"] = $hs->title;
                $defaultvalues["hotspot_content[$i]"] = $hs->content;
                $defaultvalues["hotspot_link[$i]"] = $hs->link;
                $defaultvalues["hotspot_pitch[$i]"] = $hs->pitch;
                $defaultvalues["hotspot_yaw[$i]"] = $hs->yaw;
                $defaultvalues["hotspot_icon[$i]"] = $hs->icon;
                $defaultvalues["hotspot_required[$i]"] = $hs->required;
                $i++;
            }
        }

        // Prepare file manager.
        if (!empty($defaultvalues['id'])) {
            $cm = get_coursemodule_from_instance('grad360', $defaultvalues['id']);
            if ($cm) {
                $context = context_module::instance($cm->id);
                $draftitemid = file_get_submitted_draft_itemid('panorama_file');
                file_prepare_draft_area(
                    $draftitemid,
                    $context->id,
                    'mod_grad360',
                    'panorama',
                    0,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );
                $defaultvalues['panorama_file'] = $draftitemid;
            }
        }
    }

    /**
     * Validate form data.
     *
     * @param array $data
     * @param array $files
     * @return array Errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if ($data['mode'] === 'panorama' && empty($data['image']) && empty($data['panorama_file'])) {
            $errors['image'] = get_string('error_no_image', 'mod_grad360');
        }

        if ($data['mode'] === 'rotation') {
            $urls = array_filter(array_map('trim', explode("\n", $data['images_text'] ?? '')));
            if (count($urls) < 2) {
                $errors['images_text'] = get_string('error_no_images', 'mod_grad360');
            }
        }

        if (isset($data['hfov']) && ($data['hfov'] < 30 || $data['hfov'] > 150)) {
            $errors['hfov'] = get_string('error_hfov_range', 'mod_grad360');
        }

        if ($data['viewerheight'] < 100 || $data['viewerheight'] > 1200) {
            $errors['viewerheight'] = get_string('error_height_range', 'mod_grad360');
        }

        return $errors;
    }
}
