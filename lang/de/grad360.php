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
 * German language strings for mod_grad360.
 *
 * @package    mod_grad360
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = '360°-Betrachter';
$string['modulenameplural'] = '360°-Betrachter';
$string['modulename_help'] = 'Die Aktivität „360°-Betrachter" ermöglicht es Lehrenden, interaktive Panoramabilder oder Objektrotationsansichten mit klickbaren Hotspots einzubetten. Lernende erkunden die Szene und entdecken alle erforderlichen Hotspots, um die Aktivität abzuschließen.';
$string['pluginname'] = '360°-Betrachter';
$string['pluginadministration'] = '360°-Betrachter-Verwaltung';

// Datenschutz.
$string['privacy:metadata:grad360_progress'] = 'Speichert, welche Hotspots ein/e Nutzer/in besucht hat.';
$string['privacy:metadata:grad360_progress:userid'] = 'Die ID des Nutzers/der Nutzerin.';
$string['privacy:metadata:grad360_progress:hotspotid'] = 'Die ID des besuchten Hotspots.';
$string['privacy:metadata:grad360_progress:timecreated'] = 'Der Zeitpunkt, zu dem der Hotspot besucht wurde.';

// Fähigkeiten.
$string['grad360:addinstance'] = 'Neuen 360°-Betrachter hinzufügen';
$string['grad360:view'] = '360°-Betrachter anzeigen';
$string['grad360:interact'] = 'Mit Hotspots interagieren';
$string['grad360:viewreport'] = 'Fortschrittsbericht anzeigen';

// Betrachtereinstellungen.
$string['viewersettings'] = '360°-Konfiguration';
$string['mode'] = 'Betrachter-Modus';
$string['mode_help'] = 'Wählen Sie den Betrachtermodus. „Panorama" zeigt ein äquirektangulares 360°-Bild. „Objektrotation" zeigt eine Bildfolge, durch die man ziehen kann, um ein Objekt zu drehen.';
$string['mode_panorama'] = 'Panorama (360°)';
$string['mode_rotation'] = 'Objektrotation';
$string['image_url'] = 'Panoramabild-URL';
$string['image_url_help'] = 'Geben Sie die URL eines äquirektangularen Panoramabildes ein, oder laden Sie eine Datei hoch. Wenn beides vorhanden ist, wird die hochgeladene Datei bevorzugt.';
$string['panorama_file'] = 'Panoramabild hochladen';
$string['panorama_file_help'] = 'Laden Sie ein äquirektangulares Panoramabild hoch. Dieses wird bevorzugt, wenn auch eine URL angegeben ist.';
$string['hfov'] = 'Horizontales Sichtfeld';
$string['hfov_help'] = 'Das horizontale Sichtfeld in Grad (30–150). Ein größerer Winkel zeigt mehr vom Panorama auf einmal. Standard ist 100.';
$string['autorotate'] = 'Automatisch drehen';
$string['autorotate_help'] = 'Wenn aktiviert, dreht sich das Panorama langsam automatisch, bis der/die Nutzer/in interagiert.';
$string['rotation_images'] = 'Rotationsbilder (URLs)';
$string['rotation_images_help'] = 'Geben Sie die URLs der Rotationsbilder ein, eine URL pro Zeile. Mindestens 2 Bilder sind erforderlich. Die Bilder sollten dasselbe Objekt aus verschiedenen Winkeln zeigen.';
$string['rotation_autoplay'] = 'Rotation automatisch abspielen';
$string['viewer_height'] = 'Betrachter-Höhe (Pixel)';
$string['viewer_height_help'] = 'Die Höhe des Betrachters in Pixeln (100–1200). Standard ist 450.';

// Hotspots.
$string['hotspots'] = 'Hotspots';
$string['hotspots_help'] = 'Definieren Sie interaktive Hotspots, auf die Lernende im Betrachter klicken können. Jeder Hotspot hat einen Titel, Beschreibungstext, optionalen Link, Position (Pitch/Yaw), Symbol und eine Markierung als erforderlich für den Abschluss.';
$string['hotspot_title'] = 'Titel';
$string['hotspot_text'] = 'Text';
$string['hotspot_link'] = 'Link-URL';
$string['hotspot_icon'] = 'Symbol';
$string['hotspot_required'] = 'Erforderlich';
$string['add_hotspot'] = 'Weitere Hotspots hinzufügen';

// Bewertung.
$string['grade_max'] = 'Maximale Bewertung';
$string['grade_max_help'] = 'Die maximale Bewertung für diese Aktivität. Die Bewertung wird auf Basis des Prozentsatzes der vom Lernenden besuchten erforderlichen Hotspots berechnet. Auf 0 setzen, um die Bewertung zu deaktivieren.';

// Abschluss.
$string['completionhotspots'] = 'Alle Hotspots müssen besucht werden';
$string['completionhotspots_help'] = 'Wenn aktiviert, wird die Aktivität erst als abgeschlossen markiert, wenn der/die Lernende alle als erforderlich markierten Hotspots besucht hat.';
$string['completionhotspots_desc'] = 'Lernende müssen alle {$a} erforderlichen Hotspot(s) besuchen';

// Fortschritt.
$string['progress'] = 'Fortschritt';
$string['progress_text'] = '{$a->visited} von {$a->total} erforderlichen Hotspots besucht';
$string['allhotspotsvisited'] = 'Gratulation! Sie haben alle erforderlichen Hotspots besucht.';

// Status.
$string['visited'] = 'Besucht';
$string['notvisited'] = 'Nicht besucht';
$string['required'] = 'Erforderlich';

// Bericht.
$string['report'] = 'Fortschrittsbericht';
$string['viewreport'] = 'Fortschrittsbericht anzeigen';
$string['backtoview'] = 'Zurück zum Betrachter';
$string['student'] = 'Teilnehmer/in';
$string['required_legend'] = '* = Erforderlicher Hotspot';
$string['nohotspots'] = 'Für diese Aktivität wurden keine Hotspots definiert.';
$string['nostudents'] = 'Es sind keine Lernenden in diesem Kurs eingeschrieben.';
$string['noinstances'] = 'Es gibt keine 360°-Betrachter-Aktivitäten in diesem Kurs.';

// Fehler.
$string['error_no_image'] = 'Bitte geben Sie eine Panoramabild-URL an oder laden Sie ein Panoramabild hoch.';
$string['error_no_images'] = 'Bitte geben Sie mindestens 2 Bild-URLs für den Rotationsmodus an.';
$string['error_hfov_range'] = 'Das horizontale Sichtfeld muss zwischen 30 und 150 Grad liegen.';
$string['error_height_range'] = 'Die Betrachter-Höhe muss zwischen 100 und 1200 Pixeln liegen.';
$string['missingparameter'] = 'Fehlender erforderlicher Parameter.';
