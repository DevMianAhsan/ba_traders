<?php
ob_start();
require_once 'db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

function send_json($data) {
    if (ob_get_length()) ob_clean();
    echo json_encode($data);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'save_tax_return_entry') {
    $id = !empty($_REQUEST['entry_id']) ? intval($_REQUEST['entry_id']) : 0;
    $entry_date = !empty($_REQUEST['entry_date']) ? $_REQUEST['entry_date'] : date('Y-m-d');
    $tax_month = !empty($_REQUEST['tax_month']) ? ucfirst(strtolower(trim($_REQUEST['tax_month']))) : date('M', strtotime($entry_date));
    $transaction_type = (!empty($_REQUEST['transaction_type']) && strtolower($_REQUEST['transaction_type']) === 'sale') ? 'Sale' : 'Purchase';
    $po_no = !empty($_REQUEST['po_no']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['po_no'])) : '';
    $party_name = !empty($_REQUEST['party_name']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['party_name'])) : '';
    $customer_id = !empty($_REQUEST['customer_id']) ? intval($_REQUEST['customer_id']) : NULL;
    $invoice_no = !empty($_REQUEST['invoice_no']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['invoice_no'])) : '';
    $item_name = !empty($_REQUEST['item_name']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['item_name'])) : 'ZIPPER';
    $kg = !empty($_REQUEST['kg']) ? floatval($_REQUEST['kg']) : 0;
    $rate = !empty($_REQUEST['rate_excl_tax']) ? floatval($_REQUEST['rate_excl_tax']) : 0;
    $taxable = !empty($_REQUEST['taxable_value']) ? floatval($_REQUEST['taxable_value']) : ($kg * $rate);
    $gst_rate = isset($_REQUEST['gst_rate']) ? floatval($_REQUEST['gst_rate']) : 18.00;
    if ($gst_rate <= 1 && $gst_rate > 0) {
        $gst_rate = $gst_rate * 100;
    }
    $gst_amount = !empty($_REQUEST['gst_amount']) ? floatval($_REQUEST['gst_amount']) : round($taxable * ($gst_rate / 100), 4);
    $invoice_total = !empty($_REQUEST['invoice_total']) ? floatval($_REQUEST['invoice_total']) : round($taxable + $gst_amount, 4);
    $notes = !empty($_REQUEST['notes']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['notes'])) : '';

    if (empty($customer_id) && !empty($party_name)) {
        $q_c = mysqli_query($dbc, "SELECT customer_id FROM customers WHERE customer_name = '$party_name' LIMIT 1");
        if ($r_c = mysqli_fetch_assoc($q_c)) {
            $customer_id = intval($r_c['customer_id']);
        }
    }

    $cid_sql = !empty($customer_id) ? "$customer_id" : "NULL";

    if ($id > 0) {
        $sql = "UPDATE tax_return_transactions SET 
            entry_date = '$entry_date',
            tax_month = '$tax_month',
            transaction_type = '$transaction_type',
            po_no = '$po_no',
            party_name = '$party_name',
            customer_id = $cid_sql,
            invoice_no = '$invoice_no',
            item_name = '$item_name',
            kg = '$kg',
            rate_excl_tax = '$rate',
            taxable_value = '$taxable',
            gst_rate = '$gst_rate',
            gst_amount = '$gst_amount',
            invoice_total = '$invoice_total',
            notes = '$notes'
            WHERE id = '$id'";
        $ok = mysqli_query($dbc, $sql);
    } else {
        $sql = "INSERT INTO tax_return_transactions 
            (entry_date, tax_month, transaction_type, po_no, party_name, customer_id, invoice_no, item_name, kg, rate_excl_tax, taxable_value, gst_rate, gst_amount, invoice_total, notes)
            VALUES 
            ('$entry_date', '$tax_month', '$transaction_type', '$po_no', '$party_name', $cid_sql, '$invoice_no', '$item_name', '$kg', '$rate', '$taxable', '$gst_rate', '$gst_amount', '$invoice_total', '$notes')";
        $ok = mysqli_query($dbc, $sql);
        if ($ok) {
            $id = mysqli_insert_id($dbc);
        }
    }

    if ($ok) {
        recalc_running_stock($dbc);
        send_json(['sts' => 'success', 'msg' => 'Transaction saved successfully', 'id' => $id]);
    } else {
        send_json(['sts' => 'error', 'msg' => mysqli_error($dbc)]);
    }
}

if ($action === 'delete_tax_return_entry') {
    $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id > 0) {
        $del = mysqli_query($dbc, "DELETE FROM tax_return_transactions WHERE id = '$id'");
        if ($del) {
            recalc_running_stock($dbc);
            send_json(['sts' => 'success', 'msg' => 'Transaction deleted successfully']);
        } else {
            send_json(['sts' => 'error', 'msg' => mysqli_error($dbc)]);
        }
    } else {
        send_json(['sts' => 'error', 'msg' => 'Invalid ID']);
    }
}

if ($action === 'fetch_tax_return_entry') {
    $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $res = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM tax_return_transactions WHERE id = '$id'"));
    if ($res) {
        send_json(['sts' => 'success', 'data' => $res]);
    } else {
        send_json(['sts' => 'error', 'msg' => 'Entry not found']);
    }
}

if ($action === 'recalc_tax_return_running_stock') {
    $count = recalc_running_stock($dbc);
    send_json(['sts' => 'success', 'msg' => "Running stock recalculated for $count records"]);
}

if ($action === 'import_tax_return_csv') {
    $csv_text = $_REQUEST['csv_data'] ?? '';
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $csv_text = file_get_contents($_FILES['csv_file']['tmp_name']);
    }

    if (empty(trim($csv_text))) {
        send_json(['sts' => 'error', 'msg' => 'No CSV content provided']);
    }

    $lines = preg_split("/\r\n|\n|\r/", trim($csv_text));
    $imported = 0;
    $errors = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, ',,,,,') === 0) continue;
        $row = str_getcsv($line);
        if (empty($row) || !isset($row[1])) continue;

        if (strtolower($row[0]) === 'date' && strtolower($row[1]) === 'month') continue;

        $entry_date = !empty($row[0]) ? date('Y-m-d', strtotime(trim($row[0]))) : date('Y-m-d');
        $tax_month = !empty($row[1]) ? ucfirst(strtolower(trim($row[1]))) : date('M', strtotime($entry_date));
        $type = (!empty($row[2]) && strtolower(trim($row[2])) === 'sale') ? 'Sale' : 'Purchase';
        $po_no = !empty($row[3]) ? mysqli_real_escape_string($dbc, trim($row[3])) : '';
        $party_name = !empty($row[4]) ? mysqli_real_escape_string($dbc, trim($row[4])) : '';
        $invoice_no = !empty($row[5]) ? mysqli_real_escape_string($dbc, trim($row[5])) : '';
        $item_name = !empty($row[6]) ? mysqli_real_escape_string($dbc, trim($row[6])) : 'ZIPPER';
        $kg = !empty($row[7]) ? floatval($row[7]) : 0;
        $rate = !empty($row[8]) ? floatval($row[8]) : 0;
        $taxable = !empty($row[9]) ? floatval($row[9]) : ($kg * $rate);
        $gst_rate = !empty($row[10]) ? floatval($row[10]) : 0.18;
        if ($gst_rate <= 1 && $gst_rate > 0) $gst_rate = $gst_rate * 100;
        $gst_amount = !empty($row[11]) ? floatval($row[11]) : round($taxable * ($gst_rate / 100), 4);
        $invoice_total = !empty($row[12]) ? floatval($row[12]) : round($taxable + $gst_amount, 4);
        $notes = !empty($row[14]) ? mysqli_real_escape_string($dbc, trim($row[14])) : '';

        $cid = "NULL";
        if (!empty($party_name)) {
            $q_c = mysqli_query($dbc, "SELECT customer_id FROM customers WHERE customer_name LIKE '%$party_name%' LIMIT 1");
            if ($r_c = mysqli_fetch_assoc($q_c)) {
                $cid = intval($r_c['customer_id']);
            }
        }

        $ins = "INSERT INTO tax_return_transactions 
            (entry_date, tax_month, transaction_type, po_no, party_name, customer_id, invoice_no, item_name, kg, rate_excl_tax, taxable_value, gst_rate, gst_amount, invoice_total, notes)
            VALUES 
            ('$entry_date', '$tax_month', '$type', '$po_no', '$party_name', $cid, '$invoice_no', '$item_name', '$kg', '$rate', '$taxable', '$gst_rate', '$gst_amount', '$invoice_total', '$notes')";

        if (mysqli_query($dbc, $ins)) {
            $imported++;
        } else {
            $errors++;
        }
    }

    recalc_running_stock($dbc);
    send_json(['sts' => 'success', 'msg' => "Successfully imported $imported rows" . ($errors > 0 ? " ($errors failed)" : "")]);
}

function recalc_running_stock($dbc) {
    $q = mysqli_query($dbc, "SELECT id, transaction_type, kg FROM tax_return_transactions ORDER BY id ASC");
    $stock = 0;
    $count = 0;
    while ($row = mysqli_fetch_assoc($q)) {
        $count++;
        if ($row['transaction_type'] === 'Purchase') {
            $stock += floatval($row['kg']);
        } else {
            $stock -= floatval($row['kg']);
        }
        $stock_fmt = round($stock, 4);
        mysqli_query($dbc, "UPDATE tax_return_transactions SET running_stock_kg = '$stock_fmt' WHERE id = '{$row['id']}'");
    }
    return $count;
}

send_json(['sts' => 'error', 'msg' => 'Invalid action']);
?>
