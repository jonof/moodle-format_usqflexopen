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

namespace format_usqflexopen;

defined('MOODLE_INTERNAL') || die;

use stdClass;
use context_course;
use context_module;
use local_usqcourseassess\courseassess;
use local_usqcourseassess\assessment_item;
use coding_exception;

/**
 * Helper class for section manipulations, upconversion from the old methods.
 * @author Jonathon Fowler <fowlerj@usq.edu.au>
 */
class format_helper {
    /**
     * The course id.
     * @var integer
     */
    private $courseid;

    /**
     * The course context.
     * @var context_course
     */
    private $context;

    /**
     * The course format.
     * @var format_base
     */
    private $courseformat;

    /**
     * The number of the last section in the course.
     * @var integer
     */
    private $lastsection;

    /**
     * Running counter of where new sections get inserted.
     * @var integer
     */
    private $sectioninsert = 1;

    /**
     * Constructor.
     * @param object $course the course
     */
    public function __construct($course) {
        global $CFG, $DB;

        require_once $CFG->dirroot . '/course/lib.php';
        require_once $CFG->dirroot . '/course/modlib.php';

        $this->courseid = $course->id;
        $this->context = context_course::instance($course->id);

        $this->courseformat = course_get_format($course);

        $this->lastsection = $this->courseformat->get_last_section_number();
    }

    /**
     * Checks that the passed section type is one we recognise.
     * @param string
     * @return boolean
     */
    public static function is_valid_section_type($type) {
        return in_array($type, ['default', 'week', 'topic', 'assess', 'getstarted']);
    }

    /**
     * Tests whether a course has at least one section of the given type.
     * @param object the course
     * @param string the section type to check
     * @return boolean
     */
    public static function has_section($course, $type) {
        $courseformatoptions = course_get_format($course)->get_format_options();
        $defaultsectiontype = $courseformatoptions['defaultsectiontype'];

        foreach (get_fast_modinfo($course)->get_section_info_all() as $sectinfo) {
            if ($sectinfo->sectiontype === $type || ($sectinfo->sectiontype === 'default' &&
                    $defaultsectiontype === $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetches the course record, because we can't rely on a cached copy.
     * @return object
     */
    private function get_course() {
        global $DB;
        return $DB->get_record('course', ['id' => $this->courseid], '*', MUST_EXIST);
    }

    /**
     * Creates a new section in the course.
     * @param string $sectiontype
     * @return section_info
     */
    public function create_section($sectiontype) {
        $this->lastsection += 1;
        course_create_sections_if_missing($this->courseid, $this->lastsection);

        // Set the section details. get_fast_modinfo() reloads new data.
        $info = get_fast_modinfo($this->courseid)->get_section_info($this->lastsection);
        $data = array(
            'id' => $info->id,
            'sectiontype' => $sectiontype,
        );
        $this->courseformat->update_section_format_options($data);

        // Move the section into place.
        if (!move_section_to($this->get_course(), $this->lastsection, $this->sectioninsert, true)) {
            throw new coding_exception('move_section_to failed');
        }

        // Get the final section information and prepare for the next one.
        $info = get_fast_modinfo($this->courseid)->get_section_info($this->sectioninsert);
        $this->sectioninsert += 1;
        return $info;
    }
}
