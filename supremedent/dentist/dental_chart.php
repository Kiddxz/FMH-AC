<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);

start_secure_session();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "dentist") {
    header("Location: ../auth/login.php");
    exit;
}
require_once "../config/database.php";

$firstName = $_SESSION["first_name"] ?? "Dentist";
$chartId = isset($_GET['chart']) ? (int) $_GET['chart'] : 0;
$viewMode = isset($_GET['view']) && (int) $_GET['view'] === 1;
$selectedPatientId = isset($_GET['patient']) ? (int) $_GET['patient'] : 0;
$createMode = isset($_GET['new']) && (int) $_GET['new'] === 1;
$message = '';
$error = '';

$conn->query("CREATE TABLE IF NOT EXISTS dental_charts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    patient_id INT(11) NOT NULL,
    dentist_id INT(11) DEFAULT NULL,
    chart_data LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY patient_id (patient_id),
    KEY dentist_id (dentist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dental_chart'])) {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $postedChartId = (int) ($_POST['chart_id'] ?? 0);
    $formData = $_POST;
    unset($formData['save_dental_chart'], $formData['patient_id'], $formData['chart_id']);
    $json = json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($patientId <= 0 || $json === false) {
        $error = 'Please select a patient and complete the chart.';
    } else {
        if ($postedChartId > 0) {
            $stmt = $conn->prepare("UPDATE dental_charts SET patient_id=?, dentist_id=?, chart_data=? WHERE id=?");
            $dentistId = (int) $_SESSION['user_id'];
            $stmt->bind_param('iisi', $patientId, $dentistId, $json, $postedChartId);
        } else {
            $stmt = $conn->prepare("INSERT INTO dental_charts (patient_id, dentist_id, chart_data) VALUES (?, ?, ?)");
            $dentistId = (int) $_SESSION['user_id'];
            $stmt->bind_param('iis', $patientId, $dentistId, $json);
        }
        if ($stmt && $stmt->execute()) {
            $savedId = $postedChartId > 0 ? $postedChartId : $stmt->insert_id;
            $stmt->close();
            header('Location: dental_chart.php?saved=1&chart=' . $savedId);
            exit;
        }
        if ($stmt)
            $stmt->close();
        $error = 'Unable to save the dental chart.';
    }
}
if (isset($_GET['saved']))
    $message = 'Dental chart saved successfully.';

$patients = [];
$result = $conn->query("SELECT id, first_name, last_name, email FROM users WHERE role='patient' AND status='active' ORDER BY last_name, first_name");
if ($result)
    while ($row = $result->fetch_assoc())
        $patients[] = $row;

$charts = [];
$result = $conn->query("SELECT c.id, c.patient_id, c.updated_at, u.first_name, u.last_name FROM dental_charts c INNER JOIN users u ON u.id=c.patient_id ORDER BY c.updated_at DESC");
if ($result)
    while ($row = $result->fetch_assoc())
        $charts[] = $row;

$loadedChart = null;
if ($chartId > 0) {
    $stmt = $conn->prepare("SELECT c.*, u.first_name, u.last_name FROM dental_charts c INNER JOIN users u ON u.id=c.patient_id WHERE c.id=? LIMIT 1");
    $stmt->bind_param('i', $chartId);
    $stmt->execute();
    $loadedChart = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($loadedChart)
        $selectedPatientId = (int) $loadedChart['patient_id'];
}

$patientMap = [];
foreach ($patients as $p)
    $patientMap[$p['id']] = $p;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Dental Chart - SupremeDent</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Dedicated chart page layout: same SupremeDent sidebar/header language as the existing dentist pages. */
        .chart-page-main {
            background: #f3f7fa;
            min-height: calc(100vh - 72px);
            padding: 28px 34px 50px;
        }

        .chart-page-wrap {
            max-width: 1220px;
            margin: 0 auto;
        }

        .chart-page-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .chart-page-title h1 {
            margin: 0;
            font-size: 30px;
            color: #142334;
            font-weight: 600;
        }

        .chart-page-title p {
            margin: 6px 0 0;
            color: #71839c;
            font-size: 14px;
        }

        .chart-primary {
            background: #0c6654 !important;
            color: #fff !important;
            border: 0;
            border-radius: 9px;
            padding: 11px 18px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }

        .chart-primary:hover {
            background: #084f42 !important;
        }

        .chart-directory-card {
            background: #fff;
            border: 1px solid #dbe5ea;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(26, 55, 72, .06);
            overflow: hidden;
        }

        .chart-directory-head {
            padding: 20px 22px;
            border-bottom: 1px solid #e2e9ed;
        }

        .chart-directory-head h2 {
            margin: 0;
            font-size: 20px;
            color: #142334;
        }

        .chart-directory-head p {
            margin: 5px 0 0;
            color: #71839c;
            font-size: 13px;
        }

        .chart-search-row {
            display: grid;
            grid-template-columns: 1fr 110px;
            gap: 10px;
            padding: 18px 22px;
        }

        .chart-search-row input {
            height: 42px;
            border: 1px solid #b8d0c9;
            border-radius: 9px;
            padding: 0 13px;
            font-size: 14px;
            outline: none;
        }

        .chart-search-row button {
            border: 0;
            border-radius: 22px;
            background: #0c6654;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .chart-table-scroll {
            overflow-x: auto;
        }

        .chart-patient-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .chart-patient-table th,
        .chart-patient-table td {
            padding: 15px 18px;
            text-align: left;
            border-top: 1px solid #e5ebef;
            font-size: 14px;
        }

        .chart-patient-table th {
            background: #f7fafb;
            color: #17212b;
            font-weight: 700;
        }

        .chart-patient-table tbody tr:hover {
            background: #f4fbf8;
        }

        .chart-actions-cell {
            white-space: nowrap;
        }

        .chart-row-action {
            width: 42px;
            height: 42px;
            padding: 0;
            margin-right: 5px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #263b4a;
            font-size: 18px;
            cursor: pointer;
        }

        .chart-row-action:hover {
            background: #eaf6f2;
            color: #0c6654;
        }

        .chart-row-action.print:hover {
            color: #1769aa;
        }

        .chart-empty {
            padding: 44px;
            text-align: center;
            color: #71839c;
        }

        .chart-editor-shell {
            background: transparent;
        }

        /* Create/Edit chart opens as a separate page-like overlay so the chart process is not visible on the directory page. */
        .chart-editor-shell.chart-modal-mode {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(17, 35, 49, .58);
            padding: 28px 28px 40px;
            overflow-y: auto;
            backdrop-filter: blur(2px);
        }

        .chart-editor-shell.chart-modal-mode .chart-editor-top {
            max-width: 1180px;
            margin: 0 auto 14px;
            background: #fff;
            border: 1px solid #dbe5ea;
            border-radius: 14px;
            padding: 14px 18px;
            box-shadow: 0 12px 35px rgba(17, 35, 49, .16);
        }

        .chart-editor-shell.chart-modal-mode .chart-select-wrap {
            max-width: 1180px;
        }

        .chart-editor-shell.chart-modal-mode #chartEditor {
            max-width: 1180px;
            margin: 0 auto 20px;
            background: #f4f8fa;
            border-radius: 16px;
            padding: 0 0 24px;
            box-shadow: 0 16px 45px rgba(17, 35, 49, .18);
        }

        .chart-editor-shell.chart-modal-mode #chartForm {
            padding: 0 18px;
        }

        .chart-editor-shell.chart-modal-mode .chart-sheet {
            background: #fff;
        }

        .chart-modal-close {
            border: 0;
            background: #edf7f3;
            color: #0c6654;
            border-radius: 9px;
            padding: 9px 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .chart-modal-close:hover {
            background: #dff0ea;
        }

        body.chart-modal-open {
            overflow: hidden;
        }

        .chart-editor-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .chart-editor-top h2 {
            margin: 0;
            font-size: 23px;
            color: #142334;
        }

        .chart-editor-top p {
            margin: 4px 0 0;
            color: #71839c;
            font-size: 13px;
        }

        .chart-back {
            border: 1px solid #c8d9d4;
            background: #fff;
            color: #0c6654;
            border-radius: 8px;
            padding: 9px 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .chart-mode-note {
            background: #eaf6f2;
            border: 1px solid #c9e5dc;
            color: #0c6654;
            border-radius: 9px;
            padding: 10px 13px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        #chartEditor .chart-sheet {
            max-width: 980px;
            margin: 0 auto;
            padding: 20px 22px;
            border: 1px solid #d4e0e5;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(26, 55, 72, .06);
            font-family: Arial, sans-serif;
        }

        #chartEditor .chart-record-clinic {
            color: #0c6654;
        }

        #chartEditor .chart-record-title {
            font-size: 18px;
        }

        #chartEditor .chart-model-frame {
            background: #eef4f7;
            border-radius: 8px;
        }

        #chartEditor .chart-actions {
            justify-content: center;
        }

        #chartEditor .chart-actions .btn-primary {
            background: #0c6654 !important;
            border-radius: 8px;
        }

        #chartEditor .chart-actions .btn-secondary {
            background: #fff !important;
            border: 1px solid #0c6654 !important;
            color: #0c6654 !important;
            border-radius: 8px;
        }

        .chart-select-wrap {
            max-width: 980px;
            margin: 0 auto 12px;
            background: #fff;
            border: 1px solid #dbe5ea;
            border-radius: 12px;
            padding: 14px 16px;
        }

        .chart-select-wrap label {
            display: block;
            font-weight: 700;
            color: #142334;
            margin-bottom: 7px;
        }

        .chart-select-wrap select {
            width: 100%;
            height: 42px;
            border: 1px solid #b8d0c9;
            border-radius: 8px;
            padding: 0 10px;
            background: #fff;
            font-size: 14px;
        }

        .view-mode #chartForm input:not([type=checkbox]),
        .view-mode #chartForm select,
        .view-mode #chartForm textarea {
            pointer-events: none;
            background: #fafafa;
        }

        .view-mode #chartForm input[type=checkbox] {
            pointer-events: none;
        }

        .view-mode #chartForm .chart-actions {
            display: none !important;
        }

        @media(max-width:760px) {
            .chart-page-main {
                padding: 18px 14px 36px
            }

            .chart-page-title {
                align-items: flex-start;
                flex-direction: column
            }

            .chart-page-title h1 {
                font-size: 25px
            }

            .chart-search-row {
                grid-template-columns: 1fr
            }

            .chart-editor-top {
                align-items: flex-start;
                flex-direction: column
            }

            .chart-editor-top {
                position: relative
            }

            .chart-editor-shell {
                overflow-x: auto
            }

            .chart-editor-shell #chartEditor {
                min-width: 650px
            }
        }

        @media print {
            body * {
                visibility: hidden !important
            }

            .printable-chart,
            .printable-chart * {
                visibility: visible !important
            }

            .printable-chart {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important
            }
        }

        /* ===== WebUp11(2) chart visual transfer / modern layout ===== */
        .chart-page-main {
            background: linear-gradient(180deg, #f3f8fb 0%, #eef5f7 100%);
            padding: 30px 34px 60px;
        }

        .chart-page-wrap {
            max-width: 1320px
        }

        .chart-page-title {
            margin-bottom: 22px
        }

        .chart-page-title h1 {
            font-size: 31px;
            font-weight: 700;
            letter-spacing: -.3px
        }

        .chart-page-title p {
            font-size: 14px;
            color: #71839c
        }

        .chart-primary {
            background: #0d6654 !important;
            border-radius: 10px;
            padding: 12px 19px;
            box-shadow: 0 5px 12px rgba(13, 102, 84, .15);
        }

        .chart-directory-card {
            border: 1px solid #dbe7eb;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(29, 57, 72, .07);
        }

        .chart-directory-head {
            padding: 22px 24px
        }

        .chart-directory-head h2 {
            font-size: 20px
        }

        .chart-search-row {
            padding: 20px 24px;
            grid-template-columns: minmax(0, 1fr) 112px
        }

        .chart-search-row input {
            height: 46px;
            border-radius: 10px;
            background: #fbfdfe
        }

        .chart-search-row button {
            height: 46px;
            border-radius: 10px
        }

        .chart-patient-table th,
        .chart-patient-table td {
            padding: 16px 20px
        }

        .chart-row-action {
            width: 42px;
            height: 42px;
            margin-right: 6px;
            border-radius: 9px;
            color: #21394b;
            background: #fff;
            border: 1px solid #e1e8ec;
        }

        .chart-row-action:hover {
            background: #eaf7f3;
            color: #0d6654;
            border-color: #b9ded3
        }

        .chart-row-action.print:hover {
            color: #1769aa;
            background: #eef6fd
        }

        .chart-editor-shell.chart-modal-mode {
            padding: 22px 24px 40px;
            background: rgba(17, 35, 49, .62);
        }

        .chart-editor-shell.chart-modal-mode #chartEditor {
            max-width: 1240px;
            background: #f4f8fa;
            border: 1px solid #d9e4e8;
            border-radius: 18px;
        }

        .chart-editor-shell.chart-modal-mode .chart-select-wrap {
            max-width: 1240px;
            border-radius: 14px;
            box-shadow: 0 7px 22px rgba(17, 35, 49, .09)
        }

        .chart-editor-shell.chart-modal-mode .chart-editor-top {
            max-width: 1240px;
        }

        .chart-editor-top {
            padding: 2px 2px;
        }

        .chart-editor-top h2 {
            font-size: 25px;
            font-weight: 700
        }

        .chart-modal-close,
        .chart-back {
            min-height: 40px;
            border-radius: 9px;
            padding: 10px 15px;
        }

        .chart-select-wrap {
            box-shadow: 0 6px 18px rgba(26, 55, 72, .05)
        }

        .chart-select-wrap select {
            height: 46px;
            border-radius: 10px
        }

        /* Make the actual dental workspace easy to see */
        #chartEditor .chart-sheet {
            max-width: 1160px;
            padding: 26px 30px 30px;
            border: 1px solid #d7e2e7;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(24, 49, 64, .08);
        }

        #chartEditor .chart-sheet-header {
            padding-bottom: 15px;
            border-bottom: 1px solid #e4eaee;
            margin-bottom: 18px;
        }

        #chartEditor .chart-record-clinic {
            font-size: 25px
        }

        #chartEditor .chart-record-subclinic {
            font-size: 10px
        }

        #chartEditor .chart-record-title {
            font-size: 19px;
            margin: 8px 0 15px
        }

        #chartEditor .chart-date-line {
            margin-bottom: 12px
        }

        #chartEditor .chart-line-input {
            min-height: 28px
        }

        #chartEditor .chart-block-title {
            font-size: 14px;
            margin-bottom: 9px
        }

        #chartEditor .chart-two-column {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        #chartEditor .chart-block {
            padding: 16px;
            border: 1px solid #e1e8ec;
            border-radius: 12px;
            background: #fff;
            margin-bottom: 16px;
        }

        #chartEditor .chart-two-column>.chart-block {
            min-width: 0;
        }

        #chartEditor .chart-model-section {
            margin: 14px auto 20px;
            max-width: 820px;
        }

        #chartEditor .chart-model-frame {
            height: 420px;
            min-height: 420px;
            border: 1px solid #cfdce2;
            border-radius: 14px;
            background: radial-gradient(circle at 50% 42%, #f9fcfd 0%, #edf4f7 58%, #e6eef2 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8);
        }

        #chartEditor #canvas-container {
            width: 100%;
            height: 100%;
            min-height: 420px;
            cursor: grab;
        }

        #chartEditor #canvas-container:active {
            cursor: grabbing
        }

        #chartEditor .teeth-layout {
            margin-bottom: 10px
        }

        #chartEditor .chart-table {
            font-size: 11px
        }

        #chartEditor .chart-table td,
        #chartEditor .chart-table th {
            padding: 5px 6px
        }

        #chartEditor .chart-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 16px 0 4px;
        }

        #chartEditor .chart-actions .btn {
            width: auto !important;
            min-width: 135px;
            min-height: 44px;
            border-radius: 10px !important;
            font-size: 14px;
        }

        #chartEditor .chart-actions .btn-primary {
            background: #0d6654 !important;
            box-shadow: 0 5px 12px rgba(13, 102, 84, .16);
        }

        #chartEditor .chart-actions .btn-secondary {
            background: #fff !important;
            color: #0d6654 !important;
            border: 1px solid #b9d8cf !important;
        }

        @media(max-width:900px) {
            #chartEditor .chart-two-column {
                grid-template-columns: 1fr
            }

            #chartEditor .chart-model-section {
                max-width: 100%
            }

            #chartEditor .chart-model-frame,
            #chartEditor #canvas-container {
                min-height: 360px;
                height: 360px
            }
        }

        @media(max-width:700px) {
            .chart-editor-shell.chart-modal-mode {
                padding: 10px
            }

            #chartEditor .chart-sheet {
                padding: 18px
            }

            #chartEditor .chart-model-frame,
            #chartEditor #canvas-container {
                min-height: 300px;
                height: 300px
            }
        }
    </style>
</head>

<body>
    <div class="dentist-layout">
        <header class="topbar">
            <div class="brand"><img src="../assets/images/logo.png" alt="SupremeDent"><span>SupremeDent</span></div>
            <div class="user-info">
                <div><strong><?php echo htmlspecialchars($firstName); ?></strong><small>Dentist</small></div>
                <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($firstName, 0, 1))); ?></div>
            </div>
        </header>
        <aside class="dentist-sidebar">
            <div class="sidebar-title">DENTIST MENU</div>
            <nav>
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="appointments.php">📅 Appointments</a>
                <a href="Treatment_plan.php">📋 Treatment Plan</a>
                <a href="dental_management.php">🦷 Dental Management</a>
                <a href="dental_chart.php" class="active">🦷 Create Chart</a>
                <a href="../auth/logout.php?portal=dentist" class="logout-link">🚪 Logout</a>
            </nav>
        </aside>
        <main class="dentist-main chart-page-main">
            <div class="chart-page-wrap">
                <?php if ($message): ?>
                    <div class="alert alert-success" style="margin-bottom:15px;"><?php echo htmlspecialchars($message); ?>
                    </div><?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom:15px;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!$loadedChart && !$createMode): ?>
                    <div id="chartDirectory">
                        <div class="chart-page-title">
                            <div>
                                <h1>Create Dental Chart</h1>
                                <p>Search for a patient to create or manage their dental chart.</p>
                            </div><a class="chart-primary" href="dental_chart.php?new=1"><i class="fas fa-plus"></i> Create
                                Chart</a>
                        </div>
                        <div class="chart-directory-card">
                            <div class="chart-directory-head">
                                <h2>Patient Dental Charts</h2>
                                <p>Saved charts will appear here with Edit, View, and Print actions.</p>
                            </div>
                            <div class="chart-search-row"><input id="chartPatientSearch" type="search"
                                    placeholder="Search patients by name or email" oninput="filterPatients()"><button
                                    type="button" onclick="filterPatients()">Search</button></div>
                            <div class="chart-table-scroll">
                                <table class="chart-patient-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Chart Status</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="chartRows">
                                        <?php foreach ($charts as $c): ?>
                                            <tr
                                                data-search="<?php echo htmlspecialchars(strtolower($c['first_name'] . ' ' . $c['last_name'] . ' ' . $c['patient_id'])); ?>">
                                                <td><strong><?php echo htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name'])); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($patientMap[$c['patient_id']]['email'] ?? ''); ?>
                                                </td>
                                                <td><span style="color:#0c6654;font-weight:700;">Saved</span></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($c['updated_at']))); ?>
                                                </td>
                                                <td class="chart-actions-cell"><a class="chart-row-action"
                                                        href="dental_chart.php?chart=<?php echo (int) $c['id']; ?>"
                                                        title="Edit chart" aria-label="Edit chart"><i
                                                            class="fas fa-pen-to-square"></i></a><a class="chart-row-action"
                                                        href="dental_chart.php?chart=<?php echo (int) $c['id']; ?>&view=1"
                                                        title="View chart" aria-label="View chart"><i
                                                            class="fas fa-eye"></i></a><a class="chart-row-action print"
                                                        href="dental_chart.php?chart=<?php echo (int) $c['id']; ?>&view=1&print=1"
                                                        onclick="event.preventDefault(); window.open('dental_chart.php?chart=<?php echo (int) $c['id']; ?>&view=1&print=1','_blank');"
                                                        title="Print chart" aria-label="Print chart"><i
                                                            class="fas fa-print"></i></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div><?php if (!$charts): ?>
                                <div class="chart-empty">No saved dental charts yet. Click <strong>Create Chart</strong> to
                                    start.</div><?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div
                        class="chart-editor-shell <?php echo $viewMode ? 'view-mode' : ''; ?> <?php echo ($createMode && !$viewMode) ? 'chart-modal-mode' : ''; ?>">
                        <div class="chart-editor-top">
                            <div>
                                <h2><?php echo $viewMode ? 'View Dental Chart' : ($chartId ? 'Edit Dental Chart' : 'Create Dental Chart'); ?>
                                </h2>
                                <p><?php echo $viewMode ? 'Review the saved dental examination record and print it when needed.' : ($createMode ? 'Create the patient dental examination record in this workspace.' : 'Complete the dental examination record and save it for the patient.'); ?>
                                </p>
                            </div><?php if ($createMode && !$viewMode): ?><a class="chart-modal-close"
                                    href="dental_chart.php"><i class="fas fa-xmark"></i> Close</a><?php else: ?><a
                                    class="chart-back" href="dental_chart.php"><i class="fas fa-arrow-left"></i>
                                    Patients</a><?php endif; ?>
                        </div>
                        <?php if ($viewMode): ?>
                            <div class="chart-mode-note"><i class="fas fa-circle-info"></i> View mode. The saved chart is
                                read-only. Use the Print Chart button below to print the record.</div><?php endif; ?>
                        <div class="chart-select-wrap"><label for="chartPatient">Select Patient</label><select
                                id="chartPatient" name="patient_id" <?php echo $viewMode ? 'disabled' : ''; ?> required
                                onchange="loadPatientChart()">
                                <option value="">Select patient...</option>
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo (int) $p['id']; ?>"
                                        data-first-name="<?php echo htmlspecialchars($p['first_name'], ENT_QUOTES); ?>"
                                        data-last-name="<?php echo htmlspecialchars($p['last_name'], ENT_QUOTES); ?>" <?php echo $selectedPatientId == (int) $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(trim($p['first_name'] . ' ' . $p['last_name'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select></div>
                        <div id="chartEditor" class="printable-chart">

                            <div class="chart-editor-header"><button type="button" class="chart-back-button"
                                    onclick="showChartDirectory()"><i
                                        class="fas fa-arrow-left"></i><span>Patients</span></button>
                                <div><span class="chart-editor-kicker">SupremeDent</span>
                                    <h3>Dental Examination Record</h3>
                                </div>
                            </div>
                            <!-- Chart Creation Form -->
                            <form id="chartForm" method="post" onsubmit="return submitChart(event)"><input type="hidden"
                                    name="save_dental_chart" value="1"><input type="hidden" name="patient_id"
                                    id="patient_id_hidden" value="<?php echo (int) $selectedPatientId; ?>"><input
                                    type="hidden" name="chart_id" value="<?php echo (int) $chartId; ?>">
                                <div id="chartSheet" class="chart-sheet">
                                    <div class="chart-sheet-header">
                                        <div class="chart-record-brand">
                                            <div class="chart-record-clinic">SUPREMEDENT</div>
                                            <div class="chart-record-subclinic">DENTAL CLINIC</div>
                                        </div>
                                        <div class="chart-record-title">DENTAL EXAMINATION RECORD</div>
                                        <div class="chart-date-line">
                                            <span class="chart-label-inline">Date</span>
                                            <input type="text" id="recordDate" name="recordDate"
                                                class="chart-line-input chart-date-input">
                                        </div>
                                        <div class="patient-name-line">
                                            <span class="chart-label-inline">Name of Patient:</span>
                                            <div class="name-fields">
                                                <label class="line-field">
                                                    <input type="text" id="patientLastName" name="patientLastName"
                                                        class="chart-line-input" required>
                                                    <span>Last Name</span>
                                                </label>
                                                <label class="line-field">
                                                    <input type="text" id="patientFirstName" name="patientFirstName"
                                                        class="chart-line-input" required>
                                                    <span>First Name</span>
                                                </label>
                                                <label class="line-field">
                                                    <input type="text" id="patientMiddleName" name="patientMiddleName"
                                                        class="chart-line-input">
                                                    <span>Middle Name</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="chart-block">
                                        <div class="chart-block-title">DENTAL HEALTH STATUS:</div>
                                        <div class="teeth-layout pediatric-layout">
                                            <div class="teeth-grid small-grid" id="primaryUpperTeeth"></div>
                                            <div class="teeth-grid small-grid" id="primaryLowerTeeth"></div>
                                        </div>
                                        <div class="teeth-layout adult-layout">
                                            <div class="teeth-grid adult-grid" id="adultUpperTeeth"></div>
                                            <div class="teeth-grid adult-grid" id="adultLowerTeeth"></div>
                                        </div>
                                        <input type="text" id="modelSelections" name="modelSelections" hidden>
                                        <div class="chart-model-section">
                                            <div class="chart-model-frame">
                                                <div id="canvas-container"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="chart-two-column">
                                        <div class="chart-block">
                                            <div class="chart-block-title">ORAL HEALTH CONDITION:</div>
                                            <table class="chart-table oral-health-table">
                                                <tbody>
                                                    <tr>
                                                        <td><input type="text" name="examDate" class="chart-table-input"
                                                                placeholder="Date of Examination"></td>
                                                        <td class="yn-cell">Y</td>
                                                        <td class="yn-cell">N</td>
                                                        <td class="yn-cell">Y</td>
                                                        <td class="yn-cell">N</td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" name="ageLastBirthday"
                                                                class="chart-table-input" placeholder="Age last birthday">
                                                        </td>
                                                        <td class="yn-box"><input type="checkbox" name="ageLastBirthdayY1">
                                                        </td>
                                                        <td class="yn-box"><input type="checkbox" name="ageLastBirthdayN1">
                                                        </td>
                                                        <td class="yn-box"><input type="checkbox" name="ageLastBirthdayY2">
                                                        </td>
                                                        <td class="yn-box"><input type="checkbox" name="ageLastBirthdayN2">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Presence of Debris</td>
                                                        <td class="yn-box"><input type="checkbox" name="debrisY1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="debrisN1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="debrisY2"></td>
                                                        <td class="yn-box"><input type="checkbox" name="debrisN2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Inflammation of Gingiva</td>
                                                        <td class="yn-box"><input type="checkbox" name="gingivaY1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="gingivaN1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="gingivaY2"></td>
                                                        <td class="yn-box"><input type="checkbox" name="gingivaN2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Presence of Calculus</td>
                                                        <td class="yn-box"><input type="checkbox" name="calculusY1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="calculusN1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="calculusY2"></td>
                                                        <td class="yn-box"><input type="checkbox" name="calculusN2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Under Orthodontic Treatment</td>
                                                        <td class="yn-box"><input type="checkbox" name="orthoY1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="orthoN1"></td>
                                                        <td class="yn-box"><input type="checkbox" name="orthoY2"></td>
                                                        <td class="yn-box"><input type="checkbox" name="orthoN2"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <label class="chart-inline-note">
                                                Dentofacial Anomaly, Neoplasm, Others, specify:
                                                <input type="text" name="dentofacialNotes" class="chart-line-input">
                                            </label>

                                            <table class="chart-table tooth-count-table">
                                                <thead>
                                                    <tr>
                                                        <th>Tooth Count</th>
                                                        <th>T</th>
                                                        <th>P</th>
                                                        <th>T</th>
                                                        <th>P</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Number of Teeth Present</td>
                                                        <td><input type="text" name="teethPresentT1"></td>
                                                        <td><input type="text" name="teethPresentP1"></td>
                                                        <td><input type="text" name="teethPresentT2"></td>
                                                        <td><input type="text" name="teethPresentP2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Number of Caries Free Teeth</td>
                                                        <td><input type="text" name="cariesFreeT1"></td>
                                                        <td><input type="text" name="cariesFreeP1"></td>
                                                        <td><input type="text" name="cariesFreeT2"></td>
                                                        <td><input type="text" name="cariesFreeP2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Number of Decayed Teeth</td>
                                                        <td><input type="text" name="decayedT1"></td>
                                                        <td><input type="text" name="decayedP1"></td>
                                                        <td><input type="text" name="decayedT2"></td>
                                                        <td><input type="text" name="decayedP2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Number of Missing Teeth</td>
                                                        <td><input type="text" name="missingT1"></td>
                                                        <td><input type="text" name="missingP1"></td>
                                                        <td><input type="text" name="missingT2"></td>
                                                        <td><input type="text" name="missingP2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Number of Filled Teeth</td>
                                                        <td><input type="text" name="filledT1"></td>
                                                        <td><input type="text" name="filledP1"></td>
                                                        <td><input type="text" name="filledT2"></td>
                                                        <td><input type="text" name="filledP2"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Total of 8DMF Teeth</td>
                                                        <td><input type="text" name="dmfT1"></td>
                                                        <td><input type="text" name="dmfP1"></td>
                                                        <td><input type="text" name="dmfT2"></td>
                                                        <td><input type="text" name="dmfP2"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="chart-block">
                                            <div class="chart-subsection">
                                                <div class="chart-block-title centered">INITIAL SOFT TISSUE EXAM</div>
                                                <div class="soft-tissue-grid">
                                                    <label><input type="checkbox" name="softTissue" value="Lips">
                                                        Lips</label>
                                                    <label><input type="checkbox" name="softTissue" value="Floor of Mouth">
                                                        Floor of Mouth</label>
                                                    <label><input type="checkbox" name="softTissue" value="Palate">
                                                        Palate</label>
                                                    <label><input type="checkbox" name="softTissue" value="Tongue">
                                                        Tongue</label>
                                                    <label><input type="checkbox" name="softTissue"
                                                            value="Neck &amp; Nodes"> Neck &amp; Nodes</label>
                                                </div>
                                            </div>

                                            <div class="chart-subsection">
                                                <div class="chart-block-title centered">INITIAL PERIODONTAL EXAM</div>
                                                <table class="chart-table periodontal-table">
                                                    <tbody>
                                                        <tr>
                                                            <td>GINGIVAL INFLAMMATION:</td>
                                                            <td><label><input type="checkbox" name="gingivalInflammation"
                                                                        value="Slight"> Slight</label></td>
                                                            <td><label><input type="checkbox" name="gingivalInflammation"
                                                                        value="Moderate"> Moderate</label></td>
                                                            <td><label><input type="checkbox" name="gingivalInflammation"
                                                                        value="Severe"> Severe</label></td>
                                                        </tr>
                                                        <tr>
                                                            <td>SOFT PLAQUE BUILDUP:</td>
                                                            <td><label><input type="checkbox" name="softPlaque"
                                                                        value="Slight"> Slight</label></td>
                                                            <td><label><input type="checkbox" name="softPlaque"
                                                                        value="Moderate"> Moderate</label></td>
                                                            <td><label><input type="checkbox" name="softPlaque"
                                                                        value="Heavy"> Heavy</label></td>
                                                        </tr>
                                                        <tr>
                                                            <td>HARD CALC BUILDUP:</td>
                                                            <td><label><input type="checkbox" name="hardCalc" value="Light">
                                                                    Light</label></td>
                                                            <td><label><input type="checkbox" name="hardCalc"
                                                                        value="Moderate"> Moderate</label></td>
                                                            <td><label><input type="checkbox" name="hardCalc" value="Heavy">
                                                                    Heavy</label></td>
                                                        </tr>
                                                        <tr>
                                                            <td>STAINS:</td>
                                                            <td><label><input type="checkbox" name="stains" value="Light">
                                                                    Light</label></td>
                                                            <td><label><input type="checkbox" name="stains"
                                                                        value="Moderate"> Moderate</label></td>
                                                            <td><label><input type="checkbox" name="stains" value="Heavy">
                                                                    Heavy</label></td>
                                                        </tr>
                                                        <tr>
                                                            <td>HOME CARE EFFECTIVENESS:</td>
                                                            <td><label><input type="checkbox" name="homeCare" value="Good">
                                                                    Good</label></td>
                                                            <td><label><input type="checkbox" name="homeCare" value="Fair">
                                                                    Fair</label></td>
                                                            <td><label><input type="checkbox" name="homeCare" value="Poor">
                                                                    Poor</label></td>
                                                        </tr>
                                                        <tr>
                                                            <td>PERIODONTAL CONDITION:</td>
                                                            <td><label><input type="checkbox" name="periodontalCondition"
                                                                        value="Good"> Good</label></td>
                                                            <td><label><input type="checkbox" name="periodontalCondition"
                                                                        value="Fair"> Fair</label></td>
                                                            <td><label><input type="checkbox" name="periodontalCondition"
                                                                        value="Poor"> Poor</label></td>
                                                        </tr>
                                                        <tr>
                                                            <td>PERIODONTAL DIAGNOSIS:</td>
                                                            <td><label><input type="checkbox" name="periodontalDiagnosis"
                                                                        value="Normal"> Normal</label></td>
                                                            <td><label><input type="checkbox" name="periodontalDiagnosis"
                                                                        value="Gingivitis"> Gingivitis</label></td>
                                                            <td></td>
                                                        </tr>
                                                        <tr>
                                                            <td>PERIODONTITIS:</td>
                                                            <td><label><input type="checkbox" name="periodontitis"
                                                                        value="Early"> Early</label></td>
                                                            <td><label><input type="checkbox" name="periodontitis"
                                                                        value="Moderate"> Moderate</label></td>
                                                            <td><label><input type="checkbox" name="periodontitis"
                                                                        value="Advanced"> Advanced</label></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <label class="chart-inline-note compact">
                                                    MUCOGINGIVAL DEFECTS:
                                                    <input type="text" name="mucogingivalDefects" class="chart-line-input">
                                                </label>
                                                <div class="clinical-data">
                                                    <div class="clinical-title">CLINICAL DATA:</div>
                                                    <div class="clinical-row">
                                                        <span>OCCLUSION:</span>
                                                        <label><input type="checkbox" name="occlusion" value="Class I">
                                                            Class I</label>
                                                        <label><input type="checkbox" name="occlusion" value="Class II">
                                                            Class II</label>
                                                        <label><input type="checkbox" name="occlusion" value="Class III">
                                                            Class III</label>
                                                    </div>
                                                    <div class="clinical-row">
                                                        <span>T.M.J. EXAM:</span>
                                                        <label><input type="checkbox" name="tmj" value="Pain"> Pain</label>
                                                        <label><input type="checkbox" name="tmj" value="Popping">
                                                            Popping</label>
                                                        <label><input type="checkbox" name="tmj" value="Deviation">
                                                            Deviation</label>
                                                        <label><input type="checkbox" name="tmj" value="Tooth Wear"> Tooth
                                                            Wear</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="chart-block">
                                        <div class="chart-block-title">DENTAL/ORAL EXAMINATION REVEALED THE FOLLOWING
                                            CONDITIONS AND RECOMMENDATIONS.</div>
                                        <div class="recommendations-grid">
                                            <div class="recommendation-column">
                                                <label><input type="checkbox" name="recommendations" value="Caries Free">
                                                    Caries Free</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="Poor Oral Hygiene (Materia Alba, Calculus, Stain)"> Poor Oral
                                                    Hygiene (Materia Alba, Calculus, Stain)</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="Indicated for Restoration/Filling"> Indicated for
                                                    Restoration/Filling</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="Indicated for Extraction"> Indicated for Extraction</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="Gingival inflammation"> Gingival inflammation</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="Needs Oral Prophylaxis"> Needs Oral Prophylaxis</label>
                                            </div>
                                            <div class="recommendation-column">
                                                <label><input type="checkbox" name="recommendations"
                                                        value="Needs Prosthesis (Denture)"> Needs Prosthesis
                                                    (Denture)</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="For Endodontic Treatment"> For Endodontic Treatment</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="For Orthodontic Consultation"> For Orthodontic
                                                    Consultation</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="For Pits and Fissures Sealant"> For Pits and Fissures
                                                    Sealant</label>
                                                <label><input type="checkbox" name="recommendations" value="Others">
                                                    Others</label>
                                                <label><input type="checkbox" name="recommendations"
                                                        value="No Dental Treatment Needed at Present"> No Dental Treatment
                                                    Needed at Present</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="chart-actions">
                                    <button type="submit" class="btn btn-primary"
                                        style="width: auto; padding: 0.75rem 2rem;">Save Chart</button>
                                    <button type="button" onclick="clearChartForm()" class="btn btn-secondary"
                                        style="width: auto; padding: 0.75rem 2rem;">Clear</button>
                                </div>
                            </form>

                        </div>
                    <?php endif; ?>
                </div>
        </main>
    </div>
    <script type="module">
        import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.module.js';
        import {GLTFLoader} from 'https://cdn.jsdelivr.net/npm/three@0.152.2/examples/jsm/loaders/GLTFLoader.js';
        import {OrbitControls} from 'https://cdn.jsdelivr.net/npm/three@0.152.2/examples/jsm/controls/OrbitControls.js';

        const toothData = {
            1: {name: 'Upper Right Third Molar'},
            2: {name: 'Upper Right Second Molar'},
            3: {name: 'Upper Right First Molar'},
            4: {name: 'Upper Right Second Premolar'},
            5: {name: 'Upper Right First Premolar'},
            6: {name: 'Upper Right Canine'},
            7: {name: 'Upper Right Lateral Incisor'},
            8: {name: 'Upper Right Central Incisor'},
            9: {name: 'Upper Left Central Incisor'},
            10: {name: 'Upper Left Lateral Incisor'},
            11: {name: 'Upper Left Canine'},
            12: {name: 'Upper Left First Premolar'},
            13: {name: 'Upper Left Second Premolar'},
            14: {name: 'Upper Left First Molar'},
            15: {name: 'Upper Left Second Molar'},
            16: {name: 'Upper Left Third Molar'},
            17: {name: 'Lower Left Third Molar'},
            18: {name: 'Lower Left Second Molar'},
            19: {name: 'Lower Left First Molar'},
            20: {name: 'Lower Left Second Premolar'},
            21: {name: 'Lower Left First Premolar'},
            22: {name: 'Lower Left Canine'},
            23: {name: 'Lower Left Lateral Incisor'},
            24: {name: 'Lower Left Central Incisor'},
            25: {name: 'Lower Right Central Incisor'},
            26: {name: 'Lower Right Lateral Incisor'},
            27: {name: 'Lower Right Canine'},
            28: {name: 'Lower Right First Premolar'},
            29: {name: 'Lower Right Second Premolar'},
            30: {name: 'Lower Right First Molar'},
            31: {name: 'Lower Right Second Molar'},
            32: {name: 'Lower Right Third Molar'}
        };

        window.initialize3DModel = function () {
            const container = document.getElementById('canvas-container');
            if (!container) return;
            if (container.querySelector('canvas')) return;

            const width = Math.max(container.clientWidth, 520);
            const height = Math.max(container.clientHeight, 280);

            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xeef2f6);

            const defaultCameraPosition = new THREE.Vector3(0, -0.45, 24.6);
            const defaultCameraTarget = new THREE.Vector3(0, -1.7, 0.2);
            const activeCameraPosition = defaultCameraPosition.clone();
            const activeCameraTarget = defaultCameraTarget.clone();
            const camera = new THREE.PerspectiveCamera(34, width / height, 0.1, 1000);
            camera.position.copy(activeCameraPosition);

            const renderer = new THREE.WebGLRenderer({antialias: true, alpha: true});
            renderer.setSize(width, height);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.shadowMap.enabled = true;
            renderer.outputColorSpace = THREE.SRGBColorSpace;
            renderer.toneMapping = THREE.ACESFilmicToneMapping;
            renderer.toneMappingExposure = 0.92;
            container.appendChild(renderer.domElement);

            const controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.enablePan = false;
            controls.minDistance = 18;
            controls.maxDistance = 34;
            controls.minPolarAngle = 1.05;
            controls.maxPolarAngle = 2.05;
            controls.target.copy(activeCameraTarget);

            window.appScene = scene;
            window.appCamera = camera;
            window.appRenderer = renderer;
            window.appControls = controls;
            window.appRaycaster = new THREE.Raycaster();
            window.appMouse = new THREE.Vector2();
            window.appInteractableTeeth = [];
            window.appSelectedTooth = null;
            window.appSelectedTeeth = new Set();

            scene.add(new THREE.AmbientLight(0xffffff, 0.75));
            const hemiLight = new THREE.HemisphereLight(0xf8fbff, 0xc08f88, 0.68);
            scene.add(hemiLight);
            const dirLight = new THREE.DirectionalLight(0xffffff, 0.95);
            dirLight.position.set(8, 12, 14);
            dirLight.castShadow = true;
            scene.add(dirLight);
            const fillLight = new THREE.DirectionalLight(0xffffff, 0.45);
            fillLight.position.set(-10, 4, 12);
            scene.add(fillLight);

            const naturalToothColor = new THREE.Color(0xf8f6ef);
            const selectedToothColor = new THREE.Color(0x5b9cff);
            const gumMaterial = new THREE.MeshStandardMaterial({
                color: 0x9d4f55,
                roughness: 0.72,
                metalness: 0.03,
                side: THREE.FrontSide
            });

            const teethGroup = new THREE.Group();
            const upperTeethGroup = new THREE.Group();
            const lowerTeethGroup = new THREE.Group();
            teethGroup.add(upperTeethGroup, lowerTeethGroup);
            scene.add(teethGroup);

            const interactableTeeth = [];
            const toothMeshMap = new Map();
            const toothNamePattern = /(tooth|teeth|incisor|canine|premolar|molar|crown)/i;

            function applyDefaultView() {
                camera.position.copy(activeCameraPosition);
                controls.target.copy(activeCameraTarget);
                controls.update();
            }

            function buildToothMaterial(color = naturalToothColor) {
                return new THREE.MeshStandardMaterial({
                    color,
                    roughness: 0.24,
                    metalness: 0.04,
                    side: THREE.FrontSide,
                    emissive: 0x000000,
                    emissiveIntensity: 0
                });
            }

            function addToothOutline(mesh) {
                if (!mesh?.geometry || mesh.userData?.outline) return;

                const outline = new THREE.LineSegments(
                    new THREE.EdgesGeometry(mesh.geometry, 28),
                    new THREE.LineBasicMaterial({
                        color: 0xb8bec8,
                        transparent: true,
                        opacity: 0.82
                    })
                );
                outline.name = `${mesh.name || 'tooth'}-outline`;
                outline.renderOrder = 2;
                outline.raycast = () => { };
                mesh.add(outline);
                mesh.userData.outline = outline;
            }

            function isLikelyToothMesh(mesh) {
                if (!mesh?.isMesh || !mesh.geometry) return false;

                const meshName = String(mesh.name || '');
                if (toothNamePattern.test(meshName)) return true;

                const originalColor = mesh.material?.color;
                if (originalColor) {
                    const hsl = {};
                    originalColor.getHSL(hsl);
                    if (hsl.l > 0.55 && hsl.s < 0.4) {
                        return true;
                    }
                }

                return false;
            }

            function getToothArchName(id) {
                return id <= 16 ? 'upper' : 'lower';
            }

            function getToothTargetGroup(id) {
                return id <= 16 ? upperTeethGroup : lowerTeethGroup;
            }

            function hideToothDuplicate(mesh) {
                if (!mesh?.isMesh) return;
                mesh.visible = false;
                mesh.userData.isDuplicateToothShell = true;
            }

            function getNamedToothInfo(mesh) {
                const match = String(mesh?.name || '').match(/^(\d+)([DP])(?:\.(\d+))?$/i);
                if (!match) return null;

                const localId = Number.parseInt(match[1], 10);
                const archCode = match[2].toUpperCase();
                const isDuplicate = Boolean(match[3]);
                if (localId < 1 || localId > 16) return null;

                if (archCode === 'P') {
                    return {id: 17 - localId, arch: 'upper', isDuplicate};
                }

                if (archCode === 'D') {
                    return {id: 33 - localId, arch: 'lower', isDuplicate};
                }

                return null;
            }

            function registerToothMesh(mesh, id) {
                if (!toothData[id] || toothMeshMap.has(id)) return false;

                mesh.material = buildToothMaterial();
                mesh.castShadow = true;
                mesh.receiveShadow = true;
                mesh.userData = Object.assign(
                    {baseColor: naturalToothColor.clone(), group: getToothArchName(id)},
                    {id, ...toothData[id]}
                );
                addToothOutline(mesh);
                interactableTeeth.push(mesh);
                toothMeshMap.set(id, mesh);
                return true;
            }

            function applyToothSelectionVisual(mesh, isSelected) {
                if (!mesh?.material) return;
                mesh.material.color.copy(isSelected ? selectedToothColor : mesh.userData.baseColor);
                mesh.material.emissive.set(isSelected ? 0x215acb : 0x000000);
                mesh.material.emissiveIntensity = isSelected ? 0.18 : 0;
                if (mesh.userData?.outline?.material) {
                    mesh.userData.outline.material.color.set(isSelected ? 0x2f67d8 : 0xb8bec8);
                    mesh.userData.outline.material.opacity = isSelected ? 0.95 : 0.82;
                }
            }

            function updateModelSelectionField() {
                const selectedIds = Array.from(window.appSelectedTeeth || []).sort((a, b) => a - b);
                const selectionField = document.getElementById('modelSelections');
                if (selectionField) {
                    selectionField.value = selectedIds.join(',');
                }
            }

            function restoreSelectedToothVisuals() {
                toothMeshMap.forEach((mesh, id) => {
                    applyToothSelectionVisual(mesh, window.appSelectedTeeth.has(id));
                });
                updateModelSelectionField();
                window.syncToothChartFrom3DSelections?.();
            }

            window.sync3DSelectionsFromInput = function () {
                const selectionField = document.getElementById('modelSelections');
                const values = selectionField?.value
                    ? selectionField.value.split(',').map(value => Number(value.trim())).filter(Number.isFinite)
                    : [];
                window.appSelectedTeeth = new Set(values);
                restoreSelectedToothVisuals();
            };

            const loader = new GLTFLoader();
            loader.load('../assets/models/model.glb', (gltf) => {
                const box = new THREE.Box3().setFromObject(gltf.scene);
                const size = box.getSize(new THREE.Vector3());
                const maxDim = Math.max(size.x, size.y, size.z);
                const scale = 12.2 / maxDim;
                gltf.scene.scale.setScalar(scale);

                const center = box.getCenter(new THREE.Vector3());
                gltf.scene.position.sub(center);
                gltf.scene.position.y = -1.7;
                gltf.scene.position.z = 0.25;
                gltf.scene.rotation.x = -0.06;
                teethGroup.add(gltf.scene);

                const framedBox = new THREE.Box3().setFromObject(teethGroup);
                const framedCenter = framedBox.getCenter(new THREE.Vector3());
                activeCameraTarget.copy(new THREE.Vector3(framedCenter.x, framedCenter.y - 0.15, framedCenter.z + 0.2));
                activeCameraPosition.copy(new THREE.Vector3(framedCenter.x, framedCenter.y + 1.1, 24.6));
                applyDefaultView();

                const registeredToothIds = new Set();
                const unnamedToothMeshes = {
                    upper: [],
                    lower: []
                };

                gltf.scene.traverse((child) => {
                    if (child.isMesh) {
                        const namedToothInfo = getNamedToothInfo(child);
                        if (namedToothInfo) {
                            if (namedToothInfo.isDuplicate || toothMeshMap.has(namedToothInfo.id)) {
                                hideToothDuplicate(child);
                            } else if (registerToothMesh(child, namedToothInfo.id)) {
                                registeredToothIds.add(namedToothInfo.id);
                            } else {
                                hideToothDuplicate(child);
                            }
                            return;
                        }

                        if (isLikelyToothMesh(child)) {
                            const worldPosition = child.getWorldPosition(new THREE.Vector3());
                            const arch = worldPosition.y >= 0 ? 'upper' : 'lower';
                            unnamedToothMeshes[arch].push(child);
                        } else {
                            child.material = gumMaterial.clone();
                        }
                    }
                });

                const remainingToothIds = {
                    upper: Object.keys(toothData)
                        .map(Number)
                        .filter(id => id <= 16 && !registeredToothIds.has(id) && !toothMeshMap.has(id)),
                    lower: Object.keys(toothData)
                        .map(Number)
                        .filter(id => id >= 17 && !registeredToothIds.has(id) && !toothMeshMap.has(id))
                };

                Object.entries(unnamedToothMeshes).forEach(([arch, meshes]) => {
                    meshes
                        .sort((a, b) => {
                            const posA = new THREE.Vector3();
                            const posB = new THREE.Vector3();
                            a.getWorldPosition(posA);
                            b.getWorldPosition(posB);

                            return posA.x - posB.x;
                        })
                        .forEach((mesh, index) => {
                            const fallbackId = remainingToothIds[arch][index];
                            if (fallbackId) {
                                registerToothMesh(mesh, fallbackId);
                            } else {
                                hideToothDuplicate(mesh);
                            }
                        });
                });

                toothMeshMap.forEach((mesh, id) => {
                    getToothTargetGroup(id).attach(mesh);
                });

                window.appInteractableTeeth = interactableTeeth;
                window.appToothMeshMap = toothMeshMap;
                window.sync3DSelectionsFromInput?.();
                console.log('Model loaded successfully, interactive teeth:', interactableTeeth.length);

            }, undefined, (error) => {
                console.error('Error loading model.glb:', error);
                createFallbackTeeth();
            });

            function createFallbackTeeth() {
                const upperY = 2;
                const lowerY = -2;
                const radius = 6;
                for (let i = 1; i <= 32; i++) {
                    if (!toothData[i]) continue;
                    const geometry = new THREE.BoxGeometry(0.8, 1.2, 0.6);
                    const mesh = new THREE.Mesh(geometry, buildToothMaterial());
                    const isUpper = i <= 16;
                    const archY = isUpper ? upperY : lowerY;
                    const localIndex = isUpper ? i - 1 : i - 17;
                    const angle = (localIndex - 7.5) * (Math.PI / 12);
                    mesh.position.set(Math.sin(angle) * radius, archY, Math.cos(angle) * radius);
                    mesh.rotation.y = angle + Math.PI / 2;
                    mesh.userData = {
                        id: i,
                        group: isUpper ? 'upper' : 'lower',
                        ...toothData[i],
                        baseColor: naturalToothColor.clone()
                    };
                    mesh.castShadow = true;
                    mesh.receiveShadow = true;
                    addToothOutline(mesh);
                    interactableTeeth.push(mesh);
                    toothMeshMap.set(i, mesh);
                    if (isUpper) upperTeethGroup.add(mesh);
                    else lowerTeethGroup.add(mesh);
                }
                window.appInteractableTeeth = interactableTeeth;
                window.appToothMeshMap = toothMeshMap;
                window.sync3DSelectionsFromInput?.();
            }

            function showToothDetails(tooth) {
                const displayedToothNumber = window.universalToChartToothNumber?.(tooth.userData.id) || tooth.userData.id;

                const toothSelect = document.getElementById('selectedTooth');
                const detailInput = document.getElementById('toothDetail');
                toothSelect.value = displayedToothNumber;
                detailInput.value = tooth.userData.name;
            }

            function resolveClickedTooth(intersects, normalizedMouseY) {
                if (!intersects.length) return null;

                const prefersLower = normalizedMouseY < 0;
                const prioritized = intersects.find(hit => {
                    const toothId = hit.object?.userData?.id;
                    if (!toothId) return false;
                    return prefersLower ? toothId >= 17 : toothId <= 16;
                });

                return prioritized?.object || intersects[0].object;
            }

            function onMouseClick(event) {
                const rect = renderer.domElement.getBoundingClientRect();
                window.appMouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                window.appMouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

                window.appRaycaster.setFromCamera(window.appMouse, window.appCamera);
                const intersects = window.appRaycaster.intersectObjects(interactableTeeth);

                if (intersects.length > 0) {
                    const clickedTooth = resolveClickedTooth(intersects, window.appMouse.y);
                    const toothId = clickedTooth.userData.id;
                    const selectedSet = window.appSelectedTeeth || new Set();

                    if (selectedSet.has(toothId)) {
                        selectedSet.delete(toothId);
                    } else {
                        selectedSet.add(toothId);
                    }

                    window.appSelectedTeeth = selectedSet;
                    window.appSelectedTooth = clickedTooth;
                    restoreSelectedToothVisuals();
                    showToothDetails(clickedTooth);
                }
            }

            function onResize() {
                const newWidth = Math.max(container.clientWidth, 520);
                const newHeight = Math.max(container.clientHeight, 280);
                window.appRenderer.setSize(newWidth, newHeight);
                window.appCamera.aspect = newWidth / newHeight;
                window.appCamera.updateProjectionMatrix();
            }

            renderer.domElement.addEventListener('click', onMouseClick);
            window.addEventListener('resize', onResize);

            function animate() {
                requestAnimationFrame(animate);
                window.appControls.update();
                window.appRenderer.render(window.appScene, window.appCamera);
            }
            animate();
        };

        // Initialize on first load (chart may be hidden but resizing on show handles any zero-size issue)
        window.initialize3DModel();
    </script>
    <script>
        const chartData = <?php echo $loadedChart ? json_encode(json_decode($loadedChart['chart_data'], true) ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}'; ?>;
        const selectedPatientId = <?php echo (int) $selectedPatientId; ?>;
        const viewMode = <?php echo $viewMode ? 'true' : 'false'; ?>;
        function filterPatients() {const q = (document.getElementById('chartPatientSearch')?.value || '').trim().toLowerCase(); document.querySelectorAll('#chartRows tr').forEach(r => r.hidden = !r.dataset.search.includes(q));}
        function setValue(name, value) {const fields = document.querySelectorAll(`[name="${CSS.escape(name)}"]`); fields.forEach(f => {if (f.type === 'checkbox') {if (Array.isArray(value)) f.checked = value.includes(f.value || 'on'); else f.checked = !!value;} else if (f.type !== 'hidden') {f.value = value ?? '';} })}
        function populateChart(data) {if (!data) return; Object.entries(data).forEach(([k, v]) => setValue(k, v)); if (data.modelSelections) {const h = document.getElementById('modelSelections'); if (h) h.value = data.modelSelections; window.sync3DSelectionsFromInput?.();} if (window.sync3DSelectionsFromInput) window.sync3DSelectionsFromInput();}
        function autoFillPatient() {const s = document.getElementById('chartPatient'); if (!s) return; const o = s.options[s.selectedIndex]; if (!o || !o.value) return; const fn = document.getElementById('patientFirstName'), ln = document.getElementById('patientLastName'); if (fn && !fn.value) fn.value = o.dataset.firstName || ''; if (ln && !ln.value) ln.value = o.dataset.lastName || ''; const d = document.getElementById('recordDate'); if (d && !d.value) d.value = new Date().toLocaleDateString(); document.getElementById('patient_id_hidden').value = o.value;}
        function loadPatientChart() {autoFillPatient();}
        function submitChart(e) {if (e) e.preventDefault(); autoFillPatient(); const s = document.getElementById('chartPatient'); if (!s || !s.value) {alert('Please select a patient first.'); return false;} if (!document.getElementById('chartForm').reportValidity()) return false; document.getElementById('chartForm').submit(); return false;}
        function clearChartForm() {const f = document.getElementById('chartForm'); if (!f) return; f.querySelectorAll('input:not([type=hidden]),textarea,select').forEach(x => {if (x.id === 'chartPatient') return; if (x.type === 'checkbox') x.checked = false; else x.value = '';}); document.getElementById('patient_id_hidden').value = selectedPatientId || ''; window.appSelectedTeeth = new Set(); window.sync3DSelectionsFromInput?.();}
        window.syncToothChartFrom3DSelections = function () {const selected = new Set(Array.from(window.appSelectedTeeth || []).map(window.universalToChartToothNumber).filter(Boolean)); document.querySelectorAll('input[name="toothStatus"]').forEach(i => i.checked = selected.has(i.value));};
        window.sync3DSelectionsFromInput = window.sync3DSelectionsFromInput || function () { };
        function syncPrintableCloneValues(source, clone) {
            const sourceFields = source.querySelectorAll('input, textarea, select');
            const cloneFields = clone.querySelectorAll('input, textarea, select');

            sourceFields.forEach((field, index) => {
                const target = cloneFields[index];
                if (!target) return;

                if (field.type === 'checkbox') {
                    target.checked = field.checked;
                    if (field.checked) {
                        target.setAttribute('checked', 'checked');
                    } else {
                        target.removeAttribute('checked');
                    }
                    return;
                }

                target.value = field.value;
                target.setAttribute('value', field.value);
                if (target.tagName === 'TEXTAREA') {
                    target.textContent = field.value;
                }
            });

            syncToothChartVisualSelection(clone);
        }


        function convertPrintableCheckboxes(clone) {
            clone.querySelectorAll('input[type="checkbox"]').forEach(input => {
                const isToothCell = input.closest('.tooth-cell');
                if (isToothCell) {
                    input.remove();
                    return;
                }

                const marker = document.createElement('span');
                marker.className = `chart-print-check${input.checked ? ' is-checked' : ''}`;
                marker.setAttribute('aria-hidden', 'true');
                input.replaceWith(marker);
            });
        }


        function syncPrintableModelPreview(clone) {
            const cloneContainer = clone.querySelector('#canvas-container');
            if (!cloneContainer) return;

            const sourceCanvas = window.appRenderer?.domElement;
            if (!sourceCanvas) {
                cloneContainer.innerHTML = '';
                return;
            }

            try {
                const previewImage = document.createElement('img');
                previewImage.src = sourceCanvas.toDataURL('image/png');
                previewImage.alt = 'Dental model preview';
                previewImage.className = 'chart-model-print-image';
                cloneContainer.innerHTML = '';
                cloneContainer.appendChild(previewImage);
            } catch (error) {
                console.error('Unable to build print preview for dental model.', error);
                cloneContainer.innerHTML = '';
            }
        }


        function getChartPrintStyles() {
            return `
        html, body { margin: 0; padding: 0; background: #fff; color: #000; font-family: Arial, sans-serif; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-size: 10px; }
        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 10px 14px;
            background: #f8fafc;
            border-bottom: 1px solid #d6dde7;
        }
        .print-toolbar-title {
            font-size: 14px;
            font-weight: 700;
            color: #111;
        }
        .print-toolbar-note {
            font-size: 12px;
            color: #475569;
        }
        .print-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .print-toolbar-btn {
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
        }
        .print-toolbar-btn.print-action {
            background: #2563eb;
        }
        .print-page { width: 198mm; min-height: 284mm; margin: 0 auto; padding: 3mm 2mm 2mm; }
        .chart-sheet {
            width: 191mm !important;
            margin: 0 auto !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            background: #fff !important;
            font-size: 10.2px !important;
            line-height: 1.12 !important;
            max-width: none !important;
        }
        .chart-actions, .chart-toolbar, .chart-summary, .chart-model-section, .detail-panel { display: none !important; }
        .chart-sheet-header { width: 191mm !important; margin: 0 auto 7px !important; }
        .chart-record-brand { margin-bottom: 3px !important; }
        .chart-record-clinic { font-size: 20px; font-weight: 800; letter-spacing: 0.015em; line-height: 1; }
        .chart-record-subclinic { font-size: 8.5px; letter-spacing: 0.11em; margin-top: 0; }
        .chart-record-title { font-size: 11.5px; margin: 5px 0 10px; font-weight: 800; }
        .chart-date-line { align-items: center !important; gap: 4px !important; margin-bottom: 6px !important; }
        .chart-date-input { max-width: 34mm !important; }
        .patient-name-line { gap: 6px !important; align-items: flex-end !important; margin-bottom: 2px !important; }
        .chart-label-inline { font-size: 9.4px; font-weight: 700; padding-top: 0 !important; }
        .name-fields { gap: 8px !important; }
        .line-field { font-size: 8.2px; gap: 1px !important; }
        .chart-line-input, .chart-table input[type="text"], .chart-table textarea {
            border: none !important;
            border-bottom: 1px solid #000 !important;
            border-radius: 0 !important;
            background: transparent !important;
            color: #000 !important;
            min-height: 13px !important;
            padding: 0 1px !important;
            font-size: 9.2px !important;
            line-height: 1.05 !important;
            font-family: Arial, sans-serif !important;
        }
        input, textarea, select { color: #000 !important; background: transparent !important; }
        .chart-block {
            width: 191mm !important;
            margin: 0 auto 5px !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .chart-block-title { font-size: 9.4px !important; margin-bottom: 2px !important; font-weight: 800 !important; line-height: 1.1 !important; }
        .chart-block-title.centered { text-align: center !important; }
        .teeth-layout { width: fit-content !important; margin: 0 auto 3px !important; }
        .pediatric-layout { margin-top: 1px !important; }
        .adult-layout { margin-top: 5px !important; }
        .small-grid { grid-template-columns: repeat(10, 7.8mm) !important; }
        .adult-grid { grid-template-columns: repeat(16, 7.55mm) !important; }
        .tooth-cell {
            height: 8.9mm !important;
            border: 1px solid #000 !important;
            cursor: default !important;
            gap: 0.3mm !important;
        }
        .tooth-diagram { width: 5.2mm !important; height: 5.2mm !important; }
        .tooth-ring { border-color: #000 !important; border-width: 0.32mm !important; }
        .tooth-ring.inner { inset: 0.9mm !important; }
        .tooth-dot { width: 0.95mm !important; height: 0.95mm !important; background: #000 !important; }
        .tooth-number-label { font-size: 7.1px !important; }
        .tooth-cell.is-selected { background: rgba(0, 0, 0, 0.05) !important; }
        .tooth-cell.is-selected .tooth-ring { border-width: 0.42mm !important; }
        .chart-two-column {
            width: 191mm !important;
            margin: 0 auto 5px !important;
            display: grid !important;
            grid-template-columns: 69.5mm 117.5mm !important;
            gap: 4mm !important;
            align-items: start !important;
        }
        .chart-two-column > .chart-block {
            width: auto !important;
            margin: 0 !important;
            min-width: 0 !important;
        }
        .chart-table { table-layout: fixed !important; width: 100% !important; border-collapse: collapse !important; font-size: 8.4px !important; }
        .chart-table td, .chart-table th { border: 1px solid #000 !important; padding: 1px 2px !important; vertical-align: middle !important; }
        .chart-table th { font-size: 8.4px !important; font-weight: 700 !important; }
        .oral-health-table td:first-child { width: 49mm !important; }
        .oral-health-table tr:first-child td { height: 5.1mm !important; }
        .yn-cell, .yn-box { width: 5.1mm !important; text-align: center !important; }
        .chart-inline-note { font-size: 8px !important; margin: 3px 0 4px !important; gap: 3px !important; }
        .chart-inline-note .chart-line-input { min-height: 11px !important; }
        .tooth-count-table td:first-child, .tooth-count-table th:first-child { width: 45.5mm !important; }
        .tooth-count-table td, .tooth-count-table th { height: 4.8mm !important; }
        .chart-subsection { margin-bottom: 3px !important; }
        .soft-tissue-grid {
            width: 100% !important;
            display: grid !important;
            grid-template-columns: repeat(5, 1fr) !important;
            border: 1px solid #000 !important;
            border-bottom: none !important;
        }
        .soft-tissue-grid label {
            min-height: 7.1mm !important;
            padding: 0.7mm 1mm !important;
            font-size: 7.9px !important;
            line-height: 1.05 !important;
            border-color: #000 !important;
            gap: 1px !important;
        }
        .periodontal-table td:first-child { width: 34% !important; font-weight: 700 !important; }
        .periodontal-table td:nth-child(2),
        .periodontal-table td:nth-child(3),
        .periodontal-table td:nth-child(4) { width: 22% !important; }
        .periodontal-table td { height: 4.9mm !important; }
        .periodontal-table label, .clinical-row label, .recommendation-column label {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            white-space: nowrap;
        }
        .clinical-data { margin-top: 3px !important; }
        .clinical-title { font-size: 8.3px !important; margin-bottom: 1px !important; font-weight: 800 !important; }
        .clinical-row {
            border: 1px solid #000 !important;
            border-top: none !important;
            padding: 1px 2px !important;
            font-size: 8px !important;
            gap: 4px !important;
            min-height: 5.1mm !important;
        }
        .clinical-row:first-of-type { border-top: 1px solid #000 !important; }
        .clinical-row > span { min-width: 16mm !important; font-weight: 700 !important; }
        .recommendations-grid {
            width: 182mm !important;
            margin: 2px auto 0 !important;
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 8mm !important;
        }
        .recommendation-column { min-width: 0 !important; }
        .recommendation-column label {
            display: grid !important;
            grid-template-columns: 3.2mm 1fr !important;
            align-items: start !important;
            column-gap: 2px !important;
            font-size: 8.2px !important;
            line-height: 1.05 !important;
            margin-bottom: 1px !important;
        }
        .rec-line { width: 19mm !important; }
        .chart-print-check {
            width: 2.7mm !important;
            height: 2.7mm !important;
            border: 1px solid #000 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-right: 0 !important;
            margin-top: 0.3mm !important;
            flex: 0 0 auto !important;
        }
        .chart-print-check::after { content: ""; width: 1.4mm; height: 1.4mm; background: transparent; }
        .chart-print-check.is-checked::after { background: #000 !important; }
        .soft-tissue-grid .chart-print-check,
        .periodontal-table .chart-print-check,
        .clinical-row .chart-print-check,
        .recommendation-column .chart-print-check,
        .oral-health-table .chart-print-check {
            transform: none !important;
            align-self: center !important;
        }
        @media print {
            .print-toolbar { display: none !important; }
            body { background: #fff !important; }
        }
        @page { size: A4 portrait; margin: 6mm; }
    `;
        }


        function printChart(chartOrPatientId = '') {
            const sourceSheet = document.getElementById('chartSheet');
            if (!sourceSheet) {
                alert('Dental chart is not available.');
                return;
            }

            // The PHP page already loaded the saved chart from MySQL into chartData.
            // Rehydrate the visible form first so the print copy exactly matches the saved record.
            if (typeof chartData === 'object' && chartData && Object.keys(chartData).length) {
                populateChart(chartData);
            }

            const patientSelect = document.getElementById('chartPatient');
            const patientId = chartOrPatientId || patientSelect?.value || '';
            if (!patientId) {
                alert('Please select a patient first.');
                return;
            }

            const printableSheet = sourceSheet.cloneNode(true);
            syncPrintableCloneValues(sourceSheet, printableSheet);
            convertPrintableCheckboxes(printableSheet);
            syncPrintableModelPreview(printableSheet);

            const firstName = document.getElementById('patientFirstName')?.value || '';
            const lastName = document.getElementById('patientLastName')?.value || '';
            const safePatientName = `${firstName} ${lastName}`.trim() || `Patient ${patientId}`;

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Please allow pop-ups for SupremeDent to print the chart.');
                return;
            }

            printWindow.document.write(`
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Dental Chart - ${safePatientName.replace(/</g, '&lt;')}</title>
            <link rel="stylesheet" href="../assets/css/styles.css">
            <style>${getChartPrintStyles()}</style>
        </head>
        <body>
            <div class="print-toolbar">
                <div>
                    <div class="print-toolbar-title">Dental Chart Preview</div>
                    <div class="print-toolbar-note">Review the record, then click Print.</div>
                </div>
                <div class="print-toolbar-actions">
                    <button class="print-toolbar-btn print-action" type="button" onclick="window.print()">Print</button>
                    <button class="print-toolbar-btn" type="button" onclick="window.close()">Close</button>
                </div>
            </div>
            <div class="print-page">${printableSheet.outerHTML}</div>
        </body>
        </html>
    `);
            printWindow.document.close();
            printWindow.focus();
        }
        function preparePrint() {window.print();}
        document.addEventListener('DOMContentLoaded', () => {if (<?php echo ($createMode && !$viewMode) ? 'true' : 'false'; ?>  )document.body.classList.add('chart-modal-open' ) ;tr y{initializeDentalChartLayout(); }catc h(e ) {} ;const  s =document.getElementById('chartPatient') ;i f( s& &selectedPatientId )s.valu e =String(selectedPatientId) ;autoFillPatient() ;i f(Object.keys(chartData).length )populateChart(chartData) ;i f(new URLSearchParams(location.search).get('print' )== ='1' && <?php echo $viewMode ? 'true' : 'false'; ?>)setTimeout( () =>printChart(String(<?php echo (int) $chartId; ?> )),700);});

        function createToothChartCell(toothNumber) {
            return `
        <label class="tooth-cell">
            <input type="checkbox" name="toothStatus" value="${toothNumber}">
            <span class="tooth-diagram">
                <span class="tooth-ring outer"></span>
                <span class="tooth-ring inner"></span>
                <span class="tooth-dot"></span>
            </span>
            <span class="tooth-number-label">${toothNumber}</span>
        </label>
    `;
        }

        function universalToChartToothNumber(toothId) {
            if (toothId >= 1 && toothId <= 8) return String(19 - toothId);
            if (toothId >= 9 && toothId <= 16) return String(toothId + 12);
            if (toothId >= 17 && toothId <= 24) return String(55 - toothId);
            if (toothId >= 25 && toothId <= 32) return String(toothId + 16);
            return null;
        }

        function chartToUniversalToothId(chartNumber) {
            const toothNumber = Number(chartNumber);
            if (!Number.isFinite(toothNumber)) return null;
            if (toothNumber >= 11 && toothNumber <= 18) return 19 - toothNumber;
            if (toothNumber >= 21 && toothNumber <= 28) return toothNumber - 12;
            if (toothNumber >= 31 && toothNumber <= 38) return 55 - toothNumber;
            if (toothNumber >= 41 && toothNumber <= 48) return toothNumber - 16;
            return null;
        }

        function syncToothChartFrom3DSelections(scope = document) {
            const selectedChartNumbers = new Set(
                Array.from(window.appSelectedTeeth || [])
                    .map(universalToChartToothNumber)
                    .filter(Boolean)
            );

            scope.querySelectorAll('input[name="toothStatus"]').forEach(input => {
                input.checked = selectedChartNumbers.has(input.value);
            });

            syncToothChartVisualSelection(scope);
        }

        function sync3DSelectionsFromChartInputs(scope = document) {
            const selectedIds = Array.from(scope.querySelectorAll('input[name="toothStatus"]:checked'))
                .map(input => chartToUniversalToothId(input.value))
                .filter(Number.isFinite);

            const selectionField = document.getElementById('modelSelections');
            if (selectionField) {
                selectionField.value = selectedIds.join(',');
            }

            window.sync3DSelectionsFromInput?.();
        }

        window.syncToothChartFrom3DSelections = syncToothChartFrom3DSelections;
        window.universalToChartToothNumber = universalToChartToothNumber;

        function initializeDentalChartLayout() {
            const gridMap = {
                primaryUpperTeeth: ['55', '54', '53', '52', '51', '61', '62', '63', '64', '65'],
                primaryLowerTeeth: ['85', '84', '83', '82', '81', '71', '72', '73', '74', '75'],
                adultUpperTeeth: ['18', '17', '16', '15', '14', '13', '12', '11', '21', '22', '23', '24', '25', '26', '27', '28'],
                adultLowerTeeth: ['48', '47', '46', '45', '44', '43', '42', '41', '31', '32', '33', '34', '35', '36', '37', '38']
            };

            Object.entries(gridMap).forEach(([id, teeth]) => {
                const container = document.getElementById(id);
                if (container) {
                    container.innerHTML = teeth.map(createToothChartCell).join('');
                }
            });

            document.querySelectorAll('input[name="toothStatus"]').forEach(input => {
                input.addEventListener('change', () => {
                    syncToothChartVisualSelection();
                    sync3DSelectionsFromChartInputs();
                });
            });

            syncToothChartVisualSelection();
        }

        function syncToothChartVisualSelection(scope = document) {
            scope.querySelectorAll('.tooth-cell').forEach(cell => {
                const checkbox = cell.querySelector('input[type="checkbox"]');
                if (!checkbox) return;
                cell.classList.toggle('is-selected', checkbox.checked);
            });
        }

    </script>
</body>

</html> 