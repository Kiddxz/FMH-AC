{{-- The list inside the bell (loaded by /notifications when the bell is clicked). Needs: $notes, $unread, $limit
     All of them have a white background; unread ones have a bold title and a blue dot. Clicking one (or the check button) makes it read. --}}
<div class="notif-items" data-unread="{{ $unread }}">
@forelse ($notes->take($limit) as $note)
  <div class="notif-row {{ $note['read'] ? 'is-read' : 'is-unread' }}" data-key="{{ $note['key'] }}">
    <a href="{{ $note['url'] }}" class="notif-item">
      <span class="notif-icon {{ $note['tone'] }}">{{ $note['icon'] }}</span>
      <span>
        <span class="notif-title">{{ $note['title'] }}</span>
        <span class="notif-text">{{ $note['text'] }}</span>
        @if ($note['at'])<span class="notif-time">{{ $note['at']->diffForHumans() }}</span>@endif
      </span>
    </a>
    @unless ($note['read'])
      <span class="notif-dot" aria-hidden="true"></span>
      <button type="button" class="notif-mark" title="Mark as read" aria-label="Mark as read: {{ $note['title'] }}">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      </button>
    @endunless
  </div>
@empty
  <div class="notif-empty">All caught up! Nothing needs your attention right now.</div>
@endforelse
@if ($notes->count() > $limit)
  <div class="notif-more">Showing the first {{ $limit }} of {{ $notes->count() }}</div>
@endif
</div>
