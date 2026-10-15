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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Treatment Plan - SupremeDent</title>

    <link
        rel="stylesheet"
        href="../assets/css/styles.css"
    >
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>
        .dentist-main {
            padding-top: 28px;
        }

        .page-header {
            margin-bottom: 22px;
        }

        .page-label {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 18px;
            background: #dff3f7;
            color: #0c7ca2;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.16em;
        }

        .page-header h1 {
            margin: 12px 0 8px;
            font-size: clamp(2.2rem, 4vw, 4rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: #0d3860;
            font-weight: 800;
        }

        .page-header p {
            font-size: 1.05rem;
            color: #5d778d;
        }

        .patients-section {
            background: #ffffff;
            border: 1px solid #dfeaf1;
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(10, 35, 52, 0.04);
            padding: 24px 24px 18px;
            margin-top: 8px;
        }

        .appointments-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .appointments-header h2 {
            margin: 0 0 6px;
            color: #0b3d5c;
            font-size: clamp(1.6rem, 2vw, 2.3rem);
            line-height: 1.2;
        }

        .appointments-header p {
            color: #68829a;
            font-size: 0.98rem;
        }

        .treatment-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .search-box {
            max-width: 420px;
            width: 100%;
            margin-bottom: 0;
            background: #fff;
            border: 1px solid #dfeaf1;
            border-radius: 12px;
            padding: 0.8rem 0.9rem;
            box-shadow: inset 0 1px 2px rgba(12, 40, 60, 0.02);
        }

        .search-box input {
            font-size: 1rem;
            color: #183d57;
        }

        .record-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .action-btn {
            border: none;
            border-radius: 12px;
            padding: 0.82rem 1.25rem;
            font-family: inherit;
            font-size: 0.96rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
            box-shadow: 0 3px 8px rgba(16, 53, 72, 0.08);
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #0d7ca3, #0f8ab0);
            color: #fff;
        }

        .action-btn.primary:hover {
            background: linear-gradient(135deg, #0a6989, #0d7ca3);
            transform: translateY(-1px);
        }

        .action-btn.secondary {
            background: #edf5f9;
            color: #0f4d6e;
        }

        .action-btn.secondary:hover {
            background: #e2edf4;
            transform: translateY(-1px);
        }

        .appointments-table-wrapper {
            overflow: hidden;
            border: 1px solid #dfeaf1;
            border-radius: 16px;
            background: #fff;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table th,
        .appointments-table td {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
        }

        .appointments-table th {
            background: #f8fbfc;
            color: #34516d;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .appointments-table td {
            color: #183d57;
            font-size: 1rem;
        }

        .appointments-table tbody tr:last-child td {
            border-bottom: none;
        }

        .appointments-table tbody tr:hover {
            background: #f8fdff;
        }

        .appointments-table a {
            color: #0c7ca2;
            font-weight: 600;
            text-decoration: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 92px;
            padding: 0.52rem 0.8rem;
            border-radius: 999px;
            font-size: 0.83rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .status-badge.status-approved {
            background: #dff8ea;
            border-color: #bfe9cf;
            color: #246b4a;
        }

        .status-badge.status-pending {
            background: #fff1d9;
            border-color: #f3d58b;
            color: #8a5d11;
        }

        .status-badge.status-review {
            background: #e0f1ff;
            border-color: #b8dcff;
            color: #255aa8;
        }

        .plan-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 32, 46, 0.45);
            z-index: 1200;
            padding: 20px;
        }

        .plan-modal.active {
            display: flex;
        }

        .plan-modal-card {
            width: min(980px, 100%);
            background: #fff;
            border-radius: 18px;
            border: 1px solid #dfeaf1;
            box-shadow: 0 18px 50px rgba(14, 38, 52, 0.18);
            overflow: hidden;
        }

        .plan-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px;
            border-bottom: 1px solid #e5edf3;
            background: #f7fbfd;
        }

        .plan-modal-header h3 {
            font-size: 20px;
            color: #0b3d5c;
            font-weight: 800;
        }

        .close-modal {
            border: none;
            background: transparent;
            font-size: 28px;
            color: #577184;
            cursor: pointer;
            line-height: 1;
        }

        .plan-modal-body {
            padding: 0;
        }

        .restoration-layout {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            min-height: 560px;
        }

        .restoration-form {
            padding: 22px;
            background: #ffffff;
        }

        .restoration-preview {
            border-left: 1px solid #e5edf3;
            background: linear-gradient(180deg, #eef7fb 0%, #f9fbfd 100%);
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .preview-header small {
            color: #5c7788;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .preview-header span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 86px;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
            background: #e5f3fb;
            color: #136a8d;
        }

        .preview-box {
            width: 100%;
            height: 430px;
            border-radius: 16px;
            border: 1px solid #dbeaf1;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-box::before {
            display: none;
        }

        .preview-box::after {
            display: none;
        }

        .preview-tooth {
            width: 110px;
            height: 130px;
            position: relative;
            border-radius: 26% 26% 30% 30%;
            background: linear-gradient(180deg, #f8fafc 0%, #dfeef2 100%);
            border: 3px solid rgba(17, 74, 96, 0.5);
            box-shadow: 0 12px 30px rgba(20, 75, 90, 0.12);
            z-index: 1;
        }

        .preview-tooth::before,
        .preview-tooth::after {
            content: "";
            position: absolute;
            background: rgba(17, 74, 96, 0.18);
            border-radius: 999px;
        }

        .preview-tooth::before {
            width: 14px;
            height: 54px;
            left: 18px;
            top: 16px;
        }

        .preview-tooth::after {
            width: 14px;
            height: 54px;
            right: 18px;
            top: 16px;
        }

        .plan-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 700;
            color: #44657d;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            border: 1px solid #d7e4ed;
            border-radius: 10px;
            padding: 0.8rem 0.9rem;
            font: inherit;
            color: #0b334d;
            background: #fff;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .plan-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 18px 22px 22px;
            border-top: 1px solid #edf2f7;
            background: #fff;
        }

        .status-badge.status-approved {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-badge.status-pending {
            background: #feebc8;
            color: #744210;
        }

        .status-badge.status-review {
            background: #dbeafe;
            color: #1d4ed8;
        }

        @media (max-width: 760px) {
            .restoration-layout {
                grid-template-columns: 1fr;
            }

            .restoration-preview {
                border-left: none;
                border-top: 1px solid #e5edf3;
            }
        }

        @media (max-width: 640px) {
            .plan-form-grid {
                grid-template-columns: 1fr;
            }

            .treatment-toolbar {
                align-items: stretch;
            }

            .record-actions {
                width: 100%;
            }

            .action-btn {
                flex: 1;
            }
        }
    </style>

    <script type="importmap">
    {
      "imports": {
        "three": "../assets/js/three.module.js"
      }
    }
    </script>
<link rel="stylesheet" href="../assets/css/app.css">
</head>

<body>

<div class="dentist-layout">

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
                <?php echo htmlspecialchars(strtoupper(substr($firstName, 0, 1))); ?>
            </div>

        </div>

    </header>

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

            <a href="Treatment_plan.php" class="active">
                📋 Treatment Plan
            </a>

            <a href="dental_management.php">
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

    <main class="dentist-main">

        <div class="page-header">

            <span class="page-label">
                TREATMENT PLAN
            </span>

            <h1>
                Treatment Plan
            </h1>

            <p>
                Review and add treatment recommendations for each patient.
            </p>

        </div>

        <section class="patients-section">

            <div class="appointments-header">

                <div>
                    <h2>Patient Treatment Plans</h2>
                    <p>Drafted restorations, procedures, and treatment statuses.</p>
                </div>

                <div class="record-actions">
                    <button type="button" class="action-btn secondary" data-plan-type="Removable Restoration">
                        Add Removable Restoration
                    </button>
                    <button type="button" class="action-btn primary" data-plan-type="Fixed Restoration">
                        Add Fixed Restoration
                    </button>
                </div>

            </div>

            <div class="treatment-toolbar">
                <div class="search-box">
                    <i class="fa fa-search" aria-hidden="true" style="color:#718096;"></i>
                    <input
                        id="searchInput"
                        type="text"
                        placeholder="Search patient or treatment"
                        aria-label="Search treatment plans"
                    >
                </div>
            </div>

            <div class="appointments-table-wrapper">

                <table class="appointments-table" id="treatmentTable">

                    <thead>

                        <tr>
                            <th>Patient</th>
                            <th>Procedure</th>
                            <th>Type</th>
                            <th>Estimated Cost</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody id="treatmentTableBody">

                        <tr>
                            <td>John Dela Cruz</td>
                            <td>Root Canal + Crown</td>
                            <td>Fixed Restoration</td>
                            <td>₱18,500</td>
                            <td><span class="status-badge status-pending">Pending</span></td>
                            <td><a href="#">View</a></td>
                        </tr>

                        <tr>
                            <td>Maria Santos</td>
                            <td>Partial Denture</td>
                            <td>Removable Restoration</td>
                            <td>₱12,000</td>
                            <td><span class="status-badge status-approved">Approved</span></td>
                            <td><a href="#">View</a></td>
                        </tr>

                        <tr>
                            <td>Mark Reyes</td>
                            <td>Bridge Consultation</td>
                            <td>Fixed Restoration</td>
                            <td>₱25,000</td>
                            <td><span class="status-badge status-review">Review</span></td>
                            <td><a href="#">View</a></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<div id="planModal" class="plan-modal" aria-hidden="true">

    <div class="plan-modal-card" role="dialog" aria-modal="true" aria-labelledby="planModalTitle">

        <div class="plan-modal-header">
            <h3 id="planModalTitle">Add Treatment Plan</h3>
            <button type="button" class="close-modal" aria-label="Close">×</button>
        </div>

        <form id="planForm">

            <div class="plan-modal-body">

                <div class="restoration-layout">

                    <div class="restoration-form">

                        <div class="plan-form-grid">

                            <div class="form-group">
                                <label for="patientName">Patient</label>
                                <input id="patientName" name="patientName" type="text" placeholder="Enter patient name" required>
                            </div>

                            <div class="form-group">
                                <label for="planType">Type</label>
                                <select id="planType" name="planType" required>
                                    <option value="Removable Restoration">Removable Restoration</option>
                                    <option value="Fixed Restoration">Fixed Restoration</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="treatmentName">Procedure</label>
                                <input id="treatmentName" name="treatmentName" type="text" placeholder="e.g. Partial Denture" required>
                            </div>

                            <div class="form-group">
                                <label for="toothNumber">Tooth No.</label>
                                <input id="toothNumber" name="toothNumber" type="text" placeholder="e.g. 18, 24, 36" required>
                            </div>

                            <div class="form-group">
                                <label for="estimatedCost">Estimated Cost</label>
                                <input id="estimatedCost" name="estimatedCost" type="text" placeholder="₱0.00" required>
                            </div>

                            <div class="form-group">
                                <label for="planStatus">Status</label>
                                <select id="planStatus" name="planStatus" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Review">Review</option>
                                </select>
                            </div>

                            <div class="form-group full">
                                <label for="planNotes">Notes</label>
                                <textarea id="planNotes" name="planNotes" placeholder="Short treatment notes"></textarea>
                            </div>

                        </div>

                    </div>

                    <div class="restoration-preview">

                        <div class="preview-header">
                            <small>3D Preview</small>
                            <span id="previewTypeBadge">Removable</span>
                        </div>

                        <div id="restorationViewer" class="preview-box" aria-label="3D dental model preview"></div>
                        <div style="margin-top: 12px; font-size: 12px; font-weight: 700; color: #355a76; text-transform: uppercase; letter-spacing: 0.08em;">
                            Selected tooth: <span id="selectedToothLabel">None</span>
                        </div>

                    </div>

                </div>

            </div>

            <div class="plan-modal-footer">
                <button type="button" class="action-btn secondary close-form">Cancel</button>
                <button type="submit" class="action-btn primary">Save</button>
            </div>

        </form>

    </div>

</div>

<script type="module">
    import * as THREE from 'three';
    import { GLTFLoader } from '../assets/js/loaders/GLTFLoader.js';
    import { OrbitControls } from '../assets/js/controls/OrbitControls.js';

    const modal = document.getElementById('planModal');
    const planForm = document.getElementById('planForm');
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('treatmentTableBody');
    const modalTitle = document.getElementById('planModalTitle');
    const planTypeField = document.getElementById('planType');
    const previewTypeBadge = document.getElementById('previewTypeBadge');
    const selectedToothLabel = document.getElementById('selectedToothLabel');
    const closeButtons = document.querySelectorAll('.close-modal, .close-form');
    const addButtons = document.querySelectorAll('[data-plan-type]');
    const previewContainer = document.getElementById('restorationViewer');

    let threeScene = null;
    let threeCamera = null;
    let threeRenderer = null;
    let threeControls = null;
    let toothMeshes = [];
    let selectedMesh = null;
    let selectedMeshOriginalColor = null;
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2();

    function initThreePreview() {
        if (threeScene) {
            return;
        }

        threeScene = new THREE.Scene();
        threeScene.background = new THREE.Color(0x2c3e50);

        threeCamera = new THREE.PerspectiveCamera(45, previewContainer.clientWidth / previewContainer.clientHeight, 0.1, 1000);
        threeCamera.position.set(0, 0, 8);

        threeRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        threeRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        threeRenderer.setSize(previewContainer.clientWidth, previewContainer.clientHeight);
        previewContainer.appendChild(threeRenderer.domElement);

        threeControls = new OrbitControls(threeCamera, threeRenderer.domElement);
        threeControls.enableDamping = true;
        threeControls.enablePan = false;
        threeControls.enableZoom = true;
        threeControls.enableRotate = true;
        threeControls.autoRotate = false;
        threeControls.target.set(0, 0, 0);

        const ambient = new THREE.AmbientLight(0xffffff, 1.6);
        threeScene.add(ambient);

        const dir = new THREE.DirectionalLight(0xffffff, 1.1);
        dir.position.set(3, 5, 6);
        threeScene.add(dir);

        const loader = new GLTFLoader();
        loader.load('../assets/models/REMOVABLE.glb', function (gltf) {
            const model = gltf.scene;
            model.scale.setScalar(1.0);
            model.rotation.y = 0;
            model.rotation.x = 0;
            model.position.set(0, 0, 0);
            threeScene.add(model);

            toothMeshes = [];
            model.traverse(function (child) {
                if (child.isMesh) {
                    child.material = child.material.clone();
                    child.userData.originalColor = child.material.color.clone();
                    toothMeshes.push(child);
                }
            });
        }, undefined, function (error) {
            console.warn('3D model failed to load.', error);
        });

        function animate() {
            requestAnimationFrame(animate);
            if (threeControls) {
                threeControls.update();
            }
            if (threeRenderer && threeScene && threeCamera) {
                threeRenderer.render(threeScene, threeCamera);
            }
        }

        animate();

        threeRenderer.domElement.addEventListener('pointerdown', function (event) {
            const rect = threeRenderer.domElement.getBoundingClientRect();
            mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

            raycaster.setFromCamera(mouse, threeCamera);
            const intersections = raycaster.intersectObjects(toothMeshes, true);

            if (intersections.length > 0) {
                const mesh = intersections[0].object;

                if (selectedMesh && selectedMesh !== mesh) {
                    selectedMesh.material.color.copy(selectedMesh.userData.originalColor || new THREE.Color(0xffffff));
                    selectedMesh.material.emissive.setHex(0x000000);
                    selectedMesh.material.emissiveIntensity = 0;
                }

                selectedMesh = mesh;
                selectedMeshOriginalColor = mesh.userData.originalColor || new THREE.Color(0xffffff);
                mesh.material.color.set('#7fe1c8');
                mesh.material.emissive.set('#7fe1c8');
                mesh.material.emissiveIntensity = 0.35;

                const toothNumber = mesh.name || "Selected";
                selectedToothLabel.textContent = toothNumber;
                document.getElementById('toothNumber').value = toothNumber;
            }
        });

        window.addEventListener('resize', function () {
            if (!previewContainer || !threeCamera || !threeRenderer) return;
            const width = previewContainer.clientWidth;
            const height = previewContainer.clientHeight;
            threeCamera.aspect = width / height;
            threeCamera.updateProjectionMatrix();
            threeRenderer.setSize(width, height);
        });
    }

    function openModal(type) {
        modalTitle.textContent = 'Add ' + type;
        planTypeField.value = type;
        previewTypeBadge.textContent = type.includes('Removable') ? 'Removable' : 'Fixed';
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('patientName').focus();

        setTimeout(function () {
            initThreePreview();
        }, 100);
    }

    addButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(this.dataset.planType);
        });
    });

    function closeModal() {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        planForm.reset();
        planTypeField.value = 'Removable Restoration';
        previewTypeBadge.textContent = 'Removable';
        selectedToothLabel.textContent = 'None';
        if (selectedMesh) {
            selectedMesh.material.color.copy(selectedMesh.userData.originalColor || new THREE.Color(0xffffff));
            selectedMesh.material.emissive.setHex(0x000000);
            selectedMesh.material.emissiveIntensity = 0;
            selectedMesh = null;
        }
    }

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    planForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const patient = document.getElementById('patientName').value.trim();
        const type = document.getElementById('planType').value;
        const procedure = document.getElementById('treatmentName').value.trim();
        const cost = document.getElementById('estimatedCost').value.trim();
        const status = document.getElementById('planStatus').value;

        if (!patient || !procedure || !cost) {
            return;
        }

        const newRow = document.createElement('tr');
        const statusClass = status === 'Approved'
            ? 'status-approved'
            : status === 'Review'
                ? 'status-review'
                : 'status-pending';

        newRow.innerHTML = '<td>' + patient + '</td>' +
            '<td>' + procedure + '</td>' +
            '<td>' + type + '</td>' +
            '<td>' + cost + '</td>' +
            '<td><span class="status-badge ' + statusClass + '">' + status + '</span></td>' +
            '<td><a href="#">View</a></td>';

        tableBody.prepend(newRow);
        closeModal();
    });

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const rows = tableBody.querySelectorAll('tr');

        rows.forEach(function (row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    document.getElementById('toothNumber').addEventListener('input', function () {
        const value = this.value.trim();
        if (value) {
            selectedToothLabel.textContent = value;
        } else {
            selectedToothLabel.textContent = 'None';
        }
    });
</script>

</body>

</html>
