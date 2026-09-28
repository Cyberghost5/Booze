<template>
    <svg
        viewBox="0 0 240 60"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        v-bind="$attrs"
    >
        <defs>
            <!-- Amber Liquid Gradient -->
            <linearGradient id="boozeAmberGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#fbbf24" />
                <stop offset="50%" stop-color="#f59e0b" />
                <stop offset="100%" stop-color="#d97706" />
            </linearGradient>

            <!-- Glow Effect -->
            <radialGradient id="boozeGlowGrad" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.4" />
                <stop offset="100%" stop-color="#f59e0b" stop-opacity="0" />
            </radialGradient>

            <!-- Glass Gradient -->
            <linearGradient id="glassGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#ffffff" stop-opacity="0.6" />
                <stop offset="100%" stop-color="#ffffff" stop-opacity="0.1" />
            </linearGradient>
        </defs>

        <!-- Background Glow Badge -->
        <circle cx="30" cy="30" r="26" fill="url(#boozeGlowGrad)" />

        <!-- Badge Container -->
        <rect x="5" y="5" width="50" height="50" rx="14" fill="#18181b" stroke="#f59e0b" stroke-width="2" stroke-opacity="0.4" />

        <!-- Cocktail Glass Icon -->
        <!-- Stem & Base -->
        <path d="M30 43V47M24 47H36" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" />
        
        <!-- Glass Bowl -->
        <path d="M18 18L30 38L42 18Z" fill="url(#glassGrad)" stroke="#f59e0b" stroke-width="2.5" stroke-linejoin="round" />
        
        <!-- Liquid Fill inside Bowl -->
        <path d="M21 23L30 37.5L39 23Z" fill="url(#boozeAmberGrad)" />

        <!-- Ice Cube / Bubbles -->
        <circle cx="28" cy="26" r="1.5" fill="#ffffff" opacity="0.8" />
        <circle cx="33" cy="22" r="1" fill="#ffffff" opacity="0.9" />

        <!-- Straw -->
        <path d="M34 14L28 29" stroke="#ef4444" stroke-width="2" stroke-linecap="round" />
        
        <!-- Lemon Wedge Garnish -->
        <path d="M40 16A4 4 0 0 1 43 21" stroke="#facc15" stroke-width="2.5" stroke-linecap="round" />

        <!-- Text: BOOZE -->
        <text
            x="68"
            y="35"
            fill="#ffffff"
            font-family="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
            font-size="26"
            font-weight="900"
            letter-spacing="1.5"
        >
            BOOZE<tspan fill="#f59e0b">.</tspan>
        </text>

        <!-- Subtitle Badge: GWALLAMEJI -->
        <rect x="68" y="41" width="80" height="13" rx="4" fill="#f59e0b" fill-opacity="0.2" stroke="#f59e0b" stroke-opacity="0.4" stroke-width="0.8" />
        <text
            x="108"
            y="50"
            fill="#fbbf24"
            font-family="ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace"
            font-size="8.5"
            font-weight="700"
            letter-spacing="1"
            text-anchor="middle"
        >
            GWALLAMEJI
        </text>
    </svg>
</template>

