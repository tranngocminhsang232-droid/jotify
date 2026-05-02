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
         data-pinned-at="{{ $note->pinned_at?->timestamp ?? 0 }}"
         @if($note->note_color && $note->note_color !== 'none')
             style="border-top: 3px solid {{ $note->note_color }};"
         @endif
         class="note-card-inner {{ $viewMode === 'list' ? 'note-card-list' : 'note-card-grid' }}"
         onclick="editNote({{ $note->id }},{{ $note->has_password ? 'true' : 'false' }})"
         role="button" tabindex="0"
         onkeydown="if(event.key==='Enter')editNote({{ $note->id }},{{ $note->has_password ? 'true' : 'false' }})"
         aria-label="Edit note: {{ $note->title ?: 'Untitled' }}">

        @if($viewMode === 'grid')
        {{-- ════ GRID content ════ --}}
            {{-- Status icons row (lock + share only — pin moved below title) --}}
            @if($note->has_password || ($note->shares && $note->shares->count() > 0))
            <div class="flex items-center gap-1 mb-1">
                @if($note->has_password)
                <span class="material-icons-outlined" style="font-size:13px;color:#ef4444;" title="Protected">lock</span>
                @endif
                @if($note->shares && $note->shares->count() > 0)
                <span class="material-icons-outlined" style="font-size:13px;color:#3b82f6;" title="Shared">share</span>
                @endif
            </div>
            @endif

            {{-- Title + pin badge + labels (all inline, no side column) --}}
            <div class="min-w-0 mb-0.5">
                <h3 class="note-title">{{ $note->title ?: 'Untitled' }}</h3>
                @if($note->is_pinned)
                <div class="pin-badge-below-title flex items-center gap-0.5" style="margin-bottom:2px;">
                    <span class="material-icons-outlined" style="font-size:11px;color:#f59e0b;">push_pin</span>
                    <span style="font-size:9px;font-weight:700;color:#f59e0b;letter-spacing:0.04em;">Pinned</span>
                </div>
                @endif
                @if($note->labels->count() > 0)
                <div class="note-grid-labels flex flex-wrap gap-1 mt-1">
                    @foreach($note->labels->take(3) as $label)
                    <span class="note-label-chip" style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                    @endforeach
                    @if($note->labels->count() > 3)
                    <span style="font-size:9px" class="text-muted self-center">+{{ $note->labels->count() - 3 }}</span>
                    @endif
                </div>
                @endif
            </div>

            @if($note->has_password)
            <p class="note-preview" style="font-style:italic;opacity:0.5;">🔒 Content is protected</p>
            @else
            <p class="note-preview">{{ \Str::limit(strip_tags($note->content), 100) }}</p>
            @endif

            {{-- Thumbnail ảnh đính kèm đầu tiên --}}
            @if($note->images->count() > 0)
            <div class="note-thumb-wrap">
                <img src="{{ asset('storage/' . $note->images->first()->image_path) }}"
                     alt="Attachment"
                     class="note-preview-img"
                     loading="lazy">
                @if($note->images->count() > 1)
                <span class="note-thumb-count">+{{ $note->images->count() - 1 }}</span>
                @endif
            </div>
            @endif

            {{-- Footer: modified time at bottom-left --}}
            <div class="note-footer">
                <span class="note-time">{{ $note->updated_at->diffForHumans() }}</span>
            </div>

        @else
        {{-- ════ LIST content ════ --}}
            <div class="flex items-start gap-2 w-full min-w-0">
                {{-- Status icons (lock + share only — pin moved below title) --}}
                @if($note->has_password || ($note->shares && $note->shares->count() > 0))
                <div class="flex items-center gap-0.5 flex-shrink-0 mt-0.5">
                    @if($note->has_password)
                    <span class="material-icons-outlined" style="font-size:14px;color:#ef4444;" title="Protected">lock</span>
                    @endif
                    @if($note->shares && $note->shares->count() > 0)
                    <span class="material-icons-outlined" style="font-size:14px;color:#3b82f6;" title="Shared">share</span>
                    @endif
                </div>
                @endif

                {{-- Title + pin indicator + preview + bottom row --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <h3 class="font-semibold text-sm truncate flex-1 min-w-0">{{ $note->title ?: 'Untitled' }}</h3>
                    </div>
                    @if($note->is_pinned)
                    <div class="pin-badge-below-title flex items-center gap-0.5" style="margin-bottom:1px;">
                        <span class="material-icons-outlined" style="font-size:11px;color:#f59e0b;">push_pin</span>
                        <span style="font-size:9px;font-weight:700;color:#f59e0b;letter-spacing:0.04em;">Pinned</span>
                    </div>
                    @endif
                    @if($note->has_password)
                    <p class="text-xs text-muted truncate" style="font-style:italic;opacity:0.5;">🔒 Content is protected</p>
                    @else
                    <p class="text-xs text-muted truncate">{{ \Str::limit(strip_tags($note->content), 80) }}</p>
                    @endif
                    {{-- Bottom row: labels + timestamp --}}
                    <div class="flex items-center gap-2 mt-0.5">
                        <div class="flex flex-wrap gap-1 flex-1 min-w-0">
                            @foreach($note->labels->take(2) as $label)
                            <span class="note-label-chip" style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                            @endforeach
                            @if($note->labels->count() > 2)
                            <span style="font-size:9px" class="text-muted self-center">+{{ $note->labels->count() - 2 }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-muted whitespace-nowrap note-list-time flex-shrink-0" style="opacity:0.7;">
                            {{ $note->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Full-width image preview below content (same as grid view) --}}
            @if($note->images->count() > 0)
            <div class="note-thumb-wrap">
                <img src="{{ asset('storage/' . $note->images->first()->image_path) }}"
                     alt="Attachment"
                     class="note-preview-img"
                     loading="lazy">
                @if($note->images->count() > 1)
                <span class="note-thumb-count">+{{ $note->images->count() - 1 }}</span>
                @endif
            </div>
            @endif

        @endif

    </div>{{-- end note-card-inner --}}
</div>{{-- end note-card-wrapper --}}
