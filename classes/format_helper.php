<?php

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
     * The course.
     * @var object
     */
    private $course;

    /**
     * The course format.
     * @var format_base
     */
    private $courseformat;

    /**
     * The number of visible sections in the course.
     * @var integer
     */
    private $numsections;

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

        $this->course = $DB->get_record('course', array('id' => $course->id));

        $this->courseformat = course_get_format($course);

        $formatopts = $this->courseformat->get_format_options();
        $this->numsections = $formatopts['numsections'];

        $this->lastsection = max(array_keys(get_fast_modinfo($course)->get_section_info_all()));
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
     * Creates a new section in the course.
     * @param string $sectiontype
     * @return section_info
     */
    public function create_section($sectiontype) {
        $this->lastsection += 1;
        course_create_sections_if_missing($this->course, $this->lastsection);

        // Increase the course section count.
        $this->numsections += 1;
        $data = array(
            'numsections' => $this->numsections,
        );
        $this->courseformat->update_course_format_options($data);

        // Set the section details.
        $info = get_fast_modinfo($this->course)->get_section_info($this->lastsection);
        $data = array(
            'id' => $info->id,
            'sectiontype' => $sectiontype,
        );
        $this->courseformat->update_section_format_options($data);

        // Move the section into place.
        if (!move_section_to($this->course, $this->lastsection, $this->sectioninsert)) {
            throw new coding_exception('move_section_to failed');
        }

        // Get the final section information and prepare for the next one.
        $info = get_fast_modinfo($this->course)->get_section_info($this->sectioninsert);
        $this->sectioninsert += 1;
        return $info;
    }
}
