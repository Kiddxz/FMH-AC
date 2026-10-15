<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);


start_secure_session();

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "dentist"
) {
    header("Location: ../auth/login.php");
    exit;
}

$firstName = $_SESSION["first_name"] ?? "Dentist";
require_once "../config/database.php";
$labSettings = clinic_settings($conn);
$labEnabled = ($labSettings["laboratory_enabled"] ?? "0") === "1";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dental Management - SupremeDent</title>

    <link
        rel="stylesheet"
        href="../assets/css/styles.css"
    >

<link rel="stylesheet" href="../assets/css/app.css">
</head>

<body>

<div class="dentist-layout">

    <!-- TOP HEADER -->
    <header class="topbar">

        <div class="brand">

            <img
                src="../assets/images/logo.png"
                alt="SupremeDent"
            >

            <span>SupremeDent</span>

        </div>

        <div class="user-info">

            <strong>
                <?php echo htmlspecialchars($firstName); ?>
            </strong>

            <small>Dentist</small>

            <div class="avatar">
                <?php echo strtoupper(substr($firstName, 0, 1)); ?>
            </div>

        </div>

    </header>


    <!-- SIDEBAR -->
    <aside class="dentist-sidebar">

        <div class="sidebar-title">
            DENTIST MENU
        </div>

        <nav>

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="appointments.php">
                📅 Appointments
            </a>

            <a href="treatment_plans.php">
                📋 Treatment Plan
            </a>

            <a
                href="dental_management.php"
                class="active"
            >
                🦷 Dental Management
            </a>

            <a
                href="../auth/logout.php?portal=dentist"
                class="logout-link"
            >
                🚪 Logout
            </a>

        </nav>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="dentist-main">

        <div class="page-header">

            <span class="page-label">
                DENTAL MANAGEMENT
            </span>

            <h1>
                Dental Management
            </h1>

            <p>
                Access the main tools used for patient care.
            </p>

        </div>


        <!-- MANAGEMENT CARDS -->
        <section class="management-section">

            <div class="management-grid">


                <!-- PATIENTS -->
                <a
                    href="patients.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        👥
                    </div>

                    <div>
                        <h3>Patients</h3>

                        <p>
                            View registered patients and their information.
                        </p>
                    </div>

                </a>


                <!-- DENTAL RECORDS -->
                <a
                    href="dental_records.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        🦷
                    </div>

                    <div>
                        <h3>Dental Records</h3>

                        <p>
                            View and manage patient dental records.
                        </p>
                    </div>

                </a>


                <!-- DENTAL CHART -->
                <a
                    href="odontogram.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        🦷
                    </div>

                    <div>
                        <h3>Dental Chart</h3>

                        <p>
                            Manage the patient's dental chart.
                        </p>
                    </div>

                </a>


                <!-- 3D ODONTOGRAM -->
                <a
                    href="odontogram.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        🦷
                    </div>

                    <div>
                        <h3>3D Odontogram</h3>

                        <p>
                            View and manage the patient's tooth chart.
                        </p>

                    </div>

                </a>


                <!-- TREATMENT PLANS -->
                <a
                    href="treatment_plans.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        📋
                    </div>

                    <div>
                        <h3>Treatment Plans</h3>

                        <p>
                            Create and manage recommended treatment plans.
                        </p>

                    </div>

                </a>


<?php if ($labEnabled): ?>
                <!-- LABORATORY CASES -->
                <a
                    href="laboratory.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        🧪
                    </div>

                    <div>
                        <h3>Laboratory Cases</h3>

                        <p>
                            Manage laboratory cases and dental work.
                        </p>

                    </div>

                </a>
                <?php endif; ?>


            </div>

        </section>

    </main>

</div>

</body>

</html>