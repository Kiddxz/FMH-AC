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

$patientId = isset($_GET["patient_id"])
    ? (int) $_GET["patient_id"]
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
    SELECT id, first_name, last_name, email, status
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
    header("Location: patients.php");
    exit;
}

$fullName = trim(
    ($patient["first_name"] ?? "") . " " .
    ($patient["last_name"] ?? "")
);


/*
|--------------------------------------------------------------------------
| GET EXISTING ODONTOGRAM DATA
|--------------------------------------------------------------------------
*/

$odontogram = [];

$tableCheck = $conn->query("
    SHOW TABLES LIKE 'odontogram_records'
");

if ($tableCheck && $tableCheck->num_rows > 0) {

    $stmt = $conn->prepare("
        SELECT
            tooth_number,
            condition_name,
            treatment,
            notes
        FROM odontogram_records
        WHERE patient_id = ?
        ORDER BY tooth_number ASC
    ");

    $stmt->bind_param("i", $patientId);
    $stmt->execute();

    $records = $stmt->get_result();

    while ($row = $records->fetch_assoc()) {
        $odontogram[(int)$row["tooth_number"]] = [
            "condition" => $row["condition_name"] ?? "",
            "treatment" => $row["treatment"] ?? "",
            "notes" => $row["notes"] ?? ""
        ];
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>3D Odontogram - SupremeDent</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f8fb;
            color: #102d46;
        }

        /* HEADER */

        .header {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid #dbe6ee;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 32px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;

            font-size: 21px;
            font-weight: bold;
            color: #087d68;
        }

        .brand img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .account {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .account-info {
            text-align: right;
        }

        .account-name {
            font-size: 14px;
            font-weight: bold;
        }

        .account-role {
            font-size: 11px;
            color: #71839c;
            margin-top: 3px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;

            background: #58a7dc;
            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
        }


        /* LAYOUT */

        .layout {
            display: flex;
            min-height: calc(100vh - 72px);
        }


        /* SIDEBAR */

        .sidebar {
            width: 220px;
            flex-shrink: 0;

            background: #ffffff;
            border-right: 1px solid #dbe6ee;

            padding: 25px 14px;
        }

        .sidebar-title {
            padding: 0 10px;
            margin-bottom: 15px;

            font-size: 11px;
            font-weight: bold;

            text-transform: uppercase;
            color: #71839c;
        }

        .sidebar a {
            display: block;

            padding: 12px 14px;
            margin-bottom: 5px;

            border-radius: 9px;

            color: #163b59;
            text-decoration: none;

            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #dff3ed;
            color: #087d68;
        }

        .logout {
            margin-top: 25px;
            color: #d9534f !important;
        }


        /* MAIN */

        .main {
            flex: 1;
            padding: 32px;
            min-width: 0;
        }

        .page-label {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .08em;
            color: #087d68;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 7px 0;
            font-size: 30px;
        }

        .page-header p {
            margin: 0;
            color: #71839c;
            font-size: 14px;
        }


        /* PATIENT CARD */

        .patient-card {
            background: #ffffff;
            border: 1px solid #dbe6ee;
            border-radius: 12px;

            padding: 18px 20px;
            margin-bottom: 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .patient-info h2 {
            margin: 0 0 5px;
            font-size: 20px;
        }

        .patient-info p {
            margin: 0;
            color: #71839c;
            font-size: 13px;
        }

        .back-button {
            text-decoration: none;
            color: #087d68;

            border: 1px solid #b8dcd3;
            background: #f3fbf8;

            padding: 10px 15px;
            border-radius: 8px;

            font-size: 13px;
            font-weight: bold;
        }

        .back-button:hover {
            background: #dff3ed;
        }


        /* ODONTOGRAM GRID */

        .odontogram-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 20px;
            align-items: start;
        }

        .model-card,
        .details-card {
            background: #ffffff;
            border: 1px solid #dbe6ee;
            border-radius: 12px;
        }

        .model-header,
        .details-header {
            padding: 18px 20px;

            border-bottom: 1px solid #e5edf2;
        }

        .model-header h2,
        .details-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .model-header p,
        .details-header p {
            margin: 5px 0 0;
            color: #71839c;
            font-size: 12px;
        }


        /* 3D MODEL */

        #canvas-container {
            width: 100%;
            height: 560px;
            min-height: 400px;

            position: relative;
            overflow: hidden;

            background: #eef2f6;
            border-radius: 0 0 12px 12px;
        }

        #canvas-container canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        .model-loading {
            position: absolute;

            top: 50%;
            left: 50%;

            transform: translate(-50%, -50%);

            text-align: center;

            color: #71839c;
            font-size: 13px;

            pointer-events: none;
        }

        .model-error {
            position: absolute;

            left: 20px;
            right: 20px;
            bottom: 20px;

            padding: 12px 14px;

            background: #fff5f5;
            border: 1px solid #f1caca;

            color: #a33b3b;

            border-radius: 8px;

            font-size: 12px;

            display: none;
        }


        /* DETAILS */

        .details-body {
            padding: 20px;
        }

        .selected-box {
            background: #f3f8fb;
            border: 1px solid #dbe6ee;

            border-radius: 9px;

            padding: 15px;
            margin-bottom: 18px;
        }

        .selected-label {
            font-size: 11px;
            color: #71839c;
            text-transform: uppercase;
            font-weight: bold;
        }

        .selected-number {
            margin-top: 5px;

            font-size: 25px;
            font-weight: bold;

            color: #087d68;
        }

        .selected-name {
            margin-top: 3px;

            font-size: 12px;
            color: #71839c;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;

            margin-bottom: 6px;

            font-size: 12px;
            font-weight: bold;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;

            border: 1px solid #cddbe4;
            border-radius: 7px;

            padding: 10px;

            font-family: inherit;
            font-size: 13px;

            outline: none;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #087d68;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 90px;
        }


        /* BUTTONS */

        .save-button {
            width: 100%;

            border: 0;
            border-radius: 8px;

            padding: 12px;

            background: #087d68;
            color: white;

            font-weight: bold;
            font-size: 13px;

            cursor: pointer;
        }

        .save-button:hover {
            background: #066b5a;
        }

        .clear-button {
            width: 100%;

            margin-top: 8px;

            border: 1px solid #cddbe4;
            border-radius: 8px;

            padding: 11px;

            background: white;
            color: #526b7e;

            font-weight: bold;
            font-size: 13px;

            cursor: pointer;
        }

        .clear-button:hover {
            background: #f3f8fb;
        }


        /* SELECTED TEETH */

        .selected-count {
            margin-top: 18px;

            padding: 12px;

            background: #eef8f5;
            border: 1px solid #cce8df;

            border-radius: 8px;

            font-size: 12px;
            color: #087d68;
        }

        .selected-list {
            margin-top: 7px;

            font-weight: bold;
        }


        /* TOOTH GRID */

        .fallback-section {
            padding: 20px;
            border-top: 1px solid #e5edf2;
        }

        .fallback-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .tooth-grid {
            display: grid;
            grid-template-columns: repeat(16, 1fr);
            gap: 5px;
        }

        .tooth-button {
            min-height: 38px;

            border: 1px solid #cddbe4;
            background: white;

            border-radius: 5px;

            font-size: 11px;

            cursor: pointer;
        }

        .tooth-button:hover {
            background: #eef8f5;
            border-color: #087d68;
        }

        .tooth-button.selected {
            background: #087d68;
            color: white;
            border-color: #087d68;
        }


        /* MESSAGE */

        .message {
            display: none;

            padding: 12px 14px;
            margin-bottom: 15px;

            border-radius: 8px;

            font-size: 13px;
        }

        .message.success {
            display: block;

            background: #eef8f5;
            border: 1px solid #cce8df;

            color: #087d68;
        }

        .message.error {
            display: block;

            background: #fff5f5;
            border: 1px solid #f1caca;

            color: #a33b3b;
        }


        /* RESPONSIVE */

        @media (max-width: 1000px) {

            .odontogram-layout {
                grid-template-columns: 1fr;
            }

            .details-card {
                order: 2;
            }

            .model-card {
                order: 1;
            }

        }

        @media (max-width: 700px) {

            .header {
                padding: 0 18px;
            }

            .account-info {
                display: none;
            }

            .sidebar {
                width: 180px;
            }

            .main {
                padding: 20px 15px;
            }

            .patient-card {
                align-items: flex-start;
                flex-direction: column;
            }

            #canvas-container {
                height: 430px;
            }

            .tooth-grid {
                grid-template-columns: repeat(8, 1fr);
            }

        }

    </style>

<link rel="stylesheet" href="../assets/css/app.css">
</head>


<body>


<!-- HEADER -->

<header class="header">

    <div class="brand">

        <img
            src="../assets/images/logo.png"
            alt="SupremeDent"
        >

        <span>SupremeDent</span>

    </div>


    <div class="account">

        <div class="account-info">

            <div class="account-name">
                <?php echo htmlspecialchars($firstName); ?>
            </div>

            <div class="account-role">
                Dentist
            </div>

        </div>


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


<div class="layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-title">
            Dentist Menu
        </div>


        <nav>

            <a href="dashboard.php">
                🏠 Dashboard
            </a>


            <a href="appointments.php">
                📅 Appointments
            </a>


            <a
                href="patients.php"
                class="active"
            >
                🦷 Patients
            </a>


            <a
                href="../auth/logout.php?portal=dentist"
                class="logout"
            >
                🚪 Logout
            </a>

        </nav>

    </aside>


    <!-- MAIN -->

    <main class="main">


        <div class="page-header">

            <span class="page-label">
                3D ODONTOGRAM
            </span>

            <h1>
                3D Odontogram
            </h1>

            <p>
                Select teeth and record the patient's dental condition.
            </p>

        </div>


        <!-- PATIENT -->

        <section class="patient-card">

            <div class="patient-info">

                <h2>
                    <?php echo htmlspecialchars($fullName); ?>
                </h2>

                <p>
                    Patient ID:
                    <?php echo (int)$patient["id"]; ?>

                    &nbsp; • &nbsp;

                    Email:
                    <?php echo htmlspecialchars($patient["email"]); ?>
                </p>

            </div>


            <a
                href="patient_view.php?id=<?php echo (int)$patientId; ?>"
                class="back-button"
            >
                ← Back to Patient
            </a>

        </section>


        <div id="message" class="message"></div>


        <!-- ODONTOGRAM -->

        <div class="odontogram-layout">


            <!-- 3D MODEL -->

            <section class="model-card">

                <div class="model-header">

                    <h2>
                        🦷 Interactive Dental Model
                    </h2>

                    <p>
                        Click a tooth to select or deselect it.
                        You can rotate the model using your mouse.
                    </p>

                </div>


                <div id="canvas-container">

                    <div
                        id="modelLoading"
                        class="model-loading"
                    >
                        Loading 3D dental model...
                    </div>


                    <div
                        id="modelError"
                        class="model-error"
                    >
                        The 3D model could not be loaded.
                        Use the tooth grid below instead.
                    </div>

                </div>


                <!-- FALLBACK -->

                <div class="fallback-section">

                    <div class="fallback-title">
                        Tooth Selection
                    </div>

                    <div class="tooth-grid">

                        <?php for ($i = 1; $i <= 32; $i++): ?>

                            <button
                                type="button"
                                class="tooth-button"
                                data-tooth="<?php echo $i; ?>"
                                onclick="toggleTooth(<?php echo $i; ?>)"
                            >
                                <?php echo $i; ?>
                            </button>

                        <?php endfor; ?>

                    </div>

                </div>

            </section>


            <!-- DETAILS -->

            <section class="details-card">

                <div class="details-header">

                    <h2>
                        Tooth Details
                    </h2>

                    <p>
                        Enter the condition and treatment.
                    </p>

                </div>


                <div class="details-body">


                    <div class="selected-box">

                        <div class="selected-label">
                            Selected Tooth
                        </div>

                        <div
                            id="selectedNumber"
                            class="selected-number"
                        >
                            None
                        </div>

                        <div
                            id="selectedName"
                            class="selected-name"
                        >
                            Click a tooth in the model.
                        </div>

                    </div>


                    <form id="toothForm">


                        <div class="form-group">

                            <label for="condition">
                                Condition
                            </label>

                            <select id="condition">

                                <option value="">
                                    Select condition
                                </option>

                                <option value="Healthy">
                                    Healthy
                                </option>

                                <option value="Caries">
                                    Caries
                                </option>

                                <option value="Filled">
                                    Filled
                                </option>

                                <option value="Missing">
                                    Missing
                                </option>

                                <option value="Fractured">
                                    Fractured
                                </option>

                                <option value="Impacted">
                                    Impacted
                                </option>

                                <option value="Root Canal">
                                    Root Canal
                                </option>

                                <option value="Crown">
                                    Crown
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="treatment">
                                Treatment
                            </label>

                            <select id="treatment">

                                <option value="">
                                    Select treatment
                                </option>

                                <option value="None">
                                    None
                                </option>

                                <option value="Dental Filling">
                                    Dental Filling
                                </option>

                                <option value="Extraction">
                                    Extraction
                                </option>

                                <option value="Root Canal Treatment">
                                    Root Canal Treatment
                                </option>

                                <option value="Dental Crown">
                                    Dental Crown
                                </option>

                                <option value="Cleaning">
                                    Cleaning
                                </option>

                                <option value="Orthodontic Treatment">
                                    Orthodontic Treatment
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="notes">
                                Notes
                            </label>

                            <textarea
                                id="notes"
                                placeholder="Enter notes for the selected tooth..."
                            ></textarea>

                        </div>


                        <button
                            type="submit"
                            class="save-button"
                        >
                            💾 Save Tooth Record
                        </button>


                        <button
                            type="button"
                            class="clear-button"
                            onclick="clearSelection()"
                        >
                            Clear Selection
                        </button>


                    </form>


                    <div class="selected-count">

                        Selected Teeth:

                        <strong id="selectedCount">
                            0
                        </strong>


                        <div
                            id="selectedList"
                            class="selected-list"
                        >
                            None
                        </div>

                    </div>

                </div>

            </section>

        </div>

    </main>

</div>

<script type="importmap">
{
    "imports": {
        "three": "https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.module.js"
    }
}
</script>

<script type="module">

import * as THREE from
'https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.module.js';

import { GLTFLoader } from
'https://cdn.jsdelivr.net/npm/three@0.152.2/examples/jsm/loaders/GLTFLoader.js';

import { OrbitControls } from
'https://cdn.jsdelivr.net/npm/three@0.152.2/examples/jsm/controls/OrbitControls.js';


/*
|--------------------------------------------------------------------------
| PATIENT DATA FROM PHP
|--------------------------------------------------------------------------
*/

const existingRecords =
    <?php
    echo json_encode(
        $odontogram,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    );
    ?>;

const patientId =
    <?php echo (int)$patientId; ?>;


/*
|--------------------------------------------------------------------------
| TOOTH DATA
|--------------------------------------------------------------------------
*/

const toothData = {

    1: "Upper Right Third Molar",
    2: "Upper Right Second Molar",
    3: "Upper Right First Molar",
    4: "Upper Right Second Premolar",
    5: "Upper Right First Premolar",
    6: "Upper Right Canine",
    7: "Upper Right Lateral Incisor",
    8: "Upper Right Central Incisor",

    9: "Upper Left Central Incisor",
    10: "Upper Left Lateral Incisor",
    11: "Upper Left Canine",
    12: "Upper Left First Premolar",
    13: "Upper Left Second Premolar",
    14: "Upper Left First Molar",
    15: "Upper Left Second Molar",
    16: "Upper Left Third Molar",

    17: "Lower Left Third Molar",
    18: "Lower Left Second Molar",
    19: "Lower Left First Molar",
    20: "Lower Left Second Premolar",
    21: "Lower Left First Premolar",
    22: "Lower Left Canine",
    23: "Lower Left Lateral Incisor",
    24: "Lower Left Central Incisor",

    25: "Lower Right Central Incisor",
    26: "Lower Right Lateral Incisor",
    27: "Lower Right Canine",
    28: "Lower Right First Premolar",
    29: "Lower Right Second Premolar",
    30: "Lower Right First Molar",
    31: "Lower Right Second Molar",
    32: "Lower Right Third Molar"

};


/*
|--------------------------------------------------------------------------
| ELEMENTS
|--------------------------------------------------------------------------
*/

const container =
    document.getElementById(
        "canvas-container"
    );

const loading =
    document.getElementById(
        "modelLoading"
    );

const modelError =
    document.getElementById(
        "modelError"
    );

const selectedNumber =
    document.getElementById(
        "selectedNumber"
    );

const selectedName =
    document.getElementById(
        "selectedName"
    );

const selectedCount =
    document.getElementById(
        "selectedCount"
    );

const selectedList =
    document.getElementById(
        "selectedList"
    );

const condition =
    document.getElementById(
        "condition"
    );

const treatment =
    document.getElementById(
        "treatment"
    );

const notes =
    document.getElementById(
        "notes"
    );

const message =
    document.getElementById(
        "message"
    );


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

const selectedTeeth =
    new Set();

let currentTooth =
    null;

let renderer;

let camera;

let scene;

let controls;

let raycaster;

const mouse =
    new THREE.Vector2();

const toothMeshes =
    new Map();

const raycastMeshes =
    [];


/*
|--------------------------------------------------------------------------
| SCENE
|--------------------------------------------------------------------------
*/

scene =
    new THREE.Scene();

scene.background =
    new THREE.Color(
        0xeef2f6
    );


/*
|--------------------------------------------------------------------------
| CAMERA
|--------------------------------------------------------------------------
*/

const width =
    Math.max(
        container.clientWidth,
        500
    );

const height =
    Math.max(
        container.clientHeight,
        400
    );


camera =
    new THREE.PerspectiveCamera(
        34,
        width / height,
        0.1,
        1000
    );


camera.position.set(
    0,
    1,
    24.6
);


/*
|--------------------------------------------------------------------------
| RENDERER
|--------------------------------------------------------------------------
*/

renderer =
    new THREE.WebGLRenderer({

        antialias: true,

        alpha: true

    });


renderer.setSize(
    width,
    height
);


renderer.setPixelRatio(
    Math.min(
        window.devicePixelRatio,
        2
    )
);


renderer.outputColorSpace =
    THREE.SRGBColorSpace;


renderer.shadowMap.enabled =
    true;


container.appendChild(
    renderer.domElement
);


/*
|--------------------------------------------------------------------------
| CONTROLS
|--------------------------------------------------------------------------
*/

controls =
    new OrbitControls(
        camera,
        renderer.domElement
    );


controls.enableDamping =
    true;


controls.enablePan =
    false;


controls.minDistance =
    18;


controls.maxDistance =
    34;


controls.minPolarAngle =
    1.05;


controls.maxPolarAngle =
    2.05;


controls.target.set(
    0,
    -1.7,
    0
);


/*
|--------------------------------------------------------------------------
| RAYCASTER
|--------------------------------------------------------------------------
*/

raycaster =
    new THREE.Raycaster();


/*
|--------------------------------------------------------------------------
| LIGHTS
|--------------------------------------------------------------------------
*/

scene.add(

    new THREE.AmbientLight(
        0xffffff,
        0.75
    )

);


const hemisphere =
    new THREE.HemisphereLight(
        0xf8fbff,
        0xc08f88,
        0.68
    );


scene.add(
    hemisphere
);


const directional =
    new THREE.DirectionalLight(
        0xffffff,
        0.95
    );


directional.position.set(
    5,
    10,
    15
);


directional.castShadow =
    true;


scene.add(
    directional
);


/*
|--------------------------------------------------------------------------
| GROUP
|--------------------------------------------------------------------------
*/

const teethGroup =
    new THREE.Group();


scene.add(
    teethGroup
);


/*
|--------------------------------------------------------------------------
| COLORS
|--------------------------------------------------------------------------
*/

const naturalColor =
    new THREE.Color(
        0xf6f0df
    );


const selectedColor =
    new THREE.Color(
        0x2f67d8
    );


/*
|--------------------------------------------------------------------------
| MATERIAL
|--------------------------------------------------------------------------
*/

function buildToothMaterial() {

    return new THREE.MeshStandardMaterial({

        color:
            naturalColor,

        roughness:
            0.42,

        metalness:
            0.02

    });

}


/*
|--------------------------------------------------------------------------
| OUTLINE
|--------------------------------------------------------------------------
*/

function addToothOutline(
    mesh
) {

    if (
        !mesh.geometry
    ) {

        return;

    }


    const outline =
        new THREE.LineSegments(

            new THREE.EdgesGeometry(
                mesh.geometry,
                28
            ),

            new THREE.LineBasicMaterial({

                color:
                    0xb8bec8,

                transparent:
                    true,

                opacity:
                    0.82

            })

        );


    /*
    Prevent outline from
    receiving mouse clicks.
    */

    outline.raycast =
        () => {};


    mesh.add(
        outline
    );


    mesh.userData.outline =
        outline;

}


/*
|--------------------------------------------------------------------------
| REGISTER TOOTH
|--------------------------------------------------------------------------
*/

function registerTooth(
    mesh,
    id
) {

    if (
        !toothData[id]
    ) {

        return;

    }


    if (
        toothMeshes.has(id)
    ) {

        return;

    }


    mesh.material =
        buildToothMaterial();


    mesh.castShadow =
        true;


    mesh.receiveShadow =
        true;


    mesh.userData.id =
        id;

    mesh.userData.toothId =
        id;


    mesh.userData.name =
        toothData[id];


    mesh.userData.baseColor =
        naturalColor.clone();


    addToothOutline(
        mesh
    );


    toothMeshes.set(
        id,
        mesh
    );

    raycastMeshes.push(
        mesh
    );

}


/*
|--------------------------------------------------------------------------
| MODEL TOOTH NAME
|--------------------------------------------------------------------------
*/

function getNamedToothInfo(
    mesh
) {

    const match =
        String(
            mesh.name || ""
        )
        .match(
            /^(\d+)([DP])(?:\.(\d+))?$/i
        );


    if (!match) {

        return null;

    }


    const localId =
        Number.parseInt(
            match[1],
            10
        );


    const archCode =
        match[2].toUpperCase();


    const duplicate =
        Boolean(
            match[3]
        );


    if (
        localId < 1 ||
        localId > 16
    ) {

        return null;

    }


    if (
        archCode === "P"
    ) {

        return {

            id:
                17 - localId,

            duplicate:
                duplicate

        };

    }


    if (
        archCode === "D"
    ) {

        return {

            id:
                33 - localId,

            duplicate:
                duplicate

        };

    }


    return null;

}


function getToothIdFromObject(
    object
) {

    let current =
        object;


    while (
        current
    ) {

        if (
            current.userData &&
            current.userData.toothId
        ) {

            return current.userData.toothId;

        }


        current =
            current.parent;

    }


    return null;

}


/*
|--------------------------------------------------------------------------
| LOAD 3D MODEL
|--------------------------------------------------------------------------
*/

const loader =
    new GLTFLoader();


loader.load(

    "../assets/models/model.glb",


    function(gltf) {

        console.log(
            "3D dental model loaded."
        );


        loading.style.display =
            "none";


        /*
        Center and scale model
        */

        const box =
            new THREE.Box3()
            .setFromObject(
                gltf.scene
            );


        const size =
            box.getSize(
                new THREE.Vector3()
            );


        const maxDim =
            Math.max(
                size.x,
                size.y,
                size.z
            );


        const scale =
            12.2 /
            maxDim;


        gltf.scene.scale.setScalar(
            scale
        );


        const center =
            box.getCenter(
                new THREE.Vector3()
            );


        gltf.scene.position.sub(
            center
        );


        gltf.scene.position.y =
            -1.7;


        gltf.scene.position.z =
            0.25;


        gltf.scene.rotation.x =
            -0.06;


        teethGroup.add(
            gltf.scene
        );


        /*
        Find teeth
        */

        const unnamed = {

            upper: [],

            lower: []

        };


        const registered =
            new Set();


        gltf.scene.traverse(

            function(child) {

                if (
                    !child.isMesh
                ) {

                    return;

                }


                const named =
                    getNamedToothInfo(
                        child
                    );


                /*
                Named tooth
                */

                if (
                    named
                ) {

                    if (
                        named.duplicate ||
                        toothMeshes.has(
                            named.id
                        )
                    ) {

                        child.visible =
                            false;

                        child.raycast =
                            () => {};

                    } else {

                        registerTooth(
                            child,
                            named.id
                        );


                        registered.add(
                            named.id
                        );

                    }


                    return;

                }


                /*
                Detect possible
                unnamed teeth.
                */

                const material =
                    child.material;


                if (
                    material &&
                    material.color
                ) {

                    const hsl = {};


                    material.color.getHSL(
                        hsl
                    );


                    if (
                        hsl.l > 0.55 &&
                        hsl.s < 0.4
                    ) {

                        const position =
                            child.getWorldPosition(
                                new THREE.Vector3()
                            );


                        const arch =
                            position.y >= 0
                                ? "upper"
                                : "lower";


                        unnamed[
                            arch
                        ].push(
                            child
                        );


                        return;

                    }

                }

            }

        );


        /*
        Remaining tooth IDs
        */

        const remaining = {

            upper:
                Object.keys(
                    toothData
                )
                .map(Number)
                .filter(
                    id =>
                        id <= 16 &&
                        !registered.has(id) &&
                        !toothMeshes.has(id)
                ),

            lower:
                Object.keys(
                    toothData
                )
                .map(Number)
                .filter(
                    id =>
                        id >= 17 &&
                        !registered.has(id) &&
                        !toothMeshes.has(id)
                )

        };


        /*
        Map unnamed teeth
        */

        Object.entries(
            unnamed
        ).forEach(

            ([arch, meshes]) => {

                meshes.sort(
                    (a, b) => {

                        const aPos =
                            a.getWorldPosition(
                                new THREE.Vector3()
                            );


                        const bPos =
                            b.getWorldPosition(
                                new THREE.Vector3()
                            );


                        return (
                            aPos.x -
                            bPos.x
                        );

                    }
                );


                meshes.forEach(

                    (mesh, index) => {

                        const id =
                            remaining[
                                arch
                            ][index];


                        if (
                            id
                        ) {

                            registerTooth(
                                mesh,
                                id
                            );

                        }

                    }

                );

            }

        );


        /*
        Existing records
        */

        Object.keys(
            existingRecords
        ).forEach(

            id => {

                const toothId =
                    Number(id);


                if (
                    toothData[
                        toothId
                    ]
                ) {

                    selectedTeeth.add(
                        toothId
                    );

                }

            }

        );


        updateVisuals();

        updateUI();


        console.log(
            "Interactive teeth:",
            toothMeshes.size
        );

    },


    undefined,


    function(error) {

        console.error(
            "Error loading model:",
            error
        );


        loading.style.display =
            "none";


        modelError.style.display =
            "block";

    }

);


/*
|--------------------------------------------------------------------------
| VISUAL UPDATE
|--------------------------------------------------------------------------
*/

function updateVisuals() {

    toothMeshes.forEach(

        (mesh, id) => {

            const selected =
                selectedTeeth.has(
                    id
                );


            if (
                mesh.material
            ) {

                mesh.material.color.copy(

                    selected
                        ? selectedColor
                        : naturalColor

                );


                mesh.material.emissive.set(

                    selected
                        ? 0x215acb
                        : 0x000000

                );


                mesh.material.emissiveIntensity =
                    selected
                        ? 0.18
                        : 0;

            }


            if (
                mesh.userData.outline
            ) {

                mesh.userData.outline
                    .material
                    .color
                    .set(

                        selected
                            ? 0x2f67d8
                            : 0xb8bec8

                    );

            }

        }

    );


    /*
    Update tooth grid
    */

    document
        .querySelectorAll(
            ".tooth-button"
        )
        .forEach(

            button => {

                const id =
                    Number(
                        button.dataset.tooth
                    );


                button.classList.toggle(

                    "selected",

                    selectedTeeth.has(
                        id
                    )

                );

            }

        );

}


/*
|--------------------------------------------------------------------------
| UI UPDATE
|--------------------------------------------------------------------------
*/

function updateUI() {

    selectedCount.textContent =
        selectedTeeth.size;


    const ids =
        Array.from(
            selectedTeeth
        ).sort(
            (a, b) => a - b
        );


    selectedList.textContent =
        ids.length
            ? ids.join(", ")
            : "None";


    if (
        currentTooth
    ) {

        selectedNumber.textContent =
            "Tooth #" +
            currentTooth;


        selectedName.textContent =
            toothData[
                currentTooth
            ];


        const record =
            existingRecords[
                currentTooth
            ];


        if (
            record
        ) {

            condition.value =
                record.condition ||
                "";


            treatment.value =
                record.treatment ||
                "";


            notes.value =
                record.notes ||
                "";

        } else {

            condition.value =
                "";


            treatment.value =
                "";


            notes.value =
                "";

        }

    } else {

        selectedNumber.textContent =
            "None";


        selectedName.textContent =
            "Click a tooth in the model.";

    }

}


/*
|--------------------------------------------------------------------------
| SELECT TOOTH
|--------------------------------------------------------------------------
*/

function selectTooth(
    id
) {

    if (
        !toothData[id]
    ) {

        return;

    }


    currentTooth =
        id;


    /*
    Toggle selected state
    */

    if (
        selectedTeeth.has(id)
    ) {

        selectedTeeth.delete(
            id
        );

    } else {

        selectedTeeth.add(
            id
        );

    }


    updateVisuals();

    updateUI();

}


/*
|--------------------------------------------------------------------------
| TOOTH GRID BUTTON
|--------------------------------------------------------------------------
*/

window.toggleTooth =
    function(id) {

        selectTooth(
            id
        );

    };


/*
|--------------------------------------------------------------------------
| 3D MODEL CLICK
|--------------------------------------------------------------------------
*/

renderer.domElement.addEventListener(

    "click",

    function(event) {

        const rect =
            renderer.domElement
                .getBoundingClientRect();


        mouse.x =
            (
                (
                    event.clientX -
                    rect.left
                ) /
                rect.width
            ) * 2 - 1;


        mouse.y =
            -(
                (
                    event.clientY -
                    rect.top
                ) /
                rect.height
            ) * 2 + 1;


        raycaster.setFromCamera(
            mouse,
            camera
        );


        const intersects =
            raycaster.intersectObjects(
                raycastMeshes,
                true
            );


        if (
            intersects.length === 0
        ) {

            return;

        }


        const id =
            getToothIdFromObject(
                intersects[0].object
            );


        if (
            id
        ) {

            selectTooth(
                id
            );

        }

    }

);


/*
|--------------------------------------------------------------------------
| CLEAR SELECTION
|--------------------------------------------------------------------------
*/

window.clearSelection =
    function() {

        selectedTeeth.clear();

        currentTooth =
            null;


        condition.value =
            "";


        treatment.value =
            "";


        notes.value =
            "";


        updateVisuals();

        updateUI();

    };


/*
|--------------------------------------------------------------------------
| SAVE ODONTOGRAM
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        "toothForm"
    )
    .addEventListener(

        "submit",

        async function(event) {

            event.preventDefault();


            if (
                selectedTeeth.size === 0
            ) {

                showMessage(

                    "Please select at least one tooth.",

                    "error"

                );


                return;

            }


            const ids =
                Array.from(
                    selectedTeeth
                ).sort(
                    (a, b) => a - b
                );


            const data = {

                patient_id:
                    patientId,

                teeth:
                    ids,

                condition:
                    condition.value,

                treatment:
                    treatment.value,

                notes:
                    notes.value

            };


            try {

                const response =
                    await fetch(

                        "save_odontogram.php",

                        {

                            method:
                                "POST",

                            headers: {

                                "Content-Type":
                                    "application/json"

                            },

                            body:
                                JSON.stringify(
                                    data
                                )

                        }

                    );


                const result =
                    await response.json();


                if (
                    result.success
                ) {

                    showMessage(

                        result.message ||
                        "Odontogram saved successfully.",

                        "success"

                    );


                    /*
                    Update local records
                    */

                    ids.forEach(

                        id => {

                            existingRecords[id] = {

                                condition:
                                    condition.value,

                                treatment:
                                    treatment.value,

                                notes:
                                    notes.value

                            };

                        }

                    );

                } else {

                    showMessage(

                        result.message ||
                        "Unable to save odontogram.",

                        "error"

                    );

                }

            } catch(error) {

                console.error(
                    error
                );


                showMessage(

                    "Server error. Please try again.",

                    "error"

                );

            }

        }

    );


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

function showMessage(
    text,
    type
) {

    message.textContent =
        text;


    message.className =
        "message " +
        type;


    window.scrollTo({

        top: 0,

        behavior: "smooth"

    });


    setTimeout(

        function() {

            message.className =
                "message";

        },

        4000

    );

}


/*
|--------------------------------------------------------------------------
| RESIZE
|--------------------------------------------------------------------------
*/

window.addEventListener(

    "resize",

    function() {

        const newWidth =
            Math.max(
                container.clientWidth,
                500
            );


        const newHeight =
            Math.max(
                container.clientHeight,
                400
            );


        renderer.setSize(
            newWidth,
            newHeight
        );


        camera.aspect =
            newWidth /
            newHeight;


        camera.updateProjectionMatrix();

    }

);


/*
|--------------------------------------------------------------------------
| ANIMATION
|--------------------------------------------------------------------------
*/

function animate() {

    requestAnimationFrame(
        animate
    );


    controls.update();


    renderer.render(
        scene,
        camera
    );

}


animate();

</script>

</body>
</html>