<?php
/**
 * Start Session API - Kasse System
 * Starts a new cashier shift/session
 * Vardiya başlatma
 */

session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header
header('Content-Type: application/json');

// Include config
require_once __DIR__ . '/../../includes/config.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Methode nicht erlaubt'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige JSON-Daten'
    ]);
    exit;
}

// Validate required fields
if (!isset($input['cashierName']) || empty(trim($input['cashierName']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Kassierer-Name ist erforderlich'
    ]);
    exit;
}

try {
    $db = getDBConnection();

    $cashierName = trim($input['cashierName']);
    $startingCash = isset($input['startingCash']) ? floatval($input['startingCash']) : 0.00;
    $openingNotes = trim($input['openingNotes'] ?? '');

    // Check if there's already an active session for this cashier
    $stmt = $db->prepare("
        SELECT id, session_number 
        FROM kasse_sessions 
        WHERE cashier_name = ? 
        AND status = 'ACTIVE'
    ");
    $stmt->execute([$cashierName]);
    $existingSession = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSession) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Es gibt bereits eine aktive Schicht für diesen Kassierer',
            'data' => [
                'session_id' => $existingSession['id'],
                'session_number' => $existingSession['session_number']
            ]
        ]);
        exit;
    }

    // Generate unique session number
    $sessionNumber = 'KS-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

    // Check uniqueness
    $stmt = $db->prepare("SELECT id FROM kasse_sessions WHERE session_number = ?");
    $stmt->execute([$sessionNumber]);
    if ($stmt->fetch()) {
        $sessionNumber = 'KS-' . date('YmdHis') . '-' . rand(10, 99);
    }

    // Insert new session
    $stmt = $db->prepare("
        INSERT INTO kasse_sessions (
            session_number,
            cashier_name,
            start_time,
            starting_cash,
            opening_notes,
            status
        ) VALUES (?, ?, NOW(), ?, ?, 'ACTIVE')
    ");

    $stmt->execute([
        $sessionNumber,
        $cashierName,
        $startingCash,
        $openingNotes
    ]);

    $sessionId = $db->lastInsertId();

    // Store session info in PHP session
    $_SESSION['kasse_session_id'] = $sessionId;
    $_SESSION['kasse_session_number'] = $sessionNumber;
    $_SESSION['cashier_name'] = $cashierName;

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Schicht erfolgreich gestartet',
        'data' => [
            'session_id' => $sessionId,
            'session_number' => $sessionNumber,
            'cashier_name' => $cashierName,
            'starting_cash' => $startingCash,
            'start_time' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    error_log('Start session error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Starten der Schicht: ' . $e->getMessage()
    ]);
}
?>

