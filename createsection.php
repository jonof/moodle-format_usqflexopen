<?php

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

$sectiontypes = ['default', 'week', 'topic', 'assess', 'getstarted'];

$PAGE->set_url('/course/format/usqflexopen/createsection.php', ['id' => $id, 'type' => $type]);

require_login($id);
require_capability('moodle/course:update', $PAGE->context);
require_capability('moodle/course:manageactivities', $PAGE->context);

$returnurl = course_get_url($COURSE);

if (!in_array($type, $sectiontypes)) {
    die('invalid section type');
}

$modinfo = get_fast_modinfo($COURSE);
$courseformat = course_get_format($COURSE);

$lastsection = max(array_keys($modinfo->get_section_info_all())) + 1;
course_create_sections_if_missing($COURSE, $lastsection);

// Increase the course section count.
$formatopts = $courseformat->get_format_options();
$numsections = $formatopts['numsections'] + 1;
$data = array(
    'numsections' => $numsections,
);
$courseformat->update_course_format_options($data);

// Set the section details.
$modinfo = get_fast_modinfo($COURSE);   // Reload.
$info = $modinfo->get_section_info($lastsection);
$data = array(
    'id' => $info->id,
    'sectiontype' => $type,
);
$courseformat->update_section_format_options($data);

// Move the section into place.
if (!move_section_to($COURSE, $lastsection, 1)) {
    throw new coding_exception('move_section_to failed');
}

redirect($returnurl);
