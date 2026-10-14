<?php
/**
 * Dental Examination Record — the clinic's paper form, as structured data.
 * Stored inside dental_charts.examination_data under "record". The older flat
 * lists (oral_health, soft_tissue, periodontal, recommendations) are still written
 * so existing charts, search and reports keep working.
 */

const EXAM_ORAL_ROWS = ['debris' => 'Presence of Debris', 'gingiva' => 'Inflammation of Gingiva', 'calculus' => 'Presence of Calculus', 'ortho' => 'Under Orthodontic Treatment'];
const EXAM_TOOTH_COUNT_ROWS = ['present' => 'Number of Teeth Present', 'caries_free' => 'Number of Caries Free Teeth', 'decayed' => 'Number of Decayed Teeth', 'missing' => 'Number of Missing Teeth', 'filled' => 'Number of Filled Teeth', 'total' => 'Total df & DMF Teeth'];
const EXAM_SOFT_TISSUE = ['Lips', 'Floor of Mouth', 'Palate', 'Tongue', 'Neck & Nodes'];
const EXAM_PERIO_ROWS = [
    'gingival_inflammation' => ['Gingival Inflammation', ['Slight', 'Moderate', 'Severe']],
    'soft_plaque' => ['Soft Plaque Buildup', ['Slight', 'Moderate', 'Heavy']],
    'hard_calc' => ['Hard Calc Buildup', ['Light', 'Moderate', 'Heavy']],
    'stains' => ['Stains', ['Light', 'Moderate', 'Heavy']],
    'home_care' => ['Home Care Effectiveness', ['Good', 'Fair', 'Poor']],
    'perio_condition' => ['Periodontal Condition', ['Good', 'Fair', 'Poor']],
    'perio_diagnosis' => ['Periodontal Diagnosis', ['Normal', 'Gingivitis']],
    'periodontitis' => ['Periodontitis', ['Early', 'Moderate', 'Advanced']],
];
const EXAM_OCCLUSION = ['Class I', 'Class II', 'Class III'];
const EXAM_TMJ = ['Pain', 'Popping', 'Deviation', 'Tooth Wear'];
// value => printed label (values match the older checkbox values).
const EXAM_RECOMMEND_LEFT = ['Caries Free' => 'Caries Free', 'Poor Oral Hygiene' => 'Poor Oral Hygiene (Materia Alba, Calculus, Stain)', 'Indicated for Restoration/Filling' => 'Indicated for Restoration/Filling', 'Indicated for Extraction' => 'Indicated for Extraction', 'Gingival Inflammation' => 'Gingival Inflammation', 'Needs Oral Prophylaxis' => 'Needs Oral Prophylaxis'];
const EXAM_RECOMMEND_RIGHT = ['Needs Prosthesis (Denture)' => 'Needs Prosthesis (Denture)', 'For Endodontic Treatment' => 'For Endodontic Treatment', 'For Orthodontic Consultation' => 'For Orthodontic Consultation', 'For Pits and Fissures Sealant' => 'For Pits and Fissures Sealant', 'Others' => 'Others', 'No Dental Treatment Needed at Present' => 'No Dental Treatment Needed at Present'];

/** Full record with every key present; older charts are mapped onto the new structure. */
function exam_record_normalize(array $data): array
{
    $rec = is_array($data['record'] ?? null) ? $data['record'] : [];
    $legacyOral = array_flip(array_map('strval', (array)($data['oral_health'] ?? [])));
    $out = [
        'age_last_birthday' => (string)($rec['age_last_birthday'] ?? ''),
        'dentofacial' => (string)($rec['dentofacial'] ?? ''),
        'oral' => [], 'tooth_count' => [], 'perio' => [],
        'soft_tissue' => array_values(array_intersect(EXAM_SOFT_TISSUE, (array)($rec['soft_tissue'] ?? $data['soft_tissue'] ?? []))),
        'mucogingival' => (string)($rec['mucogingival'] ?? ''),
        'clinical_data' => (string)($rec['clinical_data'] ?? ''),
        'occlusion' => in_array($rec['occlusion'] ?? '', EXAM_OCCLUSION, true) ? $rec['occlusion'] : '',
        'tmj' => array_values(array_intersect(EXAM_TMJ, (array)($rec['tmj'] ?? []))),
        'recommendations' => array_values(array_map('strval', (array)($data['recommendations'] ?? []))),
        'others' => (string)($rec['others'] ?? ''),
    ];
    foreach (EXAM_ORAL_ROWS as $k => $label) {
        $pair = (array)($rec['oral'][$k] ?? []);
        $first = $pair[0] ?? (isset($legacyOral[$label]) ? 'Y' : '');
        $out['oral'][$k] = [in_array($first, ['Y', 'N'], true) ? $first : '', in_array($pair[1] ?? '', ['Y', 'N'], true) ? $pair[1] : ''];
    }
    foreach (EXAM_TOOTH_COUNT_ROWS as $k => $label) {
        $row = (array)($rec['tooth_count'][$k] ?? []);
        $out['tooth_count'][$k] = array_map(static fn($i) => preg_match('/^\d{1,2}$/', (string)($row[$i] ?? '')) ? (string)$row[$i] : '', [0, 1, 2, 3]);
    }
    foreach (EXAM_PERIO_ROWS as $k => [$label, $options]) {
        $v = $rec['perio'][$k] ?? '';
        $out['perio'][$k] = in_array($v, $options, true) ? $v : '';
    }
    return $out;
}

/** Build the examination_data array from a submitted chart form. */
function exam_record_from_post(array $post): array
{
    $in = is_array($post['exam'] ?? null) ? $post['exam'] : [];
    $clip = static fn($v, int $len) => mb_substr(trim((string)$v), 0, $len);
    $rec = ['record' => [
        'age_last_birthday' => preg_match('/^\d{1,3}$/', trim((string)($in['age_last_birthday'] ?? ''))) ? trim((string)$in['age_last_birthday']) : '',
        'dentofacial' => $clip($in['dentofacial'] ?? '', 255),
        'oral' => $in['oral'] ?? [], 'tooth_count' => $in['tooth_count'] ?? [], 'perio' => $in['perio'] ?? [],
        'soft_tissue' => (array)($in['soft_tissue'] ?? []),
        'mucogingival' => $clip($in['mucogingival'] ?? '', 255),
        'clinical_data' => $clip($in['clinical_data'] ?? '', 500),
        'occlusion' => $in['occlusion'] ?? '',
        'tmj' => (array)($in['tmj'] ?? []),
        'others' => $clip($in['others'] ?? '', 255),
    ], 'recommendations' => array_values(array_intersect(array_merge(array_keys(EXAM_RECOMMEND_LEFT), array_keys(EXAM_RECOMMEND_RIGHT)), (array)($post['recommendations'] ?? [])))];
    $norm = exam_record_normalize($rec);
    // Flat lists kept for older screens: oral items answered "Y" in the first exam, perio rows as "Label: Value".
    $oralLegacy = [];
    foreach (EXAM_ORAL_ROWS as $k => $label) if ($norm['oral'][$k][0] === 'Y') $oralLegacy[] = $label;
    $perioLegacy = [];
    foreach (EXAM_PERIO_ROWS as $k => [$label, $opts]) if ($norm['perio'][$k] !== '') $perioLegacy[] = $label . ': ' . $norm['perio'][$k];
    $record = $norm;
    unset($record['recommendations']);
    return ['oral_health' => $oralLegacy, 'soft_tissue' => $norm['soft_tissue'], 'periodontal' => $perioLegacy, 'recommendations' => $norm['recommendations'], 'record' => $record];
}

function exam_checked(bool $on): string { return $on ? 'checked' : ''; }

/** Editable paper-style form (lower half of Create / Edit Chart). */
function exam_record_form(array $rec): void
{
    ?>
<section class="card exam-paper" aria-label="Dental examination record">
  <div class="exam-paper-grid">
    <div class="exam-col">
      <h3 class="exam-title">Oral Health Condition</h3>
      <table class="exam-table oral-table">
        <thead><tr><th></th><th colspan="2">Exam 1</th><th colspan="2">Exam 2</th></tr></thead>
        <tbody>
          <tr><th>Date of Examination</th><td colspan="4"><span class="exam-date-mirror" data-mirror-date>—</span></td></tr>
          <tr><th>Age last birthday</th><td colspan="4"><input type="text" inputmode="numeric" maxlength="3" class="exam-input xs" name="exam[age_last_birthday]" id="examAge" value="<?=e($rec['age_last_birthday'])?>" placeholder="Age"></td></tr>
          <?php foreach (EXAM_ORAL_ROWS as $k => $label): ?>
          <tr><th><?=e($label)?></th>
            <?php foreach ([0, 1] as $i): foreach (['Y', 'N'] as $yn): ?>
              <td class="yn"><label class="yn-box"><input type="radio" name="exam[oral][<?=$k?>][<?=$i?>]" value="<?=$yn?>" <?=exam_checked($rec['oral'][$k][$i] === $yn)?>><span><?=$yn?></span></label></td>
            <?php endforeach; endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <label class="exam-line-label" for="examDentofacial">Dentofacial Anomaly, Neoplasm, Others, specify:</label>
      <input type="text" class="exam-input line" id="examDentofacial" name="exam[dentofacial]" maxlength="255" value="<?=e($rec['dentofacial'])?>">
      <table class="exam-table count-table">
        <thead><tr><th>Tooth Count</th><th>T</th><th>P</th><th>T</th><th>P</th></tr></thead>
        <tbody>
          <?php foreach (EXAM_TOOTH_COUNT_ROWS as $k => $label): ?>
          <tr><th><?=e($label)?></th><?php foreach ([0, 1, 2, 3] as $i): ?><td><input type="text" inputmode="numeric" maxlength="2" class="exam-input count" name="exam[tooth_count][<?=$k?>][<?=$i?>]" value="<?=e($rec['tooth_count'][$k][$i])?>" aria-label="<?=e($label.' column '.($i+1))?>"></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="exam-col">
      <h3 class="exam-title center">Initial Soft Tissue Exam</h3>
      <div class="exam-check-row"><?php foreach (EXAM_SOFT_TISSUE as $item): ?><label class="exam-check"><input type="checkbox" name="exam[soft_tissue][]" value="<?=e($item)?>" <?=exam_checked(in_array($item, $rec['soft_tissue'], true))?>><span><?=e($item)?></span></label><?php endforeach; ?></div>
      <h3 class="exam-title center">Initial Periodontal Exam</h3>
      <table class="exam-table perio-table"><tbody>
        <?php foreach (EXAM_PERIO_ROWS as $k => [$label, $options]): ?>
        <tr><th><?=e($label)?>:</th><?php foreach ($options as $opt): ?><td><label class="exam-check"><input type="radio" name="exam[perio][<?=$k?>]" value="<?=e($opt)?>" <?=exam_checked($rec['perio'][$k] === $opt)?>><span><?=e($opt)?></span></label></td><?php endforeach; ?><?php if (count($options) < 3): ?><td></td><?php endif; ?></tr>
        <?php endforeach; ?>
        <tr><th>Mucogingival Defects:</th><td colspan="3"><input type="text" class="exam-input line" name="exam[mucogingival]" maxlength="255" value="<?=e($rec['mucogingival'])?>"></td></tr>
        <tr><th>Clinical Data:</th><td colspan="3"><input type="text" class="exam-input line" name="exam[clinical_data]" maxlength="500" value="<?=e($rec['clinical_data'])?>"></td></tr>
      </tbody></table>
      <table class="exam-table perio-table occlusion-table"><tbody>
        <tr><th>Occlusion:</th><?php foreach (EXAM_OCCLUSION as $opt): ?><td><label class="exam-check"><input type="radio" name="exam[occlusion]" value="<?=e($opt)?>" <?=exam_checked($rec['occlusion'] === $opt)?>><span><?=e($opt)?></span></label></td><?php endforeach; ?><td><button type="button" class="exam-clear" data-clear-radio="exam[occlusion]">Clear</button></td></tr>
        <tr><th>T.M.J. Exam:</th><?php foreach (EXAM_TMJ as $opt): ?><td><label class="exam-check"><input type="checkbox" name="exam[tmj][]" value="<?=e($opt)?>" <?=exam_checked(in_array($opt, $rec['tmj'], true))?>><span><?=e($opt)?></span></label></td><?php endforeach; ?></tr>
      </tbody></table>
    </div>
  </div>
  <h3 class="exam-title wide">Dental/Oral Examination Revealed the Following Conditions and Recommendations.</h3>
  <div class="exam-recommend">
    <?php foreach ([EXAM_RECOMMEND_LEFT, EXAM_RECOMMEND_RIGHT] as $column): ?>
    <div><?php foreach ($column as $value => $label): ?><label class="exam-blank"><input type="checkbox" name="recommendations[]" value="<?=e($value)?>" <?=exam_checked(in_array($value, $rec['recommendations'], true))?>><span class="blank-line" aria-hidden="true"></span><span><?=e($label)?></span><?php if ($value === 'Others'): ?><input type="text" class="exam-input line others" name="exam[others]" maxlength="255" value="<?=e($rec['others'])?>" placeholder="Specify" aria-label="Other recommendation"><?php endif; ?></label><?php endforeach; ?></div>
    <?php endforeach; ?>
  </div>
</section>
<script>
(() => {
  // Mirror the examination date into the paper table, and allow a radio row to be cleared.
  const dateInput = document.getElementById('chart_date'), mirror = document.querySelector('[data-mirror-date]');
  const show = () => { if (!mirror) return; const v = dateInput?.value; mirror.textContent = v ? new Date(v + 'T12:00:00').toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' }) : '—'; };
  dateInput?.addEventListener('change', show); show();
  document.querySelectorAll('[data-clear-radio]').forEach(b => b.addEventListener('click', () => document.querySelectorAll('input[name="' + b.dataset.clearRadio + '"]').forEach(r => r.checked = false)));
  // Clicking a selected Y/N or perio radio again clears it, like erasing a pencil mark.
  document.querySelectorAll('.exam-paper input[type=radio]').forEach(r => { r.addEventListener('mousedown', () => { r.dataset.was = r.checked ? '1' : ''; }); r.addEventListener('click', () => { if (r.dataset.was === '1') { r.checked = false; r.dataset.was = ''; } }); });
  document.querySelectorAll('.exam-input.count, #examAge').forEach(i => i.addEventListener('input', () => { i.value = i.value.replace(/\D/g, ''); }));
  // Fill "Age last birthday" from the selected patient's birthday when it is empty.
  const patient = document.getElementById('patient_id'), age = document.getElementById('examAge');
  function fillAge() { const o = patient?.selectedOptions[0]; if (!o || !o.dataset.birthday || !age || age.value) return; const [y, m, d] = o.dataset.birthday.split('-').map(Number), t = new Date(); let a = t.getFullYear() - y; if (t.getMonth() + 1 < m || (t.getMonth() + 1 === m && t.getDate() < d)) a--; if (a >= 0) age.value = a; }
  patient?.addEventListener('change', () => { if (age) age.value = ''; fillAge(); }); fillAge();
})();
</script>
    <?php
}

/** Read-only, printable paper record. $teeth maps tooth number => condition. */
function exam_record_print(array $chart, array $rec, array $teeth): void
{
    $box = static fn(bool $on) => '<span class="pbox' . ($on ? ' on' : '') . '">' . ($on ? '&#10003;' : '') . '</span>';
    $upper = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'];
    $lower = ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
    $abbr = ['Healthy' => '', 'Caries' => 'C', 'Filled' => 'F', 'Missing' => 'M', 'Fractured' => 'Fx', 'Marked' => '•'];
    $toothCell = static function (string $n) use ($teeth, $abbr) {
        $c = $teeth[$n] ?? '';
        return '<span class="ptooth ' . e(strtolower($c)) . '" title="' . e($n . ($c ? ': ' . $c : '')) . '"><i>' . e($abbr[$c] ?? '') . '</i></span>';
    };
    ?>
<article class="paper-record" id="paperRecord">
  <header class="paper-head"><img src="../assets/images/logo.png" alt=""><div><strong>SUPREMEDENT</strong><small>DENTAL CLINIC</small></div></header>
  <h2 class="paper-title">Dental Examination Record</h2>
  <div class="paper-fields">
    <div class="pf"><span>Date</span><b><?=e(date('F d, Y', strtotime($chart['chart_date'])))?></b></div>
    <div class="pf wide"><span>Name of Patient</span><b><?=e($chart['last_name'])?></b><b><?=e($chart['first_name'])?></b><b>&nbsp;</b></div>
    <div class="pf-sub"><span></span><small>Last Name</small><small>First Name</small><small>Middle Name</small></div>
  </div>
  <h3 class="paper-h">Dental Health Status:</h3>
  <div class="paper-teeth">
    <div class="ptooth-row"><?php foreach ($upper as $n) echo $toothCell($n); ?></div>
    <div class="ptooth-row numbers"><?php foreach ($upper as $n): ?><b><?=$n?></b><?php endforeach; ?></div>
    <div class="ptooth-row numbers"><?php foreach ($lower as $n): ?><b><?=$n?></b><?php endforeach; ?></div>
    <div class="ptooth-row"><?php foreach ($lower as $n) echo $toothCell($n); ?></div>
    <p class="paper-legend">C = Caries · F = Filled · M = Missing · Fx = Fractured</p>
  </div>
  <div class="paper-grid">
    <div>
      <h3 class="paper-h">Oral Health Condition:</h3>
      <table class="ptable"><tbody>
        <tr><td>Date of Examination</td><td colspan="4"><?=e(date('M d, Y', strtotime($chart['chart_date'])))?></td></tr>
        <tr><td>Age last birthday</td><td colspan="4"><?=e($rec['age_last_birthday'] ?: '')?></td></tr>
        <?php foreach (EXAM_ORAL_ROWS as $k => $label): ?><tr><td><?=e($label)?></td><?php foreach ([0, 1] as $i): foreach (['Y', 'N'] as $yn): ?><td class="pyn<?=$rec['oral'][$k][$i] === $yn ? ' on' : ''?>"><?=$yn?></td><?php endforeach; endforeach; ?></tr><?php endforeach; ?>
      </tbody></table>
      <p class="pline"><span>Dentofacial Anomaly, Neoplasm, Others, specify:</span><b><?=e($rec['dentofacial'])?></b></p>
      <table class="ptable"><thead><tr><th>Tooth Count</th><th>T</th><th>P</th><th>T</th><th>P</th></tr></thead><tbody>
        <?php foreach (EXAM_TOOTH_COUNT_ROWS as $k => $label): ?><tr><td><?=e($label)?></td><?php foreach ($rec['tooth_count'][$k] as $v): ?><td class="c"><?=e($v)?></td><?php endforeach; ?></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <div>
      <h3 class="paper-h center">Initial Soft Tissue Exam</h3>
      <table class="ptable"><tbody><tr><?php foreach (EXAM_SOFT_TISSUE as $item): ?><td class="c"><?=$box(in_array($item, $rec['soft_tissue'], true))?> <?=e($item)?></td><?php endforeach; ?></tr></tbody></table>
      <h3 class="paper-h center">Initial Periodontal Exam</h3>
      <table class="ptable"><tbody>
        <?php foreach (EXAM_PERIO_ROWS as $k => [$label, $options]): ?><tr><td><b><?=e(strtoupper($label))?>:</b></td><?php foreach ($options as $opt): ?><td><?=$box($rec['perio'][$k] === $opt)?> <?=e($opt)?></td><?php endforeach; ?><?php if (count($options) < 3): ?><td></td><?php endif; ?></tr><?php endforeach; ?>
        <tr><td><b>MUCOGINGIVAL DEFECTS:</b></td><td colspan="3"><?=e($rec['mucogingival'])?></td></tr>
        <tr><td><b>CLINICAL DATA:</b></td><td colspan="3"><?=e($rec['clinical_data'])?></td></tr>
      </tbody></table>
      <table class="ptable"><tbody>
        <tr><td><b>OCCLUSION:</b></td><?php foreach (EXAM_OCCLUSION as $opt): ?><td><?=$box($rec['occlusion'] === $opt)?> <?=e($opt)?></td><?php endforeach; ?><td></td></tr>
        <tr><td><b>T.M.J. EXAM:</b></td><?php foreach (EXAM_TMJ as $opt): ?><td><?=$box(in_array($opt, $rec['tmj'], true))?> <?=e($opt)?></td><?php endforeach; ?></tr>
      </tbody></table>
    </div>
  </div>
  <h3 class="paper-h wide">Dental/Oral Examination Revealed the Following Conditions and Recommendations.</h3>
  <div class="paper-recommend">
    <?php foreach ([EXAM_RECOMMEND_LEFT, EXAM_RECOMMEND_RIGHT] as $column): ?><div><?php foreach ($column as $value => $label): $on = in_array($value, $rec['recommendations'], true); ?><p><span class="pblank"><?=$on ? '&#10003;' : ''?></span><?=e($label)?><?php if ($value === 'Others' && $rec['others'] !== ''): ?>: <b><?=e($rec['others'])?></b><?php endif; ?></p><?php endforeach; ?></div><?php endforeach; ?>
  </div>
  <?php if (trim((string)($chart['notes'] ?? '')) !== ''): ?><h3 class="paper-h">Clinical Notes</h3><p class="paper-notes"><?=nl2br(e($chart['notes']))?></p><?php endif; ?>
</article>
    <?php
}
