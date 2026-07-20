@props([
    'id' => 'confirmModal',
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin?',
    'variant' => 'warning', // danger, warning, info, success
    'confirmText' => 'Konfirmasi',
    'cancelText' => 'Batal',
    'formAction' => '',
    'formMethod' => 'POST',
    'formId' => '',
    'showCancel' => true,
    'icon' => null,
])

@php
$variantConfig = [
    'danger' => [
        'icon' => '<svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        'bgCircle' => 'bg-rose-100',
        'confirmBg' => 'bg-rose-600 hover:bg-rose-700',
    ],
    'warning' => [
        'icon' => '<svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        'bgCircle' => 'bg-amber-100',
        'confirmBg' => 'bg-amber-600 hover:bg-amber-700',
    ],
    'info' => [
        'icon' => '<svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'bgCircle' => 'bg-indigo-100',
        'confirmBg' => 'bg-indigo-600 hover:bg-indigo-700',
    ],
    'success' => [
        'icon' => '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'bgCircle' => 'bg-emerald-100',
        'confirmBg' => 'bg-emerald-600 hover:bg-emerald-700',
    ],
];
$vc = $variantConfig[$variant] ?? $variantConfig['warning'];
$iconHtml = $icon ?? $vc['icon'];
@endphp

<div id="{{ $id }}"
     class="hidden fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-all duration-200"
     onclick="if(event.target===this) closeConfirmModal('{{ $id }}')">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden opacity-0 scale-95 translate-y-4 transition-all duration-200"
         id="{{ $id }}_panel">

        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="{{ $vc['bgCircle'] }} rounded-full p-2.5 shrink-0">
                    {!! $iconHtml !!}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">{{ $title }}</h3>
                        <button type="button" onclick="closeConfirmModal('{{ $id }}')"
                                class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors -mr-1 -mt-1 shrink-0">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="mt-2 text-sm text-slate-600 leading-relaxed">
                        {!! $message !!}
                    </div>
                    {{ $slot ?? '' }}
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
            @if($showCancel)
            <button type="button" onclick="closeConfirmModal('{{ $id }}')"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-white hover:border-slate-300 transition-all">
                {{ $cancelText }}
            </button>
            @endif
            @if($formAction)
            <form action="{{ $formAction }}" method="{{ $formMethod }}" id="{{ $formId ?: $id.'_form' }}" class="inline">
                @csrf
                @if(!in_array(strtolower($formMethod), ['get', 'post']))
                @method($formMethod)
                @endif
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-sm transition-all {{ $vc['confirmBg'] }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $variant === 'danger' ? 'rose' : ($variant === 'warning' ? 'amber' : ($variant === 'success' ? 'emerald' : 'indigo')) }}-500">
                    {{ $confirmText }}
                </button>
            </form>
            @else
            <button type="button" onclick="confirmAction('{{ $id }}')"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-sm transition-all {{ $vc['confirmBg'] }}">
                {{ $confirmText }}
            </button>
            @endif
        </div>
    </div>
</div>

<script>
function openConfirmModal(id) {
    const modal = document.getElementById(id);
    const panel = document.getElementById(id + '_panel');
    if (!modal || !panel) return;
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        panel.classList.remove('opacity-0', 'scale-95', 'translate-y-4');
        panel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
    });
}

function closeConfirmModal(id) {
    const modal = document.getElementById(id);
    const panel = document.getElementById(id + '_panel');
    if (!modal || !panel) return;
    panel.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
    panel.classList.add('opacity-0', 'scale-95', 'translate-y-4');
    setTimeout(() => modal.classList.add('hidden'), 200);
}

function confirmAction(id) {
    const form = document.querySelector('#' + id + ' form, #' + id + '_form');
    if (form) form.submit();
}
</script>
