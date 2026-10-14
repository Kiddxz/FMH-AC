<?php
/**
 * Create / Edit Chart, shared by the assistant and dentist portals.
 * Expects $editorRole ('assistant'|'dentist'), $conn and a verified session.
 */
require_once __DIR__ . '/exam_record.php';
$editorRole = $editorRole === 'dentist' ? 'dentist' : 'assistant';
$userId = (int)$_SESSION['user_id'];
$listUrl = $editorRole === 'dentist' ? 'dental_charts.php' : 'charts.php';
$chartId = (int)($_GET['id'] ?? $_POST['chart_id'] ?? 0);
$patients = $conn->query("SELECT u.id, u.first_name, u.last_name, p.birthday FROM users u LEFT JOIN patient_profiles p ON p.user_id = u.id WHERE u.role = 'patient' AND u.status = 'active' ORDER BY u.last_name, u.first_name");
$chart = ['patient_id' => (int)($_GET['patient_id'] ?? 0), 'chart_date' => date('Y-m-d'), 'examination_data' => '{}', 'notes' => ''];
$marked = [];
if ($chartId) {
    $stmt = $conn->prepare('SELECT * FROM dental_charts WHERE id = ? LIMIT 1'); $stmt->bind_param('i', $chartId); $stmt->execute(); $found = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$found) { header('Location: ' . $listUrl); exit; }
    $chart = $found;
    $stmt = $conn->prepare('SELECT tooth_number, condition_name FROM dental_chart_teeth WHERE chart_id = ?'); $stmt->bind_param('i', $chartId); $stmt->execute(); $teeth = $stmt->get_result(); while ($row = $teeth->fetch_assoc()) { $marked[$row['tooth_number']] = $row['condition_name']; } $stmt->close();
}
$data = json_decode($chart['examination_data'] ?: '{}', true); if (!is_array($data)) $data = [];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int)($_POST['patient_id'] ?? 0); $chartDate = $_POST['chart_date'] ?? ''; $notes = trim($_POST['notes'] ?? '');
    $teeth = json_decode($_POST['teeth_data'] ?? '{}', true); if (!is_array($teeth)) $teeth = [];
    $exam = exam_record_from_post($_POST);
    $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient' LIMIT 1"); $check->bind_param('i', $patientId); $check->execute(); $validPatient = $check->get_result()->fetch_assoc(); $check->close();
    if (!csrf_valid($_POST['csrf_token'] ?? null)) { $error = 'Your session expired. Refresh the page and try again.'; }
    elseif (!$validPatient || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $chartDate)) { $error = 'Choose a patient and chart date before saving.'; }
    else {
        $conn->begin_transaction();
        try {
            $json = json_encode($exam, JSON_UNESCAPED_UNICODE);
            if ($chartId && $editorRole === 'dentist') { $stmt = $conn->prepare("UPDATE dental_charts SET patient_id = ?, dentist_id = ?, created_by = COALESCE(created_by, ?), created_role = COALESCE(NULLIF(created_role,''),'dentist'), chart_date = ?, examination_data = ?, notes = ? WHERE id = ?"); $stmt->bind_param('iiisssi', $patientId, $userId, $userId, $chartDate, $json, $notes, $chartId); }
            elseif ($chartId) { $stmt = $conn->prepare('UPDATE dental_charts SET patient_id = ?, chart_date = ?, examination_data = ?, notes = ? WHERE id = ?'); $stmt->bind_param('isssi', $patientId, $chartDate, $json, $notes, $chartId); }
            elseif ($editorRole === 'dentist') { $stmt = $conn->prepare("INSERT INTO dental_charts (patient_id, dentist_id, created_by, created_role, chart_date, examination_data, notes) VALUES (?, ?, ?, 'dentist', ?, ?, ?)"); $stmt->bind_param('iiisss', $patientId, $userId, $userId, $chartDate, $json, $notes); }
            else { $stmt = $conn->prepare("INSERT INTO dental_charts (patient_id, dentist_id, created_by, created_role, chart_date, examination_data, notes) VALUES (?, NULL, ?, 'assistant', ?, ?, ?)"); $stmt->bind_param('iisss', $patientId, $userId, $chartDate, $json, $notes); }
            $stmt->execute(); if (!$chartId) $chartId = $conn->insert_id; $stmt->close();
            $stmt = $conn->prepare('DELETE FROM dental_chart_teeth WHERE chart_id = ?'); $stmt->bind_param('i', $chartId); $stmt->execute(); $stmt->close();
            if ($teeth) { $stmt = $conn->prepare('INSERT INTO dental_chart_teeth (chart_id, tooth_number, condition_name) VALUES (?, ?, ?)'); foreach ($teeth as $tooth => $condition) { if (preg_match('/^[1-8][1-8]$/', (string)$tooth) && in_array($condition, ['Healthy','Caries','Filled','Missing','Fractured'], true)) { $stmt->bind_param('iss', $chartId, $tooth, $condition); $stmt->execute(); } } $stmt->close(); }
            $conn->commit(); header('Location: ' . $listUrl . '?saved=1'); exit;
        } catch (Throwable $e) { $conn->rollback(); $error = 'The chart could not be saved. Please try again.'; }
    }
    // Keep what was typed when validation fails.
    $chart['patient_id'] = $patientId; $chart['chart_date'] = $chartDate; $chart['notes'] = $notes; $data = $exam;
    $marked = array_filter($teeth, static fn($c, $t) => preg_match('/^[1-8][1-8]$/', (string)$t) && is_string($c), ARRAY_FILTER_USE_BOTH);
}
$record = exam_record_normalize($data);
$isEdit = $chartId > 0;
$title = $isEdit ? 'Edit Chart' : 'Create Chart';
require __DIR__ . '/ui.php'; ui_header($title, $editorRole); ui_sidebar($editorRole, 'chart_editor.php');
$permanent = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28','48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
?>
<div class="page-head compact-head"><div><div class="eyebrow">DENTAL EXAMINATION RECORD</div><h1><?=e($title)?></h1><div class="muted">Click teeth to mark them, complete the examination record, then save the chart.</div></div><a class="btn btn-secondary" href="<?=e($listUrl)?>"><i class="fa-solid fa-arrow-left"></i> Back to <?=$editorRole === 'dentist' ? 'Dental Charts' : 'Chart Records'?></a></div>
<?php if ($error): ?><div class="flash error"><?=e($error)?></div><?php endif; ?>
<form method="post" id="chartForm" class="chart-form"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="chart_id" value="<?=$chartId?>"><input type="hidden" name="teeth_data" id="teethData" value="<?=e(json_encode((object)$marked))?>">
<section class="card chart-patient-card fitted-row"><div class="field"><label for="patient_id">Patient</label><select name="patient_id" id="patient_id" required><option value="">Select patient</option><?php while ($p = $patients->fetch_assoc()): ?><option value="<?=$p['id']?>" data-birthday="<?=e($p['birthday'] ?? '')?>" <?=((int)$chart['patient_id']===(int)$p['id'])?'selected':''?>><?=e($p['last_name'].', '.$p['first_name'])?></option><?php endwhile; ?></select></div><div class="field"><label for="chart_date">Date of Examination</label><input type="date" name="chart_date" id="chart_date" value="<?=e($chart['chart_date'])?>" required></div></section>
<section class="card dental-chart-card"><div class="card-heading"><div><h2>Dental Health Status</h2><p class="muted">Select a condition, then click one or more teeth to apply the colored mark.</p></div><span class="badge" id="markedCount">0 marked teeth</span></div>
<div class="condition-picker" role="group" aria-label="Tooth condition"><?php foreach (['Caries'=>'caries','Filled'=>'filled','Missing'=>'missing','Fractured'=>'fractured'] as $label=>$class): ?><button type="button" class="condition-option <?=$label==='Caries'?'selected':''?>" data-condition="<?=$label?>"><span class="condition-dot <?=$class?>"></span><?=$label?></button><?php endforeach; ?></div>
<div class="chart-split">
  <div class="chart-split-left"><div class="dental-arches compact-arches"><div class="arch-label">Upper Arch</div><div class="tooth-arch"><?php foreach (array_slice($permanent,0,16) as $tooth): ?><button type="button" class="chart-tooth" data-tooth="<?=$tooth?>"><span class="tooth-crown"></span><b><?=$tooth?></b></button><?php endforeach; ?></div><div class="tooth-arch lower"><?php foreach (array_slice($permanent,16) as $tooth): ?><button type="button" class="chart-tooth" data-tooth="<?=$tooth?>"><span class="tooth-crown"></span><b><?=$tooth?></b></button><?php endforeach; ?></div><div class="arch-label">Lower Arch</div></div><p class="muted chart-tip"><i class="fa-solid fa-computer-mouse"></i> Click a tooth again with the same condition to remove its mark.</p></div>
  <div class="model-box"><div class="model-box-head"><span><i class="fa-solid fa-cube"></i> 3D Dental Model</span><button type="button" class="model-reset" id="modelReset"><i class="fa-solid fa-arrows-rotate"></i> Reset view</button></div><div class="model-stage" id="chartModel" aria-label="Interactive 3D dental model"><span>Loading dental model...</span></div><small>Drag to rotate · click a tooth to apply the selected condition.</small></div>
</div></section>
<?php exam_record_form($record); ?>
<section class="card exam-notes-card"><div class="field full"><label for="notes">Clinical Notes</label><textarea name="notes" id="notes" placeholder="Add observations, diagnosis, or other notes."><?=e($chart['notes'])?></textarea></div></section>
<div class="chart-save-bar"><a class="btn btn-secondary" href="<?=e($listUrl)?>">Cancel</a><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Chart</button></div></form>
<script>
const marked = JSON.parse(document.getElementById('teethData').value || '{}'); let selectedCondition = 'Caries';
const updateTeeth = () => { document.querySelectorAll('.chart-tooth').forEach(button => { const condition = marked[button.dataset.tooth]; button.dataset.mark = condition || ''; button.classList.toggle('is-marked', !!condition); }); document.getElementById('teethData').value = JSON.stringify(marked); const n = Object.keys(marked).length; document.getElementById('markedCount').textContent = n + ' marked ' + (n === 1 ? 'tooth' : 'teeth'); window.chartViewer?.sync(); };
document.querySelectorAll('.condition-option').forEach(button => button.addEventListener('click', () => { selectedCondition = button.dataset.condition; document.querySelectorAll('.condition-option').forEach(item => item.classList.toggle('selected', item === button)); }));
window.toggleChartTooth = tooth => { if (marked[tooth] === selectedCondition) delete marked[tooth]; else marked[tooth] = selectedCondition; updateTeeth(); };
document.querySelectorAll('.chart-tooth').forEach(button => button.addEventListener('click', () => window.toggleChartTooth(button.dataset.tooth))); updateTeeth();
</script>
<script type="importmap">{"imports":{"three":"../assets/js/three.module.js"}}</script>
<script type="module">
import { createToothViewer } from '../assets/js/tooth_viewer.js';
const colors = { Healthy: 0x3ca878, Caries: 0xdf5656, Filled: 0x3b82c4, Missing: 0x64748b, Fractured: 0xd69325 };
window.chartViewer = createToothViewer(document.getElementById('chartModel'), { colorFor: id => colors[marked[id]] ?? null, onToothClick: id => window.toggleChartTooth(id) });
document.getElementById('modelReset').addEventListener('click', () => window.chartViewer.reset());
</script>
<?php ui_footer(); ?>
