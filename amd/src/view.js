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
 * Main view module for mod_grad360.
 *
 * Initializes the appropriate viewer (panorama or rotation) and
 * handles hotspot interaction tracking via AJAX.
 *
 * @module     mod_grad360/view
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import Viewer from 'mod_grad360/viewer';
import RotationViewer from 'mod_grad360/rotation';

/**
 * Show the hotspot info popup.
 *
 * @param {object} hs The hotspot data.
 * @param {HTMLElement} container The viewer container.
 */
const showHotspotPopup = (hs, container) => {
    // Remove any existing popup.
    const existing = container.querySelector('.grad360-hotspot-popup');
    if (existing) {
        existing.remove();
    }

    const popup = document.createElement('div');
    popup.className = 'grad360-hotspot-popup';
    popup.setAttribute('role', 'dialog');
    popup.setAttribute('aria-label', hs.title);

    let html = '';
    if (hs.title) {
        html += '<h5 class="grad360-popup-title">' + escapeHtml(hs.title) + '</h5>';
    }
    if (hs.text) {
        html += '<div class="grad360-popup-text">' + escapeHtml(hs.text) + '</div>';
    }
    if (hs.link) {
        html += '<div class="grad360-popup-link"><a href="' + escapeHtml(hs.link) +
            '" target="_blank" rel="noopener">' + escapeHtml(hs.link) + '</a></div>';
    }
    html += '<button class="grad360-popup-close btn btn-sm btn-secondary" aria-label="Close">&times;</button>';

    popup.innerHTML = html;
    container.appendChild(popup);

    // Close button handler.
    popup.querySelector('.grad360-popup-close').addEventListener('click', () => {
        popup.remove();
    });

    // Close on click outside after a short delay.
    setTimeout(() => {
        const closeOnOutside = (e) => {
            if (!popup.contains(e.target)) {
                popup.remove();
                document.removeEventListener('click', closeOnOutside);
            }
        };
        document.addEventListener('click', closeOnOutside);
    }, 100);
};

/**
 * Escape HTML entities.
 *
 * @param {string} str The string to escape.
 * @return {string}
 */
const escapeHtml = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

/**
 * Update the progress bar on the page.
 *
 * @param {number} percent Progress percentage.
 * @param {number} visited Number of visited required hotspots.
 * @param {number} total Total required hotspots.
 * @param {boolean} complete Whether all required hotspots are visited.
 */
const updateProgressBar = (percent, visited, total, complete) => {
    const bar = document.querySelector('.grad360-progress-bar .progress-bar');
    if (bar) {
        bar.style.width = percent + '%';
        bar.setAttribute('aria-valuenow', percent);
        bar.textContent = percent + '%';
        if (complete) {
            bar.classList.remove('bg-primary');
            bar.classList.add('bg-success');
        }
    }

    const text = document.querySelector('.grad360-progress-text');
    if (text) {
        getString('progress_text', 'mod_grad360', {visited: visited, total: total}).then((str) => {
            text.textContent = str;
            return;
        }).catch(Notification.exception);
    }

    // Show completion message.
    if (complete) {
        const msg = document.querySelector('.grad360-complete-message');
        if (msg) {
            msg.style.display = 'block';
        }
    }
};

/**
 * Update the hotspot list below the viewer.
 *
 * @param {number} hotspotId The visited hotspot ID.
 */
const markHotspotInList = (hotspotId) => {
    const listItem = document.querySelector('.grad360-hotspot-list-item[data-id="' + hotspotId + '"]');
    if (listItem) {
        const badge = listItem.querySelector('.grad360-status-badge');
        if (badge) {
            badge.classList.remove('badge-secondary', 'bg-secondary');
            badge.classList.add('badge-success', 'bg-success');
            getString('visited', 'mod_grad360').then((str) => {
                badge.textContent = str;
                return;
            }).catch(Notification.exception);
        }
    }
};

/**
 * Initialize the module.
 *
 * @param {object} config The viewer configuration from PHP.
 */
export const init = (config) => {
    const container = document.getElementById('grad360-viewer');
    if (!container) {
        return;
    }

    // Mark already visited hotspots.
    const progressSet = new Set(config.progress || []);
    config.hotspots.forEach((hs) => {
        hs.visited = progressSet.has(hs.id);
    });

    let viewer;

    if (config.mode === 'rotation') {
        viewer = new RotationViewer(container, config);
    } else {
        viewer = new Viewer(container, config);
    }

    // Handle hotspot clicks.
    viewer.onHotspotClick = (hs) => {
        // Show popup.
        showHotspotPopup(hs, container);

        // Record visit via AJAX if user can interact and hasn't visited yet.
        if (config.caninteract && !hs.visited) {
            Ajax.call([{
                methodname: 'mod_grad360_record_visit',
                args: {
                    cmid: config.cmid,
                    hotspotid: hs.id,
                },
            }])[0].then((result) => {
                if (result.success) {
                    hs.visited = true;
                    viewer.markVisited(hs.id);
                    markHotspotInList(hs.id);
                    updateProgressBar(
                        result.progresspercent,
                        result.visitedrequired,
                        result.totalrequired,
                        result.complete
                    );
                }
                return;
            }).catch(Notification.exception);
        }
    };
};
