<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);

start_secure_session();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "superadmin") {
    header("Location: ../auth/login.php");
    exit;
}
$result = $conn->query("SELECT id,first_name,last_name,email FROM users WHERE role='patient' ORDER BY last_name,first_name");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Patient Charts - SupremeDent</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .wrap {
            padding: 32px
        }

        .card {
            background: #fff;
            border: 1px solid #dbe6ee;
            border-radius: 14px;
            padding: 20px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e5edf2;
            text-align: left
        }

        th {
            font-size: 12px;
            color: #71839c
        }

        .btn {
            display: inline-block;
            padding: 9px 12px;
            border-radius: 8px;
            background: #087d68;
            color: #fff;
            text-decoration: none
        }

        .back {
            display: inline-block;
            margin-bottom: 18px;
            color: #087d68;
            text-decoration: none
        }
    </style>
</head>

<body>
    <div class="wrap"><a class="back" href="dashboard.php">← Back to Super Admin</a>
        <div class="card">
            <div class="page-label">PATIENT MANAGEMENT</div>
            <h1>Patient Dental Charts</h1>
            <p>Select a patient to open the interactive 3D odontogram.</p>
            <table>
                <tr>
                    <th>Patient</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr><?php while ($p = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p["first_name"] . " " . $p["last_name"]); ?></td>
                        <td><?php echo htmlspecialchars($p["email"]); ?></td>
                        <td><a class="btn" href="../dentist/odontogram.php?patient_id=<?php echo (int) $p["id"]; ?>">Open 3D
                                Chart</a></td>
                    </tr><?php endwhile; ?>
            </table>
        </div>
    </div>
</body>

</html>