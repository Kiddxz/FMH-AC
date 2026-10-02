{{-- Simple "Previous / Next" links for long lists. Use: @include('partials.pager', ['items' => $pets]) --}}
@if ($items->hasPages())
    <div class="admin-tools" style="justify-content: space-between; margin-top: 15px;">
        <span style="color: #666; font-size: 14px;">
            Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }}
        </span>
        <div style="display: flex; gap: 8px;">
            @if ($items->onFirstPage())
                <button class="action-view" type="button" disabled style="opacity: 0.5;">← Previous</button>
            @else
                <button class="action-view" type="button" onclick="window.location.href='{{ $items->previousPageUrl() }}'">← Previous</button>
            @endif
            @if ($items->hasMorePages())
                <button class="action-view" type="button" onclick="window.location.href='{{ $items->nextPageUrl() }}'">Next →</button>
            @else
                <button class="action-view" type="button" disabled style="opacity: 0.5;">Next →</button>
            @endif
        </div>
    </div>
@endif
