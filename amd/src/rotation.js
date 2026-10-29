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
 * Object rotation viewer for mod_grad360.
 *
 * Displays a sequence of images that the user can drag through
 * to simulate rotating an object.
 *
 * @module     mod_grad360/rotation
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Rotation viewer class.
 */
export default class RotationViewer {

    /**
     * @param {HTMLElement} container The container element.
     * @param {object} config Configuration object.
     */
    constructor(container, config) {
        this.container = container;
        this.config = config;
        this.images = config.images || [];
        this.currentIndex = 0;
        this.dragging = false;
        this.lastX = 0;
        this.autoplay = config.autoplay || false;
        this.speed = config.speed || 100; // Ms per frame.
        this.loadedImages = [];
        this.autoplayTimer = null;
        this.hotspots = config.hotspots || [];
        this.onHotspotClick = null;

        this._init();
    }

    /**
     * Initialize the rotation viewer.
     */
    _init() {
        // Create image display.
        this.imageEl = document.createElement('img');
        this.imageEl.style.width = '100%';
        this.imageEl.style.height = '100%';
        this.imageEl.style.objectFit = 'contain';
        this.imageEl.style.userSelect = 'none';
        this.imageEl.draggable = false;
        this.container.appendChild(this.imageEl);

        // Create hotspot overlay.
        this.overlay = document.createElement('div');
        this.overlay.style.position = 'absolute';
        this.overlay.style.top = '0';
        this.overlay.style.left = '0';
        this.overlay.style.width = '100%';
        this.overlay.style.height = '100%';
        this.overlay.style.pointerEvents = 'none';
        this.container.style.position = 'relative';
        this.container.appendChild(this.overlay);

        this._bindEvents();
        this._preloadImages();
    }

    /**
     * Preload all images.
     */
    _preloadImages() {
        let loaded = 0;
        const total = this.images.length;

        if (total === 0) {
            return;
        }

        this.images.forEach((url, i) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                loaded++;
                this.loadedImages[i] = img;
                if (loaded === 1) {
                    this._showFrame(0);
                }
                if (loaded === total && this.autoplay) {
                    this._startAutoplay();
                }
            };
            img.onerror = () => {
                loaded++;
            };
            img.src = url;
        });
    }

    /**
     * Show a specific frame.
     *
     * @param {number} index Frame index.
     */
    _showFrame(index) {
        this.currentIndex = ((index % this.images.length) + this.images.length) % this.images.length;
        if (this.loadedImages[this.currentIndex]) {
            this.imageEl.src = this.loadedImages[this.currentIndex].src;
        } else {
            this.imageEl.src = this.images[this.currentIndex];
        }
        this._updateHotspots();
    }

    /**
     * Bind mouse and touch events.
     */
    _bindEvents() {
        this.container.addEventListener('mousedown', (e) => {
            this.dragging = true;
            this.lastX = e.clientX;
            this._stopAutoplay();
            e.preventDefault();
        });

        window.addEventListener('mousemove', (e) => {
            if (!this.dragging) {
                return;
            }
            const dx = e.clientX - this.lastX;
            const sensitivity = 5; // Pixels per frame.
            if (Math.abs(dx) >= sensitivity) {
                const steps = Math.floor(Math.abs(dx) / sensitivity);
                const direction = dx > 0 ? 1 : -1;
                this._showFrame(this.currentIndex + direction * steps);
                this.lastX = e.clientX;
            }
        });

        window.addEventListener('mouseup', () => {
            this.dragging = false;
        });

        // Touch events.
        this.container.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                this.dragging = true;
                this.lastX = e.touches[0].clientX;
                this._stopAutoplay();
            }
        }, {passive: true});

        this.container.addEventListener('touchmove', (e) => {
            if (!this.dragging || e.touches.length !== 1) {
                return;
            }
            e.preventDefault();
            const dx = e.touches[0].clientX - this.lastX;
            const sensitivity = 5;
            if (Math.abs(dx) >= sensitivity) {
                const steps = Math.floor(Math.abs(dx) / sensitivity);
                const direction = dx > 0 ? 1 : -1;
                this._showFrame(this.currentIndex + direction * steps);
                this.lastX = e.touches[0].clientX;
            }
        }, {passive: false});

        this.container.addEventListener('touchend', () => {
            this.dragging = false;
        });
    }

    /**
     * Start autoplay animation.
     */
    _startAutoplay() {
        this._stopAutoplay();
        this.autoplayTimer = setInterval(() => {
            this._showFrame(this.currentIndex + 1);
        }, this.speed);
    }

    /**
     * Stop autoplay animation.
     */
    _stopAutoplay() {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    /**
     * Update hotspot positions on the overlay.
     * For rotation mode, hotspots use percentage-based positioning.
     */
    _updateHotspots() {
        while (this.overlay.firstChild) {
            this.overlay.removeChild(this.overlay.firstChild);
        }

        this.hotspots.forEach((hs) => {
            // In rotation mode, pitch and yaw are used as Y% and X%.
            const el = this._createHotspotElement(hs);
            el.style.left = hs.yaw + '%';
            el.style.top = (50 - hs.pitch) + '%';
            this.overlay.appendChild(el);
        });
    }

    /**
     * Create a hotspot DOM element.
     *
     * @param {object} hs Hotspot data.
     * @return {HTMLElement}
     */
    _createHotspotElement(hs) {
        const el = document.createElement('button');
        el.className = 'grad360-hotspot';
        el.style.position = 'absolute';
        el.style.transform = 'translate(-50%, -50%)';
        el.style.pointerEvents = 'auto';
        el.style.cursor = 'pointer';
        el.dataset.hotspotId = hs.id;

        const icons = {
            info: 'ℹ',
            link: '🔗',
            arrow: '➡',
        };
        el.textContent = icons[hs.icon] || icons.info;

        if (hs.visited) {
            el.classList.add('visited');
        }

        el.title = hs.title || '';
        el.setAttribute('aria-label', hs.title || '');

        el.addEventListener('click', (e) => {
            e.stopPropagation();
            if (this.onHotspotClick) {
                this.onHotspotClick(hs);
            }
        });

        return el;
    }

    /**
     * Mark a hotspot as visited.
     *
     * @param {number} hotspotId The hotspot ID.
     */
    markVisited(hotspotId) {
        this.hotspots.forEach((hs) => {
            if (hs.id === hotspotId) {
                hs.visited = true;
            }
        });
        this._updateHotspots();
    }

    /**
     * Destroy the viewer and clean up.
     */
    destroy() {
        this._stopAutoplay();
    }
}
