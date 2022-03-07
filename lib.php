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
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');

/**
 * Main class for the usqflexopen course format
 *
 * @package    format_usqflexopen
 * @copyright  2016 The University of Southern Queensland
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_usqflexopen extends format_base {
    // Leaves room after COURSE_DISPLAY_MULTIPAGE in case Moodle develop new modes.
    const COURSE_DISPLAY_SEPARATEPAGE = 10;

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                    array('context' => context_course::instance($this->courseid)));
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the effective section type of a section (resolving 'default' to the course default).
     * @param int|stdClass|section_info $section the section
     * @param object $course the course object
     * @return string the effective section type
     */
    public function get_section_type($section) {
        if ($section instanceof section_info) {
            $sectioninfo = $section;
        } else {
            $sectioninfo = $this->get_section($section);
        }
        if (!isset($sectioninfo->sectiontype) || $sectioninfo->sectiontype === 'default') {
            $courseformatoptions = $this->get_format_options();
            return $courseformatoptions['defaultsectiontype'];
        } else {
            return $sectioninfo->sectiontype;
        }
    }

    /**
     * Returns the ordinal position of the given section within those of a given type.
     * @param section_info $sectioninfo the section object
     * @param string $sectiontype null for sections of the same type as $sectioninfo, otherwise an explicit type
     * @return integer the position, or 0 if not a valid section or $sectioninfo is not of $sectiontype
     */
    public function get_section_number_of_type(section_info $sectioninfo, $sectiontype = null) {
        if ($sectiontype === null) {
            $sectiontype = $this->get_section_type($sectioninfo);
        }

        $numoftype = 0;
        foreach ($this->get_sections() as $sinfo) {
            if ($sinfo->section == 0) {
                continue;
            }
            if ($this->get_section_type($sinfo) == $sectiontype) {
                $numoftype++;
            }
            if ($sinfo->section == $sectioninfo->section) {
                return $numoftype;
            }
        }
        return 0;
    }

    /**
     * Returns the count of sections of a given type.
     * @param section_info $sectioninfo the section object or null if $sectiontype is known
     * @param string $sectiontype null for sections of the same type as $sectioninfo, otherwise an explicit type
     * @return integer the position, or 0 if not a valid section or $sectioninfo is not of $sectiontype
     */
    public function get_section_count_of_type(section_info $sectioninfo = null, $sectiontype = null) {
        if ($sectiontype === null && $sectioninfo) {
            $sectiontype = $this->get_section_type($sectioninfo);
        } elseif (!$sectioninfo && !$sectiontype) {
            throw new coding_exception('both sectioninfo and sectiontype are null');
        }

        $numoftype = 0;
        foreach ($this->get_sections() as $sinfo) {
            if ($sinfo->section == 0) {
                continue;
            }
            if ($this->get_section_type($sinfo) == $sectiontype) {
                $numoftype++;
            }
        }
        return $numoftype;
    }

    /**
     * Returns the default section name.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * Otherwise, the default format based on section type will be returned.
     *
     * @param stdClass $section Section object from database.
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_usqflexopen');
        } else {
            $thissectioninfo = $this->get_section($section);
            $thissectiontype = $this->get_section_type($thissectioninfo);

            switch ($thissectiontype) {
                case 'week':
                    $dates = $this->get_section_dates($thissectioninfo);

                    // We subtract 24 hours for display purposes.
                    $dates->end = ($dates->end - 86400);

                    $dateformat = get_string('strftimedateshort');
                    $weekday = userdate($dates->start, $dateformat);
                    $endweekday = userdate($dates->end, $dateformat);
                    return $weekday.' - '.$endweekday;

                case 'getstarted':
                    return get_string('sectiontype' . $thissectiontype, 'format_usqflexopen');

                case 'topic':
                case 'assess':
                    $numoftype = $this->get_section_number_of_type($thissectioninfo, $thissectiontype);
                    return get_string('sectiontype' . $thissectiontype, 'format_usqflexopen') . ' ' . $numoftype;
            }

            throw new coding_exception('unhandled sectiontype ' . $thissectiontype);
        }
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = $course->coursedisplay;
            }
            if ($sectionno != 0 && $usercoursedisplay != COURSE_DISPLAY_SINGLEPAGE) {
                $url->param('section', $sectionno);
            } else {
                if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;

        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info_all();
        $formatopts = course_get_format($course)->get_format_options();

        // Insert filtered-view nodes.
        $getstartednode = null;
        $assessnode = null;
        foreach ($sectioninfo as $info) {
            $isvisible = $info->uservisible || ($info->visible && !$info->available && !empty($info->availableinfo));

            if ($info->sectiontype === 'getstarted' && $isvisible && !$getstartednode) {
                $getstartednode = $node->add(get_string('sectiontypegetstarted', 'format_usqflexopen'),
                    new moodle_url('/course/view.php', ['id' => $course->id, 'view' => 'getstarted']));
            } else if ($info->sectiontype === 'assess' && $isvisible && !$assessnode) {
                $assessnode = $node->add(get_string('sectiontypeassess', 'format_usqflexopen'),
                    new moodle_url('/course/view.php', ['id' => $course->id, 'view' => 'assess']));
            }
        }

        // Ugly way to force the active navigation tree node when viewing a filtered page.
        if ($PAGE->url->compare(new moodle_url('/course/view.php', ['id' => $course->id]))) {
            $viewparam = optional_param('view', null, PARAM_ALPHA);
            if ($viewparam === 'getstarted' && $getstartednode) {
                $getstartednode->make_active();
            } else if ($viewparam === 'assess' && $assessnode) {
                $assessnode->make_active();
            }
        }

        // if section is specified in course/view.php, make sure it is expanded in navigation
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }

        // Also remove getstarted and assess sections if they're supposed to be hidden on
        // the course page and the user isn't editing.
        if (!$PAGE->user_is_editing()) {
            foreach ($sectioninfo as $sectnum => $section) {
                if ($section->sectiontype !== 'getstarted' &&
                    $section->sectiontype !== 'assess') {
                    continue;
                }
                if (empty($formatopts['displaysectiontype'.$section->sectiontype])) {
                    $sectnode = $node->get($section->id, navigation_node::TYPE_SECTION);
                    if ($sectnode) {
                        $sectnode->remove();
                    }
                }
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $types = array();
        $current = -1;
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
                $types[$number] = $this->get_section_type($section);
                if ($this->is_section_current($section)) {
                    $current = $number;
                }
            }
        }
        return array('sectiontitles' => $titles, 'sectiontypes' => $types, 'current' => $current, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array()
        );
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Weeks format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'defaultsectiontype' => array(
                    'default' => 'topic',
                    'type' => PARAM_ALPHA,
                ),
                'displaysectiontypegetstarted' => array(
                    'default' => false,
                    'type' => PARAM_BOOL,
                ),
                'displaysectiontypeassess' => array(
                    'default' => false,
                    'type' => PARAM_BOOL,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'coursedisplay' => array(
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ),
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseconfig = get_config('moodlecourse');
            $sectionmenu = array();
            $max = $courseconfig->maxsections;
            if (!isset($max) || !is_numeric($max)) {
                $max = 52;
            }
            for ($i = 0; $i <= $max; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'defaultsectiontype' => array(
                    'label' => new lang_string('defaultsectiontype', 'format_usqflexopen'),
                    'help' => 'defaultsectiontype',
                    'help_component' => 'format_usqflexopen',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            'topic' => new lang_string('sectiontypetopic', 'format_usqflexopen'),
                            'week' => new lang_string('sectiontypeweek', 'format_usqflexopen')
                        )
                    ),
                ),
                'displaysectiontypegetstarted' => array(
                    'label' => new lang_string('displaysectiontypegetstarted', 'format_usqflexopen'),
                    'help' => 'displaysectiontypegetstarted',
                    'help_component' => 'format_usqflexopen',
                    'element_type' => 'advcheckbox',
                    'element_attributes' => array(),
                ),
                'displaysectiontypeassess' => array(
                    'label' => new lang_string('displaysectiontypeassess', 'format_usqflexopen'),
                    'help' => 'displaysectiontypeassess',
                    'help_component' => 'format_usqflexopen',
                    'element_type' => 'advcheckbox',
                    'element_attributes' => array(),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                            self::COURSE_DISPLAY_SEPARATEPAGE => new lang_string('coursedisplay_separate', 'format_usqflexopen'),
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                )
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        static $sectionformatoptions = false;
        if ($sectionformatoptions === false) {
            $sectionformatoptions = array(
                'sectiontype' => array(
                    'default' => 'default',
                    'type' => PARAM_ALPHA,
                    'cache' => true,
                ),
            );
        }
        if ($foreditform && !isset($sectionformatoptions['sectiontype']['label'])) {
            $sectionformatoptionsedit = array(
                'sectiontype' => array(
                    'label' => new lang_string('sectiontype', 'format_usqflexopen'),
                    'help' => 'sectiontype',
                    'help_component' => 'format_usqflexopen',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            'default' => new lang_string('sectiontypedefault', 'format_usqflexopen'),
                            'week' => new lang_string('sectiontypeweek', 'format_usqflexopen'),
                            'topic' => new lang_string('sectiontypetopic', 'format_usqflexopen'),
                            'assess' => new lang_string('sectiontypeassess', 'format_usqflexopen'),
                            'getstarted' => new lang_string('sectiontypegetstarted', 'format_usqflexopen'),
                        )
                    ),
                ),
            );
            $sectionformatoptions = array_merge_recursive($sectionformatoptions, $sectionformatoptionsedit);
        }
        return $sectionformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        if (!$forsection) {
            if (empty($this->courseid)) {
                $context = context_system::instance();
            } else {
                $context = context_course::instance($this->courseid);
            }
            if (!has_capability('format/usqflexopen:editcourseoptions', $context)) {
                $mform->hardFreeze('displaysectiontypegetstarted');
                $mform->hardFreeze('displaysectiontypeassess');
            }
        }
        return $elements;
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'usqflexopen', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Return the start and end date of the passed section
     *
     * @param int|stdClass|section_info $section section to get the dates for
     * @param int $startdate Force course start date, useful when the course is not yet created
     * @return stdClass property start for startdate, property end for enddate
     */
    public function get_section_dates($section, $startdate = false) {
        if ($startdate === false) {
            $course = $this->get_course();
            $startdate = $course->startdate;
        }

        if ($section instanceof section_info) {
            $sectioninfo = $section;
        } else {
            $sectioninfo = $this->get_section($section);
        }

        $isdatesection = false;
        if ($this->get_section_type($sectioninfo) === 'week') {
            $weeknum = $this->get_section_number_of_type($sectioninfo, 'week');
            $isdatesection = ($weeknum > 0);
        }
        if (!$isdatesection) {
            $dates = new stdClass();
            $dates->start = 0;
            $dates->end = 0;
            return $dates;
        }

        $oneweekseconds = 604800;
        // Hack alert. We add 2 hours to avoid possible DST problems. (e.g. we go into daylight
        // savings and the date changes.
        $startdate = $startdate + 7200;

        $dates = new stdClass();
        $dates->start = $startdate + ($oneweekseconds * ($weeknum - 1));
        $dates->end = $dates->start + $oneweekseconds;

        return $dates;
    }

    /**
     * Returns true if the specified week is current
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function is_section_current($section) {
        if ($section instanceof section_info) {
            $sectioninfo = $section;
        } else {
            $sectioninfo = $this->get_section($section);
        }

        if ($sectioninfo->section < 1) {
            // No highlighting the general section.
            return false;
        }

        $course = $this->get_course();

        if ($this->get_section_type($sectioninfo) !== 'week') {
            // The section in question is not a week type. Is it highlighted though?
            return ($course->marker == $sectioninfo->section);
        }

        // Has the highlight already been set on some other non-week section?
        if ($course->marker > 0 && $this->get_section_type($course->marker) !== 'week') {
            // Yes, so it takes priority.
            return false;
        }

        // Figure out whether the section in question is the current week.
        $timenow = time();
        $dates = $this->get_section_dates($sectioninfo);
        return (($timenow >= $dates->start) && ($timenow < $dates->end));
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    public function section_action($section, $action, $sr) {
        global $PAGE;

        $ishighlightable = in_array($this->get_section_type($this->get_section($section)), ['topic', 'assess']);
        if ($ishighlightable && $section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'usqflexopen' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_usqflexopen');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }
}

function format_usqflexopen_extend_navigation_course($coursenode, $course, $coursecontext) {
    global $PAGE;

    $caps = ['moodle/course:update', 'moodle/course:manageactivities'];
    $items = array();

    if ($PAGE->user_allowed_editing() && has_all_capabilities($caps, $coursecontext)) {
        if ($course->format === 'usqflexopen') {
            foreach (['assess', 'getstarted'] as $type) {
                $typename = get_string('sectiontype' . $type, 'format_usqflexopen');
                $items[] = [ get_string('createsection', 'format_usqflexopen', $typename),
                    '/course/format/usqflexopen/createsection.php?id='.$course->id.'&type='.$type ];
            }
        }
    }

    if ($items) {
        $sectnode = $coursenode->add(get_string('pluginname', 'format_usqflexopen'));

        if ($PAGE->user_is_editing()) {
            foreach ($items as $item) {
                list ($text, $url) = $item;
                $sectnode->add($text, $url);
            }
        } else {
            $editurl = clone($PAGE->url);
            $editurl->param('sesskey', sesskey());
            $editurl->param('edit', 'on');
            $sectnode->add(get_string('turneditingon'), $editurl);
        }
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_usqflexopen_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'usqflexopen'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Checks whether the current user has permission to view any grade reports.
 * @param context $context
 * @return boolean
 */
function format_usqflex_has_grade_report_cap(context $context) {
    foreach (array_keys(core_component::get_plugin_list('gradereport')) as $plugin) {
        if (has_capability('gradereport/'.$plugin.':view', $context)) {
            return true;
        }
    }
    return false;
}
