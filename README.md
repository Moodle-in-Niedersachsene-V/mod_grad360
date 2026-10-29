# mod_grad360 – 360° Viewer Activity

A Moodle activity module that embeds interactive 360° panorama images or object rotation views with clickable hotspots. Students explore the scene and discover required hotspots to complete the activity and earn grades.

## Features

- **Panorama mode**: Displays equirectangular 360° images in a WebGL-based viewer with mouse/touch drag navigation and zoom
- **Object rotation mode**: Displays a series of images that users drag through to simulate rotating an object
- **Interactive hotspots**: Clickable points with title, description text, optional link, and custom icon
- **Progress tracking**: Tracks which hotspots each student has visited
- **Automatic grading**: Grade calculated based on percentage of required hotspots visited
- **Activity completion**: Custom completion rule requiring all required hotspots to be visited
- **Teacher report**: Overview of all students' progress across hotspots
- **Accessible**: Hotspot list with status indicators alongside the visual viewer
- **Auto-rotate / Auto-play**: Optional automatic rotation of panorama or rotation sequence

## Requirements

- Moodle 5.1 or later
- PHP 8.2, 8.3 or 8.4
- MariaDB, MySQL or PostgreSQL

## Installation

1. Extract the plugin archive
2. Copy the `grad360` folder into your Moodle installation's `mod/` directory
3. Visit the Moodle admin notifications page to complete the installation
4. The plugin will create the necessary database tables automatically

## Usage

### Creating an activity

1. Turn editing on in your course
2. Click "Add an activity or resource"
3. Select "360° Viewer"
4. Choose the viewer mode (Panorama or Object rotation)
5. Provide the panorama image URL or upload a file (panorama mode), or enter image URLs (rotation mode)
6. Define hotspots with positions, descriptions, and required/optional status
7. Configure grading and completion settings as needed

### Hotspot positioning

- **Panorama mode**: Hotspots are positioned using pitch (vertical angle, -90 to 90) and yaw (horizontal angle, 0 to 360)
- **Rotation mode**: Pitch and yaw are used as percentage-based Y and X coordinates on the image

### Student view

Students see the interactive viewer with hotspots. Clicking a hotspot shows its information and records the visit. A progress bar shows how many required hotspots have been found.

### Teacher report

Teachers can view a progress report showing which hotspots each student has visited, with an overall completion percentage.

## License

This plugin is licensed under the GNU General Public License v3 or later.
See [LICENSE](http://www.gnu.org/copyleft/gpl.html) for details.

## Maintainer

Moodle in Niedersachsen e. V.
