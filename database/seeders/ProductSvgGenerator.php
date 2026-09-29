<?php

namespace Database\Seeders;

class ProductSvgGenerator
{
    public static function generateAll(): void
    {
        $dir = public_path('images/products');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $items = self::getDefinitions();

        foreach ($items as $item) {
            $svg = self::renderSvg($item);
            file_put_contents($dir . '/' . $item['slug'] . '.svg', $svg);
        }
    }

    public static function getImageUrl(string $nameOrSlug): string
    {
        $map = self::getImageMap();
        return $map[$nameOrSlug] ?? '/images/products/trophy-lager.svg';
    }

    public static function getImageMap(): array
    {
        return [
            'Trophy Extra Quality Lager (600ml)' => '/images/products/trophy-lager.svg',
            'Star Larger Beer (600ml)' => '/images/products/star-lager.svg',
            'Guinness Extra Stout (600ml)' => '/images/products/guinness-stout.svg',
            'Goldberg Premium Lager (600ml)' => '/images/products/goldberg-lager.svg',
            'Hero Premium Lager (600ml)' => '/images/products/hero-lager.svg',
            'Life Continental Lager (600ml)' => '/images/products/life-lager.svg',
            'Gulder Ultimate Lager (600ml)' => '/images/products/gulder-lager.svg',
            'Legend Extra Stout (600ml)' => '/images/products/legend-stout.svg',
            'Heineken Premium Lager (600ml)' => '/images/products/heineken-lager.svg',
            'Budweiser King of Beers (600ml)' => '/images/products/budweiser-lager.svg',
            'Orijin Herbal Beer (600ml)' => '/images/products/orijin-beer.svg',
            'Smirnoff Ice Double Black (330ml Can)' => '/images/products/smirnoff-ice-can.svg',
            'Desperados Tequila Flavoured Beer (330ml)' => '/images/products/desperados-beer.svg',
            'Flying Fish Passion Fruit Beer (330ml)' => '/images/products/flying-fish-beer.svg',
            'Trophy Stout (600ml)' => '/images/products/trophy-stout.svg',
            'Malta Guinness (330ml Can)' => '/images/products/malta-guinness-can.svg',
            'Amstel Malta Ultra (330ml Can)' => '/images/products/amstel-malta-can.svg',
            'Maltina Classic (330ml Can)' => '/images/products/maltina-can.svg',
            'Dubic Malt (330ml Can)' => '/images/products/dubic-malt-can.svg',
            'Grand Malt (330ml Can)' => '/images/products/grand-malt-can.svg',
            'Orijin Herbal Bitters (75cl)' => '/images/products/orijin-bitters.svg',
            'Action Bitters (75cl)' => '/images/products/action-bitters.svg',
            'Alomo Bitters (75cl)' => '/images/products/alomo-bitters.svg',
            'Odogwu Bitters (75cl)' => '/images/products/odogwu-bitters.svg',
            "Seaman's Aromatic Schnapps (75cl)" => '/images/products/seamans-schnapps.svg',
            'Best Dark Rum (75cl)' => '/images/products/best-dark-rum.svg',
            'Chelsea Dry Gin (75cl)' => '/images/products/chelsea-dry-gin.svg',
            "Lord's Dry Gin (75cl)" => '/images/products/lords-dry-gin.svg',
            'Squadron Dark Rum (75cl)' => '/images/products/squadron-dark-rum.svg',
            'Campari Aperitif (75cl)' => '/images/products/campari-aperitif.svg',
            'Hennessy Very Special Cognac (70cl)' => '/images/products/hennessy-cognac.svg',
            'Martell VS Single Distillery (70cl)' => '/images/products/martell-cognac.svg',
            'Jameson Triple Distilled Irish Whiskey (70cl)' => '/images/products/jameson-whiskey.svg',
            "Jack Daniel's Old No.7 Tennessee Whiskey (70cl)" => '/images/products/jack-daniels-whiskey.svg',
            'Johnnie Walker Black Label (75cl)' => '/images/products/johnnie-walker-black.svg',
            'Johnnie Walker Red Label (75cl)' => '/images/products/johnnie-walker-red.svg',
            "McDowell's No.1 Luxury Whiskey (75cl)" => '/images/products/mcdowells-whiskey.svg',
            'Baileys Original Irish Cream (75cl)' => '/images/products/baileys-cream.svg',
            'Sierra Tequila Reposado (70cl)' => '/images/products/sierra-tequila.svg',
            'Smirnoff Triple Distilled Vodka (75cl)' => '/images/products/smirnoff-vodka.svg',
            'Carlo Rossi Sweet Red Wine (75cl)' => '/images/products/carlo-rossi-sweet-red.svg',
            'Four Cousins Sweet Red Wine (75cl)' => '/images/products/four-cousins-red.svg',
            '4th Street Sweet Red Wine (75cl)' => '/images/products/4th-street-red.svg',
            'Baron de Valls Red Wine (75cl)' => '/images/products/baron-de-valls.svg',
            'Veuve du Vernay Ice Sparkling Wine (75cl)' => '/images/products/veuve-du-vernay.svg',
            'Andre Cellars Sparkling Pink Moscato (75cl)' => '/images/products/andre-pink-moscato.svg',
            'Chamdor Non-Alcoholic Sparkling Wine (75cl)' => '/images/products/chamdor-sparkling.svg',
            'Agor Red Wine (75cl)' => '/images/products/agor-red-wine.svg',
            'Robertson Winery Sweet Red (75cl)' => '/images/products/robertson-sweet-red.svg',
            'JP Chenet Delicious Medium Sweet Red (75cl)' => '/images/products/jp-chenet-red.svg',
            'Frontera Cabernet Sauvignon Red (75cl)' => '/images/products/frontera-red.svg',
            'Tall Horse Merlot Red Wine (75cl)' => '/images/products/tall-horse-merlot.svg',
            'Saint Celine Sweet Red Wine (75cl)' => '/images/products/saint-celine-red.svg',
            'Eva Sparkling Fruit Wine (75cl)' => '/images/products/eva-sparkling.svg',
            'Casteller Cava Brut Sparkling (75cl)' => '/images/products/casteller-cava.svg',
            'Don Morris Red Wine (75cl)' => '/images/products/don-morris-red.svg',
            'Martinellis Gold Medal Sparkling Cider (75cl)' => '/images/products/martinellis-cider.svg',
            'J&W Sparkling White Grape Wine (75cl)' => '/images/products/jw-sparkling.svg',
            'Nederburg Pinotage Red Wine (75cl)' => '/images/products/nederburg-pinotage.svg',
            'Carlo Rossi Moscato White Wine (75cl)' => '/images/products/carlo-rossi-moscato.svg',
            'Coca-Cola Original (50cl Pet)' => '/images/products/coca-cola-pet.svg',
            'Pepsi Cola (50cl Pet)' => '/images/products/pepsi-cola-pet.svg',
            'Sprite Lemon-Lime (50cl Pet)' => '/images/products/sprite-pet.svg',
            'Fanta Orange (50cl Pet)' => '/images/products/fanta-orange-pet.svg',
            'Schweppes Tonic Water (33cl Can)' => '/images/products/schweppes-tonic-can.svg',
            'Schweppes Bitter Lemon (33cl Can)' => '/images/products/schweppes-lemon-can.svg',
            'Fearless Energy Drink - Red Berry (50cl)' => '/images/products/fearless-red-berry.svg',
            'Fearless Energy Drink - Original (50cl)' => '/images/products/fearless-original.svg',
            'Climax Herbal Energy Drink (33cl Can)' => '/images/products/climax-energy-can.svg',
            'Red Bull Energy Drink (250ml Can)' => '/images/products/red-bull-can.svg',
            'Predator Energy Drink (50cl Pet)' => '/images/products/predator-energy.svg',
            'Monster Energy Drink Original (50cl Can)' => '/images/products/monster-energy-can.svg',
            'Teem Soda Water (50cl Pet)' => '/images/products/teem-soda.svg',
            'Chilled Nigerian Zobo Drink (50cl)' => '/images/products/zobo-drink.svg',
            'La Casera Apple Drink (50cl Pet)' => '/images/products/la-casera-apple.svg',
            'Bigi Cola (60cl Pet)' => '/images/products/bigi-cola.svg',
            'Aquafina Premium Table Water (75cl)' => '/images/products/aquafina-water.svg',
            'Cway Purified Table Water (75cl)' => '/images/products/cway-water.svg',
            '5Alive Berry Blast Juice (78cl)' => '/images/products/5alive-berry-blast.svg',
            'Chivita 100% Real Orange Juice (1 Litre)' => '/images/products/chivita-orange-juice.svg',
            'gwallameji-weekend-vibes-combo' => '/images/products/bundle-weekend-vibes.svg',
            'all-night-chaser-energy-pack' => '/images/products/bundle-all-night-chaser.svg',
            'vip-hostel-celebration-bundle' => '/images/products/bundle-vip-celebration.svg',
        ];
    }

    public static function renderSvg(array $item): string
    {
        $slug = $item['slug'];
        $brand = htmlspecialchars($item['brand_name']);
        $subText = htmlspecialchars($item['sub_text']);
        $bgColor = $item['bg_color'];
        $bottleColor = $item['bottle_color'];
        $labelBg = $item['label_bg'];
        $labelTextColor = $item['label_text_color'];
        $symbol = $item['badge_symbol'];
        $volume = htmlspecialchars($item['volume']);
        $type = $item['type'];

        // Determine symbol SVG path snippet
        $symbolPath = self::getSymbolPath($symbol, $labelTextColor);

        // Container graphics by type
        $containerGraphics = '';

        if ($type === 'can') {
            $containerGraphics = <<<SVG
            <!-- Metallic Can -->
            <g transform="translate(180, 110)">
                <!-- Drop shadow -->
                <ellipse cx="120" cy="400" rx="90" ry="16" fill="#000000" opacity="0.6"/>
                <!-- Can Body Base -->
                <rect x="30" y="30" width="180" height="350" rx="28" fill="{$bottleColor}" stroke="#3f3f46" stroke-width="2"/>
                <!-- Top Metallic Rim -->
                <ellipse cx="120" cy="35" rx="88" ry="14" fill="#d4d4d8" stroke="#a1a1aa" stroke-width="3"/>
                <ellipse cx="120" cy="35" rx="70" ry="10" fill="#71717a"/>
                <!-- Pull Tab -->
                <ellipse cx="120" cy="35" rx="14" ry="6" fill="#e4e4e7" stroke="#71717a" stroke-width="1.5"/>
                <!-- Main Body Wrap Label -->
                <rect x="30" y="75" width="180" height="260" fill="{$labelBg}" />
                <rect x="30" y="75" width="180" height="10" fill="#ffffff" opacity="0.15"/>
                <rect x="30" y="325" width="180" height="10" fill="#000000" opacity="0.2"/>
                
                <!-- Metallic Highlight Overlay -->
                <rect x="50" y="30" width="30" height="350" fill="#ffffff" opacity="0.12"/>

                <!-- Label Content -->
                <g transform="translate(120, 150)" text-anchor="middle">
                    {$symbolPath}
                    <text y="45" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="22" letter-spacing="1">{$brand}</text>
                    <text y="70" fill="{$labelTextColor}" opacity="0.85" font-family="system-ui, sans-serif" font-weight="700" font-size="10" letter-spacing="2">{$subText}</text>
                </g>

                <!-- Bottom Rim -->
                <ellipse cx="120" cy="375" rx="85" ry="12" fill="#a1a1aa"/>
            </g>
SVG;
        } elseif ($type === 'flask_bitters') {
            $containerGraphics = <<<SVG
            <!-- Bitters / Spirit Flask -->
            <g transform="translate(175, 90)">
                <!-- Drop shadow -->
                <ellipse cx="125" cy="425" rx="100" ry="18" fill="#000000" opacity="0.6"/>
                <!-- Cap -->
                <rect x="100" y="20" width="50" height="35" rx="6" fill="#eab308" stroke="#ca8a04" stroke-width="2"/>
                <rect x="105" y="55" width="40" height="15" fill="#18181b"/>
                <!-- Neck -->
                <path d="M100 70 L150 70 L170 120 L80 120 Z" fill="{$bottleColor}"/>
                <!-- Main Flask Body -->
                <rect x="40" y="120" width="170" height="290" rx="30" fill="{$bottleColor}" stroke="#52525b" stroke-width="2"/>
                <!-- Glass Reflection -->
                <path d="M55 130 Q125 140 195 130 L195 400 L55 400 Z" fill="#ffffff" opacity="0.05"/>
                <rect x="55" y="135" width="20" height="260" rx="10" fill="#ffffff" opacity="0.12"/>
                
                <!-- Main Front Label Badge -->
                <rect x="55" y="170" width="140" height="200" rx="16" fill="{$labelBg}" stroke="#eab308" stroke-width="2"/>
                
                <g transform="translate(125, 230)" text-anchor="middle">
                    {$symbolPath}
                    <text y="42" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="20" letter-spacing="1">{$brand}</text>
                    <text y="65" fill="{$labelTextColor}" opacity="0.85" font-family="system-ui, sans-serif" font-weight="700" font-size="9" letter-spacing="1.5">{$subText}</text>
                </g>
            </g>
SVG;
        } elseif ($type === 'bottle_schnapps') {
            $containerGraphics = <<<SVG
            <!-- Schnapps Bottle -->
            <g transform="translate(180, 80)">
                <ellipse cx="120" cy="440" rx="90" ry="16" fill="#000000" opacity="0.6"/>
                <!-- Cork Seal -->
                <rect x="105" y="15" width="30" height="25" rx="4" fill="#b91c1c"/>
                <circle cx="120" cy="27" r="8" fill="#f59e0b"/>
                <!-- Long Clear Neck -->
                <rect x="102" y="40" width="36" height="110" fill="{$bottleColor}" stroke="#a1a1aa" stroke-width="1.5"/>
                <!-- Shoulder -->
                <path d="M102 150 L138 150 L195 210 L45 210 Z" fill="{$bottleColor}" stroke="#a1a1aa" stroke-width="1.5"/>
                <!-- Main Body -->
                <rect x="45" y="210" width="150" height="220" rx="12" fill="{$bottleColor}" stroke="#d4d4d8" stroke-width="2"/>
                <!-- Glass Sheen -->
                <rect x="55" y="215" width="18" height="210" rx="8" fill="#ffffff" opacity="0.25"/>

                <!-- Ceremonial Red Ribbon & Seal -->
                <path d="M120 150 L120 250" stroke="#dc2626" stroke-width="10" stroke-linecap="round"/>
                <circle cx="120" cy="230" r="22" fill="#b91c1c" stroke="#f59e0b" stroke-width="2"/>
                <circle cx="120" cy="230" r="16" fill="#f59e0b"/>

                <!-- Front Label -->
                <rect x="60" y="270" width="120" height="140" rx="10" fill="{$labelBg}" stroke="#f59e0b" stroke-width="2"/>
                <g transform="translate(120, 310)" text-anchor="middle">
                    {$symbolPath}
                    <text y="38" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="16" letter-spacing="1">{$brand}</text>
                    <text y="58" fill="{$labelTextColor}" opacity="0.9" font-family="system-ui, sans-serif" font-weight="700" font-size="8" letter-spacing="1">{$subText}</text>
                </g>
            </g>
SVG;
        } elseif ($type === 'bottle_spirits') {
            $containerGraphics = <<<SVG
            <!-- Spirits (Cognac / Whiskey / Rum / Gin) Bottle -->
            <g transform="translate(175, 75)">
                <ellipse cx="125" cy="445" rx="95" ry="18" fill="#000000" opacity="0.6"/>
                <!-- Metallic Cap & Collar -->
                <rect x="100" y="20" width="50" height="35" rx="6" fill="#ca8a04" stroke="#fef08a" stroke-width="1.5"/>
                <rect x="98" y="55" width="54" height="20" fill="#18181b"/>
                <!-- Tapered Neck -->
                <path d="M104 75 L146 75 L158 150 L92 150 Z" fill="{$bottleColor}"/>
                <!-- Broad Shoulders -->
                <path d="M92 150 L158 150 L205 200 L45 200 Z" fill="{$bottleColor}" stroke="#3f3f46" stroke-width="1.5"/>
                <!-- Main Body -->
                <rect x="45" y="200" width="160" height="235" rx="16" fill="{$bottleColor}" stroke="#52525b" stroke-width="2"/>
                <rect x="58" y="205" width="22" height="220" rx="10" fill="#ffffff" opacity="0.15"/>

                <!-- Main Label Frame -->
                <rect x="60" y="240" width="130" height="170" rx="12" fill="{$labelBg}" stroke="#eab308" stroke-width="2"/>
                <g transform="translate(125, 290)" text-anchor="middle">
                    {$symbolPath}
                    <text y="42" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="18" letter-spacing="1">{$brand}</text>
                    <text y="64" fill="{$labelTextColor}" opacity="0.85" font-family="system-ui, sans-serif" font-weight="700" font-size="9" letter-spacing="1.5">{$subText}</text>
                </g>
            </g>
SVG;
        } elseif ($type === 'bottle_wine') {
            $containerGraphics = <<<SVG
            <!-- Wine Bottle Silhouette -->
            <g transform="translate(185, 65)">
                <ellipse cx="115" cy="460" rx="80" ry="16" fill="#000000" opacity="0.6"/>
                <!-- Foil Capsule -->
                <rect x="98" y="15" width="34" height="60" rx="4" fill="{$labelBg}" stroke="#eab308" stroke-width="1"/>
                <!-- Long Neck -->
                <rect x="98" y="75" width="34" height="120" fill="{$bottleColor}"/>
                <!-- Smooth Curved Shoulder -->
                <path d="M98 195 C98 230 45 245 45 270 L45 450 L185 450 L185 270 C185 245 132 230 132 195 Z" fill="{$bottleColor}" stroke="#27272a" stroke-width="2"/>
                <!-- Glass Sheen -->
                <path d="M55 275 Q115 285 175 275 L175 440 L55 440 Z" fill="#ffffff" opacity="0.04"/>
                <rect x="55" y="275" width="18" height="165" rx="8" fill="#ffffff" opacity="0.15"/>

                <!-- Wine Body Label -->
                <rect x="62" y="295" width="106" height="135" rx="8" fill="{$labelBg}" stroke="#eab308" stroke-width="1.5"/>
                <g transform="translate(115, 340)" text-anchor="middle">
                    {$symbolPath}
                    <text y="38" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="15" letter-spacing="0.5">{$brand}</text>
                    <text y="58" fill="{$labelTextColor}" opacity="0.9" font-family="system-ui, sans-serif" font-weight="700" font-size="8" letter-spacing="1">{$subText}</text>
                </g>
            </g>
SVG;
        } elseif ($type === 'bottle_pet') {
            $containerGraphics = <<<SVG
            <!-- PET Soft Drink / Energy Bottle -->
            <g transform="translate(180, 80)">
                <ellipse cx="120" cy="440" rx="85" ry="16" fill="#000000" opacity="0.6"/>
                <!-- Plastic Cap -->
                <rect x="96" y="20" width="48" height="28" rx="6" fill="{$labelBg}" stroke="#ffffff" stroke-width="1.5"/>
                <!-- Neck & Shoulder -->
                <path d="M98 48 L142 48 L175 110 L65 110 Z" fill="{$bottleColor}"/>
                <!-- Ribbed PET Body -->
                <rect x="65" y="110" width="110" height="315" rx="24" fill="{$bottleColor}" stroke="#3f3f46" stroke-width="2"/>
                <rect x="75" y="115" width="16" height="305" rx="8" fill="#ffffff" opacity="0.18"/>

                <!-- Wrap Label -->
                <rect x="65" y="180" width="110" height="170" fill="{$labelBg}"/>
                <g transform="translate(120, 240)" text-anchor="middle">
                    {$symbolPath}
                    <text y="40" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="16" letter-spacing="0.5">{$brand}</text>
                    <text y="60" fill="{$labelTextColor}" opacity="0.9" font-family="system-ui, sans-serif" font-weight="700" font-size="8" letter-spacing="1">{$subText}</text>
                </g>
            </g>
SVG;
        } elseif ($type === 'bundle') {
            $containerGraphics = <<<SVG
            <!-- Party Combo Bundle Visual -->
            <g transform="translate(120, 100)">
                <ellipse cx="180" cy="410" rx="160" ry="24" fill="#000000" opacity="0.7"/>
                
                <!-- Background Glow Box -->
                <rect x="20" y="80" width="320" height="310" rx="32" fill="{$bottleColor}" stroke="{$labelBg}" stroke-width="3"/>
                
                <!-- Party Confetti Elements -->
                <circle cx="60" cy="120" r="8" fill="#f59e0b"/>
                <circle cx="300" cy="140" r="10" fill="#ef4444"/>
                <rect x="280" y="100" width="12" height="12" rx="3" fill="#3b82f6" transform="rotate(25 280 100)"/>
                <rect x="50" y="340" width="14" height="14" rx="4" fill="#10b981" transform="rotate(40 50 340)"/>
                
                <!-- Front Bundle Platter Badge -->
                <rect x="40" y="150" width="280" height="180" rx="24" fill="{$labelBg}" stroke="#fef08a" stroke-width="3"/>
                
                <g transform="translate(180, 210)" text-anchor="middle">
                    {$symbolPath}
                    <text y="48" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="22" letter-spacing="1">{$brand}</text>
                    <text y="75" fill="{$labelTextColor}" opacity="0.9" font-family="system-ui, sans-serif" font-weight="800" font-size="11" letter-spacing="1.5">{$subText}</text>
                </g>
            </g>
SVG;
        } else {
            // Default Lager Bottle
            $containerGraphics = <<<SVG
            <!-- Standard 600ml Lager Bottle -->
            <g transform="translate(180, 75)">
                <ellipse cx="120" cy="445" rx="85" ry="16" fill="#000000" opacity="0.6"/>
                <!-- Gold Crown Cap -->
                <rect x="100" y="15" width="40" height="22" rx="4" fill="#f59e0b" stroke="#ca8a04" stroke-width="2"/>
                <!-- Neck Foil Wrapper -->
                <path d="M102 37 L138 37 L145 110 L95 110 Z" fill="{$labelBg}" stroke="#f59e0b" stroke-width="1.5"/>
                <!-- Glass Bottle Body -->
                <path d="M95 110 L145 110 L190 170 L50 170 Z" fill="{$bottleColor}" stroke="#3f3f46" stroke-width="1.5"/>
                <rect x="50" y="170" width="140" height="260" rx="20" fill="{$bottleColor}" stroke="#3f3f46" stroke-width="2"/>
                <rect x="62" y="175" width="18" height="245" rx="9" fill="#ffffff" opacity="0.14"/>

                <!-- Front Oval Body Label -->
                <rect x="60" y="210" width="120" height="180" rx="20" fill="{$labelBg}" stroke="#f59e0b" stroke-width="2.5"/>
                <g transform="translate(120, 265)" text-anchor="middle">
                    {$symbolPath}
                    <text y="44" fill="{$labelTextColor}" font-family="system-ui, sans-serif" font-weight="900" font-size="19" letter-spacing="1">{$brand}</text>
                    <text y="68" fill="{$labelTextColor}" opacity="0.9" font-family="system-ui, sans-serif" font-weight="700" font-size="9" letter-spacing="1.5">{$subText}</text>
                </g>
            </g>
SVG;
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" width="100%" height="100%">
    <defs>
        <!-- Background Radial Ambient Glow -->
        <radialGradient id="bgGlow_{$slug}" cx="50%" cy="45%" r="60%">
            <stop offset="0%" stop-color="{$bgColor}" stop-opacity="0.30"/>
            <stop offset="60%" stop-color="{$bgColor}" stop-opacity="0.08"/>
            <stop offset="100%" stop-color="#0b0b0e" stop-opacity="1"/>
        </radialGradient>
        <linearGradient id="cardBg" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#141418"/>
            <stop offset="100%" stop-color="#09090c"/>
        </linearGradient>
    </defs>

    <!-- Canvas Background -->
    <rect width="600" height="600" fill="url(#cardBg)"/>
    <rect width="600" height="600" fill="url(#bgGlow_{$slug})"/>

    <!-- Subtle Background Grid Pattern -->
    <g opacity="0.04" stroke="#ffffff" stroke-width="1">
        <line x1="0" y1="150" x2="600" y2="150"/>
        <line x1="0" y1="300" x2="600" y2="300"/>
        <line x1="0" y1="450" x2="600" y2="450"/>
        <line x1="150" y1="0" x2="150" y2="600"/>
        <line x1="300" y1="0" x2="300" y2="600"/>
        <line x1="450" y1="0" x2="450" y2="600"/>
    </g>

    {$containerGraphics}

    <!-- Top Badge Volume Badge -->
    <g transform="translate(560, 40)" text-anchor="end">
        <rect x="-110" y="0" width="110" height="32" rx="16" fill="#18181b" stroke="#3f3f46" stroke-width="1.5"/>
        <text x="-55" y="21" text-anchor="middle" fill="#f4f4f5" font-family="system-ui, sans-serif" font-weight="800" font-size="12">{$volume}</text>
    </g>

    <!-- Bottom Authentic Brand Tag -->
    <g transform="translate(300, 560)" text-anchor="middle">
        <text y="0" fill="#a1a1aa" font-family="system-ui, sans-serif" font-weight="700" font-size="13" letter-spacing="3">AUTHENTIC NIGERIAN DRINKS</text>
    </g>
</svg>
SVG;
    }

    private static function getSymbolPath(string $symbol, string $color): string
    {
        switch ($symbol) {
            case 'crown':
                return <<<SVG
                <path d="M-18 -10 L-10 10 L0 -5 L10 10 L18 -10 L12 18 L-12 18 Z" fill="{$color}"/>
SVG;
            case 'harp':
                return <<<SVG
                <path d="M-12 -15 Q15 -15 12 15 L-6 15 L-12 -15 M-4 -8 L4 12 M0 -8 L8 12" stroke="{$color}" stroke-width="2.5" fill="none"/>
SVG;
            case 'star':
                return <<<SVG
                <polygon points="0,-18 5,-5 18,-5 8,3 12,16 0,8 -12,16 -8,3 -18,-5 -5,-5" fill="{$color}"/>
SVG;
            case 'red_star':
                return <<<SVG
                <polygon points="0,-18 5,-5 18,-5 8,3 12,16 0,8 -12,16 -8,3 -18,-5 -5,-5" fill="#ef4444" stroke="#ffffff" stroke-width="1.5"/>
SVG;
            case 'lion':
                return <<<SVG
                <circle cx="0" cy="0" r="16" fill="{$color}" opacity="0.2"/>
                <path d="M-8 -10 Q0 -18 8 -10 Q14 0 8 10 Q0 16 -8 10 Z" fill="{$color}"/>
SVG;
            case 'leaf':
                return <<<SVG
                <path d="M0 -18 Q16 0 0 18 Q-16 0 0 -18 Z" fill="{$color}"/>
                <line x1="0" y1="-12" x2="0" y2="12" stroke="#000000" opacity="0.3" stroke-width="2"/>
SVG;
            case 'sombrero':
                return <<<SVG
                <ellipse cx="0" cy="8" rx="22" ry="6" fill="{$color}"/>
                <path d="M-10 8 C-10 -12 10 -12 10 8 Z" fill="{$color}"/>
SVG;
            case 'grapes':
                return <<<SVG
                <circle cx="-6" cy="-8" r="5" fill="{$color}"/>
                <circle cx="6" cy="-8" r="5" fill="{$color}"/>
                <circle cx="0" cy="-2" r="5" fill="{$color}"/>
                <circle cx="-6" cy="4" r="5" fill="{$color}"/>
                <circle cx="6" cy="4" r="5" fill="{$color}"/>
                <circle cx="0" cy="10" r="5" fill="{$color}"/>
SVG;
            case 'walking_man':
                return <<<SVG
                <circle cx="0" cy="-14" r="5" fill="{$color}"/>
                <path d="M-4 -6 L4 -6 L8 6 L0 18 L-6 8" stroke="{$color}" stroke-width="3.5" stroke-linecap="round" fill="none"/>
SVG;
            case 'lightning':
                return <<<SVG
                <polygon points="-4,-18 6,-4 0,-4 4,16 -6,2 0,2" fill="{$color}"/>
SVG;
            case 'wings':
                return <<<SVG
                <path d="M-18 0 Q-8 -14 0 0 Q8 -14 18 0 Q0 12 -18 0 Z" fill="{$color}"/>
SVG;
            case 'claw':
                return <<<SVG
                <path d="M-12 -15 L-6 15 M0 -18 L0 18 M12 -15 L6 15" stroke="{$color}" stroke-width="4" stroke-linecap="round"/>
SVG;
            case 'wave':
                return <<<SVG
                <path d="M-16 0 Q-8 -12 0 0 Q8 12 16 0" stroke="{$color}" stroke-width="4" stroke-linecap="round" fill="none"/>
SVG;
            case 'water_drop':
                return <<<SVG
                <path d="M0 -16 C12 0 12 14 0 14 C-12 14 -12 0 0 -16 Z" fill="{$color}"/>
SVG;
            case 'smile':
                return <<<SVG
                <circle cx="0" cy="0" r="16" fill="{$color}"/>
                <circle cx="-5" cy="-4" r="2.5" fill="#000"/>
                <circle cx="5" cy="-4" r="2.5" fill="#000"/>
                <path d="M-8 3 Q0 12 8 3" stroke="#000" stroke-width="2.5" stroke-linecap="round" fill="none"/>
SVG;
            case 'bundle_party':
                return <<<SVG
                <text x="0" y="8" font-size="28" text-anchor="middle">🎉</text>
SVG;
            case 'shield':
            default:
                return <<<SVG
                <path d="M-14 -14 L14 -14 L14 2 C14 12 0 18 0 18 C0 18 -14 12 -14 2 Z" fill="{$color}"/>
SVG;
        }
    }

    public static function getDefinitions(): array
    {
        return [
            // BEERS & MALTS
            ['slug' => 'trophy-lager', 'brand_name' => 'TROPHY', 'sub_text' => 'EXTRA QUALITY LAGER', 'type' => 'bottle_lager', 'bg_color' => '#f59e0b', 'bottle_color' => '#2b1704', 'label_bg' => '#b91c1c', 'label_text_color' => '#fef08a', 'badge_symbol' => 'crown', 'volume' => '600ml'],
            ['slug' => 'star-lager', 'brand_name' => 'STAR', 'sub_text' => 'LAGER BEER', 'type' => 'bottle_lager', 'bg_color' => '#2563eb', 'bottle_color' => '#1c1917', 'label_bg' => '#1d4ed8', 'label_text_color' => '#facc15', 'badge_symbol' => 'star', 'volume' => '600ml'],
            ['slug' => 'guinness-stout', 'brand_name' => 'GUINNESS', 'sub_text' => 'FOREIGN EXTRA STOUT', 'type' => 'bottle_stout', 'bg_color' => '#eab308', 'bottle_color' => '#09090b', 'label_bg' => '#18181b', 'label_text_color' => '#fef08a', 'badge_symbol' => 'harp', 'volume' => '600ml'],
            ['slug' => 'goldberg-lager', 'brand_name' => 'GOLDBERG', 'sub_text' => 'PREMIUM LAGER BEER', 'type' => 'bottle_lager', 'bg_color' => '#d97706', 'bottle_color' => '#291500', 'label_bg' => '#b45309', 'label_text_color' => '#fef08a', 'badge_symbol' => 'shield', 'volume' => '600ml'],
            ['slug' => 'hero-lager', 'brand_name' => 'HERO', 'sub_text' => 'PREMIUM LAGER', 'type' => 'bottle_lager', 'bg_color' => '#dc2626', 'bottle_color' => '#1c1917', 'label_bg' => '#991b1b', 'label_text_color' => '#fbbf24', 'badge_symbol' => 'lion', 'volume' => '600ml'],
            ['slug' => 'life-lager', 'brand_name' => 'LIFE', 'sub_text' => 'CONTINENTAL LAGER', 'type' => 'bottle_lager', 'bg_color' => '#0284c7', 'bottle_color' => '#1c1917', 'label_bg' => '#0369a1', 'label_text_color' => '#facc15', 'badge_symbol' => 'shield', 'volume' => '600ml'],
            ['slug' => 'gulder-lager', 'brand_name' => 'GULDER', 'sub_text' => 'ULTIMATE LAGER', 'type' => 'bottle_lager', 'bg_color' => '#991b1b', 'bottle_color' => '#261300', 'label_bg' => '#7f1d1d', 'label_text_color' => '#fbbf24', 'badge_symbol' => 'shield', 'volume' => '600ml'],
            ['slug' => 'legend-stout', 'brand_name' => 'LEGEND', 'sub_text' => 'EXTRA STOUT', 'type' => 'bottle_stout', 'bg_color' => '#ea580c', 'bottle_color' => '#09090b', 'label_bg' => '#27272a', 'label_text_color' => '#f97316', 'badge_symbol' => 'lightning', 'volume' => '600ml'],
            ['slug' => 'heineken-lager', 'brand_name' => 'HEINEKEN', 'sub_text' => 'PREMIUM QUALITY', 'type' => 'bottle_lager', 'bg_color' => '#16a34a', 'bottle_color' => '#052e16', 'label_bg' => '#15803d', 'label_text_color' => '#ffffff', 'badge_symbol' => 'red_star', 'volume' => '600ml'],
            ['slug' => 'budweiser-lager', 'brand_name' => 'BUDWEISER', 'sub_text' => 'KING OF BEERS', 'type' => 'bottle_lager', 'bg_color' => '#dc2626', 'bottle_color' => '#291500', 'label_bg' => '#b91c1c', 'label_text_color' => '#ffffff', 'badge_symbol' => 'crown', 'volume' => '600ml'],
            ['slug' => 'orijin-beer', 'brand_name' => 'ORIJIN', 'sub_text' => 'HERBAL BEER', 'type' => 'bottle_lager', 'bg_color' => '#15803d', 'bottle_color' => '#064e3b', 'label_bg' => '#166534', 'label_text_color' => '#facc15', 'badge_symbol' => 'leaf', 'volume' => '600ml'],
            ['slug' => 'smirnoff-ice-can', 'brand_name' => 'SMIRNOFF', 'sub_text' => 'ICE DOUBLE BLACK', 'type' => 'can', 'bg_color' => '#e11d48', 'bottle_color' => '#18181b', 'label_bg' => '#09090b', 'label_text_color' => '#f43f5e', 'badge_symbol' => 'crown', 'volume' => '330ml Can'],
            ['slug' => 'desperados-beer', 'brand_name' => 'DESPERADOS', 'sub_text' => 'TEQUILA FLAVOURED', 'type' => 'bottle_lager', 'bg_color' => '#eab308', 'bottle_color' => '#fef08a', 'label_bg' => '#ca8a04', 'label_text_color' => '#15803d', 'badge_symbol' => 'sombrero', 'volume' => '330ml'],
            ['slug' => 'flying-fish-beer', 'brand_name' => 'FLYING FISH', 'sub_text' => 'PASSION FRUIT LAGER', 'type' => 'bottle_lager', 'bg_color' => '#06b6d4', 'bottle_color' => '#ecfeff', 'label_bg' => '#0891b2', 'label_text_color' => '#facc15', 'badge_symbol' => 'wave', 'volume' => '330ml'],
            ['slug' => 'trophy-stout', 'brand_name' => 'TROPHY', 'sub_text' => 'EXTRA STOUT', 'type' => 'bottle_stout', 'bg_color' => '#dc2626', 'bottle_color' => '#09090b', 'label_bg' => '#7f1d1d', 'label_text_color' => '#f59e0b', 'badge_symbol' => 'crown', 'volume' => '600ml'],
            ['slug' => 'malta-guinness-can', 'brand_name' => 'MALTA GUINNESS', 'sub_text' => 'NOURISHING MALT', 'type' => 'can', 'bg_color' => '#d97706', 'bottle_color' => '#451a03', 'label_bg' => '#b45309', 'label_text_color' => '#fef08a', 'badge_symbol' => 'harp', 'volume' => '330ml Can'],
            ['slug' => 'amstel-malta-can', 'brand_name' => 'AMSTEL MALTA', 'sub_text' => 'ULTRA LOW SUGAR', 'type' => 'can', 'bg_color' => '#ef4444', 'bottle_color' => '#f8fafc', 'label_bg' => '#ffffff', 'label_text_color' => '#dc2626', 'badge_symbol' => 'shield', 'volume' => '330ml Can'],
            ['slug' => 'maltina-can', 'brand_name' => 'MALTINA', 'sub_text' => 'SHARING HAPPINESS', 'type' => 'can', 'bg_color' => '#eab308', 'bottle_color' => '#facc15', 'label_bg' => '#eab308', 'label_text_color' => '#1e3a8a', 'badge_symbol' => 'smile', 'volume' => '330ml Can'],
            ['slug' => 'dubic-malt-can', 'brand_name' => 'DUBIC MALT', 'sub_text' => 'WHOLESOME MALT', 'type' => 'can', 'bg_color' => '#b45309', 'bottle_color' => '#78350f', 'label_bg' => '#92400e', 'label_text_color' => '#fef08a', 'badge_symbol' => 'shield', 'volume' => '330ml Can'],
            ['slug' => 'grand-malt-can', 'brand_name' => 'GRAND MALT', 'sub_text' => 'ENERGY MALT DRINK', 'type' => 'can', 'bg_color' => '#b91c1c', 'bottle_color' => '#7f1d1d', 'label_bg' => '#991b1b', 'label_text_color' => '#facc15', 'badge_symbol' => 'crown', 'volume' => '330ml Can'],

            // SPIRITS & BITTERS
            ['slug' => 'orijin-bitters', 'brand_name' => 'ORIJIN', 'sub_text' => 'HERBAL BITTERS', 'type' => 'flask_bitters', 'bg_color' => '#166534', 'bottle_color' => '#052e16', 'label_bg' => '#14532d', 'label_text_color' => '#facc15', 'badge_symbol' => 'leaf', 'volume' => '75cl'],
            ['slug' => 'action-bitters', 'brand_name' => 'ACTION', 'sub_text' => 'HERBAL BITTERS', 'type' => 'flask_bitters', 'bg_color' => '#ea580c', 'bottle_color' => '#431407', 'label_bg' => '#c2410c', 'label_text_color' => '#ffffff', 'badge_symbol' => 'lightning', 'volume' => '75cl'],
            ['slug' => 'alomo-bitters', 'brand_name' => 'ALOMO', 'sub_text' => 'BITTERS', 'type' => 'flask_bitters', 'bg_color' => '#eab308', 'bottle_color' => '#2e1005', 'label_bg' => '#ca8a04', 'label_text_color' => '#991b1b', 'badge_symbol' => 'leaf', 'volume' => '75cl'],
            ['slug' => 'odogwu-bitters', 'brand_name' => 'ODOGWU', 'sub_text' => 'HERBAL BITTERS', 'type' => 'flask_bitters', 'bg_color' => '#eab308', 'bottle_color' => '#09090b', 'label_bg' => '#18181b', 'label_text_color' => '#eab308', 'badge_symbol' => 'lion', 'volume' => '75cl'],
            ['slug' => 'seamans-schnapps', 'brand_name' => 'SEAMAN\'S', 'sub_text' => 'AROMATIC SCHNAPPS', 'type' => 'bottle_schnapps', 'bg_color' => '#38bdf8', 'bottle_color' => '#f8fafc', 'label_bg' => '#dc2626', 'label_text_color' => '#ffffff', 'badge_symbol' => 'crown', 'volume' => '75cl'],
            ['slug' => 'best-dark-rum', 'brand_name' => 'BEST', 'sub_text' => 'DARK RUM', 'type' => 'bottle_spirits', 'bg_color' => '#92400e', 'bottle_color' => '#451a03', 'label_bg' => '#78350f', 'label_text_color' => '#facc15', 'badge_symbol' => 'shield', 'volume' => '75cl'],
            ['slug' => 'chelsea-dry-gin', 'brand_name' => 'CHELSEA', 'sub_text' => 'DRY GIN', 'type' => 'bottle_spirits', 'bg_color' => '#1e40af', 'bottle_color' => '#eff6ff', 'label_bg' => '#1d4ed8', 'label_text_color' => '#ffffff', 'badge_symbol' => 'shield', 'volume' => '75cl'],
            ['slug' => 'lords-dry-gin', 'brand_name' => 'LORD\'S', 'sub_text' => 'DRY GIN', 'type' => 'bottle_spirits', 'bg_color' => '#4338ca', 'bottle_color' => '#eef2ff', 'label_bg' => '#3730a3', 'label_text_color' => '#facc15', 'badge_symbol' => 'crown', 'volume' => '75cl'],
            ['slug' => 'squadron-dark-rum', 'brand_name' => 'SQUADRON', 'sub_text' => 'DARK RUM', 'type' => 'bottle_spirits', 'bg_color' => '#78350f', 'bottle_color' => '#3b0764', 'label_bg' => '#581c87', 'label_text_color' => '#facc15', 'badge_symbol' => 'wave', 'volume' => '75cl'],
            ['slug' => 'campari-aperitif', 'brand_name' => 'CAMPARI', 'sub_text' => 'BITTER APERITIF', 'type' => 'bottle_spirits', 'bg_color' => '#ef4444', 'bottle_color' => '#dc2626', 'label_bg' => '#ffffff', 'label_text_color' => '#b91c1c', 'badge_symbol' => 'shield', 'volume' => '75cl'],
            ['slug' => 'hennessy-cognac', 'brand_name' => 'HENNESSY', 'sub_text' => 'VERY SPECIAL COGNAC', 'type' => 'bottle_spirits', 'bg_color' => '#f59e0b', 'bottle_color' => '#451a03', 'label_bg' => '#18181b', 'label_text_color' => '#f59e0b', 'badge_symbol' => 'grapes', 'volume' => '70cl'],
            ['slug' => 'martell-cognac', 'brand_name' => 'MARTELL', 'sub_text' => 'SINGLE DISTILLERY', 'type' => 'bottle_spirits', 'bg_color' => '#1e3a8a', 'bottle_color' => '#312e81', 'label_bg' => '#1e3a8a', 'label_text_color' => '#facc15', 'badge_symbol' => 'wings', 'volume' => '70cl'],
            ['slug' => 'jameson-whiskey', 'brand_name' => 'JAMESON', 'sub_text' => 'IRISH WHISKEY', 'type' => 'bottle_spirits', 'bg_color' => '#047857', 'bottle_color' => '#064e3b', 'label_bg' => '#fef3c7', 'label_text_color' => '#064e3b', 'badge_symbol' => 'shield', 'volume' => '70cl'],
            ['slug' => 'jack-daniels-whiskey', 'brand_name' => 'JACK DANIEL\'S', 'sub_text' => 'OLD NO. 7 TENNESSEE', 'type' => 'bottle_spirits', 'bg_color' => '#eab308', 'bottle_color' => '#09090b', 'label_bg' => '#18181b', 'label_text_color' => '#ffffff', 'badge_symbol' => 'shield', 'volume' => '70cl'],
            ['slug' => 'johnnie-walker-black', 'brand_name' => 'JOHNNIE WALKER', 'sub_text' => 'BLACK LABEL 12Y', 'type' => 'bottle_spirits', 'bg_color' => '#eab308', 'bottle_color' => '#09090b', 'label_bg' => '#18181b', 'label_text_color' => '#eab308', 'badge_symbol' => 'walking_man', 'volume' => '75cl'],
            ['slug' => 'johnnie-walker-red', 'brand_name' => 'JOHNNIE WALKER', 'sub_text' => 'RED LABEL', 'type' => 'bottle_spirits', 'bg_color' => '#ef4444', 'bottle_color' => '#1c1917', 'label_bg' => '#991b1b', 'label_text_color' => '#ffffff', 'badge_symbol' => 'walking_man', 'volume' => '75cl'],
            ['slug' => 'mcdowells-whiskey', 'brand_name' => 'MCDOWELL\'S', 'sub_text' => 'NO.1 LUXURY WHISKEY', 'type' => 'bottle_spirits', 'bg_color' => '#d97706', 'bottle_color' => '#451a03', 'label_bg' => '#b45309', 'label_text_color' => '#fef08a', 'badge_symbol' => 'shield', 'volume' => '75cl'],
            ['slug' => 'baileys-cream', 'brand_name' => 'BAILEYS', 'sub_text' => 'IRISH CREAM', 'type' => 'bottle_spirits', 'bg_color' => '#059669', 'bottle_color' => '#0f172a', 'label_bg' => '#065f46', 'label_text_color' => '#fef08a', 'badge_symbol' => 'leaf', 'volume' => '75cl'],
            ['slug' => 'sierra-tequila', 'brand_name' => 'SIERRA', 'sub_text' => 'TEQUILA REPOSADO', 'type' => 'bottle_spirits', 'bg_color' => '#ef4444', 'bottle_color' => '#ca8a04', 'label_bg' => '#dc2626', 'label_text_color' => '#fef08a', 'badge_symbol' => 'sombrero', 'volume' => '70cl'],
            ['slug' => 'smirnoff-vodka', 'brand_name' => 'SMIRNOFF', 'sub_text' => 'NO. 21 VODKA', 'type' => 'bottle_spirits', 'bg_color' => '#ef4444', 'bottle_color' => '#f8fafc', 'label_bg' => '#dc2626', 'label_text_color' => '#ffffff', 'badge_symbol' => 'crown', 'volume' => '75cl'],

            // WINES
            ['slug' => 'carlo-rossi-sweet-red', 'brand_name' => 'CARLO ROSSI', 'sub_text' => 'SWEET RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#9f1239', 'bottle_color' => '#4c0519', 'label_bg' => '#881337', 'label_text_color' => '#fecdd3', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'four-cousins-red', 'brand_name' => 'FOUR COUSINS', 'sub_text' => 'SWEET RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#be123c', 'bottle_color' => '#4c0519', 'label_bg' => '#9f1239', 'label_text_color' => '#ffffff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => '4th-street-red', 'brand_name' => '4TH STREET', 'sub_text' => 'SWEET RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#7e22ce', 'bottle_color' => '#3b0764', 'label_bg' => '#6b21a8', 'label_text_color' => '#f3e8ff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'baron-de-valls', 'brand_name' => 'BARON DE VALLS', 'sub_text' => 'RED TABLE WINE', 'type' => 'bottle_wine', 'bg_color' => '#b91c1c', 'bottle_color' => '#450a0a', 'label_bg' => '#991b1b', 'label_text_color' => '#fef08a', 'badge_symbol' => 'shield', 'volume' => '75cl'],
            ['slug' => 'veuve-du-vernay', 'brand_name' => 'VEUVE DU VERNAY', 'sub_text' => 'ICE SPARKLING WINE', 'type' => 'bottle_wine', 'bg_color' => '#38bdf8', 'bottle_color' => '#f8fafc', 'label_bg' => '#0284c7', 'label_text_color' => '#ffffff', 'badge_symbol' => 'star', 'volume' => '75cl'],
            ['slug' => 'andre-pink-moscato', 'brand_name' => 'ANDRE', 'sub_text' => 'SPARKLING PINK MOSCATO', 'type' => 'bottle_wine', 'bg_color' => '#f43f5e', 'bottle_color' => '#ffe4e6', 'label_bg' => '#e11d48', 'label_text_color' => '#ffffff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'chamdor-sparkling', 'brand_name' => 'CHAMDOR', 'sub_text' => 'SPARKLING GRAPE DRINK', 'type' => 'bottle_wine', 'bg_color' => '#eab308', 'bottle_color' => '#14532d', 'label_bg' => '#ca8a04', 'label_text_color' => '#ffffff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'agor-red-wine', 'brand_name' => 'AGOR', 'sub_text' => 'SACRAMENTAL RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#991b1b', 'bottle_color' => '#450a0a', 'label_bg' => '#7f1d1d', 'label_text_color' => '#fef08a', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'robertson-sweet-red', 'brand_name' => 'ROBERTSON', 'sub_text' => 'SWEET RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#15803d', 'bottle_color' => '#450a0a', 'label_bg' => '#166534', 'label_text_color' => '#fef08a', 'badge_symbol' => 'leaf', 'volume' => '75cl'],
            ['slug' => 'jp-chenet-red', 'brand_name' => 'JP CHENET', 'sub_text' => 'MEDIUM SWEET RED', 'type' => 'bottle_wine', 'bg_color' => '#b91c1c', 'bottle_color' => '#450a0a', 'label_bg' => '#991b1b', 'label_text_color' => '#ffffff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'frontera-red', 'brand_name' => 'FRONTERA', 'sub_text' => 'CABERNET SAUVIGNON', 'type' => 'bottle_wine', 'bg_color' => '#9a3412', 'bottle_color' => '#450a0a', 'label_bg' => '#7c2d12', 'label_text_color' => '#ffedd5', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'tall-horse-merlot', 'brand_name' => 'TALL HORSE', 'sub_text' => 'MERLOT RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#c026d3', 'bottle_color' => '#450a0a', 'label_bg' => '#a21caf', 'label_text_color' => '#fae8ff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'saint-celine-red', 'brand_name' => 'SAINT CELINE', 'sub_text' => 'SWEET RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#991b1b', 'bottle_color' => '#450a0a', 'label_bg' => '#7f1d1d', 'label_text_color' => '#ffffff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'eva-sparkling', 'brand_name' => 'EVA', 'sub_text' => 'SPARKLING FRUIT WINE', 'type' => 'bottle_wine', 'bg_color' => '#dc2626', 'bottle_color' => '#450a0a', 'label_bg' => '#b91c1c', 'label_text_color' => '#ffffff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'casteller-cava', 'brand_name' => 'CASTELLER', 'sub_text' => 'CAVA BRUT SPARKLING', 'type' => 'bottle_wine', 'bg_color' => '#ca8a04', 'bottle_color' => '#fef08a', 'label_bg' => '#a16207', 'label_text_color' => '#ffffff', 'badge_symbol' => 'crown', 'volume' => '75cl'],
            ['slug' => 'don-morris-red', 'brand_name' => 'DON MORRIS', 'sub_text' => 'SWEET RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#991b1b', 'bottle_color' => '#450a0a', 'label_bg' => '#7f1d1d', 'label_text_color' => '#fef08a', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'martinellis-cider', 'brand_name' => 'MARTINELLI\'S', 'sub_text' => 'GOLD MEDAL CIDER', 'type' => 'bottle_wine', 'bg_color' => '#eab308', 'bottle_color' => '#fef08a', 'label_bg' => '#ca8a04', 'label_text_color' => '#ffffff', 'badge_symbol' => 'star', 'volume' => '75cl'],
            ['slug' => 'jw-sparkling', 'brand_name' => 'J&W', 'sub_text' => 'SPARKLING WHITE GRAPE', 'type' => 'bottle_wine', 'bg_color' => '#facc15', 'bottle_color' => '#fef08a', 'label_bg' => '#eab308', 'label_text_color' => '#1e3a8a', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'nederburg-pinotage', 'brand_name' => 'NEDERBURG', 'sub_text' => 'PINOTAGE RED WINE', 'type' => 'bottle_wine', 'bg_color' => '#991b1b', 'bottle_color' => '#450a0a', 'label_bg' => '#7f1d1d', 'label_text_color' => '#fef08a', 'badge_symbol' => 'grapes', 'volume' => '75cl'],
            ['slug' => 'carlo-rossi-moscato', 'brand_name' => 'CARLO ROSSI', 'sub_text' => 'MOSCATO WHITE WINE', 'type' => 'bottle_wine', 'bg_color' => '#eab308', 'bottle_color' => '#fef08a', 'label_bg' => '#ca8a04', 'label_text_color' => '#ffffff', 'badge_symbol' => 'grapes', 'volume' => '75cl'],

            // MIXERS & SOFT DRINKS & WATER
            ['slug' => 'coca-cola-pet', 'brand_name' => 'COCA-COLA', 'sub_text' => 'ORIGINAL TASTE', 'type' => 'bottle_pet', 'bg_color' => '#dc2626', 'bottle_color' => '#7f1d1d', 'label_bg' => '#b91c1c', 'label_text_color' => '#ffffff', 'badge_symbol' => 'wave', 'volume' => '50cl PET'],
            ['slug' => 'pepsi-cola-pet', 'brand_name' => 'PEPSI', 'sub_text' => 'COLA BEVERAGE', 'type' => 'bottle_pet', 'bg_color' => '#2563eb', 'bottle_color' => '#1e3a8a', 'label_bg' => '#1d4ed8', 'label_text_color' => '#ffffff', 'badge_symbol' => 'wave', 'volume' => '50cl PET'],
            ['slug' => 'sprite-pet', 'brand_name' => 'SPRITE', 'sub_text' => 'LEMON-LIME SODA', 'type' => 'bottle_pet', 'bg_color' => '#16a34a', 'bottle_color' => '#15803d', 'label_bg' => '#166534', 'label_text_color' => '#facc15', 'badge_symbol' => 'star', 'volume' => '50cl PET'],
            ['slug' => 'fanta-orange-pet', 'brand_name' => 'FANTA', 'sub_text' => 'ORANGE SODA', 'type' => 'bottle_pet', 'bg_color' => '#ea580c', 'bottle_color' => '#c2410c', 'label_bg' => '#d97706', 'label_text_color' => '#ffffff', 'badge_symbol' => 'leaf', 'volume' => '50cl PET'],
            ['slug' => 'schweppes-tonic-can', 'brand_name' => 'SCHWEPPES', 'sub_text' => 'INDIAN TONIC WATER', 'type' => 'can', 'bg_color' => '#eab308', 'bottle_color' => '#fef08a', 'label_bg' => '#ca8a04', 'label_text_color' => '#09090b', 'badge_symbol' => 'crown', 'volume' => '33cl Can'],
            ['slug' => 'schweppes-lemon-can', 'brand_name' => 'SCHWEPPES', 'sub_text' => 'BITTER LEMON', 'type' => 'can', 'bg_color' => '#84cc16', 'bottle_color' => '#bef264', 'label_bg' => '#65a30d', 'label_text_color' => '#09090b', 'badge_symbol' => 'crown', 'volume' => '33cl Can'],
            ['slug' => 'fearless-red-berry', 'brand_name' => 'FEARLESS', 'sub_text' => 'RED BERRY ENERGY', 'type' => 'bottle_pet', 'bg_color' => '#ef4444', 'bottle_color' => '#991b1b', 'label_bg' => '#7f1d1d', 'label_text_color' => '#fef08a', 'badge_symbol' => 'lightning', 'volume' => '50cl PET'],
            ['slug' => 'fearless-original', 'brand_name' => 'FEARLESS', 'sub_text' => 'ORIGINAL ENERGY', 'type' => 'bottle_pet', 'bg_color' => '#38bdf8', 'bottle_color' => '#0369a1', 'label_bg' => '#0284c7', 'label_text_color' => '#ffffff', 'badge_symbol' => 'lightning', 'volume' => '50cl PET'],
            ['slug' => 'climax-energy-can', 'brand_name' => 'CLIMAX', 'sub_text' => 'HERBAL ENERGY', 'type' => 'can', 'bg_color' => '#eab308', 'bottle_color' => '#09090b', 'label_bg' => '#18181b', 'label_text_color' => '#eab308', 'badge_symbol' => 'lightning', 'volume' => '330ml Can'],
            ['slug' => 'red-bull-can', 'brand_name' => 'RED BULL', 'sub_text' => 'ENERGY DRINK', 'type' => 'can', 'bg_color' => '#2563eb', 'bottle_color' => '#e0f2fe', 'label_bg' => '#1d4ed8', 'label_text_color' => '#ef4444', 'badge_symbol' => 'wings', 'volume' => '250ml Can'],
            ['slug' => 'predator-energy', 'brand_name' => 'PREDATOR', 'sub_text' => 'ENERGY DRINK', 'type' => 'bottle_pet', 'bg_color' => '#22c55e', 'bottle_color' => '#14532d', 'label_bg' => '#166534', 'label_text_color' => '#facc15', 'badge_symbol' => 'lightning', 'volume' => '50cl PET'],
            ['slug' => 'monster-energy-can', 'brand_name' => 'MONSTER', 'sub_text' => 'ENERGY DRINK', 'type' => 'can', 'bg_color' => '#22c55e', 'bottle_color' => '#09090b', 'label_bg' => '#18181b', 'label_text_color' => '#22c55e', 'badge_symbol' => 'claw', 'volume' => '500ml Can'],
            ['slug' => 'teem-soda', 'brand_name' => 'TEEM', 'sub_text' => 'CLUB SODA WATER', 'type' => 'bottle_pet', 'bg_color' => '#38bdf8', 'bottle_color' => '#e0f2fe', 'label_bg' => '#0284c7', 'label_text_color' => '#ffffff', 'badge_symbol' => 'water_drop', 'volume' => '50cl PET'],
            ['slug' => 'zobo-drink', 'brand_name' => 'ZOBO', 'sub_text' => 'NATURAL HIBISCUS', 'type' => 'bottle_pet', 'bg_color' => '#b91c1c', 'bottle_color' => '#450a0a', 'label_bg' => '#881337', 'label_text_color' => '#fef08a', 'badge_symbol' => 'leaf', 'volume' => '50cl PET'],
            ['slug' => 'la-casera-apple', 'brand_name' => 'LA CASERA', 'sub_text' => 'CARBONATED APPLE', 'type' => 'bottle_pet', 'bg_color' => '#84cc16', 'bottle_color' => '#3f6212', 'label_bg' => '#4d7c0f', 'label_text_color' => '#fef08a', 'badge_symbol' => 'leaf', 'volume' => '50cl PET'],
            ['slug' => 'bigi-cola', 'brand_name' => 'BIGI', 'sub_text' => 'COLA BEVERAGE', 'type' => 'bottle_pet', 'bg_color' => '#dc2626', 'bottle_color' => '#450a0a', 'label_bg' => '#991b1b', 'label_text_color' => '#ffffff', 'badge_symbol' => 'wave', 'volume' => '60cl PET'],
            ['slug' => 'aquafina-water', 'brand_name' => 'AQUAFINA', 'sub_text' => 'PURE DRINKING WATER', 'type' => 'bottle_pet', 'bg_color' => '#0284c7', 'bottle_color' => '#f0f9ff', 'label_bg' => '#0369a1', 'label_text_color' => '#ffffff', 'badge_symbol' => 'water_drop', 'volume' => '75cl PET'],
            ['slug' => 'cway-water', 'brand_name' => 'CWAY', 'sub_text' => 'PURIFIED DRINKING WATER', 'type' => 'bottle_pet', 'bg_color' => '#38bdf8', 'bottle_color' => '#f0f9ff', 'label_bg' => '#0284c7', 'label_text_color' => '#ffffff', 'badge_symbol' => 'water_drop', 'volume' => '75cl PET'],
            ['slug' => '5alive-berry-blast', 'brand_name' => '5ALIVE', 'sub_text' => 'BERRY BLAST JUICE', 'type' => 'bottle_pet', 'bg_color' => '#a855f7', 'bottle_color' => '#581c87', 'label_bg' => '#7e22ce', 'label_text_color' => '#fef08a', 'badge_symbol' => 'leaf', 'volume' => '78cl'],
            ['slug' => 'chivita-orange-juice', 'brand_name' => 'CHIVITA', 'sub_text' => '100% REAL ORANGE JUICE', 'type' => 'bottle_pet', 'bg_color' => '#f97316', 'bottle_color' => '#ea580c', 'label_bg' => '#c2410c', 'label_text_color' => '#ffffff', 'badge_symbol' => 'leaf', 'volume' => '1 Litre'],

            // PARTY BUNDLES
            ['slug' => 'bundle-weekend-vibes', 'brand_name' => 'WEEKEND VIBES', 'sub_text' => 'TROPHY + GUINNESS + ORIJIN', 'type' => 'bundle', 'bg_color' => '#f59e0b', 'bottle_color' => '#1c1917', 'label_bg' => '#b91c1c', 'label_text_color' => '#fef08a', 'badge_symbol' => 'bundle_party', 'volume' => 'COMBO PACK'],
            ['slug' => 'bundle-all-night-chaser', 'brand_name' => 'CHASER PACK', 'sub_text' => 'FEARLESS + COKE + TROPHY', 'type' => 'bundle', 'bg_color' => '#ef4444', 'bottle_color' => '#18181b', 'label_bg' => '#1d4ed8', 'label_text_color' => '#fef08a', 'badge_symbol' => 'bundle_party', 'volume' => 'CHASER PACK'],
            ['slug' => 'bundle-vip-celebration', 'brand_name' => 'VIP CELEBRATION', 'sub_text' => 'CARLO ROSSI + HEINEKEN', 'type' => 'bundle', 'bg_color' => '#eab308', 'bottle_color' => '#4c0519', 'label_bg' => '#9f1239', 'label_text_color' => '#fef08a', 'badge_symbol' => 'bundle_party', 'volume' => 'VIP BUNDLE'],
        ];
    }
}
