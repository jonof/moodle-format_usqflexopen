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
 * USQ Flexi course format
 *
 * @package    format_usqflexopen
 * @copyright  2016 The University of Southern Queensland
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../../config.php';

use format_usqflexopen\format_helper;

$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);

$PAGE->set_url('/course/format/usqflexopen/createsection.php', ['id' => $id, 'type' => $type]);

require_login($id);
require_capability('moodle/course:update', $PAGE->context);
require_capability('moodle/course:manageactivities', $PAGE->context);

$returnurl = course_get_url($COURSE);

if (!format_helper::is_valid_section_type($type)) {
    die('invalid section type');
}

$helper = new format_helper($COURSE);

$section = $helper->create_section($type);

redirect($returnurl);
