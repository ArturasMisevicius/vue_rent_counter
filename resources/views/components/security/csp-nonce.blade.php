{{-- 
    CSP Nonce Component
    
    Provides the current CSP nonce for inline scripts and styles.
    Integrates with both our SecurityHeaderService and Vite's CSP system.
--}}
@php
    $nonce = request()->attributes->get('csp_nonce') ?? '';
@endphp

@if($nonce)
    nonce="{{ $nonce }}"
@endif