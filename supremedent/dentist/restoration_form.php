<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);

$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$type = $_GET['type'] ?? $_POST['restoration_type'] ?? 'removable';
if (!in_array($type, ['removable', 'fixed'], true)) $type = 'removable';

// Lab prescription options (revision 2 layout).
$removableWork = [
    'Complete Denture' => ['Full arch', ['Bite Block', 'Setup for Try-In', 'Process & Finish', 'Custom Tray']],
    'Cast Partial Framework' => ['Metal framework', ['Frame Try-In', 'Frame w/ Bite Block', 'Frame w/ Teeth Try-In', 'Process & Finish']],
    'Combo Partial' => ['Cast frame & thermoplastic w/ colored clamp', ['Frame Try-In', 'Frame w/ Teeth Try-In', 'Process & Finish']],
    'Flexible' => ['Flexible partial', ['Bite Block', 'Setup for Try-In', 'Process & Finish']],
    'Acrylic Partials' => ['Acrylic base', ['Bite Block', 'Setup for Try-In', 'Process & Finish']],
];
$teethTypes = ['Plastic', 'Porcelain', 'Ivoclar', 'Bioform', 'New Ace PX'];
$fixedWork = ['Zircon', 'Pressed Ceramic', 'Ceramage', 'Plastic', 'Full Metal', 'PFM'];
$alloys = ['Non-Precious', 'Tilite'];
$margins = ['Porcelain Butt Margin', 'Metal Margin on Buccal', 'Metal-Porcelain Junction Margin'];
$occlusal = ['None', 'Light', 'Medium', 'Dark'];
$translucency = ['Low', 'Medium', 'High'];
// Metal designs: M1.glb Design 1-4, M2.glb Design 5-9 (mesh names from the 3D files).
$metalDesigns = [
    'Design 1' => ['M1.glb', ['Molar_A_Clean']], 'Design 2' => ['M1.glb', ['Molar_B_Thin_Band']], 'Design 3' => ['M1.glb', ['Molar_C_Wide_Band']], 'Design 4' => ['M1.glb', ['Molar_D_Corner']],
    'Design 5' => ['M2.glb', ['Molar_A_Side_Region']], 'Design 6' => ['M2.glb', ['Molar_B_Large_Side_Region']], 'Design 7' => ['M2.glb', ['Incisor_C_Clean']],
    'Design 8' => ['M2.glb', ['Incisor_D_Edge_Region']], 'Design 9' => ['M2.glb', ['Incisor_E_Deep_Region']],
];
// Pontic designs: P.glb tooth + its dark base. Stored values are unchanged from the earlier form.
$ponticDesigns = [
    'Hygienic' => ['Hygienic', ['Tooth_A_Peak', 'Tooth_A_Peak_DarkBase']],
    'Modified Ridge Lap' => ['Modified RL', ['Tooth_B_Wedge', 'Tooth_B_Wedge_DarkBase']],
    'Bullet' => ['Bullet', ['Tooth_C_Flat', 'Tooth_C_Flat_DarkBase']],
    'Ridge Lap' => ['Ridge Lap', ['Tooth_D_Mound', 'Tooth_D_Mound_DarkBase']],
];
$teeth = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28','48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
$statuses = ['Draft', 'Sent', 'In Progress', 'Ready', 'Completed', 'Cancelled'];

$order = [
    'id' => 0, 'patient_id' => (int)($_GET['patient_id'] ?? 0), 'treatment_plan_id' => null, 'restoration_type' => $type,
    'order_no' => '', 'date_sent' => date('Y-m-d'), 'date_due' => '', 'phone' => '', 'sex' => '', 'age' => '', 'selected_teeth' => '[]',
    'work_options' => '[]', 'teeth_material_options' => '[]', 'shade' => '', 'mould' => '', 'staining_options' => '[]', 'metal_design' => '',
    'pontic_design' => '', 'instructions' => '', 'status' => 'Draft'
];
if ($id) {
    $st = $conn->prepare('SELECT * FROM restoration_orders WHERE id=? AND dentist_id=? LIMIT 1');
    $st->bind_param('ii', $id, $uid);
    $st->execute();
    $found = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$found) { header('Location: treatment_plans.php'); exit; }
    $order = array_merge($order, $found);
    $type = $order['restoration_type'];
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient = (int)($_POST['patient_id'] ?? 0);
    $orderNo = trim($_POST['order_no'] ?? '');
    $dateSent = trim($_POST['date_sent'] ?? '') ?: null;
    $dateDue = trim($_POST['date_due'] ?? '') ?: null;
    $phone = trim($_POST['phone'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $selected = json_decode($_POST['selected_teeth'] ?? '[]', true);
    if (!is_array($selected)) $selected = [];
    $selected = array_values(array_filter(array_unique(array_map('strval', $selected)), fn($t) => in_array($t, $teeth, true)));
    $selected = array_values(array_intersect($teeth, $selected));
    $pick = static fn($values, array $allowed) => array_values(array_intersect($allowed, is_array($values) ? array_map('strval', $values) : []));
    $shade = trim($_POST['shade'] ?? '');
    $mould = trim($_POST['mould'] ?? '');
    $metal = '';
    $pontic = '';
    $staining = [];
    if ($type === 'removable') {
        $workType = (string)($_POST['work_type'] ?? '');
        $work = isset($removableWork[$workType]) ? array_merge([$workType], $pick($_POST['stages'] ?? [], $removableWork[$workType][1])) : [];
        $materials = $pick($_POST['teeth_types'] ?? [], $teethTypes);
    } else {
        $work = $pick($_POST['work_options'] ?? [], $fixedWork);
        $materials = array_merge($pick($_POST['alloys'] ?? [], $alloys), $pick($_POST['margins'] ?? [], $margins));
        $oc = (string)($_POST['occlusal_staining'] ?? '');
        $tr = (string)($_POST['translucency'] ?? '');
        if (in_array($oc, $occlusal, true)) $staining[] = 'Staining: ' . $oc;
        if (in_array($tr, $translucency, true)) $staining[] = 'Translucency: ' . $tr;
        $metal = isset($metalDesigns[$_POST['metal_design'] ?? '']) ? $_POST['metal_design'] : '';
        $pontic = isset($ponticDesigns[$_POST['pontic_design'] ?? '']) ? $_POST['pontic_design'] : '';
    }
    $instructions = trim($_POST['instructions'] ?? '');
    $status = $_POST['status'] ?? 'Draft';
    if (!in_array($status, $statuses, true)) $status = 'Draft';
    $isDraft = ($_POST['save_mode'] ?? '') === 'draft';
    // "Save as Draft" keeps the order as a draft; "Create Restoration Plan" sends a draft to the lab.
    if ($isDraft) $status = 'Draft';
    elseif ($status === 'Draft') $status = 'Sent';
    $chk = $conn->prepare("SELECT id FROM users WHERE id=? AND role='patient' AND status='active'");
    $chk->bind_param('i', $patient);
    $chk->execute();
    $valid = $chk->get_result()->fetch_assoc();
    $chk->close();
    // Keep what was entered if the form has to be shown again.
    $order = array_merge($order, ['patient_id' => $patient, 'order_no' => $orderNo, 'date_sent' => (string)$dateSent, 'date_due' => (string)$dateDue, 'phone' => $phone, 'sex' => $sex, 'age' => $age,
        'selected_teeth' => json_encode($selected), 'work_options' => json_encode($work), 'teeth_material_options' => json_encode($materials), 'shade' => $shade, 'mould' => $mould,
        'staining_options' => json_encode($staining), 'metal_design' => $metal, 'pontic_design' => $pontic, 'instructions' => $instructions, 'status' => $status]);
    if (!$valid) $error = 'Choose a valid patient.';
    elseif (!$selected && !$isDraft) $error = 'Select at least one tooth for this restoration plan.';
    else {
        $workJson = json_encode(array_values($work));
        $matJson = json_encode(array_values($materials));
        $stainJson = json_encode(array_values($staining));
        $teethJson = json_encode($selected);
        $conn->begin_transaction();
        try {
            $tpId = (int)($order['treatment_plan_id'] ?? 0);
            $procedure = $type === 'removable' ? 'Removable Restoration' : 'Fixed Restoration';
            $toothText = implode(', ', $selected);
            $description = $procedure . ' order' . ($orderNo !== '' ? ' #' . $orderNo : '');
            $notes = $instructions;
            $tpStatus = $status === 'Completed' ? 'Completed' : ($status === 'Cancelled' ? 'Cancelled' : 'Proposed');
            if ($tpId) {
                $st = $conn->prepare('UPDATE treatment_plans SET patient_id=?,dentist_id=?,procedure_name=?,tooth_number=?,description=?,status=?,notes=? WHERE id=?');
                $st->bind_param('iisssssi', $patient, $uid, $procedure, $toothText, $description, $tpStatus, $notes, $tpId);
                $st->execute();
                $st->close();
                // Every later save by the dentist is announced to the patient as well.
                $n = $conn->prepare("INSERT INTO notifications(patient_id,title,message,type,is_read) VALUES(?,'Treatment Plan Updated',?,'treatment',0)");
                $nm = 'Your dentist updated your ' . $procedure . ' plan. Status: ' . $status . ($toothText !== '' ? ' · Teeth: ' . $toothText : '') . '.';
                $n->bind_param('is', $patient, $nm);
                $n->execute();
                $n->close();
            } else {
                $st = $conn->prepare('INSERT INTO treatment_plans(patient_id,dentist_id,procedure_name,tooth_number,description,status,notes) VALUES(?,?,?,?,?,?,?)');
                $st->bind_param('iisssss', $patient, $uid, $procedure, $toothText, $description, $tpStatus, $notes);
                $st->execute();
                $tpId = $conn->insert_id;
                $st->close();
                $n = $conn->prepare("INSERT INTO notifications(patient_id,title,message,type,is_read) VALUES(?,'Treatment Plan Available',?,'treatment',0)");
                $nm = 'A new ' . $procedure . ' plan is available in your account.';
                $n->bind_param('is', $patient, $nm);
                $n->execute();
                $n->close();
            }
            if ($id) {
                $st = $conn->prepare('UPDATE restoration_orders SET patient_id=?,treatment_plan_id=?,restoration_type=?,order_no=?,date_sent=?,date_due=?,phone=?,sex=?,age=?,selected_teeth=?,work_options=?,teeth_material_options=?,shade=?,mould=?,staining_options=?,metal_design=?,pontic_design=?,instructions=?,status=? WHERE id=? AND dentist_id=?');
                $st->bind_param('iisssssssssssssssssii', $patient, $tpId, $type, $orderNo, $dateSent, $dateDue, $phone, $sex, $age, $teethJson, $workJson, $matJson, $shade, $mould, $stainJson, $metal, $pontic, $instructions, $status, $id, $uid);
                $st->execute();
                $st->close();
            } else {
                $st = $conn->prepare('INSERT INTO restoration_orders(patient_id,dentist_id,treatment_plan_id,restoration_type,order_no,date_sent,date_due,phone,sex,age,selected_teeth,work_options,teeth_material_options,shade,mould,staining_options,metal_design,pontic_design,instructions,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $st->bind_param('iiisssssssssssssssss', $patient, $uid, $tpId, $type, $orderNo, $dateSent, $dateDue, $phone, $sex, $age, $teethJson, $workJson, $matJson, $shade, $mould, $stainJson, $metal, $pontic, $instructions, $status);
                $st->execute();
                $id = $conn->insert_id;
                $st->close();
            }
            $conn->commit();
            log_activity($conn, $uid, 'Saved restoration plan', 'Treatment Plans', 'Order #' . $id . ' · patient #' . $patient . ' · ' . $status);
            header('Location: restoration_view.php?id=' . $id . '&saved=1');
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Unable to save the restoration plan. Please try again.';
            error_log('[SupremeDent restoration] ' . $e->getMessage());
        }
    }
}
$patients = $conn->query("SELECT u.id,u.first_name,u.last_name,p.phone,p.gender,p.birthday FROM users u LEFT JOIN patient_profiles p ON p.user_id=u.id WHERE u.role='patient' AND u.status='active' ORDER BY u.last_name,u.first_name");
$jsonList = static function ($v): array { $d = json_decode((string)$v ?: '[]', true); return is_array($d) ? array_map('strval', $d) : []; };
$selected = $jsonList($order['selected_teeth']);
$workSelected = $jsonList($order['work_options']);
$materialsSelected = $jsonList($order['teeth_material_options']);
$stainSelected = $jsonList($order['staining_options']);
$currentWork = '';
foreach ($workSelected as $w) if (isset($removableWork[$w])) { $currentWork = $w; break; }
if ($type === 'removable' && $currentWork === '' && !$id) $currentWork = 'Complete Denture';
$currentOcclusal = '';
$currentTranslucency = '';
foreach ($stainSelected as $sv) {
    if (str_starts_with($sv, 'Staining: ')) $currentOcclusal = substr($sv, 10);
    if (str_starts_with($sv, 'Translucency: ')) $currentTranslucency = substr($sv, 14);
}
$checked = static fn(bool $on) => $on ? 'checked' : '';
$title = ($id ? 'Edit' : 'Create') . ' ' . ucfirst($type) . ' Restoration Plan';
require '../includes/ui.php';
ui_header(ucfirst($type) . ' Restoration', 'dentist');
ui_sidebar('dentist', 'treatment_plans.php');
?>
<div class="page-head compact-head"><div><div class="eyebrow"><?= e(strtoupper($type)) ?> RESTORATION</div><h1><?= e($title) ?></h1><div class="muted">Fill in the laboratory-style prescription and select teeth using the chart or 3D model.</div></div><a class="btn btn-secondary" href="treatment_plans.php">Back to History</a></div>
<?php if ($error) flash('error', $error); ?>
<form method="post" id="restorationForm" class="rx-form rx-<?= e($type) ?>" novalidate>
    <input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="restoration_type" value="<?= e($type) ?>">
    <input type="hidden" name="selected_teeth" id="selectedTeeth" value="<?= e(json_encode($selected)) ?>"><input type="hidden" name="save_mode" id="saveMode" value="create">
    <div class="rx-grid">
        <div class="rx-col">
            <section class="card rx-card">
                <h2>Patient &amp; Order</h2>
                <div class="rx-fields">
                    <div class="rx-field"><label>Dentist</label><input value="<?= e(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))) ?>" disabled></div>
                    <div class="rx-field"><label for="patientSelect">Patient *</label><select name="patient_id" id="patientSelect" required><option value="">Select patient</option><?php while ($p = $patients->fetch_assoc()): $pAge = $p['birthday'] ? (new DateTime($p['birthday']))->diff(new DateTime('today'))->y : ''; ?><option value="<?= (int)$p['id'] ?>" data-phone="<?= e($p['phone'] ?? '') ?>" data-sex="<?= e($p['gender'] ?? '') ?>" data-age="<?= e($pAge) ?>" <?= ((int)$order['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= e($p['last_name'] . ', ' . $p['first_name']) ?></option><?php endwhile; ?></select></div>
                    <div class="rx-field"><label for="orderNo">Order No.</label><input id="orderNo" name="order_no" value="<?= e($order['order_no']) ?>" placeholder="e.g. <?= $type === 'removable' ? 'RR' : 'FR' ?>-<?= date('Y') ?>-001"></div>
                    <div class="rx-field"><label for="orderStatus">Status</label><select id="orderStatus" name="status"><?php foreach ($statuses as $x): ?><option <?= $order['status'] === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select></div>
                    <div class="rx-field"><label for="dateSent">Date Sent</label><input id="dateSent" type="date" name="date_sent" value="<?= e($order['date_sent']) ?>"></div>
                    <div class="rx-field"><label for="dateDue">Date Due</label><input id="dateDue" type="date" name="date_due" value="<?= e($order['date_due']) ?>"></div>
                    <div class="rx-field"><label for="patientPhone">Phone</label><input id="patientPhone" name="phone" value="<?= e($order['phone']) ?>"></div>
                    <div class="rx-field rx-sex-age"><div><label for="patientSex">Sex</label><select id="patientSex" name="sex"><option value="">Select</option><?php foreach (['Male', 'Female'] as $sx): ?><option <?= strcasecmp((string)$order['sex'], $sx) === 0 ? 'selected' : '' ?>><?= $sx ?></option><?php endforeach; ?></select></div><div><label for="patientAge">Age</label><input id="patientAge" name="age" value="<?= e($order['age']) ?>" inputmode="numeric"></div></div>
                </div>
            </section>
            <section class="card rx-card">
                <h2>Lab Prescription</h2>
                <?php if ($type === 'removable'): ?>
                    <div class="rx-label">1 · TYPE OF WORK</div>
                    <div class="rx-work-cards" role="radiogroup" aria-label="Type of work">
                        <?php foreach ($removableWork as $w => [$desc, $stages]): ?><label class="rx-work-card"><input type="radio" name="work_type" value="<?= e($w) ?>" data-stages="<?= e(json_encode($stages)) ?>" <?= $checked($currentWork === $w) ?>><span><b><?= e($w) ?></b><small><?= e($desc) ?></small></span></label><?php endforeach; ?>
                    </div>
                    <div class="rx-stage-panel"><div class="rx-label" id="stageTitle">2 · STAGE</div><div class="rx-chips" id="stageChips" data-selected="<?= e(json_encode(array_values(array_slice($workSelected, $currentWork !== '' ? 1 : 0)))) ?>"></div></div>
                    <div class="rx-label">3 · TYPE OF TEETH</div>
                    <div class="rx-chips"><?php foreach ($teethTypes as $x): ?><label class="rx-chip"><input type="checkbox" name="teeth_types[]" value="<?= e($x) ?>" <?= $checked(in_array($x, $materialsSelected, true)) ?>><span><?= e($x) ?></span></label><?php endforeach; ?></div>
                    <div class="rx-fields rx-two">
                        <div class="rx-field"><label for="rxShade">SHADE</label><input id="rxShade" name="shade" value="<?= e($order['shade']) ?>" placeholder="e.g. A2"></div>
                        <div class="rx-field"><label for="rxMould">MOULD</label><input id="rxMould" name="mould" value="<?= e($order['mould']) ?>" placeholder="e.g. 33M"></div>
                    </div>
                <?php else: ?>
                    <div class="rx-label">TYPE OF WORK</div>
                    <div class="rx-chips rx-chips-3"><?php foreach ($fixedWork as $x): ?><label class="rx-chip"><input type="checkbox" name="work_options[]" value="<?= e($x) ?>" <?= $checked(in_array($x, $workSelected, true)) ?>><span><?= e($x) ?></span></label><?php endforeach; ?></div>
                    <div class="rx-fields rx-two rx-alloy">
                        <div><div class="rx-label">TYPE OF ALLOY</div><div class="rx-chips"><?php foreach ($alloys as $x): ?><label class="rx-chip"><input type="checkbox" name="alloys[]" value="<?= e($x) ?>" <?= $checked(in_array($x, $materialsSelected, true)) ?>><span><?= e($x) ?></span></label><?php endforeach; ?></div><small class="muted">For PFM and Full Metal work.</small></div>
                        <div><div class="rx-label">BUCCAL MARGIN</div><div class="rx-chips rx-stack"><?php foreach ($margins as $x): ?><label class="rx-chip"><input type="checkbox" name="margins[]" value="<?= e($x) ?>" <?= $checked(in_array($x, $materialsSelected, true)) ?>><span><?= e($x) ?></span></label><?php endforeach; ?></div></div>
                    </div>
                    <div class="rx-label rx-label-row"><span>TOOTH TO BE RESTORED</span><span class="rx-encircle">Tap to encircle: <b id="encircleList">—</b></span></div>
                    <div class="rx-quadrants" aria-label="Tooth to be restored">
                        <?php foreach (['Upper' => ['1', '2'], 'Lower' => ['4', '3']] as $arch => [$rightQ, $leftQ]): ?>
                            <div class="rx-quad-row"><span class="rx-quad-name"><?= $arch ?></span><div class="rx-quad-teeth">
                                <?php for ($n = 8; $n >= 1; $n--): ?><button type="button" class="rx-qtooth" data-tooth="<?= $rightQ . $n ?>" title="<?= ($arch === 'Upper' ? 'UR' : 'LR') . $n ?> (tooth <?= $rightQ . $n ?>)"><?= $n ?></button><?php endfor; ?>
                                <span class="rx-midline" aria-hidden="true"></span>
                                <?php for ($n = 1; $n <= 8; $n++): ?><button type="button" class="rx-qtooth" data-tooth="<?= $leftQ . $n ?>" title="<?= ($arch === 'Upper' ? 'UL' : 'LL') . $n ?> (tooth <?= $leftQ . $n ?>)"><?= $n ?></button><?php endfor; ?>
                            </div></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="rx-sides"><span>Patient's right</span><span>Patient's left</span></div>
                <?php endif; ?>
                <div class="rx-label">INSTRUCTIONS</div>
                <textarea name="instructions" class="rx-textarea" placeholder="Special instructions for the laboratory..."><?= e($order['instructions']) ?></textarea>
            </section>
        </div>
        <div class="rx-col">
            <?php if ($type === 'removable'): ?>
                <section class="card rx-card">
                    <div class="rx-card-head"><h2>Interactive 3D Model</h2><span class="muted" id="selectedCount">0 selected</span></div>
                    <p class="muted rx-sub">Click a tooth on the model or the chart to mark it for replacement.</p>
                    <div class="rx-model" id="archModel"><span>Loading 3D model...</span></div>
                    <div class="rx-label rx-label-row"><span>TEETH TO REPLACE</span><b id="teethList">—</b></div>
                    <div class="rx-fdi">
                        <div class="rx-fdi-row"><?php foreach (array_slice($teeth, 0, 16) as $t): ?><button type="button" class="rx-tooth" data-tooth="<?= $t ?>"><?= $t ?></button><?php endforeach; ?></div>
                        <div class="rx-fdi-row"><?php foreach (array_slice($teeth, 16) as $t): ?><button type="button" class="rx-tooth" data-tooth="<?= $t ?>"><?= $t ?></button><?php endforeach; ?></div>
                    </div>
                    <div class="rx-sides"><span>Patient's right</span><span>Patient's left</span></div>
                </section>
                <section class="card rx-card rx-summary">
                    <h2>Order Summary</h2>
                    <dl><dt>Type of work</dt><dd id="sumWork">—</dd><dt>Stage</dt><dd id="sumStage">—</dd><dt>Teeth type</dt><dd id="sumTeethType">—</dd><dt>Teeth</dt><dd id="sumTeeth">—</dd></dl>
                </section>
            <?php else: ?>
                <section class="card rx-card">
                    <h2>Staining &amp; Design</h2>
                    <div class="rx-label">CHART FOR STAINING</div>
                    <div class="rx-model rx-stain-model" id="stainModel"><span>Loading staining chart...</span></div>
                    <div class="rx-label">SHADE</div>
                    <input class="rx-input" name="shade" value="<?= e($order['shade']) ?>" maxlength="100" placeholder="e.g. A2, B1 (VITA classical)">
                    <div class="rx-fields rx-two">
                        <div><div class="rx-label">OCCLUSAL STAINING</div><div class="rx-seg"><?php foreach ($occlusal as $x): ?><label><input type="radio" name="occlusal_staining" value="<?= e($x) ?>" <?= $checked($currentOcclusal === $x) ?>><span><?= e($x) ?></span></label><?php endforeach; ?></div></div>
                        <div><div class="rx-label">TRANSLUCENCY</div><div class="rx-seg"><?php foreach ($translucency as $x): ?><label><input type="radio" name="translucency" value="<?= e($x) ?>" <?= $checked($currentTranslucency === $x) ?>><span><?= e($x) ?></span></label><?php endforeach; ?></div></div>
                    </div>
                    <div class="rx-label">METAL DESIGN</div>
                    <div class="rx-designs rx-designs-metal">
                        <?php foreach ($metalDesigns as $value => [$model, $nodes]): ?><label class="design-tile"><input type="radio" name="metal_design" value="<?= e($value) ?>" <?= $checked($order['metal_design'] === $value) ?>><span class="design-thumb"><img alt="" data-model="<?= e($model) ?>" data-nodes="<?= e(json_encode($nodes)) ?>"></span><span class="design-name"><?= e($value) ?></span></label><?php endforeach; ?>
                    </div>
                    <div class="rx-label">PONTIC DESIGN</div>
                    <div class="rx-designs rx-designs-pontic">
                        <?php foreach ($ponticDesigns as $value => [$label, $nodes]): ?><label class="design-tile"><input type="radio" name="pontic_design" value="<?= e($value) ?>" <?= $checked($order['pontic_design'] === $value) ?>><span class="design-thumb"><img alt="" data-model="P.glb" data-nodes="<?= e(json_encode($nodes)) ?>"></span><span class="design-name"><?= e($label) ?></span></label><?php endforeach; ?>
                    </div>
                    <button type="button" class="rx-clear-designs" id="clearDesigns">Clear staining &amp; designs</button>
                </section>
            <?php endif; ?>
            <div class="rx-actions">
                <button type="submit" class="btn rx-draft" data-mode="draft">Save as Draft</button>
                <button type="submit" class="btn btn-primary rx-create" data-mode="create"><?= $id ? 'Save Restoration Plan' : 'Create Restoration Plan' ?></button>
            </div>
        </div>
    </div>
</form>
<script>
(() => {
    const form = document.getElementById('restorationForm');
    const selected = new Set(JSON.parse(document.getElementById('selectedTeeth').value || '[]'));
    const order = <?= json_encode($teeth) ?>;
    const sorted = () => [...selected].sort((a, b) => Number(a) - Number(b));
    const quadName = t => ({ 1: 'UR', 2: 'UL', 3: 'LL', 4: 'LR' })[t[0]] + t[1];
    window.rxSelected = selected;
    function syncTeeth() {
        const list = sorted();
        document.getElementById('selectedTeeth').value = JSON.stringify(list);
        document.querySelectorAll('[data-tooth]').forEach(b => { const on = selected.has(b.dataset.tooth); b.classList.toggle('selected', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
        const count = document.getElementById('selectedCount'); if (count) count.textContent = list.length + ' selected';
        const tl = document.getElementById('teethList'); if (tl) tl.textContent = list.length ? list.join(', ') : '—';
        const enc = document.getElementById('encircleList'); if (enc) enc.textContent = list.length ? list.map(quadName).join(', ') : '—';
        window.rxArch?.sync();
        summary();
    }
    window.rxToggleTooth = t => { selected.has(t) ? selected.delete(t) : selected.add(t); syncTeeth(); };
    document.querySelectorAll('[data-tooth]').forEach(b => b.addEventListener('click', () => window.rxToggleTooth(b.dataset.tooth)));

    // Removable: the stage list follows the chosen type of work.
    const stageChips = document.getElementById('stageChips');
    let rememberedStages = stageChips ? JSON.parse(stageChips.dataset.selected || '[]') : [];
    function renderStages() {
        if (!stageChips) return;
        const work = form.querySelector('input[name="work_type"]:checked');
        document.getElementById('stageTitle').textContent = '2 · STAGE FOR ' + (work ? work.value.toUpperCase() : 'TYPE OF WORK');
        stageChips.replaceChildren();
        (work ? JSON.parse(work.dataset.stages) : []).forEach(stage => {
            const label = document.createElement('label'); label.className = 'rx-chip';
            const input = document.createElement('input'); input.type = 'checkbox'; input.name = 'stages[]'; input.value = stage; input.checked = rememberedStages.includes(stage);
            input.addEventListener('change', () => { rememberedStages = [...stageChips.querySelectorAll('input:checked')].map(i => i.value); summary(); });
            const span = document.createElement('span'); span.textContent = stage;
            label.append(input, span); stageChips.append(label);
        });
        if (!work) stageChips.innerHTML = '<span class="muted">Choose the type of work first.</span>';
        summary();
    }
    form.querySelectorAll('input[name="work_type"]').forEach(r => r.addEventListener('change', renderStages));
    form.querySelectorAll('input[name="teeth_types[]"]').forEach(r => r.addEventListener('change', summary));
    function summary() {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };
        const work = form.querySelector('input[name="work_type"]:checked');
        set('sumWork', work ? work.value : '');
        set('sumStage', [...form.querySelectorAll('input[name="stages[]"]:checked')].map(i => i.value).join(', '));
        set('sumTeethType', [...form.querySelectorAll('input[name="teeth_types[]"]:checked')].map(i => i.value).join(', '));
        set('sumTeeth', sorted().join(', '));
    }
    document.getElementById('clearDesigns')?.addEventListener('click', () => form.querySelectorAll('.rx-seg input, .rx-designs input').forEach(r => { r.checked = false; }));

    const ps = document.getElementById('patientSelect');
    function fillPatient() { const o = ps.options[ps.selectedIndex]; if (!o || !o.value) return; const ph = document.getElementById('patientPhone'), sx = document.getElementById('patientSex'), ag = document.getElementById('patientAge'); if (!ph.value) ph.value = o.dataset.phone || ''; if (!sx.value && o.dataset.sex) sx.value = o.dataset.sex; if (!ag.value) ag.value = o.dataset.age || ''; }
    ps.addEventListener('change', fillPatient);

    form.querySelectorAll('button[data-mode]').forEach(b => b.addEventListener('click', () => { document.getElementById('saveMode').value = b.dataset.mode; }));
    form.addEventListener('submit', e => {
        if (!ps.value) { e.preventDefault(); ps.focus(); alert('Please select a patient.'); return; }
        if (document.getElementById('saveMode').value !== 'draft' && !selected.size) { e.preventDefault(); alert('Select at least one tooth for this restoration plan.'); }
    });
    renderStages(); syncTeeth(); fillPatient();
})();
</script>
<script type="importmap">{"imports":{"three":"../assets/js/three.module.js"}}</script>
<script type="module">
import { createArchViewer, createStainingViewer, renderDesignThumbnails } from '../assets/js/restoration_3d.js';
const arch = document.getElementById('archModel');
if (arch) window.rxArch = createArchViewer(arch, { selected: window.rxSelected, onToggle: t => window.rxToggleTooth(t) });
const stain = document.getElementById('stainModel');
if (stain) createStainingViewer(stain);
const imgs = [...document.querySelectorAll('.design-thumb img')];
if (imgs.length) renderDesignThumbnails(imgs.map(img => ({ model: img.dataset.model, nodes: JSON.parse(img.dataset.nodes), img })));
</script>
<?php ui_footer(); ?>
