{{-- The list inside the bell (loaded by /notifications when the bell is clicked). Needs: $notes, $limit --}}
@forelse ($notes->take($limit) as $note)
  <a href="{{ $note['url'] }}" class="notif-item">
    <span class="notif-icon {{ $note['tone'] }}">{{ $note['icon'] }}</span>
    <span>
      <span class="notif-title">{{ $note['title'] }}</span>
      <span class="notif-text">{{ $note['text'] }}</span>
      @if ($note['at'])<span class="notif-time">{{ $note['at']->diffForHumans() }}</span>@endif
    </span>
  </a>
@empty
  <div class="notif-empty">✅ All caught up! Nothing needs your attention right now.</div>
@endforelse
@if ($notes->count() > $limit)
  <div class="notif-more">Showing the first {{ $limit }} of {{ $notes->count() }}</div>
@endif
