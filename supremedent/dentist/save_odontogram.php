<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', true);


start_secure_session();

header("Content-Type: application/json");

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "dentist"
) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| READ JSON
|--------------------------------------------------------------------------
*/

$input = json_decode(
    file_get_contents("php://input"),
    true
);


if (!is_array($input)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    exit;
}


$patientId =
    isset($input["patient_id"])
    ? (int) $input["patient_id"]
    : 0;


$teeth =
    isset($input["teeth"]) &&
    is_array($input["teeth"])
    ? $input["teeth"]
    : [];


$condition =
    trim(
        $input["condition"] ?? ""
    );


$treatment =
    trim(
        $input["treatment"] ?? ""
    );


$notes =
    trim(
        $input["notes"] ?? ""
    );


$dentistId =
    (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($patientId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid patient."
    ]);

    exit;
}


if (empty($teeth)) {

    echo json_encode([
        "success" => false,
        "message" => "Please select at least one tooth."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK PATIENT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role = 'patient'
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $patientId
);

$stmt->execute();

$result =
    $stmt->get_result();

$patient =
    $result->fetch_assoc();

$stmt->close();


if (!$patient) {

    echo json_encode([
        "success" => false,
        "message" => "Patient not found."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK TABLE
|--------------------------------------------------------------------------
*/

$tableCheck =
    $conn->query(
        "SHOW TABLES LIKE 'odontogram_records'"
    );


if (
    !$tableCheck ||
    $tableCheck->num_rows === 0
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "odontogram_records table does not exist. Please create the table first."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| SAVE EACH TOOTH
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO odontogram_records
    (
        patient_id,
        tooth_number,
        condition_name,
        treatment,
        notes,
        dentist_id
    )
    VALUES
    (?, ?, ?, ?, ?, ?)

    ON DUPLICATE KEY UPDATE
        condition_name = VALUES(condition_name),
        treatment = VALUES(treatment),
        notes = VALUES(notes),
        dentist_id = VALUES(dentist_id),
        updated_at = CURRENT_TIMESTAMP
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " . $conn->error
    ]);

    exit;
}


$conn->begin_transaction();


try {

    foreach ($teeth as $tooth) {

        $toothNumber =
            (int) $tooth;


        if (
            $toothNumber < 1 ||
            $toothNumber > 32
        ) {
            continue;
        }


        $stmt->bind_param(
            "iisssi",
            $patientId,
            $toothNumber,
            $condition,
            $treatment,
            $notes,
            $dentistId
        );


        if (
            !$stmt->execute()
        ) {

            throw new Exception(
                $stmt->error
            );

        }

    }


    $conn->commit();


    $stmt->close();


    echo json_encode([
        "success" => true,
        "message" =>
            "Odontogram record saved successfully."
    ]);


} catch (
    Throwable $e
) {

    $conn->rollback();

    $stmt->close();


    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to save odontogram record."
    ]);

}

?>