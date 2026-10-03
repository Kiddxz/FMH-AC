{{-- Add / edit an inventory item (Staff only) --}}
@extends('layouts.staff')
@section('title', 'FMH Animal Clinic | ' . ($item->exists ? 'Edit Item' : 'Add Item'))
@section('body_class', 'admin-layout')
@section('footer', '© 2026 FMH Animal Clinic | Staff Panel')
@section('content')
<main class="admin-container">
  <div class="admin-page-heading">
    <div>
      <h1>{{ $item->exists ? 'Edit Item' : 'Add Inventory Item' }}</h1>
      <p>Stock is not typed here: use "Stock In" on the item page so every delivery has a batch and expiry date.</p>
    </div>
    <div class="admin-date">
      📅 {{ now()->format('F j, Y') }}
    </div>
  </div>
  <form class="admin-table-card" method="post" action="{{ $item->exists ? route('staff.inventory.update', $item) : route('staff.inventory.store') }}">
    @csrf
    @if ($item->exists)
      @method('PUT')
    @endif
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0 15px;">
      <div class="form-group">
        <label for="sku">Item Code</label>
        <input type="text" id="sku" name="sku" value="{{ old('sku', $item->sku) }}" placeholder="e.g. MED-AMOX" required>
        @error('sku') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="name">Item Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $item->name) }}" placeholder="e.g. Amoxicillin 250mg" required>
        @error('name') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category" required>
          <option value="">Select category</option>
          @foreach (\App\Models\InventoryItem::CATEGORIES as $option)
            <option value="{{ $option }}" @selected(old('category', $item->category) === $option)>{{ ucfirst($option) }}</option>
          @endforeach
        </select>
        @error('category') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="unit">Unit</label>
        <input type="text" id="unit" name="unit" value="{{ old('unit', $item->unit) }}" placeholder="e.g. tablets, doses, bottles" required>
        @error('unit') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="reorder_level">Minimum Stock (low-stock alert)</label>
        <input type="number" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level) }}" min="0" required>
        @error('reorder_level') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="selling_price">Selling Price (₱)</label>
        <input type="number" id="selling_price" name="selling_price" value="{{ old('selling_price', $item->selling_price) }}" min="0" step="0.01" placeholder="Leave empty if not for sale">
        @error('selling_price') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
      <div class="form-group">
        <label for="supplier_id">Main Supplier</label>
        <select id="supplier_id" name="supplier_id">
          <option value="">—</option>
          @foreach ($suppliers as $supplier)
            <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $item->supplier_id) === $supplier->id)>{{ $supplier->name }}</option>
          @endforeach
        </select>
        @error('supplier_id') <p style="color: #c0392b; margin-top: 5px;">{{ $message }}</p> @enderror
      </div>
    </div>
    <div class="form-group">
      <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" style="width: auto;" @checked(old('is_active', $item->is_active))>
        Active (shown in the inventory list and alerts)
      </label>
    </div>
    <div class="admin-tools">
      <button class="admin-add-btn" type="submit">{{ $item->exists ? 'Save Changes' : 'Add Item' }}</button>
      <button class="action-view" type="button" onclick="window.location.href='{{ $item->exists ? route('staff.inventory.show', $item) : route('staff.inventory.index') }}'">Cancel</button>
    </div>
  </form>
</main>
@endsection
