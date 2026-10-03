{{-- "Previous / Next" links for the Super Admin lists. Use: @include('partials.superadmin-pager', ['items' => $logs]) --}}
@if ($items->hasPages())
  <div class="superadmin-tools" style="justify-content: space-between; align-items: center; margin-top: 15px;">
    <span style="color: #64748b; font-size: 14px;">Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }}</span>
    <div style="display: flex; gap: 8px;">
      @if (! $items->onFirstPage())
        <a class="action-edit" style="text-decoration: none;" href="{{ $items->previousPageUrl() }}">← Previous</a>
      @endif
      @if ($items->hasMorePages())
        <a class="action-edit" style="text-decoration: none;" href="{{ $items->nextPageUrl() }}">Next →</a>
      @endif
    </div>
  </div>
@endif
