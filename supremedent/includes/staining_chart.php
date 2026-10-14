<?php
/**
 * "Chart for Staining" block of the fixed restoration prescription, drawn like the lab form:
 * crown sketches, shade, occlusal staining and translucency, metal design and pontic design pickers.
 * Field names and stored values match the previous checkbox/select version.
 */
function staining_chart(array $stainSelected, string $shade, string $metal, string $pontic): void
{
    $crown = 'M7 12 Q7 5 13 5 Q17 3 20 7 Q23 3 27 5 Q33 5 33 12 L31 36 Q20 42 9 36 Z';
    $incisor = 'M11 6 Q20 2 29 6 L31 34 Q20 42 9 34 Z';
    // [outline, dark-region rect (x,y,w,h)] for Design 1..9
    $designs = [
        [$crown, [0, 30, 40, 14]], [$crown, [0, 22, 40, 22]], [$crown, [0, 0, 40, 14]], [$crown, [20, 0, 20, 44]],
        [$crown, [0, 0, 18, 44]], [$crown, [0, 0, 40, 44]], [$incisor, null], [$incisor, [0, 28, 40, 16]], [$incisor, [0, 18, 40, 26]],
    ];
    $pontics = [
        'Hygienic' => ['Hygienic', 'M12 4 Q20 0 28 4 L29 26 Q20 32 11 26 Z'],
        'Modified Ridge Lap' => ['Modified RL', 'M12 4 Q20 0 28 4 L29 36 Q24 40 20 36 L11 26 Z'],
        'Bullet' => ['Bullet', 'M12 4 Q20 0 28 4 L28 26 Q24 36 20 38 Q16 36 12 26 Z'],
        'Ridge Lap' => ['Ridge Lap', 'M12 4 Q20 0 28 4 L30 36 Q25 34 20 38 Q15 34 10 36 Z'],
    ];
    $stain = static fn(string $v) => in_array($v, $stainSelected, true) ? 'checked' : '';
    ?>
<div class="stain-chart">
  <div class="stain-bar">Chart for Staining</div>
  <div class="stain-crowns" aria-hidden="true">
    <svg viewBox="0 0 60 70"><path d="M14 8 Q30 2 46 8 L50 60 Q30 66 10 60 Z" fill="none" stroke="#222" stroke-width="2"/><path d="M18 14 L18 56 M42 14 L42 56 M12 50 L48 50" stroke="#222" stroke-width="1.4" fill="none"/></svg>
    <svg viewBox="0 0 80 70"><path d="M8 20 Q8 6 22 8 Q30 2 40 12 Q50 2 58 8 Q72 6 72 20 L68 60 Q40 68 12 60 Z" fill="none" stroke="#222" stroke-width="2"/><path d="M20 22 L40 30 L60 22 M40 30 L40 34 M12 50 L68 50" stroke="#222" stroke-width="1.4" fill="none"/></svg>
  </div>
  <label class="stain-shade">Shade: <input name="shade" value="<?=e($shade)?>" maxlength="100"></label>
  <div class="stain-options">
    <div><b>Occlusal Staining</b><div class="stain-grid"><?php foreach (['None', 'Medium', 'Light', 'Dark'] as $o): ?><label><input type="checkbox" name="staining_options[]" value="Staining: <?=$o?>" <?=$stain('Staining: ' . $o)?>> <?=$o?></label><?php endforeach; ?></div></div>
    <div><b>Translucency</b><div class="stain-grid"><?php foreach (['Low', 'High', 'Medium'] as $o): ?><label><input type="checkbox" name="staining_options[]" value="Translucency: <?=$o?>" <?=$stain('Translucency: ' . $o)?>> <?=$o?></label><?php endforeach; ?></div></div>
  </div>
  <div class="stain-bar">Metal Design</div>
  <div class="design-grid metal" role="radiogroup" aria-label="Metal design">
    <?php foreach ($designs as $i => [$path, $dark]): $value = 'Design ' . ($i + 1); $clip = 'md' . ($i + 1); ?>
    <label class="design-option" title="<?=e($value)?>"><svg viewBox="0 0 40 44" aria-hidden="true"><defs><clipPath id="<?=$clip?>"><path d="<?=$path?>"/></clipPath></defs><?php if ($dark): ?><rect x="<?=$dark[0]?>" y="<?=$dark[1]?>" width="<?=$dark[2]?>" height="<?=$dark[3]?>" clip-path="url(#<?=$clip?>)" fill="#111"/><?php endif; ?><path d="<?=$path?>" fill="none" stroke="#222" stroke-width="2"/></svg><input type="radio" name="metal_design" value="<?=$value?>" <?=$metal === $value ? 'checked' : ''?>><span class="design-box" aria-hidden="true"></span><span class="sr-only"><?=e($value)?></span></label>
    <?php endforeach; ?>
  </div>
  <div class="stain-bar">Pontic Design</div>
  <div class="design-grid pontic" role="radiogroup" aria-label="Pontic design">
    <?php foreach ($pontics as $value => [$label, $path]): ?>
    <label class="design-option"><svg viewBox="0 0 40 50" aria-hidden="true"><path d="M2 48 L20 34 L38 48 Z" fill="#111"/><path d="<?=$path?>" fill="#fff" stroke="#222" stroke-width="2"/></svg><span class="design-label"><?=e($label)?></span><input type="radio" name="pontic_design" value="<?=e($value)?>" <?=$pontic === $value ? 'checked' : ''?>><span class="design-box" aria-hidden="true"></span></label>
    <?php endforeach; ?>
  </div>
  <button type="button" class="exam-clear" onclick="document.querySelectorAll('.stain-chart input[type=radio]').forEach(r=>r.checked=false)">Clear designs</button>
</div>
    <?php
}
