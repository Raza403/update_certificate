<?php
require_once('../../config.php');
require_once('classes/form/update_form.php');

global $DB, $USER;

$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/local/update_certificate/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('updatecompletiondate', 'local_update_certificate'));

// Load the necessary JS for AJAX and autocomplete
$PAGE->requires->js('/local/update_certificate/js/update_courses.js');
$PAGE->requires->js('/local/update_certificate/js/autocomplete.js'); // JS file for autocomplete

$mform = new \local_update_certificate\form\update_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($fromform = $mform->get_data()) {
    // Initialize message and alert class at the start
    $message = '';
    $alert_class = 'alert-success';

    // Process the form data
    $completiondate = isset($fromform->completiondate) && is_numeric($fromform->completiondate) ? intval($fromform->completiondate) : 0;
    $userid = $fromform->userid;
    $courseid = $fromform->courseid;
    if ($completiondate > 0) {
        $completion = $DB->get_record('course_completions', [
            'userid' => $userid,
            'course' => $courseid
        ]);

        if ($completion) {
            // Update existing completion date
            $completion->timecompleted = $completiondate;
            $DB->update_record('course_completions', $completion);
            $message .= "Updated completion date for course $courseid.<br>";
        } else {
            // Insert new completion record if it doesn't exist
            $completion = (object)[
                'userid' => $userid,
                'course' => $courseid,
                'timeenrolled' => time(),
                'timestarted' => time(),
                'timecompleted' => $completiondate
            ];
            $DB->insert_record('course_completions', $completion);
            $message .= "Inserted new completion date for course $courseid.<br>";
        }
    }

    // Only update the renew by date if courseid is 14 and renewbydate is provided
    if ($courseid == 14 && isset($fromform->renewbydate)) {
        $itemid = $DB->get_field('grade_items', 'id', ['courseid' => $courseid, 'idnumber' => '999']);

        if ($itemid) {
            $grade_record = $DB->get_record('grade_grades', ['userid' => $userid, 'itemid' => $itemid]);

            if ($grade_record && is_null($grade_record->timemodified)) {
                $message = get_string('renewactivityerror', 'local_update_certificate');
                $alert_class = 'alert-danger';
            } else if ($grade_record) {
                $renewbydate = isset($fromform->renewbydate) ? intval($fromform->renewbydate) : 0;
                if ($renewbydate > 0) {
                    $grade_record->timemodified = $renewbydate;
                    $DB->update_record('grade_grades', $grade_record);
                    $message = get_string('renewbydateset', 'local_update_certificate'); // Success message for renew by date
                }
            }
        }
    }

    // Update the issue date for the certificate
    $templateid = $DB->get_field('customcert', 'id', ['course' => $courseid]);

    if ($templateid) {
        $cert_record = $DB->get_record('customcert_issues', ['userid' => $userid, 'customcertid' => $templateid]);

        if ($cert_record) {
            $cert_record->timecreated = $completiondate; // Update the issue date
            $DB->update_record('customcert_issues', $cert_record);
            $message = get_string('completiondateset', 'local_update_certificate'); // Success message for completion date
        } else {
            $message = get_string('certificatenotfound', 'local_update_certificate') . '<br>';
            $message .= 'UserID: ' . $userid . '<br>';
            $message .= 'CourseID: ' . $courseid . '<br>';
            $message .= 'TemplateID: ' . $templateid . '<br>';
            $alert_class = 'alert-danger';
        }
    }

    // Display the message
    echo $OUTPUT->header();
    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
    echo '<strong>' . $message . '</strong>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';

    $mform = new \local_update_certificate\form\update_form();
    $mform->set_data($fromform);
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('updatecompletiondate', 'local_update_certificate')); // This adds the heading to the page
$mform->display();
echo $OUTPUT->footer();
