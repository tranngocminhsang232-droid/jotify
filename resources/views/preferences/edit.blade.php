@extends('layouts.app')
@section('title', 'Preferences - JOTIFY')

@section('header')
<h1 style="font-size:1.125rem;font-weight:700;color:var(--color-body-text);">Preferences</h1>
@endsection

@section('content')
<style>
    .pref-card {
        background: var(--color-card);
        border: 1px solid var(--color-border);
        border-radius: 1.25rem;
        padding: 1.75rem;
        max-width: 640px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    .pref-section-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--color-muted);
        margin-bottom: 0.75rem;
    }

    /* ── Radio Option (Theme, Font Size) ── */
    .pref-option-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .pref-option {
        flex: 1;
        min-width: 120px;
    }
    .pref-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .pref-option label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border-radius: 0.875rem;
        border: 2px solid var(--color-border);
        background: var(--color-hover);
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .pref-option label:hover {
        border-color: var(--accent-border);
        background: var(--accent-subtle);
    }
    .pref-option input[type="radio"]:checked + label {
        border-color: var(--accent-dim);
        background: var(--accent-subtle);
        box-shadow: 0 0 0 3px var(--accent-subtle);
    }
    .pref-option label .opt-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .pref-option label .opt-text p:first-child {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--color-body-text);
        margin: 0;
    }
    .pref-option label .opt-text p:last-child {
        font-size: 0.75rem;
        color: var(--color-muted);
        margin: 0;
    }

    /* ── Font Size compact ── */
    .font-option-group {
        display: flex;
        gap: 0.625rem;
    }
    .font-option {
        flex: 1;
    }
    .font-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .font-option label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 0.5rem;
        border-radius: 0.875rem;
        border: 2px solid var(--color-border);
        background: var(--color-hover);
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
        gap: 0.25rem;
    }
    .font-option label:hover {
        border-color: var(--accent-border);
        background: var(--accent-subtle);
    }
    .font-option input[type="radio"]:checked + label {
        border-color: var(--accent-dim);
        background: var(--accent-subtle);
        box-shadow: 0 0 0 3px var(--accent-subtle);
    }
    .font-option label .size-letter {
        font-weight: 700;
        color: var(--color-body-text);
    }
    .font-option label .size-name {
        font-size: 0.7rem;
        color: var(--color-muted);
        text-transform: capitalize;
    }

    /* ── Color Swatches ── */
    .color-option-group {
        display: flex;
        gap: 0.625rem;
        flex-wrap: wrap;
    }
    .color-option {
        position: relative;
    }
    .color-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .color-option label {
        display: block;
        width: 44px;
        height: 44px;
        border-radius: 0.75rem;
        border: 2px solid var(--color-border);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .color-option label:hover {
        transform: scale(1.15);
        border-color: var(--accent-dim);
    }
    .color-option input[type="radio"]:checked + label {
        border-color: var(--accent-dim);
        box-shadow: 0 0 0 3px var(--accent-subtle), 0 0 0 5px var(--accent-dim);
        transform: scale(1.1);
    }

    /* ── Selected indicator ── */
    .pref-option input[type="radio"]:checked + label::after,
    .font-option input[type="radio"]:checked + label::after {
        content: '✓';
        margin-left: auto;
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--accent-dim);
        flex-shrink: 0;
    }
    .font-option input[type="radio"]:checked + label::after {
        position: absolute;
        right: 6px;
        top: 4px;
        font-size: 0.6rem;
    }
    .font-option {
        position: relative;
    }

    /* ── Divider ── */
    .pref-divider {
        border: none;
        border-top: 1px solid var(--color-border);
        margin: 0;
    }

    /* ── Save button ── */
    .pref-submit {
        display: flex;
        justify-content: flex-end;
    }
</style>

<form action="{{ route('preferences.update') }}" method="POST" class="pref-card">
    @csrf
    @method('PUT')

    @if($errors->any())
    <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:0.75rem;padding:0.875rem 1rem;">
        @foreach($errors->all() as $error)
        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8rem;color:#f87171;">
            <span class="material-icons-outlined" style="font-size:1rem;">error</span>
            {{ $error }}
        </div>
        @endforeach
    </div>
    @endif




    {{-- Font Size --}}
    <div>
        <p class="pref-section-label">Note Font Size</p>
        <div class="font-option-group">
            @foreach(['small' => ['S','14px'], 'medium' => ['M','16px'], 'large' => ['L','18px'], 'x-large' => ['XL','20px']] as $val => [$letter, $px])
            <div class="font-option">
                <input type="radio" name="font_size" value="{{ $val }}" id="font-{{ $val }}"
                       {{ $preferences->font_size === $val ? 'checked' : '' }}
                       onchange="previewFontSize(this.value)">
                <label for="font-{{ $val }}">
                    <span class="size-letter" style="font-size:{{ $px }}">{{ $letter }}</span>
                    <span class="size-name">{{ $val }}</span>
                </label>
            </div>
            @endforeach
        </div>

        {{-- Live Preview --}}
        <div id="font-preview-box" style="
            margin-top: 1rem;
            padding: 1rem 1.25rem;
            border-radius: 0.875rem;
            border: 1.5px dashed var(--color-border);
            background: var(--color-hover);
            transition: all 0.2s ease;
        ">
            <p style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-muted);margin:0 0 0.5rem;">Preview</p>
            <p id="font-preview-title" style="
                font-weight: 700;
                color: var(--color-body-text);
                margin: 0 0 0.35rem;
                line-height: 1.3;
                transition: font-size 0.2s ease;
                font-size: {{ ['small'=>'1.25rem','medium'=>'1.5rem','large'=>'1.75rem','x-large'=>'2rem'][$preferences->font_size] }};
            ">My note title</p>
            <p id="font-preview-content" style="
                color: var(--color-muted);
                margin: 0;
                line-height: 1.6;
                transition: font-size 0.2s ease;
                font-size: {{ ['small'=>'14px','medium'=>'16px','large'=>'18px','x-large'=>'20px'][$preferences->font_size] }};
            ">This is how your note content will look inside the editor when you write.</p>
        </div>
    </div>

    <hr class="pref-divider">

    {{-- Note Color --}}
    <div>
        <p class="pref-section-label">Default Note Color</p>
        <p style="font-size:0.78rem;color:var(--color-muted);margin-bottom:0.75rem;">
            New notes will use this color as their accent stripe.
        </p>
        <div class="color-option-group">
            @foreach([
                '#ffffff' => 'White',
                '#fef3c7' => 'Yellow',
                '#dbeafe' => 'Blue',
                '#dcfce7' => 'Green',
                '#fce7f3' => 'Pink',
                '#ede9fe' => 'Purple',
                '#e0e7ff' => 'Indigo',
                '#f1f5f9' => 'Slate',
                '#fee2e2' => 'Red',
                '#ffedd5' => 'Orange',
            ] as $color => $name)
            <div class="color-option">
                <input type="radio" name="note_color" value="{{ $color }}" id="color-{{ $loop->index }}"
                       {{ $preferences->note_color === $color ? 'checked' : '' }}>
                <label for="color-{{ $loop->index }}"
                       style="background-color: {{ $color }};"
                       title="{{ $name }}">
                </label>
            </div>
            @endforeach
        </div>
    </div>

    <hr class="pref-divider">

    <div class="pref-submit">
        <button type="submit" class="btn-primary">
            <span class="material-icons-outlined" style="font-size:1.1rem;">save</span>
            Save Preferences
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
const fontSizeMap = {
    'small':   { title: '1.25rem', content: '14px' },
    'medium':  { title: '1.5rem',  content: '16px' },
    'large':   { title: '1.75rem', content: '18px' },
    'x-large': { title: '2rem',    content: '20px' },
};

function previewFontSize(value) {
    const sizes = fontSizeMap[value];
    if (!sizes) return;

    const titleEl   = document.getElementById('font-preview-title');
    const contentEl = document.getElementById('font-preview-content');
    const box       = document.getElementById('font-preview-box');

    if (titleEl)   titleEl.style.fontSize   = sizes.title;
    if (contentEl) contentEl.style.fontSize = sizes.content;

    // Pulse the preview box to draw attention
    if (box) {
        box.style.borderColor = 'var(--accent-dim)';
        box.style.background  = 'var(--accent-subtle)';
        setTimeout(() => {
            box.style.borderColor = 'var(--color-border)';
            box.style.background  = 'var(--color-hover)';
        }, 600);
    }
}
</script>
@endpush
