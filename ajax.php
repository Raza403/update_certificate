<?php
require('../../config.php');
require_login();

$query = optional_param('query', '', PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);

if ($query) {
    // Clean and prepare the query
    $query = trim($query);
    $search_terms = explode(' ', $query); // Split query into terms (e.g., "Ahmed Raza" -> ["Ahmed", "Raza"])
    $conditions = [];
    $params = [];

    // Build conditions for firstname and lastname separately
    foreach ($search_terms as $term) {
        if (!empty($term)) {
            $conditions[] = 'LOWER(email) LIKE ?';
            $params[] = '%' . strtolower($term) . '%';
        }
    }

    // If no valid terms, return empty result
    if (empty($conditions)) {
        $suggestions = [];
    } else {
        // Combine conditions with AND to match all terms
        $sql_conditions = implode(' AND ', $conditions);
        $sql = "SELECT id, email AS name
            FROM {user}
            WHERE ($sql_conditions)
            AND deleted = 0
            ORDER BY email
            LIMIT 20";

        $users = $DB->get_records_sql($sql, $params);

        $suggestions = [];
        foreach ($users as $user) {
            $suggestions[] = ['id' => $user->id, 'name' => $user->name];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['suggestions' => $suggestions]);
} elseif ($userid) {
    // Handle course retrieval based on user
    $enrolled_courses = enrol_get_users_courses($userid);

    $courses = [];
    foreach ($enrolled_courses as $course) {
        $courses[] = ['id' => $course->id, 'fullname' => $course->fullname];
    }

    header('Content-Type: application/json');
    echo json_encode(['courses' => $courses]);
}
