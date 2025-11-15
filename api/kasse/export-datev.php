<?php
/**
 * Export DATEV API - Kasse System
 * Exports consolidated accounting data in DATEV CSV format
 * TÜM kaynaklardan (Website, Kassa, Lieferando) birleşik muhasebe raporu
 * SKR03/SKR04 uyumlu, MwSt 7%/19% ayrımlı
 */

session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/../../includes/config.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Methode nicht erlaubt'
    ]);
    exit;
}

// Get parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default: first day of month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default: today
$format = isset($_GET['format']) ? $_GET['format'] : 'csv'; // csv or json
$skr = isset($_GET['skr']) ? $_GET['skr'] : 'SKR03'; // SKR03 or SKR04

// Validate dates
$startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
$endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);

if (!$startDateObj || $startDateObj->format('Y-m-d') !== $startDate) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültiges Startdatum. Format: YYYY-MM-DD'
    ]);
    exit;
}

if (!$endDateObj || $endDateObj->format('Y-m-d') !== $endDate) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültiges Enddatum. Format: YYYY-MM-DD'
    ]);
    exit;
}

try {
    $db = getDBConnection();

    // Query: Get all orders from ALL sources with tax breakdown
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.order_source,
            DATE(o.created_at) as order_date,
            o.total_amount,
            o.subtotal,
            o.tax,
            o.payment_method,
            o.payment_status,
            o.cashier_name,
            oi.product_name,
            oi.product_price,
            oi.quantity,
            oi.subtotal as item_subtotal,
            COALESCE(mi.tax_rate, 19.00) as tax_rate
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.product_id = mi.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND o.payment_status = 'completed'
        ORDER BY o.created_at, o.id
    ");

    $stmt->execute([$startDate, $endDate]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orderItems)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Keine Daten im angegebenen Zeitraum gefunden',
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_records' => 0
            ]
        ]);
        exit;
    }

    // Process data for DATEV export
    $datevRecords = [];
    $processedOrders = [];

    foreach ($orderItems as $item) {
        $orderId = $item['id'];
        
        // Process each order only once (summary level)
        if (!isset($processedOrders[$orderId])) {
            $processedOrders[$orderId] = true;

            $taxRate = floatval($item['tax_rate']);
            $isTax7 = ($taxRate == 7.00);
            
            // DATEV Konten (SKR03)
            // Erlöskonto: 8400 (19% MwSt) oder 8300 (7% MwSt)
            $erloeseKonto = $isTax7 ? '8300' : '8400';
            
            // Gegenkonto (Debitor/Kunde)
            // 10000 = Kasse, 10800 = Bank
            $gegenKonto = ($item['payment_method'] === 'CASH') ? '10000' : '10800';

            $datevRecords[] = [
                'umsatz' => number_format($item['total_amount'], 2, ',', ''), // Bruttobetrag
                'soll_haben' => 'S', // Soll
                'wkz_umsatz' => 'EUR',
                'kurs' => '',
                'basis_umsatz' => '',
                'wkz_basis_umsatz' => '',
                'konto' => $erloeseKonto, // Erlöskonto
                'gegenkonto' => $gegenKonto, // Kasse oder Bank
                'bu_schluessel' => $isTax7 ? '2' : '3', // BU-Schlüssel (2=7%, 3=19%)
                'belegdatum' => date('dmY', strtotime($item['order_date'])), // DDMMYYYY
                'belegfeld1' => $item['order_number'],
                'belegfeld2' => $item['order_source'],
                'skonto' => '',
                'buchungstext' => substr($item['product_name'], 0, 60), // Max 60 Zeichen
                'postensperre' => '',
                'diverse_adressnummer' => '',
                'geschaeftspartnerbank' => '',
                'sachverhalt' => '',
                'zinssperre' => '',
                'beleglink' => '',
                'beleginfo_art_1' => '',
                'beleginfo_inhalt_1' => '',
                'beleginfo_art_2' => '',
                'beleginfo_inhalt_2' => '',
                'beleginfo_art_3' => '',
                'beleginfo_inhalt_3' => '',
                'beleginfo_art_4' => '',
                'beleginfo_inhalt_4' => '',
                'beleginfo_art_5' => '',
                'beleginfo_inhalt_5' => '',
                'beleginfo_art_6' => '',
                'beleginfo_inhalt_6' => '',
                'beleginfo_art_7' => '',
                'beleginfo_inhalt_7' => '',
                'beleginfo_art_8' => '',
                'beleginfo_inhalt_8' => '',
                'kost1' => $item['order_source'], // Kostenstelle 1 (Quelle)
                'kost2' => '',
                'kost_menge' => $item['quantity'],
                'eu_land' => '',
                'eu_ust_id' => '',
                'eu_steuersatz' => '',
                'abw_versteuerungsart' => '',
                'sachverhalt_l_l' => '',
                'funktionsergaenzung_l_l' => '',
                'bu_49_hauptfunktionstyp' => '',
                'bu_49_hauptfunktionsnummer' => '',
                'bu_49_funktionsergaenzung' => '',
                'zusatzinformation_art_1' => '',
                'zusatzinformation_inhalt_1' => '',
                'zusatzinformation_art_2' => '',
                'zusatzinformation_inhalt_2' => '',
                'zusatzinformation_art_3' => '',
                'zusatzinformation_inhalt_3' => '',
                'zusatzinformation_art_4' => '',
                'zusatzinformation_inhalt_4' => '',
                'zusatzinformation_art_5' => '',
                'zusatzinformation_inhalt_5' => '',
                'zusatzinformation_art_6' => '',
                'zusatzinformation_inhalt_6' => '',
                'zusatzinformation_art_7' => '',
                'zusatzinformation_inhalt_7' => '',
                'zusatzinformation_art_8' => '',
                'zusatzinformation_inhalt_8' => '',
                'zusatzinformation_art_9' => '',
                'zusatzinformation_inhalt_9' => '',
                'zusatzinformation_art_10' => '',
                'zusatzinformation_inhalt_10' => '',
                'zusatzinformation_art_11' => '',
                'zusatzinformation_inhalt_11' => '',
                'zusatzinformation_art_12' => '',
                'zusatzinformation_inhalt_12' => '',
                'zusatzinformation_art_13' => '',
                'zusatzinformation_inhalt_13' => '',
                'zusatzinformation_art_14' => '',
                'zusatzinformation_inhalt_14' => '',
                'zusatzinformation_art_15' => '',
                'zusatzinformation_inhalt_15' => '',
                'zusatzinformation_art_16' => '',
                'zusatzinformation_inhalt_16' => '',
                'zusatzinformation_art_17' => '',
                'zusatzinformation_inhalt_17' => '',
                'zusatzinformation_art_18' => '',
                'zusatzinformation_inhalt_18' => '',
                'zusatzinformation_art_19' => '',
                'zusatzinformation_inhalt_19' => '',
                'zusatzinformation_art_20' => '',
                'zusatzinformation_inhalt_20' => '',
                'stueck' => $item['quantity'],
                'gewicht' => '',
                'zahlweise' => substr($item['payment_method'], 0, 2),
                'forderungsart' => '',
                'veranlagungsjahr' => date('Y', strtotime($item['order_date'])),
                'zugeordnete_faelligkeit' => ''
            ];
        }
    }

    // Output format
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'skr' => $skr,
                'total_records' => count($datevRecords),
                'records' => $datevRecords
            ]
        ]);
    } else {
        // CSV output
        $filename = 'DATEV_Export_' . $startDate . '_' . $endDate . '.csv';
        
        header('Content-Type: text/csv; charset=Windows-1252');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Write BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write header row
        if (!empty($datevRecords)) {
            fputcsv($output, array_keys($datevRecords[0]), ';');
            
            // Write data rows
            foreach ($datevRecords as $record) {
                fputcsv($output, $record, ';');
            }
        }

        fclose($output);
    }

} catch (Exception $e) {
    error_log('DATEV export error: ' . $e->getMessage());

    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Exportieren: ' . $e->getMessage()
    ]);
}
?>

