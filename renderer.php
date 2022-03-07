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
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_topics_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

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
        return get_string('topicoutline');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        $format = course_get_format($course);
        $out = $this->render($format->inplace_editable_render_section_name($section));
        $out .= $this->section_title_suffix($format, $section);
        return $out;
    }

    /**
     * Generate the section title to be displayed on the section page, without a link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        $format = course_get_format($course);
        $out = $this->render($format->inplace_editable_render_section_name($section, false));
        $out .= $this->section_title_suffix($format, $section);
        return $out;
    }

    /**
     * Prepare supplement text to render after a week section title.
     * @param format_base $format
     * @param stdClass $section
     * @return string
     */
    private function section_title_suffix($format, $section) {
        $out = '';
        if ($format->get_section_type($section) === 'week' && $section->section > 0) {
            $numoftype = $format->get_section_number_of_type($section, 'week');
            $out = null;

            $weekname = get_string('weekn', 'format_usqflexopen', $numoftype);
            if ($section->name === '' || $section->name === null) {
                $out = $weekname;
            } else {
                $a = [
                    'name' => $weekname,
                    'range' => $format->get_default_section_name($section),
                ];
                $out = get_string('weeknamewithrange', 'format_usqflexopen', $a);
            }
            $out = html_writer::span($out, 'weeknum');
        }
        return $out;
    }

    /**
     * Returns the effective section type of a section (resolving 'default' to the course default).
     * @param object $section the section object
     * @param object $course the course object
     * @return string the effective section type
     */
    private function get_section_type($section, $course) {
        return course_get_format($course)->get_section_type($section);
    }

    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        $o = parent::section_header($section, $course, $onsectionpage, $sectionreturn);
        if ($section->section > 0) {
            $sectiontype = $this->get_section_type($section, $course);

            $labelinsert = '<span class="sectiontype sectiontype-'.$sectiontype.'">' .
                get_string('sectiontype' . $sectiontype, 'format_usqflexopen') . '</span>';
            $o = preg_replace(
                    '~(<h3\b[^>]*?\bclass="sectionname[^"]*"[^>]*?>.+?)(</h3>)~s',
                    '$1'.$labelinsert.'$2',
                    $o);
        }
        return $o;
    }

    protected function section_summary($section, $course, $mods) {
        $o = parent::section_summary($section, $course, $mods);
        if ($section->section > 0) {
            $sectiontype = $this->get_section_type($section, $course);
            $format = course_get_format($course);

            $labelinsert = '<span class="sectiontype sectiontype-'.$sectiontype.'">' .
                get_string('sectiontype' . $sectiontype, 'format_usqflexopen') . '</span>';
            $suffixinsert = $this->section_title_suffix($format, $section);
            $o = preg_replace(
                    '~(<h3 class="section-title[^"]*">)(<a [^>]+>.+?</a>)</h3>~s',
                    '$1<span>$2'.$suffixinsert.'</span>'.$labelinsert.'</h3>',
                    $o);
        }
        return $o;
    }

    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $format = course_get_format($course);
        $course = $format->get_course();

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection)) || !$sectioninfo->uservisible) {
            // This section doesn't exist or is not available for the user.
            // We actually already check this in course/view.php but just in case exit from this function as well.
            print_error('unknowncoursesection', 'error', course_get_url($course),
                format_string($course->fullname));
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);

        $isseparatemode = ($format->get_format_options()['coursedisplay'] === format_usqflexopen::COURSE_DISPLAY_SEPARATEPAGE);
        $thissection = $modinfo->get_section_info(0);
        if (!$isseparatemode && (($thissection->summary or !empty($modinfo->sections[0])) or $PAGE->user_is_editing())) {
            echo $this->start_section_list();
            echo $this->section_header($thissection, $course, true, $displaysection);
            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();
        }

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section ' . ($isseparatemode ? 'without-general' : '')));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation navigationtitle'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        // Title attributes
        $classes = 'sectionname';
        if (!$thissection->visible) {
            $classes .= ' dimmed_text';
        }
        $sectionname = html_writer::tag('span', $this->section_title_without_link($thissection, $course));
        $sectiontitle .= $this->output->heading($sectionname, 3, $classes);

        $sectiontitle .= html_writer::end_tag('div');

        // NOTE: Variance from format_section_renderer_base.
        $sectiontype = $this->get_section_type($thissection, $course);
        $labelinsert = '<span class="sectiontype sectiontype-'.$sectiontype.'">' .
            get_string('sectiontype' . $sectiontype, 'format_usqflexopen') . '</span>';
        $sectiontitle = preg_replace(
                '~(<h3 class="sectionname[^"]*">.+?)(</h3>)~s',
                '$1'.$labelinsert.'$2',
                $sectiontitle);

        echo $sectiontitle;

        // Now the list of sections..
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
        echo $this->section_footer();
        echo $this->end_section_list();

        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection),
            array('class' => 'mdl-align'));
        $sectionbottomnav .= html_writer::end_tag('div');
        echo $sectionbottomnav;

        // Close single-section div.
        echo html_writer::end_tag('div');
    }

    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        // NOTE: Variance from format_section_renderer_base.
        if ($PAGE->user_is_editing()) {
            return parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
        }

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        // NOTE: Variance from format_section_renderer_base.
        $displayoncourse = ['default', 'week', 'topic'];
        if ($course->displaysectiontypeassess) $displayoncourse[] = 'assess';
        if ($course->displaysectiontypegetstarted) $displayoncourse[] = 'getstarted';

        $context = context_course::instance($course->id);
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();

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
            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }

            // NOTE: Variance from format_section_renderer_base.
            if (!in_array($thissection->sectiontype, $displayoncourse)) {
                if (has_capability('moodle/course:update', $context)) {
                    echo $this->section_hidden_from_participants($section, $course->id);
                }
                continue;
            }

            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                    (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay != COURSE_DISPLAY_SINGLEPAGE) {
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

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
        }
    }

    public function print_filtered_page($course, $type) {
        global $PAGE, $USER;

        $wasediting = $USER->editing;
        $USER->editing = false;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0 || $this->get_section_type($thissection, $course) != $type) {
                continue;
            }
            if ($section > $numsections) {
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

    public function print_filtered_page_prologue($course, $view): void {
        if ($view === 'getstarted' || $view === 'assess') {
            if ($this->page->user_is_editing()) {
                echo $this->output->notification(get_string('editingmode', 'format_usqflexopen'), 'notifymessage');
            }
        }
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
        $o.= html_writer::tag('div', $strnotavailable, ['class' => 'dimmed_text']);
        $o.= html_writer::end_tag('div');
        $o.= html_writer::end_tag('li');
        return $o;
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $ishighlightable = in_array($this->get_section_type($section, $course), ['topic', 'assess']);
        $controls = array();
        if ($ishighlightable && $section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                               'name' => $highlightoff,
                                               'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic,
                                                   'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                               'name' => $highlight,
                                               'pixattr' => array('class' => '', 'alt' => $markthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic,
                                                   'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Amend the standard section nav links pair by including the main course page
     * when in single-section-no-general mode and viewing the first section.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the course which is being displayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {
        $links = parent::get_nav_links($course, $sections, $sectionno);
        $formatoptions = course_get_format($course)->get_format_options();
        if ($formatoptions['coursedisplay'] === format_usqflexopen::COURSE_DISPLAY_SEPARATEPAGE) {
            if ($sectionno == 1) {
                $previouslink = html_writer::tag('span', $this->output->larrow(), array('class' => 'larrow'));
                $previouslink .= get_string('maincoursepage');
                $links['previous'] = html_writer::link(course_get_url($course), $previouslink);
            }
        }
        return $links;
    }
}
