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
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/course/format/usqflexopen/lib.php');


/**
 * Basic renderer for usqflexopen format.
 *
 * @package    format_usqflexopen
 * @copyright  2016 The University of Southern Queensland
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_usqflexopen_renderer extends format_section_renderer_base {
    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        global $PAGE;

        $PAGE->requires->strings_for_js(
            array('sectiontypeassess', 'sectiontypegetstarted', 'sectiontypetopic', 'sectiontypeweek'),
            'format_usqflexopen'
        );

        return html_writer::start_tag('ul', array('class' => 'usqflexopen'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('weeklyoutline');
    }

    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        $o = parent::section_header($section, $course, $onsectionpage, $sectionreturn);
        if ($section->section > 0) {
            $courseformatoptions = course_get_format($course)->get_format_options();
            $sectiontype = $section->sectiontype === 'default' ?
                $courseformatoptions['defaultsectiontype'] :
                $section->sectiontype;

            $labelinsert = '<span class="label sectiontype sectiontype-'.$sectiontype.'">' .
                get_string('sectiontype' . $sectiontype, 'format_usqflexopen') . '</span>';
            $o = preg_replace(
                    '~(<h3 class="sectionname[^"]*">.+?)(</h3>)~',
                    '$1'.$labelinsert.'$2',
                    $o);
        }
        return $o;
    }

    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        if ($PAGE->user_is_editing()) {
            return parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
        }

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $displayoncourse = ['default', 'week', 'topic'];
        if ($course->displaysectiontypeassess) $displayoncourse[] = 'assess';
        if ($course->displaysectiontypegetstarted) $displayoncourse[] = 'getstarted';

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    echo $this->section_header($thissection, $course, false, 0);
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    echo $this->section_footer();
                }
                continue;
            }
            if ($section > $course->numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }

            if (!in_array($thissection->sectiontype, $displayoncourse)) {
                if (has_capability('moodle/course:update', $context)) {
                    echo $this->section_hidden_from_participants($section, $course->id);
                }
                continue;
            }

            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo));
            if (!$showsection) {
                // If the hiddensections option is set to 'show hidden sections in collapsed
                // form', then display the hidden section message - UNLESS the section is
                // hidden by the availability system, which is set to hide the reason.
                if (!$course->hiddensections && $thissection->available) {
                    echo $this->section_hidden($section, $course->id);
                }

                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.
                echo $this->section_summary($thissection, $course, null);
            } else {
                echo $this->section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                echo $this->section_footer();
            }
        }

        echo $this->end_section_list();
    }

    public function print_filtered_page($course, $type) {
        global $PAGE, $USER;

        $wasediting = $USER->editing;
        $USER->editing = false;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0 || $thissection->sectiontype != $type) {
                continue;
            }
            if ($section > $course->numsections) {
                break;
            }

            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo));
            if (!$showsection) {
                // If the hiddensections option is set to 'show hidden sections in collapsed
                // form', then display the hidden section message - UNLESS the section is
                // hidden by the availability system, which is set to hide the reason.
                if (!$course->hiddensections && $thissection->available) {
                    echo $this->section_hidden($section, $course->id);
                }

                continue;
            }

            echo $this->section_header($thissection, $course, false, 0);
            if ($thissection->uservisible) {
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
            }
            echo $this->section_footer();
        }

        echo $this->end_section_list();

        $USER->editing = $wasediting;
    }

    /**
     * Generate the html for a section hidden from participants but not from editors
     *
     * @param int $sectionno The section number in the course which is being dsiplayed
     * @param int|stdClass $courseorid The course to get the section name for (object or just course id)
     * @return string HTML to output.
     */
    protected function section_hidden_from_participants($sectionno, $courseorid) {
        $sectionname = get_section_name($courseorid, $sectionno);
        $strnotavailable = get_string('sectionhidden', 'format_usqflexopen', $sectionname);

        $o = '';
        $o.= html_writer::start_tag('li', array('id' => 'section-'.$sectionno, 'class' => 'section main clearfix hidden'));
        $o.= html_writer::tag('div', '', array('class' => 'left side'));
        $o.= html_writer::tag('div', '', array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));
        $o.= html_writer::tag('div', $strnotavailable);
        $o.= html_writer::end_tag('div');
        $o.= html_writer::end_tag('li');
        return $o;
    }
}
