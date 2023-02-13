<?php
/**
 * Renderer for block shelf_badge
 *
 * @package    block_shelf_badge
 * @copyright  2021 De Chiara Antonella <antonella.dechiara@eticeo.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
define('BLOCK_SHELF_BADGE', 'block_shelf_badge');

require_once($CFG->libdir . "/badgeslib.php");

/**
 * shelf_badge block rendrer
 *
 * @package    block
 * @subpackage shelf_badge
 * @copyright  2021 De Chiara Antonella <antonella.dechiara@eticeo.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_shelf_badge extends block_base
{
    public function init() {
        $this->title = get_string('pluginname', BLOCK_SHELF_BADGE); // définit le titre du bloc
    }

    /**
     * The block is usable in all pages
     */
    function applicable_formats() {
        global $CFG;
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $context = context_system::instance();

        /* if (has_capability('report/indicators:student_panel', $context) &&
            $actual_link == $CFG->wwwroot . "/report/indicators/personal-view.php") { */
        if (has_capability('report/indicators:student_panel', $context)) {
            $yes = true;
        } else {
            $yes = false;
        }
        $yes  = true;
        return array('all' => true);
    }

    /**
     * The block can be used repeatedly in a page.
     */
    function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return true;
    }

    function html_attributes() {
        $attributes = array(
            'id' => 'inst' . $this->instance->id,
            'class' => 'block_' . $this->name() . ' block ' . $this->bootstrap_size(),
            'role' => $this->get_aria_role()
        );
        if ($this->hide_header()) {
            $attributes['class'] .= ' no-header';
        }
        if ($this->instance_can_be_docked() && get_user_preferences('docked_block_instance_' . $this->instance->id, 0)) {
            $attributes['class'] .= ' dock_on_load';
        }
        return $attributes;
    }

    /**
     * Build the block content.
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->footer = '<div class="eticeo-indicators">'.block_shelf_badge::eticeo_vue().'</div>';

        return $this->content;
    }

    /**
     * Course Indicators
     * Montre le type de vue
     * @return string
     */
    public function eticeo_vue()
    {
        global $OUTPUT, $CFG, $DB, $USER;

        if(!empty($this->config->title)) {
            $title = $this->config->title;
        } else {
            $title = get_string('thetitle', BLOCK_SHELF_BADGE);
        }

        $content = '';

        $content .= '<div class="container mt80"><div class="row">';

        $buttonFoldUp = $script = '';
        if($this->config->deploy_button_available) {
            $buttonFoldUp = '<i class="fa fa-chevron-down eticeoDeployBadges" onclick="eticeoDeployBadges()"></i>';
            $script = '<script>
                           function eticeoDeployBadges() {
                               if ($(".eticeo-indicators .temps-total").is(":visible")) {
                                   $(".eticeo-indicators .temps-total").hide();
                                   $(".eticeoDeployBadges").removeClass("fa-chevron-down");
                                   $(".eticeoDeployBadges").addClass("fa-chevron-up");
                               } else {
                                   $(".eticeo-indicators .temps-total").show();
                                   $(".eticeoDeployBadges").removeClass("fa-chevron-up");
                                   $(".eticeoDeployBadges").addClass("fa-chevron-down");
                               }
                           }
                       </script>';
        }
        // TITRE
        $content .= '<div class="headerbloc col-12 col-lg-12 con-md-12 col-sm-12">
                        <h2 class="text-left eticeo-infosconnexion-title">'.($this->config->bullet_icon ? '<i class="icon-title '.$this->config->bullet_icon.'"></i>' : '').$title.$buttonFoldUp.'</h2>
                    </div>';
        
        // BLOC 1
        $content .= block_shelf_badge::simple_block();

        $content .= '</div></div>'.$script; // end row

        return $content;
    }

    public function simple_block()
    {
        global $USER, $PAGE, $CFG;

        $content = '';

        // BADGES
        $badges = "";

        // Number of badges to display.
        if (!isset($this->config->numberofbadges)) {
            $numberofbadges = 10;
        } else {
            $numberofbadges = $this->config->numberofbadges;
        }

        // USER
        if(!empty($_GET['userReplace'])) {
            $userid = $_GET['userReplace'];
            $user = core_user::get_user($userid);
        } else {
            $userid = $USER->id;
            $user = $USER;
        }
        // END USER

        if (empty($CFG->enablebadges)) {
            $badges .= get_string('badgesdisabled', 'badges');
        }

        $courseid = $this->page->course->id;
        if ($courseid == SITEID) {
            $courseid = null;
        }
        // END
        $class = 'eticeo-label';
        $content .= '<div class="col-12">';
        $badgesOutput = $this->page->get_renderer('core', 'badges');
        if ($this->config->sort_by_courses) {
            /* On récupère tous les cours */
            $categories = core_course_category::get_all(); //$this->get_all_courses();
            foreach ($categories as $category) {
                $courses = $category->get_courses();
                foreach ($courses as $course) {
                    $courseBadges = badges_get_user_badges($USER->id, $course->id, 0, $numberofbadges);
                    $numBadges = $numBadgesObtained = 0;
                    $courseLine = "";
                    if ($courseBadges) {
                        $courseLine .= $badgesOutput->print_badges_list($courseBadges, $USER->id, true);
                        $numBadgesObtained = count($courseBadges);
                        
                        $numBadges = badges_get_badges(BADGE_TYPE_COURSE, $course->id, '', '', 0, BADGE_PERPAGE, $USER->id);
                        $numBadges = count($numBadges);
                    }
                    if ($numBadgesObtained > 0) {
                        $badges .= '<div class="courses-badges">
                                        <div class="eticeoTitleCourse">'.$course->fullname.' 
                                            <i class="badges-obtained-number" hidden> '.$numBadgesObtained.' '.get_string('obtained-badges-number', BLOCK_SHELF_BADGE).' '.
                                                                                 $numBadges.' '.get_string('possibles-badges-number', BLOCK_SHELF_BADGE).'</i>
                                            <i class="badges-obtained-number"> '.$numBadgesObtained.'/'.
                                                                                 $numBadges.'</i>

                                         </div>'.
                                         $courseLine.
                                    '</div>';
                    }
                }
            }
            $class .= ' eticeo-sort-by-course';
        } else {
            if ($badgesc = badges_get_user_badges($userid, $courseid, 0, $numberofbadges)) {
                $badges = $badgesOutput->print_badges_list($badgesc, $USER->id, true);
            }
        }
        if ($badges == '') {
            $badges .= get_string('nothingtodisplay', 'block_badges');
        }
        $content .= '<div class="text-left temps-total">
                         <span class="'.$class.'">'. $badges .'</span>
                     </div> <!-- text-center -->';


        $content .= '</div>'; // END col-4
        
        return $content;
    }

    /**
     * @return string
     */
    public function bootstrap_size() {
        $space = !empty($this->config->space) ? $this->config->space : 7;

        return "col-".$space." col-md-".$space." col-sm-12";
    }

    public function user_inscription() {
        global $USER;

        $result = $USER->firstaccess ? userdate($USER->firstaccess, '%d %B %Y') : get_string('noinfo', 'block_badges');

        return $result;
    }


   /**
    * Hide or display the header
    * @return boolean
    */
    function hide_header()
    {
        return true;
    }

}