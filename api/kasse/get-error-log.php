<?php
/**
 * Get last N lines from error log
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$logFile = __DIR__ . '/../../logs/error.log';
$lines = isset($_GET['lines']) ? intval($_GET['lines']) : 30;

if (!file_exists($logFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Log file not found',
        'path' => $logFile
    ]);
    exit;
}

// Read last N lines
$file = new SplFileObject($logFile, 'r');
$file->seek(PHP_INT_MAX);
$lastLine = $file->key();
$startLine = max(0, $lastLine - $lines);

$logLines = [];
$file->seek($startLine);
while (!$file->eof()) {
    $line = trim($file->current());
    if (!empty($line)) {
        $logLines[] = $line;
    }
    $file->next();
}

echo json_encode([
    'success' => true,
    'total_lines' => $lastLine + 1,
    'showing' => count($logLines),
    'log' => $logLines
], JSON_PRETTY_PRINT);
?>

