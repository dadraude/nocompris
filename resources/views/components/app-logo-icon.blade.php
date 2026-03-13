<svg
    {{ $attributes->merge(['class' => 'block']) }}
    data-brand-mark
    viewBox="0 0 64 64"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <defs>
        <linearGradient id="brand-mark-background" x1="12" y1="6" x2="54" y2="58" gradientUnits="userSpaceOnUse">
            <stop stop-color="#18362E" />
            <stop offset="0.52" stop-color="#2E544A" />
            <stop offset="1" stop-color="#7AA08C" />
        </linearGradient>

        <linearGradient id="brand-mark-sheen" x1="14" y1="12" x2="47" y2="46" gradientUnits="userSpaceOnUse">
            <stop stop-color="#FFF8EE" stop-opacity="0.24" />
            <stop offset="1" stop-color="#FFF8EE" stop-opacity="0" />
        </linearGradient>
    </defs>

    <rect x="4" y="4" width="56" height="56" rx="18" fill="url(#brand-mark-background)" />
    <rect x="8" y="8" width="48" height="48" rx="14" fill="url(#brand-mark-sheen)" />
    <path
        d="M20 47V17H27.35L40.65 35.65V17H47V47H39.65L26.35 28.35V47H20Z"
        fill="#F7F2E8"
    />
    <rect x="42.5" y="14" width="8.5" height="8.5" rx="4.25" fill="#F2B56B" />
</svg>
