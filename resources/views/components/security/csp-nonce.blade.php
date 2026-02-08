{{-- 
    CSP Nonce Component
    
    Provides the current CSP nonce for inline scripts and styles.
    Integrates with both our SecurityHeaderService and Vite's CSP system.
--}}
@if(request()->attributes->get('csp_nonce'))
    nonce="{{ request()->attributes->get('csp_nonce') }}"
@endif
