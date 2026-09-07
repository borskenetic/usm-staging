@forelse ($activities as $activity)
    <div class="list-group-item activity-feed-item">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="activity-feed-item__type badge text-bg-light text-dark">{{ $activity->typeLabel() }}</span>
                    <time class="text-muted small">{{ $activity->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time>
                </div>
                <h2 class="activity-feed-item__title">{{ $activity->title }}</h2>
                @if ($activity->body)
                    <p class="text-muted small mb-1">{{ $activity->body }}</p>
                @endif
                @if ($activity->user)
                    <p class="text-muted small mb-0">By {{ $activity->user->name }}</p>
                @endif
            </div>
            @if ($activity->action_url)
                <a href="{{ $activity->action_url }}" class="activity-btn activity-btn--primary align-self-start">Open</a>
            @endif
        </div>
    </div>
@empty
    <div class="activity-feed-empty">
        @if ($category === 'patron')
            No patron notifications for this period.
        @else
            No staff activity for this period.
        @endif
    </div>
@endforelse
