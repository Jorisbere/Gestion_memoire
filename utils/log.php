<?php
function logAction($conn, $action, $user_id = null, $details = null) {
    $stmt = $conn->prepare("INSERT INTO journal_actions (user_id, action, details, date_action) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
}
