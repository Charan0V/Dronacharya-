<?php
session_start();
require_once '../auth/check_auth.php';

// Require authentication
requireAuth();

// Get node data from POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nodeIndex = (int)($_POST['index'] ?? 0);
    $nodeTitle = trim($_POST['title'] ?? '');
    $nodeContent = trim($_POST['content'] ?? '');
    
    if (!empty($nodeTitle)) {
        // Set node data in session
        $_SESSION['current_node'] = [
            'index' => $nodeIndex,
            'title' => $nodeTitle,
            'content' => $nodeContent
        ];
        
        // Redirect to learning page
        header('Location: learning.php');
        exit();
    } else {
        // Invalid data, redirect back to dashboard
        header('Location: dashboard.php');
        exit();
    }
} else {
    // Not a POST request, redirect to dashboard
    header('Location: dashboard.php');
    exit();
}
?>
