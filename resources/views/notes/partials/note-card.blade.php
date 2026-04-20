{{-- Note card partial — hover-action layout --}}
{{-- .note-hover-actions là SIBLING (không phải con) của .note-card-inner --}}
{{-- → click pin/delete không bao giờ bubble lên card --}}
<div class="note-card-wrapper swipe-row {{ $viewMode === 'list' ? 'note-list-row' : '' }}">

    {{-- Swipe reveals (mobile) --}}
    <div class="swipe-reveal swipe-pin-reveal" aria-hidden="true">
        <span class="material-icons-outlined">push_pin</span>
        <span class="swipe-label">{{ $note->is_pinned ? 'Unpin' : 'Pin' }}</span>
    </div>
    <div class="swipe-reveal swipe-delete-reveal" aria-hidden="true">
        <span class="material-icons-outlined">delete</span>
        <span class="swipe-label">Delete</span>
    </div>

    {{-- ── Hover action buttons — sibling của card, không phải con ──────── --}}
    <div class="note-hover-actions">
        <button type="button"
                class="note-hover-btn pin-hover-btn {{ $note->is_pinned ? 'is-pinned' : '' }}"
                onclick="window.togglePin({{ $note->id }})"
                title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}"
                aria-label="{{ $note->is_pinned ? 'Unpin note' : 'Pin note' }}">
            <span class="material-icons-outlined"
                  style="{{ $note->is_pinned ? 'color:#f59e0b' : '' }}">push_pin</span>
        </button>
        <button type="button"
                class="note-hover-btn delete-hover-btn"
                onclick="window.confirmDelete({{ $note->id }},{{ $note->has_password ? 'true' : 'false' }})"
                title="Delete note"
                aria-label="Delete note">
            <span class="material-icons-outlined">delete</span>
        </button>
    </div>

    {{-- ── Card body (click → edit) ──────────────────────────────────────── --}}
    <div id="note-card-{{ $note->id }}"
         data-pinned="{{ $note->is_pinned ? '1' : '0' }}"
         data-has-password="{{ $note->has_password ? 'true' : 'false' }}"
         data-note-ts="{{ $note->created_at->timestamp }}"
         @if($note->note_color && $note->note_color !== '#ffffff')
             style="border-top: 3px solid {{ $note->note_color }};"
         @endif
         class="note-card-inner {{ $viewMode === 'list' ? 'note-card-list' : 'note-card-grid' }}"
         onclick="editNote({{ $note->id }},{{ $note->has_password ? 'true' : 'false' }})"
         role="button" tabindex="0"
         onkeydown="if(event.key==='Enter')editNote({{ $note->id }},{{ $note->has_password ? 'true' : 'false' }})"
         aria-label="Edit note: {{ $note->title ?: 'Untitled' }}">

        @if($viewMode === 'grid')
        {{-- ════ GRID content ════ --}}
            @if($note->is_pinned || $note->has_password || ($note->shares && $note->shares->count() > 0))
            <div class="flex items-center gap-1 mb-1.5">
                @if($note->is_pinned)
                <span class="material-icons-outlined text-amber-500" style="font-size:13px" title="Pinned">push_pin</span>
                @endif
                @if($note->has_password)
                <span class="material-icons-outlined text-red-400" style="font-size:13px" title="Protected">lock</span>
                @endif
                @if($note->shares && $note->shares->count() > 0)
                <span class="material-icons-outlined text-blue-400" style="font-size:13px" title="Shared">share</span>
                @endif
            </div>
            @endif

            <h3 class="note-title">{{ $note->title ?: 'Untitled' }}</h3>
            <p class="note-preview">{{ \Str::limit(strip_tags($note->content), 100) }}</p>

            @if($note->labels->count() > 0)
            <div class="flex flex-wrap gap-1 mb-1">
                @foreach($note->labels->take(2) as $label)
                <span class="note-label-chip" style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                @endforeach
                @if($note->labels->count() > 2)
                <span style="font-size:9px" class="text-muted self-center">+{{ $note->labels->count() - 2 }}</span>
                @endif
            </div>
            @endif

            <div class="note-footer">
                <span class="note-time">{{ $note->updated_at->diffForHumans() }}</span>
            </div>

        @else
        {{-- ════ LIST content ════ --}}
            <div class="flex items-center gap-2 w-full min-w-0">
                @if($note->is_pinned || $note->has_password || ($note->shares && $note->shares->count() > 0))
                <div class="flex items-center gap-0.5 flex-shrink-0">
                    @if($note->is_pinned)
                    <span class="material-icons-outlined text-amber-500" style="font-size:14px" title="Pinned">push_pin</span>
                    @endif
                    @if($note->has_password)
                    <span class="material-icons-outlined text-red-400" style="font-size:14px" title="Protected">lock</span>
                    @endif
                    @if($note->shares && $note->shares->count() > 0)
                    <span class="material-icons-outlined text-blue-400" style="font-size:14px" title="Shared">share</span>
                    @endif
                </div>
                @endif

                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-sm truncate">{{ $note->title ?: 'Untitled' }}</h3>
                    <p class="text-xs text-muted truncate">{{ \Str::limit(strip_tags($note->content), 80) }}</p>
                </div>

                @if($note->labels->count() > 0)
                <div class="hidden md:flex gap-1 flex-shrink-0">
                    @foreach($note->labels->take(2) as $label)
                    <span class="note-label-chip" style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                    @endforeach
                </div>
                @endif

                <span class="text-xs text-muted flex-shrink-0 hidden lg:block whitespace-nowrap note-list-time">
                    {{ $note->updated_at->diffForHumans() }}
                </span>
            </div>
        @endif

    </div>{{-- end note-card-inner --}}
</div>{{-- end note-card-wrapper --}}
