{{-- A list of repeating rows (treatments, prescriptions or vaccinations) with "+ Add" and "Remove".
     Use: @include('admin.records.rows', ['list' => 'treatments', 'title' => 'Treatments', 'add' => '+ Add Treatment',
                                          'fields' => ['procedure_name' => ['Procedure', 'text', 'e.g. Wound cleaning'], ...],
                                          'rows' => [...existing rows...]]) --}}
@php
  // Always show the saved rows plus one empty row to type in
  $rows = array_values($rows);
  $rows[] = [];
  $cell = 'display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; align-items: end;';
@endphp
<div class="admin-table-card">
  <div class="admin-panel-header">
    <h2>{{ $title }}</h2>
  </div>
  @error($list)
    <p style="color: #c0392b; margin: 0 0 10px;">{{ $message }}</p>
  @enderror
  <div data-rows="{{ $list }}" data-next="{{ count($rows) }}">
    @foreach ($rows as $i => $row)
      <div class="record-row" style="{{ $cell }} border-bottom: 1px solid #eee; padding: 10px 0;">
        @foreach ($fields as $name => [$label, $type, $placeholder])
          <div class="form-group" style="margin-bottom: 0;">
            <label>{{ $label }}</label>
            <input type="{{ $type }}" name="{{ $list }}[{{ $i }}][{{ $name }}]" value="{{ $row[$name] ?? '' }}" placeholder="{{ $placeholder }}">
          </div>
        @endforeach
        <div><button class="action-edit" type="button" data-remove-row>Remove</button></div>
      </div>
      @foreach ($fields as $name => $field)
        @error($list . '.' . $i . '.' . $name)
          <p style="color: #c0392b; margin: 5px 0;">{{ $message }}</p>
        @enderror
      @endforeach
    @endforeach
  </div>
  <template data-template="{{ $list }}">
    <div class="record-row" style="{{ $cell }} border-bottom: 1px solid #eee; padding: 10px 0;">
      @foreach ($fields as $name => [$label, $type, $placeholder])
        <div class="form-group" style="margin-bottom: 0;">
          <label>{{ $label }}</label>
          <input type="{{ $type }}" name="{{ $list }}[__INDEX__][{{ $name }}]" placeholder="{{ $placeholder }}">
        </div>
      @endforeach
      <div><button class="action-edit" type="button" data-remove-row>Remove</button></div>
    </div>
  </template>
  <div class="admin-tools" style="margin-top: 12px;">
    <button class="action-view" type="button" data-add-row="{{ $list }}">{{ $add }}</button>
  </div>
</div>
