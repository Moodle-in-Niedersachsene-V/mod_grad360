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
 * WebGL panorama viewer for mod_grad360.
 *
 * Renders equirectangular panorama images in a WebGL canvas
 * with mouse/touch drag navigation and hotspot support.
 *
 * @module     mod_grad360/viewer
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const VERTEX_SHADER_SRC = `
attribute vec4 aPosition;
attribute vec2 aTexCoord;
uniform mat4 uProjection;
uniform mat4 uModelView;
varying highp vec2 vTexCoord;
void main() {
    gl_Position = uProjection * uModelView * aPosition;
    vTexCoord = aTexCoord;
}`;

const FRAGMENT_SHADER_SRC = `
precision highp float;
varying highp vec2 vTexCoord;
uniform sampler2D uSampler;
void main() {
    gl_FragColor = texture2D(uSampler, vTexCoord);
}`;

/**
 * Panorama viewer class.
 */
export default class Viewer {

    /**
     * @param {HTMLElement} container The container element.
     * @param {object} config Configuration object.
     */
    constructor(container, config) {
        this.container = container;
        this.config = config;
        this.pitch = 0;
        this.yaw = 0;
        this.hfov = config.hfov || 100;
        this.dragging = false;
        this.lastX = 0;
        this.lastY = 0;
        this.autoRotateSpeed = 0.05;
        this.autoRotating = config.autorotate || false;
        this.hotspots = config.hotspots || [];
        this.onHotspotClick = null;
        this.animationId = null;

        this._init();
    }

    /**
     * Initialize the viewer.
     */
    _init() {
        // Create canvas.
        this.canvas = document.createElement('canvas');
        this.canvas.style.width = '100%';
        this.canvas.style.height = '100%';
        this.canvas.style.display = 'block';
        this.container.appendChild(this.canvas);

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

        // Init WebGL.
        this.gl = this.canvas.getContext('webgl') || this.canvas.getContext('experimental-webgl');
        if (!this.gl) {
            this.container.innerHTML = '<p class="alert alert-danger">WebGL is not supported.</p>';
            return;
        }

        this._initShaders();
        this._initSphere();
        this._resize();
        this._bindEvents();
        this._loadTexture(this.config.image);
    }

    /**
     * Initialize shaders.
     */
    _initShaders() {
        const gl = this.gl;

        const vs = gl.createShader(gl.VERTEX_SHADER);
        gl.shaderSource(vs, VERTEX_SHADER_SRC);
        gl.compileShader(vs);

        const fs = gl.createShader(gl.FRAGMENT_SHADER);
        gl.shaderSource(fs, FRAGMENT_SHADER_SRC);
        gl.compileShader(fs);

        this.program = gl.createProgram();
        gl.attachShader(this.program, vs);
        gl.attachShader(this.program, fs);
        gl.linkProgram(this.program);
        gl.useProgram(this.program);

        this.aPosition = gl.getAttribLocation(this.program, 'aPosition');
        this.aTexCoord = gl.getAttribLocation(this.program, 'aTexCoord');
        this.uProjection = gl.getUniformLocation(this.program, 'uProjection');
        this.uModelView = gl.getUniformLocation(this.program, 'uModelView');
        this.uSampler = gl.getUniformLocation(this.program, 'uSampler');
    }

    /**
     * Create the sphere geometry for equirectangular mapping.
     */
    _initSphere() {
        const gl = this.gl;
        const latBands = 40;
        const lonBands = 80;
        const positions = [];
        const texCoords = [];
        const indices = [];

        for (let lat = 0; lat <= latBands; lat++) {
            const theta = (lat * Math.PI) / latBands;
            const sinTheta = Math.sin(theta);
            const cosTheta = Math.cos(theta);

            for (let lon = 0; lon <= lonBands; lon++) {
                const phi = (lon * 2 * Math.PI) / lonBands;
                const sinPhi = Math.sin(phi);
                const cosPhi = Math.cos(phi);

                const x = cosPhi * sinTheta;
                const y = cosTheta;
                const z = sinPhi * sinTheta;

                // Invert so we look from inside the sphere.
                positions.push(-x, y, z);
                texCoords.push(1 - lon / lonBands, lat / latBands);
            }
        }

        for (let lat = 0; lat < latBands; lat++) {
            for (let lon = 0; lon < lonBands; lon++) {
                const first = lat * (lonBands + 1) + lon;
                const second = first + lonBands + 1;
                indices.push(first, second, first + 1);
                indices.push(second, second + 1, first + 1);
            }
        }

        this.positionBuffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, this.positionBuffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(positions), gl.STATIC_DRAW);

        this.texCoordBuffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, this.texCoordBuffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(texCoords), gl.STATIC_DRAW);

        this.indexBuffer = gl.createBuffer();
        gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.indexBuffer);
        gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array(indices), gl.STATIC_DRAW);

        this.indexCount = indices.length;
    }

    /**
     * Load the panorama texture.
     *
     * @param {string} url Image URL.
     */
    _loadTexture(url) {
        const gl = this.gl;
        this.texture = gl.createTexture();

        // Placeholder pixel.
        gl.bindTexture(gl.TEXTURE_2D, this.texture);
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, 1, 1, 0, gl.RGBA, gl.UNSIGNED_BYTE,
            new Uint8Array([128, 128, 128, 255]));

        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            gl.bindTexture(gl.TEXTURE_2D, this.texture);
            gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, img);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
            this._render();
        };
        img.src = url;

        this._startAnimation();
    }

    /**
     * Handle canvas resize.
     */
    _resize() {
        const rect = this.container.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = rect.width * dpr;
        this.canvas.height = rect.height * dpr;
        if (this.gl) {
            this.gl.viewport(0, 0, this.canvas.width, this.canvas.height);
        }
    }

    /**
     * Bind mouse and touch events.
     */
    _bindEvents() {
        window.addEventListener('resize', () => this._resize());

        // Mouse events.
        this.canvas.addEventListener('mousedown', (e) => {
            this.dragging = true;
            this.lastX = e.clientX;
            this.lastY = e.clientY;
            this.autoRotating = false;
        });

        window.addEventListener('mousemove', (e) => {
            if (!this.dragging) {
                return;
            }
            const dx = e.clientX - this.lastX;
            const dy = e.clientY - this.lastY;
            this.yaw -= dx * 0.3;
            this.pitch += dy * 0.3;
            this.pitch = Math.max(-85, Math.min(85, this.pitch));
            this.lastX = e.clientX;
            this.lastY = e.clientY;
        });

        window.addEventListener('mouseup', () => {
            this.dragging = false;
        });

        // Touch events.
        this.canvas.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                this.dragging = true;
                this.lastX = e.touches[0].clientX;
                this.lastY = e.touches[0].clientY;
                this.autoRotating = false;
            }
        }, {passive: true});

        this.canvas.addEventListener('touchmove', (e) => {
            if (!this.dragging || e.touches.length !== 1) {
                return;
            }
            e.preventDefault();
            const dx = e.touches[0].clientX - this.lastX;
            const dy = e.touches[0].clientY - this.lastY;
            this.yaw -= dx * 0.3;
            this.pitch += dy * 0.3;
            this.pitch = Math.max(-85, Math.min(85, this.pitch));
            this.lastX = e.touches[0].clientX;
            this.lastY = e.touches[0].clientY;
        }, {passive: false});

        this.canvas.addEventListener('touchend', () => {
            this.dragging = false;
        });

        // Mouse wheel for zoom.
        this.canvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            this.hfov += e.deltaY * 0.05;
            this.hfov = Math.max(30, Math.min(150, this.hfov));
        }, {passive: false});
    }

    /**
     * Start the animation loop.
     */
    _startAnimation() {
        const animate = () => {
            if (this.autoRotating) {
                this.yaw += this.autoRotateSpeed;
            }
            this._render();
            this._updateHotspots();
            this.animationId = requestAnimationFrame(animate);
        };
        animate();
    }

    /**
     * Create the perspective projection matrix.
     *
     * @param {number} fov Field of view in degrees.
     * @param {number} aspect Aspect ratio.
     * @param {number} near Near clipping plane.
     * @param {number} far Far clipping plane.
     * @return {Float32Array}
     */
    _perspective(fov, aspect, near, far) {
        const f = 1.0 / Math.tan((fov * Math.PI) / 360);
        const nf = 1 / (near - far);
        return new Float32Array([
            f / aspect, 0, 0, 0,
            0, f, 0, 0,
            0, 0, (far + near) * nf, -1,
            0, 0, 2 * far * near * nf, 0,
        ]);
    }

    /**
     * Create the model-view matrix based on pitch and yaw.
     *
     * @return {Float32Array}
     */
    _getModelViewMatrix() {
        const pitchRad = (this.pitch * Math.PI) / 180;
        const yawRad = (this.yaw * Math.PI) / 180;

        const cp = Math.cos(pitchRad);
        const sp = Math.sin(pitchRad);
        const cy = Math.cos(yawRad);
        const sy = Math.sin(yawRad);

        // Combined rotation: pitch then yaw.
        return new Float32Array([
            cy, sp * sy, -cp * sy, 0,
            0, cp, sp, 0,
            sy, -sp * cy, cp * cy, 0,
            0, 0, 0, 1,
        ]);
    }

    /**
     * Render a frame.
     */
    _render() {
        const gl = this.gl;
        if (!gl) {
            return;
        }

        gl.clearColor(0, 0, 0, 1);
        // eslint-disable-next-line no-bitwise
        gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
        gl.enable(gl.DEPTH_TEST);

        const aspect = this.canvas.width / this.canvas.height;
        const projection = this._perspective(this.hfov, aspect, 0.1, 100);
        const modelView = this._getModelViewMatrix();

        gl.uniformMatrix4fv(this.uProjection, false, projection);
        gl.uniformMatrix4fv(this.uModelView, false, modelView);

        gl.bindBuffer(gl.ARRAY_BUFFER, this.positionBuffer);
        gl.enableVertexAttribArray(this.aPosition);
        gl.vertexAttribPointer(this.aPosition, 3, gl.FLOAT, false, 0, 0);

        gl.bindBuffer(gl.ARRAY_BUFFER, this.texCoordBuffer);
        gl.enableVertexAttribArray(this.aTexCoord);
        gl.vertexAttribPointer(this.aTexCoord, 2, gl.FLOAT, false, 0, 0);

        gl.activeTexture(gl.TEXTURE0);
        gl.bindTexture(gl.TEXTURE_2D, this.texture);
        gl.uniform1i(this.uSampler, 0);

        gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.indexBuffer);
        gl.drawElements(gl.TRIANGLES, this.indexCount, gl.UNSIGNED_SHORT, 0);
    }

    /**
     * Project a 3D point (pitch, yaw) to 2D screen coordinates.
     *
     * @param {number} hsPitch Hotspot pitch in degrees.
     * @param {number} hsYaw Hotspot yaw in degrees.
     * @return {object|null} {x, y} screen coordinates or null if behind camera.
     */
    _projectToScreen(hsPitch, hsYaw) {
        const pitchRad = (hsPitch * Math.PI) / 180;
        const yawRad = (hsYaw * Math.PI) / 180;

        // Convert spherical to cartesian.
        const x = -Math.cos(pitchRad) * Math.sin(yawRad);
        const y = Math.sin(pitchRad);
        const z = Math.cos(pitchRad) * Math.cos(yawRad);

        // Apply camera rotation.
        const mv = this._getModelViewMatrix();
        const cx = mv[0] * x + mv[4] * y + mv[8] * z;
        const cy = mv[1] * x + mv[5] * y + mv[9] * z;
        const cz = mv[2] * x + mv[6] * y + mv[10] * z;

        // Behind camera check.
        if (cz >= 0) {
            return null;
        }

        // Apply projection.
        const aspect = this.canvas.width / this.canvas.height;
        const f = 1.0 / Math.tan((this.hfov * Math.PI) / 360);

        const px = (cx * (f / aspect)) / -cz;
        const py = (cy * f) / -cz;

        // Convert to pixel coordinates.
        const rect = this.container.getBoundingClientRect();
        const screenX = ((px + 1) / 2) * rect.width;
        const screenY = ((1 - py) / 2) * rect.height;

        return {x: screenX, y: screenY};
    }

    /**
     * Convert screen pixel coordinates to spherical (pitch, yaw) coordinates.
     *
     * This is the inverse of _projectToScreen: given a click position on the
     * container, it returns the pitch and yaw of the point on the sphere.
     *
     * @param {number} screenX X pixel position relative to container.
     * @param {number} screenY Y pixel position relative to container.
     * @return {object} {pitch, yaw} in degrees.
     */
    screenToCoords(screenX, screenY) {
        const rect = this.container.getBoundingClientRect();

        // Convert pixel coordinates to NDC (-1 to 1).
        const ndcX = (screenX / rect.width) * 2 - 1;
        const ndcY = 1 - (screenY / rect.height) * 2;

        // Unproject using the inverse of the projection.
        const aspect = this.canvas.width / this.canvas.height;
        const f = 1.0 / Math.tan((this.hfov * Math.PI) / 360);

        // Ray direction in camera space (pointing into -Z).
        const dirX = ndcX * aspect / f;
        const dirY = ndcY / f;
        const dirZ = -1;

        // Normalize.
        const len = Math.sqrt(dirX * dirX + dirY * dirY + dirZ * dirZ);
        const nx = dirX / len;
        const ny = dirY / len;
        const nz = dirZ / len;

        // Inverse of model-view rotation to get world-space direction.
        // The model-view matrix is orthogonal, so its inverse is its transpose.
        const mv = this._getModelViewMatrix();
        const wx = mv[0] * nx + mv[1] * ny + mv[2] * nz;
        const wy = mv[4] * nx + mv[5] * ny + mv[6] * nz;
        const wz = mv[8] * nx + mv[9] * ny + mv[10] * nz;

        // Convert cartesian back to spherical (pitch, yaw).
        // Matches the convention in _projectToScreen:
        // x = -cos(pitch)*sin(yaw), y = sin(pitch), z = cos(pitch)*cos(yaw)
        const pitch = Math.asin(wy) * 180 / Math.PI;
        const yaw = Math.atan2(-wx, wz) * 180 / Math.PI;

        return {pitch, yaw};
    }

    /**
     * Update hotspot positions on the overlay.
     */
    _updateHotspots() {
        // Remove old elements.
        while (this.overlay.firstChild) {
            this.overlay.removeChild(this.overlay.firstChild);
        }

        const rect = this.container.getBoundingClientRect();

        this.hotspots.forEach((hs) => {
            const pos = this._projectToScreen(hs.pitch, hs.yaw);
            if (!pos) {
                return;
            }

            // Check if within viewport bounds.
            if (pos.x < -20 || pos.x > rect.width + 20 || pos.y < -20 || pos.y > rect.height + 20) {
                return;
            }

            const el = this._createHotspotElement(hs);
            el.style.left = pos.x + 'px';
            el.style.top = pos.y + 'px';
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

        // Icon mapping.
        const icons = {
            info: 'ℹ',
            link: '🔗',
            arrow: '➡',
        };
        el.textContent = icons[hs.icon] || icons.info;

        // Mark as visited.
        if (hs.visited) {
            el.classList.add('visited');
        }

        // Title attribute.
        el.title = hs.title || '';
        el.setAttribute('aria-label', hs.title || '');

        // Click handler.
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
    }

    /**
     * Destroy the viewer and clean up resources.
     */
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        if (this.gl) {
            this.gl.getExtension('WEBGL_lose_context')?.loseContext();
        }
    }
}
