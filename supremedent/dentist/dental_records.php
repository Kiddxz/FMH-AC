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

require_once "../config/database.php";

$firstName = $_SESSION["first_name"] ?? "Dentist";
$lastName  = $_SESSION["last_name"] ?? "";

$dentistName = trim($firstName . " " . $lastName);

$patientId = isset($_GET["patient_id"])
    ? (int)$_GET["patient_id"]
    : 0;

if ($patientId <= 0) {
    header("Location: patients.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET PATIENT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        first_name,
        last_name,
        email,
        status
    FROM users
    WHERE id = ?
      AND role = 'patient'
    LIMIT 1
");

$stmt->bind_param("i", $patientId);
$stmt->execute();

$result = $stmt->get_result();
$patient = $result->fetch_assoc();

$stmt->close();

if (!$patient) {
    die("Patient not found.");
}

$fullName =
    $patient["first_name"] . " " . $patient["last_name"];


/*
|--------------------------------------------------------------------------
| ADD DENTAL RECORD
|--------------------------------------------------------------------------
*/

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $recordDate = $_POST["record_date"] ?? "";
    $procedureName = trim($_POST["procedure_name"] ?? "");
    $diagnosis = trim($_POST["diagnosis"] ?? "");
    $notes = trim($_POST["notes"] ?? "");

    if ($recordDate === "") {

        $error = "Please select a record date.";

    } elseif ($procedureName === "") {

        $error = "Please enter the procedure.";

    } else {

        $insertStmt = $conn->prepare("
            INSERT INTO dental_records
            (
                patient_id,
                record_date,
                procedure_name,
                diagnosis,
                dentist_name,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $insertStmt->bind_param(
            "isssss",
            $patientId,
            $recordDate,
            $procedureName,
            $diagnosis,
            $dentistName,
            $notes
        );

        if ($insertStmt->execute()) {

            $insertStmt->close();

            header(
                "Location: dental_records.php?patient_id="
                . $patientId
                . "&saved=1"
            );

            exit;

        } else {

            $error = "Unable to save dental record: "
                . $insertStmt->error;

            $insertStmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

$saved = isset($_GET["saved"]) && $_GET["saved"] == "1";


/*
|--------------------------------------------------------------------------
| GET DENTAL RECORDS
|--------------------------------------------------------------------------
*/

$recordStmt = $conn->prepare("
    SELECT
        id,
        record_date,
        procedure_name,
        diagnosis,
        dentist_name,
        notes,
        created_at
    FROM dental_records
    WHERE patient_id = ?
    ORDER BY record_date DESC, id DESC
");

$recordStmt->bind_param("i", $patientId);
$recordStmt->execute();

$recordsResult = $recordStmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dental Records - SupremeDent
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/styles.css"
    >

<link rel="stylesheet" href="../assets/css/app.css">
</head>


<body>


<div class="dentist-layout">


    <!-- =====================================================
         TOP HEADER
    ====================================================== -->

    <header class="topbar">

        <div class="brand">

            <img
                src="../assets/images/logo.png"
                alt="SupremeDent"
            >

            <span>
                SupremeDent
            </span>

        </div>


        <div class="user-info">

            <strong>
                <?php
                echo htmlspecialchars($firstName);
                ?>
            </strong>

            <small>
                Dentist
            </small>

            <div class="avatar">

                <?php
                echo htmlspecialchars(
                    strtoupper(
                        substr($firstName, 0, 1)
                    )
                );
                ?>

            </div>

        </div>

    </header>



    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

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



    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="dentist-main dental-records-main">


        <!-- PAGE HEADER -->

        <div class="dental-records-header">

            <span class="page-label">
                DENTAL RECORDS
            </span>

            <h1>
                Dental Records
            </h1>

            <p>
                Dental records for

                <strong>
                    <?php
                    echo htmlspecialchars($fullName);
                    ?>
                </strong>
            </p>

        </div>



        <!-- =====================================================
             SUCCESS MESSAGE
        ====================================================== -->

        <?php if ($saved): ?>

            <div class="record-success">

                ✓ Dental record successfully saved.

            </div>

        <?php endif; ?>



        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <?php if ($error !== ""): ?>

            <div class="record-error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             PATIENT INFORMATION
        ====================================================== -->

        <section class="dental-records-information">

            <div class="dental-records-section-title">

                <h2>
                    👤 Patient Information
                </h2>

            </div>


            <div class="dental-records-info-grid">


                <div class="dental-records-info-item">

                    <strong>
                        Patient ID
                    </strong>

                    <span>
                        <?php
                        echo (int)$patient["id"];
                        ?>
                    </span>

                </div>


                <div class="dental-records-info-item">

                    <strong>
                        Full Name
                    </strong>

                    <span>
                        <?php
                        echo htmlspecialchars($fullName);
                        ?>
                    </span>

                </div>


                <div class="dental-records-info-item">

                    <strong>
                        Email
                    </strong>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            $patient["email"]
                        );
                        ?>
                    </span>

                </div>


                <div class="dental-records-info-item">

                    <strong>
                        Status
                    </strong>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            ucfirst($patient["status"])
                        );
                        ?>
                    </span>

                </div>


            </div>

        </section>



        <!-- =====================================================
             ADD DENTAL RECORD
        ====================================================== -->

        <section class="add-record-section">


            <div class="add-record-header">

                <div>

                    <h2>
                        ➕ Add Dental Record
                    </h2>

                    <p>
                        Record the patient's dental procedure,
                        diagnosis, and clinical notes.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action="dental_records.php?patient_id=<?php echo (int)$patientId; ?>"
                class="dental-record-form"
            >


                <!-- DATE -->

                <div class="form-group">

                    <label for="record_date">
                        Record Date
                    </label>

                    <input
                        type="date"
                        id="record_date"
                        name="record_date"
                        value="<?php echo date("Y-m-d"); ?>"
                        required
                    >

                </div>


                <!-- PROCEDURE -->

                <div class="form-group">

                    <label for="procedure_name">
                        Procedure
                    </label>

                    <input
                        type="text"
                        id="procedure_name"
                        name="procedure_name"
                        placeholder="Example: Dental Cleaning"
                        maxlength="150"
                        required
                    >

                </div>


                <!-- DIAGNOSIS -->

                <div class="form-group">

                    <label for="diagnosis">
                        Diagnosis
                    </label>

                    <input
                        type="text"
                        id="diagnosis"
                        name="diagnosis"
                        placeholder="Example: Dental caries"
                        maxlength="255"
                    >

                </div>


                <!-- DENTIST -->

                <div class="form-group">

                    <label>
                        Dentist
                    </label>

                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($dentistName); ?>"
                        readonly
                    >

                </div>


                <!-- NOTES -->

                <div class="form-group form-group-full">

                    <label for="notes">
                        Clinical Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        placeholder="Enter clinical findings, observations, or other notes..."
                    ></textarea>

                </div>


                <!-- BUTTON -->

                <div class="form-actions">

                    <button
                        type="submit"
                        class="save-record-btn"
                    >
                        💾 Save Dental Record
                    </button>

                </div>


            </form>

        </section>



        <!-- =====================================================
             DENTAL HISTORY
        ====================================================== -->

        <section class="dental-records-section">


            <div class="dental-records-section-header">

                <div>

                    <h2>
                        🦷 Dental History
                    </h2>

                    <p>
                        Previous dental procedures,
                        diagnoses, and clinical notes.
                    </p>

                </div>


                <div class="record-count">

                    <?php
                    echo $recordsResult->num_rows;
                    ?>

                    <span>

                        Record<?php
                        echo $recordsResult->num_rows == 1
                            ? ""
                            : "s";
                        ?>

                    </span>

                </div>

            </div>



            <?php if ($recordsResult->num_rows > 0): ?>


                <div class="dental-records-list">


                    <?php while ($record = $recordsResult->fetch_assoc()): ?>


                        <div class="dental-record-item">


                            <!-- RECORD HEADER -->

                            <div class="dental-record-item-header">

                                <div>

                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $record["procedure_name"]
                                        );
                                        ?>

                                    </h3>


                                    <span class="record-date">

                                        📅

                                        <?php
                                        echo htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime(
                                                    $record["record_date"]
                                                )
                                            )
                                        );
                                        ?>

                                    </span>

                                </div>


                                <span class="record-number">

                                    Record #

                                    <?php
                                    echo (int)$record["id"];
                                    ?>

                                </span>

                            </div>



                            <!-- RECORD DETAILS -->

                            <div class="dental-record-details">


                                <!-- DIAGNOSIS -->

                                <div class="dental-record-detail">

                                    <strong>
                                        Diagnosis
                                    </strong>

                                    <span>

                                        <?php

                                        if (
                                            !empty(
                                                $record["diagnosis"]
                                            )
                                        ) {

                                            echo nl2br(
                                                htmlspecialchars(
                                                    $record["diagnosis"]
                                                )
                                            );

                                        } else {

                                            echo "No diagnosis recorded.";

                                        }

                                        ?>

                                    </span>

                                </div>



                                <!-- DENTIST -->

                                <div class="dental-record-detail">

                                    <strong>
                                        Dentist
                                    </strong>

                                    <span>

                                        <?php

                                        if (
                                            !empty(
                                                $record["dentist_name"]
                                            )
                                        ) {

                                            echo htmlspecialchars(
                                                $record["dentist_name"]
                                            );

                                        } else {

                                            echo "Not specified.";

                                        }

                                        ?>

                                    </span>

                                </div>



                                <!-- CLINICAL NOTES -->

                                <div class="dental-record-detail notes-detail">

                                    <strong>
                                        Clinical Notes
                                    </strong>

                                    <span>

                                        <?php

                                        if (
                                            !empty(
                                                $record["notes"]
                                            )
                                        ) {

                                            echo nl2br(
                                                htmlspecialchars(
                                                    $record["notes"]
                                                )
                                            );

                                        } else {

                                            echo "No clinical notes recorded.";

                                        }

                                        ?>

                                    </span>

                                </div>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="dental-records-empty">

                    <div class="empty-record-icon">
                        🦷
                    </div>

                    <h3>
                        No Dental Records Yet
                    </h3>

                    <p>
                        No dental records have been recorded
                        for this patient.
                    </p>

                </div>


            <?php endif; ?>


        </section>



        <!-- BACK -->

        <a
            href="patient_view.php?id=<?php echo (int)$patient["id"]; ?>"
            class="dental-records-back"
        >
            ← Back to Patient Details
        </a>


    </main>


</div>



<!-- =============================================================
     DENTAL RECORDS PAGE CSS
============================================================= -->

<style>


.dental-records-main {
    padding: 34px;
}


.dental-records-header {
    margin-bottom: 22px;
}


.dental-records-header .page-label {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    background: #dff5ef;
    color: #1685c4;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
}


.dental-records-header h1 {
    margin: 10px 0 2px;
    font-size: 32px;
    color: #073b63;
}


.dental-records-header p {
    margin: 0;
    color: #52729a;
    font-size: 14px;
}


/* SUCCESS */

.record-success {
    background: #e1f6ec;
    border: 1px solid #bce8d1;
    color: #16764a;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 13px;
    font-weight: 600;
}


/* ERROR */

.record-error {
    background: #fff0f0;
    border: 1px solid #f1c5c5;
    color: #b33a3a;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 13px;
}


/* PATIENT INFORMATION */

.dental-records-information {
    background: #ffffff;
    border: 1px solid #d5e3ee;
    border-radius: 14px;
    padding: 22px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .04);
}


.dental-records-section-title {
    margin-bottom: 18px;
}


.dental-records-section-title h2 {
    margin: 0;
    color: #073b63;
    font-size: 18px;
}


.dental-records-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 24px;
}


.dental-records-info-item {
    padding: 12px 14px;
    background: #f6fafc;
    border-radius: 10px;
}


.dental-records-info-item strong {
    display: block;
    margin-bottom: 4px;
    color: #073b63;
    font-size: 13px;
}


.dental-records-info-item span {
    color: #52729a;
    font-size: 14px;
}


/* =============================================================
   ADD RECORD
============================================================= */

.add-record-section {
    background: #ffffff;
    border: 1px solid #d5e3ee;
    border-radius: 14px;
    padding: 22px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .04);
}


.add-record-header {
    margin-bottom: 20px;
}


.add-record-header h2 {
    margin: 0;
    color: #073b63;
    font-size: 20px;
}


.add-record-header p {
    margin: 5px 0 0;
    color: #52729a;
    font-size: 13px;
}


.dental-record-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px 20px;
}


.form-group {
    display: flex;
    flex-direction: column;
}


.form-group-full {
    grid-column: 1 / -1;
}


.form-group label {
    margin-bottom: 7px;
    color: #073b63;
    font-size: 13px;
    font-weight: 700;
}


.form-group input,
.form-group textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #d5e3ee;
    border-radius: 9px;
    background: #f9fcfd;
    padding: 11px 12px;
    color: #073b63;
    font-family: inherit;
    font-size: 13px;
    outline: none;
}


.form-group input:focus,
.form-group textarea:focus {
    border-color: #72b8dc;
    background: #ffffff;
}


.form-group input[readonly] {
    background: #f1f6f9;
    color: #52729a;
}


.form-group textarea {
    resize: vertical;
    min-height: 95px;
}


.form-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    margin-top: 2px;
}


.save-record-btn {
    border: none;
    border-radius: 9px;
    padding: 11px 18px;
    background: #1685c4;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}


.save-record-btn:hover {
    opacity: .9;
}


/* =============================================================
   DENTAL RECORDS SECTION
============================================================= */

.dental-records-section {
    background: #ffffff;
    border: 1px solid #d5e3ee;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .04);
}


.dental-records-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}


.dental-records-section-header h2 {
    margin: 0;
    color: #073b63;
    font-size: 20px;
}


.dental-records-section-header p {
    margin: 5px 0 0;
    color: #52729a;
    font-size: 13px;
}


/* RECORD COUNT */

.record-count {
    min-width: 65px;
    padding: 8px 12px;
    border-radius: 10px;
    background: #e1f4ef;
    color: #1685c4;
    text-align: center;
    font-size: 18px;
    font-weight: 700;
}


.record-count span {
    display: block;
    font-size: 10px;
    font-weight: 500;
}


/* RECORD LIST */

.dental-records-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}


.dental-record-item {
    border: 1px solid #d5e3ee;
    border-radius: 12px;
    background: #f9fcfd;
    padding: 18px;
}


.dental-record-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e3edf3;
}


.dental-record-item-header h3 {
    margin: 0 0 6px;
    color: #073b63;
    font-size: 16px;
}


.record-date {
    color: #52729a;
    font-size: 12px;
}


.record-number {
    padding: 5px 9px;
    border-radius: 8px;
    background: #e1f4ef;
    color: #1685c4;
    font-size: 11px;
    white-space: nowrap;
}


/* RECORD DETAILS */

.dental-record-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 14px;
}


.dental-record-detail {
    padding: 12px;
    background: #ffffff;
    border-radius: 9px;
}


.dental-record-detail strong {
    display: block;
    margin-bottom: 5px;
    color: #073b63;
    font-size: 12px;
}


.dental-record-detail span {
    color: #52729a;
    font-size: 13px;
    line-height: 1.5;
}


.notes-detail {
    grid-column: 1 / -1;
}


/* EMPTY STATE */

.dental-records-empty {
    text-align: center;
    padding: 35px 20px;
    border: 1px dashed #cbdde8;
    border-radius: 12px;
    background: #f9fcfd;
}


.empty-record-icon {
    width: 52px;
    height: 52px;
    margin: 0 auto 12px;
    border-radius: 14px;
    background: #e1f4ef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}


.dental-records-empty h3 {
    margin: 0 0 6px;
    color: #073b63;
    font-size: 16px;
}


.dental-records-empty p {
    margin: 0;
    color: #52729a;
    font-size: 12px;
}


/* BACK */

.dental-records-back {
    display: inline-block;
    margin-top: 20px;
    color: #1685c4;
    text-decoration: none;
    font-size: 14px;
}


.dental-records-back:hover {
    text-decoration: underline;
}


/* =============================================================
   RESPONSIVE
============================================================= */

@media (max-width: 800px) {

    .dental-records-main {
        padding: 20px;
    }

    .dental-records-info-grid,
    .dental-record-form,
    .dental-record-details {
        grid-template-columns: 1fr;
    }

    .form-group-full,
    .notes-detail,
    .form-actions {
        grid-column: auto;
    }

    .dental-records-section-header {
        align-items: flex-start;
        gap: 15px;
    }

}


</style>


</body>

</html>