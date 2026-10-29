{{-- Signature box: the owner signs with the finger (phone, tablet) or the mouse.
     The drawing is sent as a PNG picture in the hidden "signature" field.
     Use inside a <form>: @include('partials.signature-pad') --}}
@once
<style>
  .sig-box { position: relative; border: 1px dashed #cfc6b8; border-radius: 10px; background: #fffdf9; touch-action: none; }
  .sig-box canvas { display: block; width: 100%; height: 170px; cursor: crosshair; }
  .sig-box .sig-hint { position: absolute; left: 0; right: 0; bottom: 34px; margin: 0 24px; padding-top: 6px; border-top: 1px solid #d9d2c6; color: #a39b8e; font-size: 12.5px; text-align: center; pointer-events: none; }
  .sig-box.signed .sig-hint { visibility: hidden; }
  .sig-tools { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 6px; }
  .sig-tools small { color: #8a8f98; font-size: 12.5px; }
  .sig-tools button { padding: 6px 14px; border: 1px solid #ddd5ca; border-radius: 8px; background: #fff; color: #26364a; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; }
  .sig-tools button:hover { border-color: #e89427; color: #c46f0c; }
  .sig-error { margin-top: 6px; color: #c0392b; font-size: 13.5px; }
</style>
@endonce
<div class="form-group sig-field">
  <label>Signature</label>
  <div class="sig-box">
    <canvas aria-label="Signature box: sign here with your finger or the mouse"></canvas>
    <div class="sig-hint">Sign here</div>
  </div>
  <div class="sig-tools">
    <small>Use your finger (phone or tablet) or the mouse.</small>
    <button type="button" class="sig-clear">Clear</button>
  </div>
  <input type="hidden" name="signature" value="">
  <p class="sig-error" @error('signature') @else hidden @enderror>@error('signature'){{ $message }}@else Please sign in the signature box. @enderror</p>
</div>
<script>
  (function () {
    var field = document.currentScript.previousElementSibling;
    var box = field.querySelector('.sig-box');
    var canvas = field.querySelector('canvas');
    var input = field.querySelector('input[name=signature]');
    var error = field.querySelector('.sig-error');
    var ctx = canvas.getContext('2d');
    var drawing = false, drawn = false, last = null, area = null;

    // the canvas has the real pixel size of the screen, so the line is sharp
    function size() {
      var ratio = Math.min(window.devicePixelRatio || 1, 2);
      var rect = canvas.getBoundingClientRect();
      canvas.width = Math.round(rect.width * ratio);
      canvas.height = Math.round(rect.height * ratio);
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineWidth = 2.4;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.strokeStyle = '#1f2a44';
    }
    function point(event) {
      var rect = canvas.getBoundingClientRect();
      var p = { x: event.clientX - rect.left, y: event.clientY - rect.top };
      // remember the part of the box that was drawn on
      area = area ? { x1: Math.min(area.x1, p.x), y1: Math.min(area.y1, p.y), x2: Math.max(area.x2, p.x), y2: Math.max(area.y2, p.y) } : { x1: p.x, y1: p.y, x2: p.x, y2: p.y };
      return p;
    }
    // only the drawn part is sent (with a small margin), so the signature fills the line of the form
    function picture() {
      var ratio = canvas.width / canvas.getBoundingClientRect().width;
      var pad = 8;
      var x = Math.max(0, Math.floor((area.x1 - pad) * ratio)), y = Math.max(0, Math.floor((area.y1 - pad) * ratio));
      var w = Math.min(canvas.width - x, Math.ceil((area.x2 - area.x1 + pad * 2) * ratio));
      var h = Math.min(canvas.height - y, Math.ceil((area.y2 - area.y1 + pad * 2) * ratio));
      var copy = document.createElement('canvas');
      copy.width = Math.max(1, w);
      copy.height = Math.max(1, h);
      copy.getContext('2d').drawImage(canvas, x, y, w, h, 0, 0, w, h);
      return copy.toDataURL('image/png');
    }
    function clear() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      drawn = false;
      area = null;
      input.value = '';
      box.classList.remove('signed');
    }

    canvas.addEventListener('pointerdown', function (event) {
      event.preventDefault();
      canvas.setPointerCapture(event.pointerId);
      drawing = true;
      last = point(event);
      ctx.beginPath();
      ctx.arc(last.x, last.y, 1.1, 0, Math.PI * 2);
      ctx.fillStyle = ctx.strokeStyle;
      ctx.fill();
    });
    canvas.addEventListener('pointermove', function (event) {
      if (!drawing) return;
      var p = point(event);
      ctx.beginPath();
      ctx.moveTo(last.x, last.y);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      last = p;
      drawn = true;
      box.classList.add('signed');
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (name) {
      canvas.addEventListener(name, function () {
        if (!drawing) return;
        drawing = false;
        if (drawn) {
          input.value = picture();
          error.hidden = true;
        }
      });
    });
    field.querySelector('.sig-clear').addEventListener('click', clear);
    // the box changes size when the phone is turned: start again (a signature cannot be stretched)
    var width = canvas.getBoundingClientRect().width;
    window.addEventListener('resize', function () {
      var now = canvas.getBoundingClientRect().width;
      if (Math.abs(now - width) > 1) { width = now; size(); clear(); }
    });
    size();

    // no signature: stop the form and show the message
    field.closest('form').addEventListener('submit', function (event) {
      if (!input.value) {
        event.preventDefault();
        error.textContent = 'Please sign in the signature box.';
        error.hidden = false;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  })();
</script>
