<?php
// Pre-fetch pending POs in PHP so modal renders immediately without waiting or failing if JS is delayed
if (!function_exists('normalize_target_date_val')) {
    function normalize_target_date_val($date_str) {
        $date_str = trim((string)$date_str);
        if (empty($date_str) || $date_str == '0000-00-00') return null;
        if (preg_match('/^0+([1-9]\d)-(\d{2})-(\d{2})$/', $date_str, $m)) {
            $date_str = '20' . $m[1] . '-' . $m[2] . '-' . $m[3];
        } elseif (preg_match('/^260(\d{2})-(\d{2})-(\d{2})$/', $date_str, $m)) {
            $date_str = '20' . $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        $ts = strtotime($date_str);
        return ($ts !== false && $ts > 0) ? date('Y-m-d', $ts) : null;
    }
}

if (!function_exists('get_pending_pos_data')) {
    function get_pending_pos_data($dbc) {
        $today = date('Y-m-d');
        $today_ts = strtotime($today);

        $sql = "
            SELECT 
                p.po_id,
                p.po_partypo,
                p.po_customer,
                p.po_customer_name,
                p.po_date,
                p.po_targetdate,
                p.po_remarks,
                c.customer_name,
                poi.po_item_id,
                poi.product_id,
                prod.product_name,
                poi.quantity AS ordered_qty,
                SUM(COALESCE(oitem.quantity, 0)) AS delivered_qty
            FROM purchase_order p
            LEFT JOIN customers c ON c.customer_id = p.po_customer
            INNER JOIN purchase_order_item poi ON p.po_id = poi.po_id
            LEFT JOIN orders o ON p.po_partypo = o.party_po AND (o.customer_account = p.po_customer OR p.po_customer IS NULL OR p.po_customer = 0)
            LEFT JOIN order_item oitem ON o.order_id = oitem.order_id AND poi.product_id = oitem.product_id
            LEFT JOIN product prod ON prod.product_id = poi.product_id
            GROUP BY p.po_id, poi.po_item_id, poi.product_id, prod.product_name, poi.quantity
            ORDER BY p.po_id DESC
        ";

        $res = @mysqli_query($dbc, $sql);
        if (!$res) {
            return [
                'status' => 'error',
                'message' => @mysqli_error($dbc),
                'today' => $today,
                'today_formatted' => date('d/m/Y', $today_ts),
                'counts' => ['total' => 0, 'today' => 0, 'overdue' => 0, 'soon' => 0, 'upcoming' => 0, 'nodate' => 0],
                'pos' => []
            ];
        }

        $pos_map = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $pId = (int)$r['po_id'];
            if (!isset($pos_map[$pId])) {
                $norm_date = normalize_target_date_val($r['po_targetdate']);
                $days_diff = null;
                $status = 'nodate';
                $status_label = 'No Target Date';
                $badge_class = 'badge-secondary';
                $days_text = 'No Date';

                if ($norm_date) {
                    $target_ts = strtotime($norm_date);
                    $days_diff = (int)round(($target_ts - $today_ts) / 86400);

                    if ($days_diff < 0) {
                        $status = 'overdue';
                        $status_label = 'DATE OVER';
                        $days_text = abs($days_diff) . 'd ago';
                        $badge_class = 'badge-danger';
                    } elseif ($days_diff === 0) {
                        $status = 'today';
                        $status_label = 'DUE TODAY';
                        $days_text = 'Today (' . date('d/m/Y', $target_ts) . ')';
                        $badge_class = 'badge-warning text-dark font-weight-bold';
                    } elseif ($days_diff <= 3) {
                        $status = 'soon';
                        $status_label = 'DUE SOON';
                        $days_text = 'Due in ' . $days_diff . 'd';
                        $badge_class = 'badge-info';
                    } elseif ($days_diff <= 7) {
                        $status = 'soon';
                        $status_label = 'THIS WEEK';
                        $days_text = 'Due in ' . $days_diff . 'd';
                        $badge_class = 'badge-primary';
                    } else {
                        $status = 'upcoming';
                        $status_label = 'UPCOMING';
                        $days_text = 'In ' . $days_diff . 'd';
                        $badge_class = 'badge-light border';
                    }
                }

                $cust_name = !empty($r['customer_name']) ? $r['customer_name'] : (!empty($r['po_customer_name']) ? $r['po_customer_name'] : 'Customer #' . $r['po_customer']);
                $formatted_po_date = (!empty($r['po_date']) && $r['po_date'] != '0000-00-00') ? date('d/m/Y', strtotime($r['po_date'])) : 'N/A';

                $pos_map[$pId] = [
                    'po_id' => $pId,
                    'party_po' => (string)$r['po_partypo'],
                    'customer_id' => $r['po_customer'],
                    'customer_name' => $cust_name,
                    'po_date' => $r['po_date'],
                    'formatted_po_date' => $formatted_po_date,
                    'raw_targetdate' => $r['po_targetdate'],
                    'target_date' => $norm_date,
                    'formatted_target_date' => $norm_date ? date('d/m/Y', strtotime($norm_date)) : 'N/A',
                    'days_diff' => $days_diff,
                    'status' => $status,
                    'status_label' => $status_label,
                    'days_text' => $days_text,
                    'badge_class' => $badge_class,
                    'remarks' => $r['po_remarks'] ?? '',
                    'total_ordered' => 0,
                    'total_delivered' => 0,
                    'total_remaining' => 0,
                    'items' => [],
                    'has_pending' => false
                ];
            }

            $ord = (float)$r['ordered_qty'];
            $del = (float)$r['delivered_qty'];
            $rem = max(0, $ord - $del);

            $pos_map[$pId]['items'][] = [
                'product_id' => (int)$r['product_id'],
                'product_name' => !empty($r['product_name']) ? $r['product_name'] : 'Product #' . $r['product_id'],
                'ordered_qty' => $ord,
                'delivered_qty' => $del,
                'remaining_qty' => $rem,
                'is_completed' => ($rem <= 0)
            ];
            $pos_map[$pId]['total_ordered'] += $ord;
            $pos_map[$pId]['total_delivered'] += $del;
            $pos_map[$pId]['total_remaining'] += $rem;
            if ($rem > 0) {
                $pos_map[$pId]['has_pending'] = true;
            }
        }

        // Filter pending POs
        $pending_pos = array_values(array_filter($pos_map, function($p) {
            return $p['has_pending'] && $p['total_remaining'] > 0;
        }));

        // Calculate delivery percent
        foreach ($pending_pos as &$p) {
            $p['delivery_percent'] = ($p['total_ordered'] > 0) 
                ? round(($p['total_delivered'] / $p['total_ordered']) * 100, 1) 
                : 0;
        }
        unset($p);

        // Default sort: Due Today -> Date Over -> Upcoming -> No date
        usort($pending_pos, function($a, $b) {
            $getGroup = function($p) {
                if ($p['status'] === 'today') return 1;
                if ($p['status'] === 'overdue') return 2;
                if ($p['days_diff'] !== null && $p['days_diff'] > 0) return 3;
                return 4;
            };
            $ga = $getGroup($a);
            $gb = $getGroup($b);
            if ($ga !== $gb) return $ga - $gb;
            if ($ga === 1) return $b['po_id'] - $a['po_id'];
            if ($ga === 2) return $b['days_diff'] - $a['days_diff'];
            if ($ga === 3) return $a['days_diff'] - $b['days_diff'];
            return $b['po_id'] - $a['po_id'];
        });

        $counts = [
            'total' => count($pending_pos),
            'today' => 0,
            'overdue' => 0,
            'soon' => 0,
            'upcoming' => 0,
            'nodate' => 0
        ];
        foreach ($pending_pos as $p) {
            if (isset($counts[$p['status']])) {
                $counts[$p['status']]++;
            }
        }

        return [
            'status' => 'success',
            'today' => $today,
            'today_formatted' => date('d/m/Y', $today_ts),
            'counts' => $counts,
            'pos' => $pending_pos
        ];
    }
}

if (!function_exists('render_pending_po_cards_html')) {
    function render_pending_po_cards_html($pos) {
        if (empty($pos)) {
            return '
            <div class="text-center py-4 text-muted">
              <i class="fe fe-check-circle fe-24 text-success mb-2 d-block"></i>
              <h6 class="font-weight-bold mb-1" style="font-size: 13px;">All POs Completed!</h6>
              <small>No pending purchase orders with remaining quantities.</small>
            </div>';
        }

        $html = '';
        foreach ($pos as $p) {
            $statusClass = 'status-' . htmlspecialchars($p['status'] ?? 'upcoming');
            $pulseClass = (($p['status'] ?? '') === 'today') ? 'pulse-today' : '';
            $collapseId = 'po_items_' . $p['po_id'];

            // Delivery badge
            $deliveryBadge = '';
            if ($p['status'] === 'today') {
                $deliveryBadge = '<span class="badge badge-warning text-dark font-weight-bold px-1.5 py-0.5 shadow-sm" style="font-size: 10.5px;"><i class="fe fe-alert-triangle mr-1"></i>DUE TODAY (' . htmlspecialchars($p['formatted_target_date']) . ')</span>';
            } elseif ($p['status'] === 'overdue') {
                $deliveryBadge = '<span class="badge badge-danger text-white font-weight-bold px-1.5 py-0.5 shadow-sm" style="font-size: 10.5px;"><i class="fe fe-alert-octagon mr-1"></i>DATE OVER (' . htmlspecialchars($p['days_text']) . ')</span>';
            } elseif ($p['status'] === 'soon') {
                $deliveryBadge = '<span class="badge badge-info text-white font-weight-bold px-1.5 py-0.5" style="font-size: 10.5px;"><i class="fe fe-clock mr-1"></i>' . htmlspecialchars($p['days_text']) . ' (' . htmlspecialchars($p['formatted_target_date']) . ')</span>';
            } else {
                $deliveryBadge = '<span class="badge badge-light border text-muted px-1.5 py-0.5" style="font-size: 10.5px;"><i class="fe fe-calendar mr-1"></i>Due: ' . htmlspecialchars($p['formatted_target_date']) . '</span>';
            }

            // Items table rows
            $itemRows = '';
            if (!empty($p['items'])) {
                foreach ($p['items'] as $item) {
                    $remBadge = ($item['remaining_qty'] > 0)
                        ? '<span class="text-danger font-weight-bold">' . number_format($item['remaining_qty']) . '</span>'
                        : '<span class="badge badge-success px-1.5 py-0.5" style="font-size: 10px;"><i class="fe fe-check mr-1"></i>0</span>';

                    $itemRows .= '
                    <tr>
                      <td class="text-center font-weight-bold text-muted" style="padding: 3px 5px !important;">' . $item['product_id'] . '</td>
                      <td class="font-weight-600" style="padding: 3px 5px !important;">' . htmlspecialchars($item['product_name']) . '</td>
                      <td class="text-right" style="padding: 3px 5px !important;">' . number_format($item['ordered_qty']) . '</td>
                      <td class="text-right text-success" style="padding: 3px 5px !important;">' . number_format($item['delivered_qty']) . '</td>
                      <td class="text-right" style="padding: 3px 5px !important;">' . $remBadge . '</td>
                    </tr>';
                }
            } else {
                $itemRows = '<tr><td colspan="5" class="text-center text-muted" style="padding: 3px 5px !important;">No items found</td></tr>';
            }

            $challanUrl = 'credit_sale.php?cust_id=' . urlencode($p['customer_id'] ?? '') . '&po_no=' . urlencode($p['party_po'] ?? '');
            $itemCount = count($p['items'] ?? []);

            $html .= '
            <div class="po-card ' . $statusClass . ' ' . $pulseClass . '" data-po-id="' . $p['po_id'] . '">
              <div class="po-card-inner">

                <!-- Header Row (Click to toggle items) -->
                <div class="po-card-clickable-header d-flex align-items-center justify-content-between mb-1" style="cursor: pointer;" data-target="#' . $collapseId . '" onclick="window.togglePOItems(\'' . $collapseId . '\', this.closest(\'.po-card\').querySelector(\'.btn-toggle-items\'), event)" title="Click to view/hide product items">
                  <div class="d-flex align-items-center text-truncate mr-2">
                    <span class="badge badge-dark px-1.5 py-0.5 font-weight-bold mr-1.5" style="font-size: 11px;">PO# ' . htmlspecialchars($p['party_po']) . '</span>
                    <strong class="text-dark text-truncate po-customer-title" title="' . htmlspecialchars($p['customer_name']) . '" style="font-size: 12.5px;">' . htmlspecialchars($p['customer_name']) . '</strong>
                  </div>
                  <div class="flex-shrink-0">
                    ' . $deliveryBadge . '
                  </div>
                </div>

                <!-- Date Info & Target -->
                <div class="d-flex align-items-center justify-content-between text-muted mb-1" style="font-size: 10.5px;">
                  <span><i class="fe fe-calendar fe-10 mr-1"></i>Ordered: ' . htmlspecialchars($p['formatted_po_date']) . '</span>
                  <span>Target Date: <strong>' . htmlspecialchars($p['formatted_target_date']) . '</strong></span>
                </div>

                <!-- Sleek Inline Quantity Summary -->
                <div class="d-flex align-items-center justify-content-between bg-light rounded px-2 py-1 mb-1 border po-stat-row" style="font-size: 11px;">
                  <span>Req: <strong>' . number_format($p['total_ordered']) . '</strong></span>
                  <span class="text-muted">|</span>
                  <span class="text-success font-weight-600">Del: <strong>' . number_format($p['total_delivered']) . '</strong></span>
                  <span class="text-muted">|</span>
                  <span class="text-danger font-weight-bold">Rem: <strong>' . number_format($p['total_remaining']) . '</strong></span>
                  <span class="text-muted">|</span>
                  <span class="badge badge-light border text-muted px-1" style="font-size: 10px;">' . $p['delivery_percent'] . '%</span>
                </div>

                <!-- Mini Progress Bar -->
                <div class="progress mb-1.5" style="height: 3px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: ' . $p['delivery_percent'] . '%;" aria-valuenow="' . $p['delivery_percent'] . '" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <!-- Compact Actions & View Items Row -->
                <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="font-size: 11px;">
                  <button type="button" class="btn btn-xs btn-outline-secondary btn-toggle-items py-0 px-1.5 font-weight-600" data-target="#' . $collapseId . '" style="font-size: 11px; height: 22px; cursor: pointer;" onclick="window.togglePOItems(\'' . $collapseId . '\', this, event);" title="Toggle product items table">
                    <i class="fe fe-chevron-down mr-1"></i><span>Items (' . $itemCount . ')</span>
                  </button>
                  <div class="d-flex align-items-center" style="gap: 3px;">
                    <a href="print_po.php?id=' . $p['po_id'] . '" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-1.5" style="font-size: 11px; height: 22px;" title="Print Purchase Order">
                      <i class="fe fe-printer mr-0.5"></i>Print
                    </a>
                    <a href="' . $challanUrl . '" class="btn btn-xs btn-primary font-weight-bold py-0 px-2" style="font-size: 11px; height: 22px;" title="Create Delivery Challan / Sale">
                      <i class="fe fe-truck mr-1"></i>Challan
                    </a>
                  </div>
                </div>

                <!-- Product Items Details Container (Hidden by default, expands on click) -->
                <div class="po-items-container mt-1.5" id="' . $collapseId . '" style="display: none;">
                  <div class="border rounded bg-white p-1.5 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-1 px-1">
                      <strong class="text-uppercase" style="font-size: 10px; color: #4a5568;"><i class="fe fe-layers mr-1"></i>Product Breakdown</strong>
                      <small class="text-muted" style="font-size: 10px;">' . $itemCount . ' product' . ($itemCount > 1 ? 's' : '') . '</small>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered table-striped table-po-items mb-0">
                        <thead>
                          <tr>
                            <th style="width: 14%; text-align: center; padding: 3px 5px !important;">ID</th>
                            <th style="width: 44%; padding: 3px 5px !important;">Product Name</th>
                            <th style="width: 14%; text-align: right; padding: 3px 5px !important;">Req</th>
                            <th style="width: 14%; text-align: right; padding: 3px 5px !important;">Del</th>
                            <th style="width: 14%; text-align: right; padding: 3px 5px !important;">Rem</th>
                          </tr>
                        </thead>
                        <tbody>
                          ' . $itemRows . '
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

              </div>
            </div>';
        }

        return $html;
    }
}

$initialPendingData = isset($dbc) ? get_pending_pos_data($dbc) : [
    'status' => 'success',
    'today' => date('Y-m-d'),
    'today_formatted' => date('d/m/Y'),
    'counts' => ['total' => 0, 'today' => 0, 'overdue' => 0, 'soon' => 0, 'upcoming' => 0, 'nodate' => 0],
    'pos' => []
];

$initTotal = $initialPendingData['counts']['total'] ?? 0;
$initToday = $initialPendingData['counts']['today'] ?? 0;
$initOverdue = $initialPendingData['counts']['overdue'] ?? 0;
$initUpcoming = ($initialPendingData['counts']['soon'] ?? 0) + ($initialPendingData['counts']['upcoming'] ?? 0);
$initTodayFormatted = $initialPendingData['today_formatted'] ?? date('d/m/Y');
?>

<!-- Pending POs Side Modal (Drawer) -->
<style>
  /* Sleek Compact Styles for Pending POs Drawer */
  .modal.fade.modal-slide .modal-dialog.modal-dialog-slide-po {
    width: 500px;
    max-width: 96vw;
    height: 100%;
    margin: 0;
    position: fixed;
    right: 0;
    top: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.16);
    background: #ffffff;
    z-index: 1060;
  }

  body.dark-theme .modal.fade.modal-slide .modal-dialog.modal-dialog-slide-po,
  body.dark .modal.fade.modal-slide .modal-dialog.modal-dialog-slide-po {
    background: #1b2130 !important;
    color: #e5e9f2;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.5);
  }

  .po-drawer-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: transparent;
    border: none;
  }

  .po-drawer-header {
    flex: 0 0 auto;
    padding: 10px 14px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    background: inherit;
  }

  body.dark-theme .po-drawer-header,
  body.dark .po-drawer-header {
    border-bottom-color: rgba(255, 255, 255, 0.1);
  }

  .po-drawer-body {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 10px 14px;
  }

  .po-drawer-footer {
    flex: 0 0 auto;
    padding: 8px 14px;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    background: inherit;
  }

  body.dark-theme .po-drawer-footer,
  body.dark .po-drawer-footer {
    border-top-color: rgba(255, 255, 255, 0.1);
  }

  /* Compact Filter Chips */
  .po-filter-chip {
    cursor: pointer;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 14px;
    border: 1px solid #ced4da;
    background: #fff;
    color: #495057;
    transition: all 0.15s ease-in-out;
    user-select: none;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    line-height: 1.4;
  }
  .po-filter-chip:hover {
    background: #f1f3f5;
    border-color: #adb5bd;
  }
  .po-filter-chip.active {
    background: #1b68ff;
    color: #fff;
    border-color: #1b68ff;
  }
  .po-filter-chip.chip-today.active {
    background: #e67e22;
    border-color: #e67e22;
    color: #fff;
  }
  .po-filter-chip.chip-overdue.active {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
  }
  .po-filter-chip.chip-upcoming.active {
    background: #17a2b8;
    border-color: #17a2b8;
    color: #fff;
  }

  body.dark-theme .po-filter-chip,
  body.dark .po-filter-chip {
    background: #272e48;
    border-color: #3b4566;
    color: #ced4da;
  }
  body.dark-theme .po-filter-chip:hover,
  body.dark .po-filter-chip:hover {
    background: #323b5c;
  }

  /* Compact PO Card Styling */
  .po-card {
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    margin-bottom: 7px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    transition: all 0.15s ease;
    border-left: 4px solid #6c757d;
  }
  .po-card:hover {
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
  }
  .po-card-inner {
    padding: 8px 10px;
  }
  .po-card.status-today {
    border-left-color: #e67e22;
    background: #fffdf9;
    border-color: #fbd38d;
  }
  .po-card.status-overdue {
    border-left-color: #dc3545;
    background: #fffafa;
    border-color: #feb2b2;
  }
  .po-card.status-soon {
    border-left-color: #3182ce;
  }
  .po-card.status-upcoming {
    border-left-color: #718096;
  }

  body.dark-theme .po-card,
  body.dark .po-card {
    background: #23293d !important;
    border-color: #374151 !important;
  }
  body.dark-theme .po-card.status-today,
  body.dark .po-card.status-today {
    background: #2a241b !important;
    border-color: #744210 !important;
  }
  body.dark-theme .po-card.status-overdue,
  body.dark .po-card.status-overdue {
    background: #2d1d20 !important;
    border-color: #742a2a !important;
  }
  body.dark-theme .po-customer-title {
    color: #f8f9fa !important;
  }
  body.dark-theme .po-stat-row {
    background: #1b2130 !important;
    border-color: #3b4566 !important;
    color: #ced4da !important;
  }

  /* Compact Product Items Table */
  .po-items-container {
    transition: none !important;
  }
  .table-po-items {
    font-size: 11px;
    margin-bottom: 0;
  }
  .table-po-items th {
    padding: 3px 5px !important;
    background-color: #2d3748 !important;
    color: #fff !important;
    font-weight: 600;
  }
  .table-po-items td {
    padding: 3px 5px !important;
    vertical-align: middle;
  }
  body.dark-theme .table-po-items,
  body.dark .table-po-items {
    color: #e5e9f2;
    background-color: #1b2130;
  }
  body.dark-theme .table-po-items td {
    border-color: #3b4566;
  }

  /* Pulse animation for Due Today */
  @keyframes po-pulse-warning {
    0% { box-shadow: 0 0 0 0 rgba(230, 126, 34, 0.35); }
    70% { box-shadow: 0 0 0 4px rgba(230, 126, 34, 0); }
    100% { box-shadow: 0 0 0 0 rgba(230, 126, 34, 0); }
  }
  .pulse-today {
    animation: po-pulse-warning 2s infinite;
  }

  /* Rotate icon for refresh button */
  .rotating {
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin {
    100% { transform: rotate(360deg); }
  }
</style>

<div class="modal fade modal-notif modal-slide" id="pendingPOModal" tabindex="-1" role="dialog" aria-labelledby="pendingPOModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-slide-po" role="document">
    <div class="modal-content po-drawer-content">
      
      <!-- Modal Header -->
      <div class="po-drawer-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-sm mr-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
            <i class="fe fe-truck fe-16"></i>
          </div>
          <div>
            <div class="d-flex align-items-center">
              <h6 class="modal-title font-weight-bold mb-0" id="pendingPOModalLabel" style="font-size: 14.5px;">Pending POs</h6>
              <span class="badge badge-primary ml-1.5 px-1.5 py-0.5" id="badge-total-pending" style="font-size: 11px;"><?=$initTotal?></span>
            </div>
            <small class="text-muted" id="po-drawer-subtitle" style="font-size: 10.5px;">
              Today: <?=$initTodayFormatted?> | Delivery & Remaining Status
            </small>
          </div>
        </div>
        <div class="d-flex align-items-center">
          <button type="button" class="btn btn-sm btn-light border py-0 px-1.5 mr-1" id="btn-refresh-po-data" title="Refresh pending POs data" style="height: 26px; cursor: pointer;" onclick="window.refreshPOModal()">
            <i class="fe fe-rotate-cw fe-12"></i>
          </button>
          <button type="button" class="close text-muted py-0 px-1 ml-1" data-dismiss="modal" aria-label="Close" style="opacity: 0.7;">
            <span aria-hidden="true" style="font-size: 22px; line-height: 1;">&times;</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs (Pending POs vs Shortcuts) -->
      <div class="px-3 pt-1.5 pb-1.5 bg-light border-bottom d-flex align-items-center justify-content-between">
        <ul class="nav nav-pills" id="poSideNavTabs" role="tablist" style="gap: 4px;">
          <li class="nav-item">
            <a class="nav-link active py-0.5 px-2.5 font-weight-bold" id="tab-po-btn" data-toggle="pill" href="#tab-po-content" role="tab" style="font-size: 11.5px;">
              <i class="fe fe-list mr-1"></i> Pending POs
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-0.5 px-2.5" id="tab-shortcuts-btn" data-toggle="pill" href="#tab-shortcuts-content" role="tab" style="font-size: 11.5px;">
              <i class="fe fe-command mr-1"></i> Shortcuts
            </a>
          </li>
        </ul>
        <a href="purchase_order_list.php" class="btn btn-link btn-sm text-primary p-0 font-weight-bold" style="font-size: 11.5px;">
          PO List <i class="fe fe-external-link fe-10 ml-0.5"></i>
        </a>
      </div>

      <!-- Modal Body -->
      <div class="po-drawer-body tab-content" id="poDrawerTabContent">
        
        <!-- TAB 1: PENDING POS -->
        <div class="tab-pane fade show active" id="tab-po-content" role="tabpanel">

          <!-- Search Bar -->
          <div class="input-group input-group-sm mb-1.5 shadow-sm">
            <div class="input-group-prepend">
              <span class="input-group-text bg-white border-right-0 py-0" style="height: 28px;"><i class="fe fe-search text-muted fe-12"></i></span>
            </div>
            <input type="text" id="po_search_input" class="form-control border-left-0 py-0" placeholder="Search PO#, customer, product name..." autocomplete="off" style="height: 28px; font-size: 11.5px;" oninput="window.applyPOSearch(this.value)">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary border-left-0 bg-white py-0 px-2" type="button" id="btn_clear_po_search" title="Clear search" style="display:none; height: 28px; cursor: pointer;" onclick="window.clearPOSearch()">
                <i class="fe fe-x fe-11"></i>
              </button>
            </div>
          </div>

          <!-- Compact Filter Chips (Image 1) -->
          <div class="d-flex flex-wrap align-items-center mb-1.5" style="gap: 5px;">
            <span class="po-filter-chip active" data-filter="all" role="button" onclick="window.applyPOFilter('all', this, event)" title="Show all pending POs">
              All <span class="badge badge-secondary badge-pill ml-1" id="chip-count-all" style="font-size: 10px;"><?=$initTotal?></span>
            </span>
            <span class="po-filter-chip chip-today" data-filter="today" role="button" onclick="window.applyPOFilter('today', this, event)" title="Show only POs due today">
              <i class="fe fe-alert-triangle fe-10 text-warning"></i> Due Today <span class="badge badge-warning badge-pill text-dark ml-1" id="chip-count-today" style="font-size: 10px;"><?=$initToday?></span>
            </span>
            <span class="po-filter-chip chip-overdue" data-filter="overdue" role="button" onclick="window.applyPOFilter('overdue', this, event)" title="Show only POs with delivery date passed">
              <i class="fe fe-alert-octagon fe-10 text-danger"></i> Date Over <span class="badge badge-danger badge-pill ml-1" id="chip-count-overdue" style="font-size: 10px;"><?=$initOverdue?></span>
            </span>
            <span class="po-filter-chip chip-upcoming" data-filter="upcoming" role="button" onclick="window.applyPOFilter('upcoming', this, event)" title="Show upcoming pending POs">
              <i class="fe fe-calendar fe-10 text-info"></i> Upcoming <span class="badge badge-info badge-pill ml-1" id="chip-count-upcoming" style="font-size: 10px;"><?=$initUpcoming?></span>
            </span>
          </div>

          <!-- Controls: Sort (Image 3) & Expand All (Image 2) -->
          <div class="d-flex align-items-center justify-content-between mb-2 pt-1 border-top" style="font-size: 11px;">
            <div class="d-flex align-items-center">
              <span class="text-muted mr-1"><i class="fe fe-sliders mr-0.5"></i>Sort:</span>
              <select id="po_sort_select" class="custom-select custom-select-sm py-0 pl-1.5 pr-3.5" style="height: 24px; font-size: 11px; width: auto; cursor: pointer;" onchange="window.applyPOSort(this.value, event)">
                <option value="nearest" selected>Delivery Date (Near / Today First)</option>
                <option value="overdue_first">Date Over (Overdue First)</option>
                <option value="upcoming_first">Upcoming Dates First</option>
                <option value="highest_rem">Highest Remaining Qty</option>
                <option value="po_desc">PO# (Newest to Oldest)</option>
              </select>
            </div>
            <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="btn-toggle-all-items" style="font-size: 11px; text-decoration: none; cursor: pointer;" onclick="window.toggleAllPOItems(this, event)" title="Expand or collapse all items tables">
              <i class="fe fe-maximize-2 fe-10 mr-0.5"></i><span id="toggle-all-items-text">Expand All</span>
            </button>
          </div>

          <!-- Alert summary box if overdue or today -->
          <div id="po-summary-alert-banner" class="alert alert-warning py-1 px-2.5 small mb-2 <?=($initToday > 0 || $initOverdue > 0)?'d-flex':'d-none'?> align-items-center justify-content-between" style="border-radius: 4px; font-size: 11px;">
            <div class="d-flex align-items-center">
              <i class="fe fe-bell fe-12 mr-1.5 text-warning"></i>
              <span id="po-summary-alert-text">
                <?php
                $msgArr = [];
                if ($initToday > 0) $msgArr[] = "<strong>{$initToday} PO" . ($initToday > 1 ? 's' : '') . "</strong> due today";
                if ($initOverdue > 0) $msgArr[] = "<strong>{$initOverdue} PO" . ($initOverdue > 1 ? 's' : '') . "</strong> date over";
                echo implode(' and ', $msgArr) . '!';
                ?>
              </span>
            </div>
          </div>

          <!-- Cards Container (Pre-rendered for zero loading time) -->
          <div id="po-cards-container">
            <?=render_pending_po_cards_html($initialPendingData['pos'] ?? [])?>
          </div>

        </div>

        <!-- TAB 2: KEYBOARD SHORTCUTS -->
        <div class="tab-pane fade" id="tab-shortcuts-content" role="tabpanel">
          <div class="alert alert-info py-1.5 px-2.5 small mb-2.5" style="font-size: 11.5px;">
            <i class="fe fe-info mr-1"></i> Quick keyboard shortcuts for common actions in Sales and Purchases.
          </div>
          <div class="list-group list-group-flush my-n2">
            <div class="list-group-item bg-transparent py-2">
              <div class="row align-items-center">
                <div class="col-auto">
                  <span class="fe fe-download fe-20 text-primary"></span>
                </div>
                <div class="col">
                  <small><strong>Sale And Purchases (Add Product row)</strong></small>
                  <div class="my-0 small text-muted"><kbd>alt</kbd> + <kbd>enter</kbd></div>
                </div>
              </div>
            </div>
            <div class="list-group-item bg-transparent py-2">
              <div class="row align-items-center">
                <div class="col-auto">
                  <span class="fe fe-printer fe-20 text-success"></span>
                </div>
                <div class="col">
                  <small><strong>Print Sale or Purchase</strong></small>
                  <div class="my-0 small text-muted"><kbd>alt</kbd> + <kbd>p</kbd></div>
                </div>
              </div>
            </div>
            <div class="list-group-item bg-transparent py-2">
              <div class="row align-items-center">
                <div class="col-auto">
                  <span class="fe fe-save fe-20 text-info"></span>
                </div>
                <div class="col">
                  <small><strong>Save Sale And Purchase</strong></small>
                  <div class="my-0 small text-muted"><kbd>alt</kbd> + <kbd>s</kbd></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="po-drawer-footer d-flex align-items-center justify-content-between">
        <small class="text-muted" id="po-drawer-footer-count" style="font-size: 11px;">
          Showing <strong><?=$initTotal?></strong> pending orders
        </small>
        <button type="button" class="btn btn-xs btn-secondary py-0.5 px-2.5" data-dismiss="modal" style="font-size: 11px;">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- Embedded Pre-fetched JSON Data -->
<script>
window.INITIAL_PENDING_POS = <?=json_encode($initialPendingData)?>;
</script>

<!-- Bulletproof Drawer Controller: Global Window API + Event Delegation -->
<script>
(function() {
  'use strict';

  // Global State Store
  window.PO_DRAWER = window.PO_DRAWER || {
    poData: (window.INITIAL_PENDING_POS && window.INITIAL_PENDING_POS.pos) ? window.INITIAL_PENDING_POS.pos : [],
    activeFilter: 'all',
    activeSort: 'nearest',
    searchQuery: '',
    allExpanded: false,
    isFetching: false
  };

  function formatNumber(num) {
    if (isNaN(num) || num === null || num === undefined) return '0';
    return parseFloat(num).toLocaleString('en-US');
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getAjaxUrl() {
    var path = window.location.pathname || '';
    var cleanDir = path.substring(0, path.lastIndexOf('/') + 1);
    return (cleanDir ? cleanDir : '') + 'php_action/custom_action.php';
  }

  // 1. FILTER FUNCTION
  function filterPOs(list) {
    var filter = window.PO_DRAWER.activeFilter || 'all';
    var query = (window.PO_DRAWER.searchQuery || '').toLowerCase();

    return list.filter(function(p) {
      // Urgency / Status Filter
      if (filter === 'today') {
        if (p.status !== 'today') return false;
      } else if (filter === 'overdue') {
        if (p.status !== 'overdue') return false;
      } else if (filter === 'upcoming') {
        if (p.status !== 'soon' && p.status !== 'upcoming') return false;
      }

      // Search Query Filter
      if (query) {
        var poMatch = (p.party_po || '').toLowerCase().indexOf(query) !== -1;
        var custMatch = (p.customer_name || '').toLowerCase().indexOf(query) !== -1;
        var dateMatch = (p.formatted_target_date || '').indexOf(query) !== -1 || (p.target_date || '').indexOf(query) !== -1;
        var itemMatch = false;

        if (p.items && p.items.length) {
          for (var i = 0; i < p.items.length; i++) {
            var item = p.items[i];
            if ((item.product_name || '').toLowerCase().indexOf(query) !== -1 || String(item.product_id).indexOf(query) !== -1) {
              itemMatch = true;
              break;
            }
          }
        }
        if (!poMatch && !custMatch && !dateMatch && !itemMatch) {
          return false;
        }
      }

      return true;
    });
  }

  // 2. SORT FUNCTION
  function sortPOs(list, sortType) {
    var sorted = list.slice();

    if (sortType === 'nearest') {
      // Due Today on top, then Date Over (closest to today), then upcoming (+1, +2...), then nodate
      sorted.sort(function(a, b) {
        var getGroup = function(p) {
          if (p.status === 'today') return 1;
          if (p.status === 'overdue') return 2;
          if (p.days_diff !== null && p.days_diff > 0) return 3;
          return 4;
        };
        var ga = getGroup(a);
        var gb = getGroup(b);
        if (ga !== gb) return ga - gb;
        if (ga === 1) return (b.po_id || 0) - (a.po_id || 0);
        if (ga === 2) return (b.days_diff || 0) - (a.days_diff || 0);
        if (ga === 3) return (a.days_diff || 0) - (b.days_diff || 0);
        return (b.po_id || 0) - (a.po_id || 0);
      });
    } else if (sortType === 'overdue_first') {
      // Date Over first (oldest first), then Due Today, then upcoming
      sorted.sort(function(a, b) {
        var getGroup = function(p) {
          if (p.status === 'overdue') return 1;
          if (p.status === 'today') return 2;
          if (p.days_diff !== null && p.days_diff > 0) return 3;
          return 4;
        };
        var ga = getGroup(a);
        var gb = getGroup(b);
        if (ga !== gb) return ga - gb;
        if (ga === 1) return (a.days_diff || 0) - (b.days_diff || 0);
        if (ga === 2) return (b.po_id || 0) - (a.po_id || 0);
        if (ga === 3) return (a.days_diff || 0) - (b.days_diff || 0);
        return (b.po_id || 0) - (a.po_id || 0);
      });
    } else if (sortType === 'upcoming_first') {
      // Due today, then upcoming ascending, then overdue
      sorted.sort(function(a, b) {
        var getGroup = function(p) {
          if (p.status === 'today') return 1;
          if (p.days_diff !== null && p.days_diff > 0) return 2;
          if (p.status === 'overdue') return 3;
          return 4;
        };
        var ga = getGroup(a);
        var gb = getGroup(b);
        if (ga !== gb) return ga - gb;
        if (ga === 1) return (b.po_id || 0) - (a.po_id || 0);
        if (ga === 2) return (a.days_diff || 0) - (b.days_diff || 0);
        if (ga === 3) return (b.days_diff || 0) - (a.days_diff || 0);
        return (b.po_id || 0) - (a.po_id || 0);
      });
    } else if (sortType === 'highest_rem') {
      // Highest remaining quantity first
      sorted.sort(function(a, b) {
        return (b.total_remaining || 0) - (a.total_remaining || 0);
      });
    } else if (sortType === 'po_desc') {
      // PO ID descending (newest PO first)
      sorted.sort(function(a, b) {
        return (b.po_id || 0) - (a.po_id || 0);
      });
    }

    return sorted;
  }

  // 3. RENDER PO CARDS
  window.renderPOCards = function() {
    var poData = window.PO_DRAWER.poData || [];
    var filtered = filterPOs(poData);
    var sorted = sortPOs(filtered, window.PO_DRAWER.activeSort || 'nearest');
    var container = document.getElementById('po-cards-container');
    var footerCount = document.getElementById('po-drawer-footer-count');
    var allExpanded = window.PO_DRAWER.allExpanded;

    if (footerCount) {
      footerCount.innerHTML = 'Showing <strong>' + sorted.length + '</strong> of ' + poData.length + ' pending orders';
    }

    if (!container) return;

    if (!sorted.length) {
      if (poData.length === 0) {
        container.innerHTML =
          '<div class="text-center py-4 text-muted">' +
            '<i class="fe fe-check-circle fe-24 text-success mb-2 d-block"></i>' +
            '<h6 class="font-weight-bold mb-1" style="font-size: 13px;">All POs Completed!</h6>' +
            '<small>No pending purchase orders with remaining quantities.</small>' +
          '</div>';
      } else {
        container.innerHTML =
          '<div class="text-center py-4 text-muted">' +
            '<i class="fe fe-search fe-24 mb-2 d-block"></i>' +
            '<h6 class="font-weight-bold mb-1" style="font-size: 13px;">No matching POs found</h6>' +
            '<p class="small text-muted mb-2">No orders match filter "<strong>' + escapeHtml(window.PO_DRAWER.activeFilter) + '</strong>"' +
            (window.PO_DRAWER.searchQuery ? ' and search query "' + escapeHtml(window.PO_DRAWER.searchQuery) + '"' : '') + '.</p>' +
            '<button type="button" class="btn btn-xs btn-outline-primary" onclick="window.resetPOFilters()">Reset Filters & Show All</button>' +
          '</div>';
      }
      return;
    }

    var html = '';

    sorted.forEach(function(p) {
      var statusClass = 'status-' + (p.status || 'upcoming');
      var pulseClass = (p.status === 'today') ? 'pulse-today' : '';
      var collapseId = 'po_items_' + p.po_id;
      var isCollapsed = !allExpanded;

      // Delivery badge
      var deliveryBadge = '';
      if (p.status === 'today') {
        deliveryBadge = '<span class="badge badge-warning text-dark font-weight-bold px-1.5 py-0.5 shadow-sm" style="font-size: 10.5px;"><i class="fe fe-alert-triangle mr-1"></i>DUE TODAY (' + escapeHtml(p.formatted_target_date) + ')</span>';
      } else if (p.status === 'overdue') {
        deliveryBadge = '<span class="badge badge-danger text-white font-weight-bold px-1.5 py-0.5 shadow-sm" style="font-size: 10.5px;"><i class="fe fe-alert-octagon mr-1"></i>DATE OVER (' + escapeHtml(p.days_text) + ')</span>';
      } else if (p.status === 'soon') {
        deliveryBadge = '<span class="badge badge-info text-white font-weight-bold px-1.5 py-0.5" style="font-size: 10.5px;"><i class="fe fe-clock mr-1"></i>' + escapeHtml(p.days_text) + ' (' + escapeHtml(p.formatted_target_date) + ')</span>';
      } else {
        deliveryBadge = '<span class="badge badge-light border text-muted px-1.5 py-0.5" style="font-size: 10.5px;"><i class="fe fe-calendar mr-1"></i>Due: ' + escapeHtml(p.formatted_target_date) + '</span>';
      }

      // Items table rows
      var itemRows = '';
      if (p.items && p.items.length) {
        p.items.forEach(function(item) {
          var remBadge = '';
          if (item.remaining_qty > 0) {
            remBadge = '<span class="text-danger font-weight-bold">' + formatNumber(item.remaining_qty) + '</span>';
          } else {
            remBadge = '<span class="badge badge-success px-1.5 py-0.5" style="font-size: 10px;"><i class="fe fe-check mr-1"></i>0</span>';
          }

          itemRows += 
            '<tr>' +
              '<td class="text-center font-weight-bold text-muted" style="padding: 3px 5px !important;">' + item.product_id + '</td>' +
              '<td class="font-weight-600" style="padding: 3px 5px !important;">' + escapeHtml(item.product_name) + '</td>' +
              '<td class="text-right" style="padding: 3px 5px !important;">' + formatNumber(item.ordered_qty) + '</td>' +
              '<td class="text-right text-success" style="padding: 3px 5px !important;">' + formatNumber(item.delivered_qty) + '</td>' +
              '<td class="text-right" style="padding: 3px 5px !important;">' + remBadge + '</td>' +
            '</tr>';
        });
      } else {
        itemRows = '<tr><td colspan="5" class="text-center text-muted" style="padding: 3px 5px !important;">No items found</td></tr>';
      }

      var challanUrl = 'credit_sale.php?cust_id=' + encodeURIComponent(p.customer_id || '') + '&po_no=' + encodeURIComponent(p.party_po || '');
      var itemCount = p.items ? p.items.length : 0;

      html += 
        '<div class="po-card ' + statusClass + ' ' + pulseClass + '" data-po-id="' + p.po_id + '">' +
          '<div class="po-card-inner">' +

            // Row 1: Header (Clickable to toggle items)
            '<div class="po-card-clickable-header d-flex align-items-center justify-content-between mb-1" style="cursor: pointer;" data-target="#' + collapseId + '" onclick="window.togglePOItems(\'' + collapseId + '\', this.closest(\'.po-card\').querySelector(\'.btn-toggle-items\'), event)" title="Click to view/hide product items">' +
              '<div class="d-flex align-items-center text-truncate mr-2">' +
                '<span class="badge badge-dark px-1.5 py-0.5 font-weight-bold mr-1.5" style="font-size: 11px;">PO# ' + escapeHtml(p.party_po) + '</span>' +
                '<strong class="text-dark text-truncate po-customer-title" title="' + escapeHtml(p.customer_name) + '" style="font-size: 12.5px;">' + escapeHtml(p.customer_name) + '</strong>' +
              '</div>' +
              '<div class="flex-shrink-0">' +
                deliveryBadge +
              '</div>' +
            '</div>' +

            // Row 2: Date Info
            '<div class="d-flex align-items-center justify-content-between text-muted mb-1" style="font-size: 10.5px;">' +
              '<span><i class="fe fe-calendar fe-10 mr-1"></i>Ordered: ' + escapeHtml(p.formatted_po_date) + '</span>' +
              '<span>Target Date: <strong>' + escapeHtml(p.formatted_target_date) + '</strong></span>' +
            '</div>' +

            // Row 3: Sleek Inline Quantity Summary
            '<div class="d-flex align-items-center justify-content-between bg-light rounded px-2 py-1 mb-1 border po-stat-row" style="font-size: 11px;">' +
              '<span>Req: <strong>' + formatNumber(p.total_ordered) + '</strong></span>' +
              '<span class="text-muted">|</span>' +
              '<span class="text-success font-weight-600">Del: <strong>' + formatNumber(p.total_delivered) + '</strong></span>' +
              '<span class="text-muted">|</span>' +
              '<span class="text-danger font-weight-bold">Rem: <strong>' + formatNumber(p.total_remaining) + '</strong></span>' +
              '<span class="text-muted">|</span>' +
              '<span class="badge badge-light border text-muted px-1" style="font-size: 10px;">' + p.delivery_percent + '%</span>' +
            '</div>' +

            // Progress bar
            '<div class="progress mb-1.5" style="height: 3px;">' +
              '<div class="progress-bar bg-success" role="progressbar" style="width: ' + p.delivery_percent + '%;" aria-valuenow="' + p.delivery_percent + '" aria-valuemin="0" aria-valuemax="100"></div>' +
            '</div>' +

            // Row 4: Actions & View Items Toggle Button
            '<div class="d-flex align-items-center justify-content-between pt-1 border-top" style="font-size: 11px;">' +
              '<button type="button" class="btn btn-xs btn-outline-secondary btn-toggle-items py-0 px-1.5 font-weight-600" data-target="#' + collapseId + '" style="font-size: 11px; height: 22px; cursor: pointer;" onclick="window.togglePOItems(\'' + collapseId + '\', this, event);" title="Toggle product items table">' +
                '<i class="fe ' + (isCollapsed ? 'fe-chevron-down' : 'fe-chevron-up') + ' mr-1"></i>' +
                '<span>' + (isCollapsed ? 'Items (' + itemCount + ')' : 'Hide') + '</span>' +
              '</button>' +
              '<div class="d-flex align-items-center" style="gap: 3px;">' +
                '<a href="print_po.php?id=' + p.po_id + '" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-1.5" style="font-size: 11px; height: 22px;" title="Print Purchase Order">' +
                  '<i class="fe fe-printer mr-0.5"></i>Print' +
                '</a>' +
                '<a href="' + challanUrl + '" class="btn btn-xs btn-primary font-weight-bold py-0 px-2" style="font-size: 11px; height: 22px;" title="Create Delivery Challan / Sale">' +
                  '<i class="fe fe-truck mr-1"></i>Challan' +
                '</a>' +
              '</div>' +
            '</div>' +

            // Collapsible Product Items Details Container (Image 4)
            '<div class="po-items-container mt-1.5" id="' + collapseId + '" style="display: ' + (isCollapsed ? 'none' : 'block') + ';">' +
              '<div class="border rounded bg-white p-1.5 shadow-sm">' +
                '<div class="d-flex align-items-center justify-content-between mb-1 px-1">' +
                  '<strong class="text-uppercase" style="font-size: 10px; color: #4a5568;"><i class="fe fe-layers mr-1"></i>Product Breakdown</strong>' +
                  '<small class="text-muted" style="font-size: 10px;">' + itemCount + ' product' + (itemCount > 1 ? 's' : '') + '</small>' +
                '</div>' +
                '<div class="table-responsive">' +
                  '<table class="table table-sm table-bordered table-striped table-po-items mb-0">' +
                    '<thead>' +
                      '<tr>' +
                        '<th style="width: 14%; text-align: center; padding: 3px 5px !important;">ID</th>' +
                        '<th style="width: 44%; padding: 3px 5px !important;">Product Name</th>' +
                        '<th style="width: 14%; text-align: right; padding: 3px 5px !important;">Req</th>' +
                        '<th style="width: 14%; text-align: right; padding: 3px 5px !important;">Del</th>' +
                        '<th style="width: 14%; text-align: right; padding: 3px 5px !important;">Rem</th>' +
                      '</tr>' +
                    '</thead>' +
                    '<tbody>' +
                      itemRows +
                    '</tbody>' +
                  '</table>' +
                '</div>' +
              '</div>' +
            '</div>' +

          '</div>' +
        '</div>';
    });

    container.innerHTML = html;
  };

  // 4. ACTION: INDIVIDUAL ITEM TOGGLE (Image 4)
  window.togglePOItems = function(collapseId, btnElem, e) {
    if (e && e.stopPropagation) {
      e.stopPropagation();
    }

    var now = Date.now();
    if (window.togglePOItems._lastCall && window.togglePOItems._lastId === collapseId && (now - window.togglePOItems._lastCall < 200)) {
      return false;
    }
    window.togglePOItems._lastCall = now;
    window.togglePOItems._lastId = collapseId;

    var container = document.getElementById(collapseId);
    if (!container) return false;

    var btn = btnElem || document.querySelector('.btn-toggle-items[data-target="#' + collapseId + '"]');
    var isHidden = (container.style.display === 'none' || window.getComputedStyle(container).display === 'none');

    if (isHidden) {
      container.style.setProperty('display', 'block', 'important');
      if (btn) {
        var icon = btn.querySelector('i');
        var span = btn.querySelector('span');
        if (icon) icon.className = 'fe fe-chevron-up mr-1';
        if (span) span.textContent = 'Hide';
      }
    } else {
      container.style.setProperty('display', 'none', 'important');
      if (btn) {
        var icon = btn.querySelector('i');
        var span = btn.querySelector('span');
        if (icon) icon.className = 'fe fe-chevron-down mr-1';
        var trs = container.querySelectorAll('tbody tr').length;
        if (span) span.textContent = 'Items (' + trs + ')';
      }
    }
    return false;
  };

  // 5. ACTION: EXPAND / COLLAPSE ALL (Image 2)
  window.toggleAllPOItems = function(btnElem, e) {
    if (e) {
      try { e.preventDefault(); e.stopPropagation(); } catch (err) {}
    }

    // Debounce to prevent duplicate execution from event bubbling
    var now = Date.now();
    if (window.toggleAllPOItems._lastClick && (now - window.toggleAllPOItems._lastClick < 250)) {
      return false;
    }
    window.toggleAllPOItems._lastClick = now;

    var isExpanding = !window.PO_DRAWER.allExpanded;
    window.PO_DRAWER.allExpanded = isExpanding;

    var txt = document.getElementById('toggle-all-items-text');
    var btn = btnElem || document.getElementById('btn-toggle-all-items');
    var icon = btn ? btn.querySelector('i') : null;

    if (txt) txt.textContent = isExpanding ? 'Collapse All' : 'Expand All';
    if (icon) icon.className = isExpanding ? 'fe fe-minimize-2 fe-10 mr-0.5' : 'fe fe-maximize-2 fe-10 mr-0.5';

    // Toggle all product breakdown containers synchronously
    var containers = document.querySelectorAll('.po-items-container');
    containers.forEach(function(c) {
      c.style.setProperty('display', isExpanding ? 'block' : 'none', 'important');
    });

    // Update all item button icons and labels across all cards
    var itemBtns = document.querySelectorAll('.btn-toggle-items');
    itemBtns.forEach(function(b) {
      var bIcon = b.querySelector('i');
      var bSpan = b.querySelector('span');
      if (isExpanding) {
        if (bIcon) bIcon.className = 'fe fe-chevron-up mr-1';
        if (bSpan) bSpan.textContent = 'Hide';
      } else {
        if (bIcon) bIcon.className = 'fe fe-chevron-down mr-1';
        var targetId = b.getAttribute('data-target') ? b.getAttribute('data-target').replace('#', '') : '';
        var targetEl = document.getElementById(targetId);
        var count = targetEl ? targetEl.querySelectorAll('tbody tr').length : 0;
        if (bSpan) bSpan.textContent = 'Items (' + count + ')';
      }
    });

    return false;
  };

  // 6. ACTION: APPLY FILTER CHIP (Image 1)
  window.applyPOFilter = function(filterType, chipElem, e) {
    if (e && e.stopPropagation) {
      e.stopPropagation();
    }

    window.PO_DRAWER.activeFilter = filterType || 'all';

    // Highlight active chip
    var chips = document.querySelectorAll('.po-filter-chip');
    chips.forEach(function(c) {
      c.classList.remove('active');
    });

    if (chipElem) {
      chipElem.classList.add('active');
    } else {
      var activeEl = document.querySelector('.po-filter-chip[data-filter="' + filterType + '"]');
      if (activeEl) activeEl.classList.add('active');
    }

    window.renderPOCards();
  };

  // 7. ACTION: APPLY SORT DROPDOWN (Image 3)
  window.applyPOSort = function(sortValue, e) {
    if (e && e.stopPropagation) {
      e.stopPropagation();
    }

    window.PO_DRAWER.activeSort = sortValue || 'nearest';
    var sel = document.getElementById('po_sort_select');
    if (sel && sel.value !== sortValue) {
      sel.value = sortValue;
    }
    window.renderPOCards();
  };

  // 8. ACTION: SEARCH INPUT
  window.applyPOSearch = function(val) {
    window.PO_DRAWER.searchQuery = (val || '').trim();
    var clearBtn = document.getElementById('btn_clear_po_search');
    if (clearBtn) {
      clearBtn.style.display = window.PO_DRAWER.searchQuery ? 'inline-block' : 'none';
    }
    window.renderPOCards();
  };

  window.clearPOSearch = function() {
    var inp = document.getElementById('po_search_input');
    if (inp) inp.value = '';
    window.applyPOSearch('');
  };

  window.resetPOFilters = function() {
    window.clearPOSearch();
    window.applyPOFilter('all');
  };

  // 9. UPDATE COUNTS & BADGES
  window.updatePOCountsAndBadges = function(resp) {
    var counts = resp.counts || {};
    var total = counts.total || 0;
    var todayCount = counts.today || 0;
    var overdueCount = counts.overdue || 0;
    var upcomingCount = (counts.soon || 0) + (counts.upcoming || 0);

    var badgeTotal = document.getElementById('badge-total-pending');
    if (badgeTotal) badgeTotal.textContent = total;

    var cAll = document.getElementById('chip-count-all');
    if (cAll) cAll.textContent = total;

    var cToday = document.getElementById('chip-count-today');
    if (cToday) cToday.textContent = todayCount;

    var cOverdue = document.getElementById('chip-count-overdue');
    if (cOverdue) cOverdue.textContent = overdueCount;

    var cUpcoming = document.getElementById('chip-count-upcoming');
    if (cUpcoming) cUpcoming.textContent = upcomingCount;

    if (resp.today_formatted) {
      var sub = document.getElementById('po-drawer-subtitle');
      if (sub) sub.textContent = 'Today: ' + resp.today_formatted + ' | Delivery & Remaining Status';
    }

    // Top Header Navbar Bell Badge
    var totalUrgent = todayCount + overdueCount;
    var navBadge = document.getElementById('nav-po-badge');
    var navDot = document.getElementById('nav-po-dot');

    if (total > 0) {
      if (navBadge) {
        navBadge.textContent = total;
        navBadge.style.display = 'inline-block';
      }
      if (navDot) {
        navDot.className = 'dot ' + (totalUrgent > 0 ? 'bg-danger' : 'bg-success');
        navDot.style.display = 'inline-block';
      }
    } else {
      if (navBadge) navBadge.style.display = 'none';
      if (navDot) navDot.style.display = 'none';
    }

    // Alert Banner in modal
    var banner = document.getElementById('po-summary-alert-banner');
    var alertText = document.getElementById('po-summary-alert-text');
    if (banner && alertText) {
      if (todayCount > 0 || overdueCount > 0) {
        var alertParts = [];
        if (todayCount > 0) {
          alertParts.push('<strong>' + todayCount + ' PO' + (todayCount > 1 ? 's' : '') + '</strong> due today');
        }
        if (overdueCount > 0) {
          alertParts.push('<strong>' + overdueCount + ' PO' + (overdueCount > 1 ? 's' : '') + '</strong> date over');
        }
        alertText.innerHTML = alertParts.join(' and ') + '!';
        banner.className = 'alert alert-warning py-1 px-2.5 small mb-2 d-flex align-items-center justify-content-between';
      } else {
        banner.className = 'alert alert-warning py-1 px-2.5 small mb-2 d-none';
      }
    }
  };

  // 10. REFRESH MODAL DATA (AJAX)
  window.refreshPOModal = function(callback) {
    if (window.PO_DRAWER.isFetching) return;
    window.PO_DRAWER.isFetching = true;

    var refreshIcon = document.querySelector('#btn-refresh-po-data i');
    if (refreshIcon) refreshIcon.classList.add('rotating');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', getAjaxUrl(), true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        window.PO_DRAWER.isFetching = false;
        if (refreshIcon) refreshIcon.classList.remove('rotating');

        if (xhr.status === 200) {
          try {
            var resp = JSON.parse(xhr.responseText);
            if (resp && resp.status === 'success') {
              window.PO_DRAWER.poData = resp.pos || [];
              window.updatePOCountsAndBadges(resp);
              window.renderPOCards();
              if (typeof callback === 'function') callback();
            }
          } catch (e) {
            console.error('Error parsing pending POs JSON:', e);
          }
        }
      }
    };
    xhr.send('get_pending_pos_modal=1');
  };

  // Sync Bell Badge on startup
  if (window.INITIAL_PENDING_POS) {
    window.updatePOCountsAndBadges(window.INITIAL_PENDING_POS);
  }

  // Setup Modal Show Listener when jQuery is available
  function bindListeners() {
    if (typeof jQuery !== 'undefined') {
      var $ = jQuery;
      // Refresh on modal show
      $(document).off('show.bs.modal.poModal', '#pendingPOModal').on('show.bs.modal.poModal', '#pendingPOModal', function() {
        window.refreshPOModal();
      });
    } else {
      setTimeout(bindListeners, 50);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindListeners);
  } else {
    bindListeners();
  }

})();
</script>
