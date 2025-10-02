<?php
namespace local_update_certificate\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

use moodleform;

class update_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        // Autocomplete input for student selection
        $mform->addElement('autocomplete', 'userid', get_string('selectstudent', 'local_update_certificate'),
            $this->get_students_for_autocomplete(), ['placeholder' => 'Start typing to search...']);

        // Dropdown for course selection (initially populated)
        $courses = $this->get_courses_for_dropdown();
        $mform->addElement('select', 'courseid', get_string('selectcourse', 'local_update_certificate'), $courses);

        // Date selection for issue date
        $mform->addElement('date_selector', 'completiondate', get_string('Issuedate', 'local_update_certificate'), array('default' => time()));

        // Date selection for renew by date (conditionally shown)
        // Add an id attribute for the renewbydate row so that it can be easily accessed by JS
        $mform->addElement('date_selector', 'renewbydate', get_string('renewbydate', 'local_update_certificate'), array('optional' => true));
        $mform->setType('renewbydate', PARAM_RAW); // Set type for the element

        // Add submit and cancel buttons
        $this->add_action_buttons(true, get_string('setcompletiondate', 'local_update_certificate'));
    }

    // Method to get students for autocomplete
    private function get_students_for_autocomplete() {
        global $DB;
        return $DB->get_records_menu('user', ['deleted' => 0], 'email ASC', 'id, email');
    }

    // Method to get courses for dropdown (initially empty)
    private function get_courses_for_dropdown() {
        global $DB;
        return $DB->get_records_menu('course', null, 'fullname ASC', 'id, fullname');
    }
}
