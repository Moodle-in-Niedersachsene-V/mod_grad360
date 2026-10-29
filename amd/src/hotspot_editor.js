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
 * Visual hotspot editor for mod_grad360.
 *
 * Allows teachers to click on the panorama to place hotspots,
 * edit their properties via a modal, drag them to reposition,
 * and delete them — all saved via AJAX.
 *
 * @module     mod_grad360/hotspot_editor
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

/** @type {boolean} Whether edit mode is active. */
let editMode = false;

/** @type {object|null} Reference to the viewer instance. */
let viewerInstance = null;

/** @type {number} Course module ID. */
let cmid = 0;

/** @type {HTMLElement|null} The viewer container. */
let container = null;

/** @type {object|null} Hotspot currently being dragged. */
let dragState = null;

/**
 * Initialize the hotspot editor.
 *
 * @param {object} viewer The Viewer instance (must have screenToCoords method).
 * @param {object} config The viewer config from PHP.
 */
export const init = (viewer, config) => {
    viewerInstance = viewer;
    cmid = config.cmid;
    container = document.getElementById('grad360-viewer');

    const btn = document.getElementById('grad360-edit-hotspots-btn');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', () => {
        toggleEditMode();
    });
};

/**
 * Toggle edit mode on/off.
 */
const toggleEditMode = () => {
    editMode = !editMode;
    const btn = document.getElementById('grad360-edit-hotspots-btn');

    if (editMode) {
        container.classList.add('grad360-edit-mode');
        getString('editor_stop', 'mod_grad360').then((str) => {
            btn.textContent = str;
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-warning');
            return;
        }).catch(Notification.exception);
        enableEditListeners();
        showEditorHint();
    } else {
        container.classList.remove('grad360-edit-mode');
        getString('editor_start', 'mod_grad360').then((str) => {
            btn.textContent = str;
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-primary');
            return;
        }).catch(Notification.exception);
        disableEditListeners();
        hideEditorHint();
        removeModal();
    }
};

/**
 * Show the editor hint banner.
 */
const showEditorHint = () => {
    let hint = document.getElementById('grad360-editor-hint');
    if (!hint) {
        hint = document.createElement('div');
        hint.id = 'grad360-editor-hint';
        hint.className = 'alert alert-info mt-2 mb-2';
        container.parentNode.insertBefore(hint, container);
    }
    getString('editor_hint', 'mod_grad360').then((str) => {
        hint.textContent = str;
        hint.style.display = 'block';
        return;
    }).catch(Notification.exception);
};

/**
 * Hide the editor hint banner.
 */
const hideEditorHint = () => {
    const hint = document.getElementById('grad360-editor-hint');
    if (hint) {
        hint.style.display = 'none';
    }
};

/** @type {Function|null} Stored click handler reference for cleanup. */
let canvasClickHandler = null;

/** @type {Function|null} Stored mousedown handler on overlay for drag. */
let overlayMousedownHandler = null;

/** @type {Function|null} Stored mousemove handler for drag. */
let overlayMousemoveHandler = null;

/** @type {Function|null} Stored mouseup handler for drag. */
let overlayMouseupHandler = null;

/**
 * Enable click-to-place and hotspot editing listeners.
 */
const enableEditListeners = () => {
    // Click on canvas to place a new hotspot.
    canvasClickHandler = (e) => {
        // Only on the canvas, not on hotspot buttons.
        if (e.target !== viewerInstance.canvas) {
            return;
        }
        // Ignore if we just finished dragging.
        if (dragState && dragState.didDrag) {
            dragState = null;
            return;
        }
        const rect = container.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const coords = viewerInstance.screenToCoords(x, y);
        showModal(null, coords.pitch, coords.yaw);
    };
    viewerInstance.canvas.addEventListener('click', canvasClickHandler);

    // Override hotspot click to show edit modal instead of info popup.
    viewerInstance.onHotspotClick = (hs) => {
        if (editMode) {
            showModal(hs, hs.pitch, hs.yaw);
        }
    };

    // Drag support: mousedown on hotspot buttons in overlay.
    overlayMousedownHandler = (e) => {
        const hsBtn = e.target.closest('.grad360-hotspot');
        if (!hsBtn) {
            return;
        }
        const hsId = parseInt(hsBtn.dataset.hotspotId, 10);
        const hs = viewerInstance.hotspots.find((h) => h.id === hsId);
        if (!hs) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        dragState = {hs, startX: e.clientX, startY: e.clientY, didDrag: false};
    };

    overlayMousemoveHandler = (e) => {
        if (!dragState) {
            return;
        }
        const dx = e.clientX - dragState.startX;
        const dy = e.clientY - dragState.startY;
        if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
            dragState.didDrag = true;
        }
        if (dragState.didDrag) {
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const coords = viewerInstance.screenToCoords(x, y);
            dragState.hs.pitch = coords.pitch;
            dragState.hs.yaw = coords.yaw;
        }
    };

    overlayMouseupHandler = (e) => {
        if (!dragState) {
            return;
        }
        if (dragState.didDrag) {
            // Save new position.
            const hs = dragState.hs;
            saveHotspot(hs.id, hs.title, hs.text || '', hs.link || '', hs.pitch, hs.yaw, hs.icon, hs.required);
        }
        // Reset drag state after a brief delay so the click handler can check it.
        const wasDrag = dragState.didDrag;
        if (!wasDrag) {
            dragState = null;
        } else {
            setTimeout(() => {
                dragState = null;
            }, 50);
        }
        e.stopPropagation();
    };

    const overlay = viewerInstance.overlay;
    overlay.addEventListener('mousedown', overlayMousedownHandler);
    window.addEventListener('mousemove', overlayMousemoveHandler);
    window.addEventListener('mouseup', overlayMouseupHandler);
};

/**
 * Disable edit listeners and restore normal hotspot click behavior.
 */
const disableEditListeners = () => {
    if (canvasClickHandler) {
        viewerInstance.canvas.removeEventListener('click', canvasClickHandler);
        canvasClickHandler = null;
    }
    if (overlayMousedownHandler) {
        viewerInstance.overlay.removeEventListener('mousedown', overlayMousedownHandler);
        overlayMousedownHandler = null;
    }
    if (overlayMousemoveHandler) {
        window.removeEventListener('mousemove', overlayMousemoveHandler);
        overlayMousemoveHandler = null;
    }
    if (overlayMouseupHandler) {
        window.removeEventListener('mouseup', overlayMouseupHandler);
        overlayMouseupHandler = null;
    }

    // Restore normal hotspot click (will be set by view.js init again if needed).
    viewerInstance.onHotspotClick = null;
    dragState = null;
};

/**
 * Remove any open modal.
 */
const removeModal = () => {
    const existing = container.querySelector('.grad360-editor-modal');
    if (existing) {
        existing.remove();
    }
};

/**
 * Show the hotspot editor modal.
 *
 * @param {object|null} hs Existing hotspot data or null for new.
 * @param {number} pitch Pitch coordinate.
 * @param {number} yaw Yaw coordinate.
 */
const showModal = (hs, pitch, yaw) => {
    removeModal();

    const isNew = !hs;
    const modal = document.createElement('div');
    modal.className = 'grad360-editor-modal';
    modal.setAttribute('role', 'dialog');

    const titleVal = hs ? escapeAttr(hs.title || '') : '';
    const linkVal = hs ? escapeAttr(hs.link || '') : '';
    const iconVal = hs ? hs.icon : 'info';
    const reqVal = hs ? hs.required : false;

    // Build form synchronously with fallback labels, then load translated strings.
    modal.innerHTML = `
        <div class="grad360-editor-modal-content">
            <h5 class="grad360-editor-modal-title">${isNew ? 'New hotspot' : 'Edit hotspot'}</h5>
            <div class="form-group mb-2">
                <label for="grad360-ed-title" class="form-label">Title</label>
                <input type="text" id="grad360-ed-title" class="form-control" value="${titleVal}" required>
            </div>
            <div class="form-group mb-2">
                <label for="grad360-ed-text" class="form-label">Text</label>
                <textarea id="grad360-ed-text" class="form-control" rows="3">${hs ? escapeHtml(hs.text || '') : ''}</textarea>
            </div>
            <div class="form-group mb-2">
                <label for="grad360-ed-link" class="form-label">Link</label>
                <input type="url" id="grad360-ed-link" class="form-control" value="${linkVal}">
            </div>
            <div class="form-group mb-2">
                <label for="grad360-ed-icon" class="form-label">Icon</label>
                <select id="grad360-ed-icon" class="form-control">
                    <option value="info" ${iconVal === 'info' ? 'selected' : ''}>ℹ Info</option>
                    <option value="link" ${iconVal === 'link' ? 'selected' : ''}>🔗 Link</option>
                    <option value="arrow" ${iconVal === 'arrow' ? 'selected' : ''}>➡ Arrow</option>
                </select>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" id="grad360-ed-required" class="form-check-input"
                    ${reqVal ? 'checked' : ''}>
                <label for="grad360-ed-required" class="form-check-label">Required</label>
            </div>
            <div class="grad360-editor-modal-actions">
                <button type="button" id="grad360-ed-save" class="btn btn-primary mr-2">Save</button>
                ${!isNew ? '<button type="button" id="grad360-ed-delete" class="btn btn-danger mr-2">Delete</button>' : ''}
                <button type="button" id="grad360-ed-cancel" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    `;

    container.appendChild(modal);

    // Load translated strings for labels.
    loadModalStrings(modal, isNew);

    // Focus the title field.
    const titleInput = modal.querySelector('#grad360-ed-title');
    titleInput.focus();

    // Save handler.
    modal.querySelector('#grad360-ed-save').addEventListener('click', () => {
        const title = titleInput.value.trim();
        if (!title) {
            titleInput.classList.add('is-invalid');
            titleInput.focus();
            return;
        }
        const text = modal.querySelector('#grad360-ed-text').value.trim();
        const link = modal.querySelector('#grad360-ed-link').value.trim();
        const icon = modal.querySelector('#grad360-ed-icon').value;
        const required = modal.querySelector('#grad360-ed-required').checked;
        const hotspotId = hs ? hs.id : 0;

        saveHotspot(hotspotId, title, text, link, pitch, yaw, icon, required).then((result) => {
            if (result.success) {
                if (isNew) {
                    // Add to viewer's hotspot list.
                    viewerInstance.hotspots.push({
                        id: result.hotspotid,
                        title: title,
                        text: text,
                        link: link,
                        pitch: pitch,
                        yaw: yaw,
                        icon: icon,
                        required: required,
                        visited: false,
                    });
                } else {
                    // Update existing hotspot data.
                    hs.title = title;
                    hs.text = text;
                    hs.link = link;
                    hs.icon = icon;
                    hs.required = required;
                }
                removeModal();
            }
            return;
        }).catch(Notification.exception);
    });

    // Delete handler.
    const deleteBtn = modal.querySelector('#grad360-ed-delete');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            getString('editor_delete_confirm', 'mod_grad360').then((confirmMsg) => {
                if (window.confirm(confirmMsg)) {
                    deleteHotspot(hs.id).then((result) => {
                        if (result.success) {
                            // Remove from viewer's hotspot list.
                            const idx = viewerInstance.hotspots.findIndex((h) => h.id === hs.id);
                            if (idx !== -1) {
                                viewerInstance.hotspots.splice(idx, 1);
                            }
                            removeModal();
                        }
                        return;
                    }).catch(Notification.exception);
                }
                return;
            }).catch(Notification.exception);
        });
    }

    // Cancel handler.
    modal.querySelector('#grad360-ed-cancel').addEventListener('click', () => {
        removeModal();
    });
};

/**
 * Load translated strings into the modal form.
 *
 * @param {HTMLElement} modal The modal element.
 * @param {boolean} isNew Whether this is a new hotspot.
 */
const loadModalStrings = (modal, isNew) => {
    const stringKeys = [
        {key: 'editor_modal_title_new', component: 'mod_grad360'},
        {key: 'editor_modal_title_edit', component: 'mod_grad360'},
        {key: 'hotspot_title', component: 'mod_grad360'},
        {key: 'hotspot_text', component: 'mod_grad360'},
        {key: 'hotspot_link', component: 'mod_grad360'},
        {key: 'hotspot_icon', component: 'mod_grad360'},
        {key: 'hotspot_required', component: 'mod_grad360'},
        {key: 'editor_save', component: 'mod_grad360'},
        {key: 'editor_delete', component: 'mod_grad360'},
        {key: 'editor_cancel', component: 'mod_grad360'},
    ];

    const promises = stringKeys.map((s) => getString(s.key, s.component));
    Promise.all(promises).then((strings) => {
        const [titleNew, titleEdit, labelTitle, labelText, labelLink, labelIcon, labelRequired,
            btnSave, btnDelete, btnCancel] = strings;
        const heading = modal.querySelector('.grad360-editor-modal-title');
        if (heading) {
            heading.textContent = isNew ? titleNew : titleEdit;
        }
        const labels = modal.querySelectorAll('.form-label');
        if (labels[0]) {
            labels[0].textContent = labelTitle;
        }
        if (labels[1]) {
            labels[1].textContent = labelText;
        }
        if (labels[2]) {
            labels[2].textContent = labelLink;
        }
        if (labels[3]) {
            labels[3].textContent = labelIcon;
        }
        const checkLabel = modal.querySelector('.form-check-label');
        if (checkLabel) {
            checkLabel.textContent = labelRequired;
        }
        const saveBtn = modal.querySelector('#grad360-ed-save');
        if (saveBtn) {
            saveBtn.textContent = btnSave;
        }
        const delBtn = modal.querySelector('#grad360-ed-delete');
        if (delBtn) {
            delBtn.textContent = btnDelete;
        }
        const cancelBtn = modal.querySelector('#grad360-ed-cancel');
        if (cancelBtn) {
            cancelBtn.textContent = btnCancel;
        }
        return;
    }).catch(Notification.exception);
};

/**
 * Save a hotspot via AJAX.
 *
 * @param {number} hotspotid 0 for new.
 * @param {string} title Title.
 * @param {string} text Content text.
 * @param {string} link Link URL.
 * @param {number} pitch Pitch.
 * @param {number} yaw Yaw.
 * @param {string} icon Icon type.
 * @param {boolean} required Whether required.
 * @return {Promise}
 */
const saveHotspot = (hotspotid, title, text, link, pitch, yaw, icon, required) => {
    return Ajax.call([{
        methodname: 'mod_grad360_save_hotspot',
        args: {
            cmid: cmid,
            hotspotid: hotspotid,
            title: title,
            content: text,
            link: link,
            pitch: pitch,
            yaw: yaw,
            icon: icon,
            required: required,
        },
    }])[0];
};

/**
 * Delete a hotspot via AJAX.
 *
 * @param {number} hotspotid Hotspot ID.
 * @return {Promise}
 */
const deleteHotspot = (hotspotid) => {
    return Ajax.call([{
        methodname: 'mod_grad360_delete_hotspot',
        args: {
            cmid: cmid,
            hotspotid: hotspotid,
        },
    }])[0];
};

/**
 * Escape HTML entities.
 *
 * @param {string} str The string.
 * @return {string}
 */
const escapeHtml = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

/**
 * Escape for use in HTML attributes.
 *
 * @param {string} str The string.
 * @return {string}
 */
const escapeAttr = (str) => {
    return escapeHtml(str).replace(/"/g, '&quot;');
};
