{{-- Colored status label, e.g. @include('partials.status-badge', ['status' => 'pending']) --}}
<span class="status {{ $status }}" @if ($status === 'cancelled') style="background: #ffe5e5; color: #d9534f;" @elseif (in_array($status, ['deceased', 'archived'])) style="background: #eee; color: #666;" @endif>{{ ucfirst($status) }}</span>
