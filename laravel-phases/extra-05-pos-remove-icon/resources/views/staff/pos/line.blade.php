{{-- One line of the POS bill. Needs: $index, $line (item = "service:3" or "product:5", quantity), $services, $products --}}
<tr>
  <td>
    <select name="items[{{ $index }}][item]" aria-label="Service or product" required style="width: 100%; padding: 9px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;">
      <option value="">Choose...</option>
      <optgroup label="Services">
        @foreach ($services as $service)
          <option value="service:{{ $service->id }}" data-price="{{ $service->price }}" @selected(($line['item'] ?? '') === 'service:' . $service->id)>{{ $service->name }} · ₱{{ number_format($service->price, 2) }}</option>
        @endforeach
      </optgroup>
      <optgroup label="Products">
        @foreach ($products as $product)
          <option value="product:{{ $product->id }}" data-price="{{ $product->selling_price }}" @disabled((int) $product->stock < 1) @selected(($line['item'] ?? '') === 'product:' . $product->id)>{{ $product->name }} · ₱{{ number_format($product->selling_price, 2) }} ({{ (int) $product->stock }} {{ $product->unit }} left)</option>
        @endforeach
      </optgroup>
    </select>
  </td>
  <td><input type="number" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" min="1" max="999" required aria-label="Quantity" style="width: 90px; padding: 9px 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;"></td>
  <td class="line-price">—</td>
  <td class="line-amount">₱0.00</td>
  <td><button class="action-edit remove-line" type="button" title="Remove line" aria-label="Remove line"><svg class="ui-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width: 16px; height: 16px; vertical-align: middle; pointer-events: none;"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button></td>
</tr>
