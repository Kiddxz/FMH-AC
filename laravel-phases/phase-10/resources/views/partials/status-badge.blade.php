{{-- Colored status label, e.g. @include('partials.status-badge', ['status' => 'pending'])
     Optional: 'label' => 'Released' shows other text with the same color --}}
<span class="status {{ $status }}" @if ($status === 'cancelled') style="background: #ffe5e5; color: #d9534f;" @elseif (in_array($status, ['deceased', 'archived'])) style="background: #eee; color: #666;" @endif>{{ $label ?? ucfirst($status) }}</span>
