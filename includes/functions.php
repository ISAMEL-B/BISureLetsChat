<?php
/**
 * BUSure Chat - Helper Functions
 */

/**
 * Get the base URL of the BUSureLetsChat project
 * Works on both localhost and production
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $parts = explode('/', trim($scriptDir, '/'));
    
    $projectPath = '';
    foreach ($parts as $part) {
        $projectPath .= '/' . $part;
        if ($part === 'bisureletschat' || $part === 'bisureletschat') {
            break;
        }
    }
    
    // Fallback if project folder not found
    if (strpos($projectPath, 'bisureletschat') === false && strpos($projectPath, 'bisureletschat') === false) {
        $projectPath = '/' . implode('/', array_slice($parts, 0, 2));
    }
    
    return $protocol . '://' . $host . $projectPath;
}

/**
 * Check if the current URL path matches a nav item
 */
function isActive($path) {
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return (strpos($currentPath, $path) !== false);
}