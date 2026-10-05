<?php

/*
| AutoMail colour themes - the list shown on the Themes page and the only values
| accepted when a user picks one. The CSS for each lives in resources/css/themes.css.
| 'preview' holds plain hex values used to draw the little preview cards, so a card
| looks right no matter which theme is currently active.
*/

return [

    'default' => 'ember',

    'palettes' => [
        'ember' => [
            'name' => 'Ember',
            'tagline' => 'Warm rust on cream. The default AutoMail look.',
            'preview' => ['accent' => '#B85D33', 'sun' => '#E9A24F', 'sidebar' => '#1D1E22', 'paper' => '#FFF3E4', 'card' => '#FFFAF3', 'ink' => '#1D1E22', 'border' => '#E2D2BC', 'dark_paper' => '#17181B', 'dark_card' => '#1F2024'],
        ],
        'ocean' => [
            'name' => 'Ocean',
            'tagline' => 'Calm blue with a navy sidebar.',
            'preview' => ['accent' => '#1B6CA8', 'sun' => '#F2B544', 'sidebar' => '#0F1F2E', 'paper' => '#EEF5FA', 'card' => '#F8FBFD', 'ink' => '#0F1F2E', 'border' => '#C9DBE9', 'dark_paper' => '#0B1620', 'dark_card' => '#101E2B'],
        ],
        'forest' => [
            'name' => 'Forest',
            'tagline' => 'Fresh green with deep woodland tones.',
            'preview' => ['accent' => '#2F7A4F', 'sun' => '#E3B341', 'sidebar' => '#14231B', 'paper' => '#F1F6EE', 'card' => '#FAFDF8', 'ink' => '#14231B', 'border' => '#CCDCC6', 'dark_paper' => '#0E1712', 'dark_card' => '#121D16'],
        ],
        'plum' => [
            'name' => 'Plum',
            'tagline' => 'Rich purple with a soft lilac canvas.',
            'preview' => ['accent' => '#8A3FA0', 'sun' => '#F2B36B', 'sidebar' => '#20132A', 'paper' => '#F8F1FB', 'card' => '#FDF9FE', 'ink' => '#20132A', 'border' => '#DEC9E8', 'dark_paper' => '#150D1C', 'dark_card' => '#1A1124'],
        ],
        'slate' => [
            'name' => 'Slate',
            'tagline' => 'Neutral and professional, with an amber highlight.',
            'preview' => ['accent' => '#475569', 'sun' => '#F59E0B', 'sidebar' => '#111827', 'paper' => '#F3F4F6', 'card' => '#FFFFFF', 'ink' => '#111827', 'border' => '#D1D5DB', 'dark_paper' => '#0B0F16', 'dark_card' => '#111722'],
        ],
        'lagoon' => [
            'name' => 'Lagoon',
            'tagline' => 'Deep navy, teal and a bright amber highlight.',
            'preview' => ['accent' => '#1C7E9B', 'sun' => '#FFAA00', 'sidebar' => '#032B3F', 'paper' => '#EEF6FA', 'card' => '#F8FCFE', 'ink' => '#032B3F', 'border' => '#C5DDEA', 'dark_paper' => '#06161F', 'dark_card' => '#0A1D2A'],
        ],
        'dahlia' => [
            'name' => 'Dahlia',
            'tagline' => 'Soft stone and slate blue with a turquoise touch.',
            'preview' => ['accent' => '#697184', 'sun' => '#66BAC8', 'sidebar' => '#413F3D', 'paper' => '#F2F1EF', 'card' => '#FAF9F8', 'ink' => '#413F3D', 'border' => '#D8CFD0', 'dark_paper' => '#1B1A1A', 'dark_card' => '#211F20'],
        ],
        'sage' => [
            'name' => 'Sage',
            'tagline' => 'Fresh mint and cream with a sunny yellow accent.',
            'preview' => ['accent' => '#4A7F69', 'sun' => '#E8DA70', 'sidebar' => '#1E1E1E', 'paper' => '#F7F5EE', 'card' => '#FDFCF8', 'ink' => '#1E1E1E', 'border' => '#D9DCC9', 'dark_paper' => '#111513', 'dark_card' => '#161B18'],
        ],
    ],

];
