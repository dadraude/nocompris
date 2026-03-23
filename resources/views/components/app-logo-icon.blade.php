<svg
    {{ $attributes->merge(['class' => 'block']) }}
    data-brand-mark
    viewBox="0 0 64 64"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <defs>
        <linearGradient id="brand-mark-background" x1="10" y1="6" x2="54" y2="58" gradientUnits="userSpaceOnUse">
            <stop stop-color="#102b33" />
            <stop offset="0.56" stop-color="#1e5a61" />
            <stop offset="1" stop-color="#52a8a1" />
        </linearGradient>

        <linearGradient id="brand-mark-sheen" x1="16" y1="12" x2="46" y2="46" gradientUnits="userSpaceOnUse">
            <stop stop-color="#ffffff" stop-opacity="0.28" />
            <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
        </linearGradient>
    </defs>

    <rect x="4" y="4" width="56" height="56" rx="18" fill="url(#brand-mark-background)" />
    <rect x="8" y="8" width="48" height="48" rx="14" fill="url(#brand-mark-sheen)" />
    <rect x="18" y="19" width="28" height="4.6" rx="2.3" fill="#F4F6F3" />
    <rect x="18" y="29.2" width="20.5" height="4.6" rx="2.3" fill="#F4F6F3" fill-opacity="0.96" />
    <rect x="18" y="39.4" width="15" height="4.6" rx="2.3" fill="#F4F6F3" fill-opacity="0.92" />
    <circle cx="43.5" cy="31.5" r="6.6" fill="#FF8B5E" />
    <path d="M40.8 31.5L42.6 33.3L46.4 29.5" stroke="#0F2F33" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" />
</svg>
