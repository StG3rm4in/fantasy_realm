(function () {
    'use strict';

    var SKIN_COLORS = {
        'Claire': '#FDDCB5',
        'Pale': '#F5E0D0',
        'Mate': '#D4A574',
        'Bronzee': '#C68642',
        'Sombre': '#8D5524',
        'Ebene': '#5C3A1E'
    };

    var EYE_COLORS = {
        'Bleu': '#4A90D9',
        'Vert': '#2ECC71',
        'Marron': '#8B4513',
        'Noir': '#1a1a1a',
        'Violet': '#9B59B6',
        'Rouge': '#E74C3C',
        'Dore': '#F1C40F'
    };

    var HAIR_COLORS = {
        'Blond': '#F4D03F',
        'Brun': '#6B4226',
        'Noir': '#1a1a1a',
        'Roux': '#D35400',
        'Blanc': '#ECF0F1',
        'Argente': '#BDC3C7',
        'Bleu': '#3498DB',
        'Vert': '#27AE60'
    };

    function darkenColor(hex, amount) {
        hex = hex.replace('#', '');
        var r = Math.max(0, parseInt(hex.substring(0, 2), 16) - amount);
        var g = Math.max(0, parseInt(hex.substring(2, 4), 16) - amount);
        var b = Math.max(0, parseInt(hex.substring(4, 6), 16) - amount);
        return '#' + ('0' + r.toString(16)).slice(-2) + ('0' + g.toString(16)).slice(-2) + ('0' + b.toString(16)).slice(-2);
    }

    function renderFace(genre, skinColor, skinShadow) {
        var svg = '';
        if (genre === 'Masculin') {
            svg += '<rect x="62" y="60" width="76" height="90" rx="32" fill="' + skinColor + '"/>';
            svg += '<rect x="68" y="120" width="64" height="20" rx="10" fill="' + skinShadow + '" opacity="0.15"/>';
        } else if (genre === 'Feminin') {
            svg += '<ellipse cx="100" cy="105" rx="40" ry="48" fill="' + skinColor + '"/>';
            svg += '<ellipse cx="100" cy="125" rx="30" ry="15" fill="' + skinShadow + '" opacity="0.1"/>';
        } else {
            svg += '<ellipse cx="100" cy="105" rx="38" ry="46" fill="' + skinColor + '"/>';
            svg += '<ellipse cx="100" cy="123" rx="28" ry="14" fill="' + skinShadow + '" opacity="0.12"/>';
        }
        return svg;
    }

    function renderEyes(formeYeux, eyeColor, genre) {
        var svg = '';
        switch (formeYeux) {
            case 'Rond':
                svg += '<ellipse cx="82" cy="100" rx="10" ry="10" fill="white"/>';
                svg += '<circle cx="82" cy="100" r="6" fill="' + eyeColor + '"/>';
                svg += '<circle cx="82" cy="100" r="3" fill="#111"/>';
                svg += '<circle cx="84" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                svg += '<ellipse cx="118" cy="100" rx="10" ry="10" fill="white"/>';
                svg += '<circle cx="118" cy="100" r="6" fill="' + eyeColor + '"/>';
                svg += '<circle cx="118" cy="100" r="3" fill="#111"/>';
                svg += '<circle cx="120" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                break;
            case 'Ovale':
                svg += '<ellipse cx="82" cy="100" rx="11" ry="8" fill="white"/>';
                svg += '<circle cx="82" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="82" cy="100" r="2.5" fill="#111"/>';
                svg += '<circle cx="84" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                svg += '<ellipse cx="118" cy="100" rx="11" ry="8" fill="white"/>';
                svg += '<circle cx="118" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="118" cy="100" r="2.5" fill="#111"/>';
                svg += '<circle cx="120" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                break;
            case 'En amande':
                svg += '<path d="M71 100 Q82 90 93 100 Q82 108 71 100Z" fill="white"/>';
                svg += '<circle cx="82" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="82" cy="100" r="2.5" fill="#111"/>';
                svg += '<circle cx="84" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                svg += '<path d="M107 100 Q118 90 129 100 Q118 108 107 100Z" fill="white"/>';
                svg += '<circle cx="118" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="118" cy="100" r="2.5" fill="#111"/>';
                svg += '<circle cx="120" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                break;
            case 'Tombant':
                svg += '<path d="M71 98 Q82 92 93 100 Q82 108 71 103Z" fill="white"/>';
                svg += '<circle cx="82" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="82" cy="100" r="2.5" fill="#111"/>';
                svg += '<circle cx="84" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                svg += '<path d="M107 100 Q118 92 129 98 Q129 103 118 108 Q107 108 107 100Z" fill="white"/>';
                svg += '<circle cx="118" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="118" cy="100" r="2.5" fill="#111"/>';
                svg += '<circle cx="120" cy="98" r="1.5" fill="white" opacity="0.8"/>';
                break;
            default:
                svg += '<ellipse cx="82" cy="100" rx="11" ry="8" fill="white"/>';
                svg += '<circle cx="82" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="82" cy="100" r="2.5" fill="#111"/>';
                svg += '<ellipse cx="118" cy="100" rx="11" ry="8" fill="white"/>';
                svg += '<circle cx="118" cy="100" r="5" fill="' + eyeColor + '"/>';
                svg += '<circle cx="118" cy="100" r="2.5" fill="#111"/>';
        }

        if (genre === 'Feminin') {
            svg += '<line x1="70" y1="96" x2="67" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            svg += '<line x1="74" y1="94" x2="72" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            svg += '<line x1="90" y1="94" x2="92" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            svg += '<line x1="94" y1="96" x2="97" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            svg += '<line x1="106" y1="96" x2="103" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            svg += '<line x1="110" y1="94" x2="108" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            svg += '<line x1="126" y1="94" x2="128" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            svg += '<line x1="130" y1="96" x2="133" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        }

        return svg;
    }

    function renderEyebrows(genre, hairColor) {
        var strokeWidth = genre === 'Masculin' ? '3' : '2';
        var svg = '';
        svg += '<path d="M72 88 Q82 83 92 87" stroke="' + hairColor + '" stroke-width="' + strokeWidth + '" fill="none" stroke-linecap="round"/>';
        svg += '<path d="M108 87 Q118 83 128 88" stroke="' + hairColor + '" stroke-width="' + strokeWidth + '" fill="none" stroke-linecap="round"/>';
        return svg;
    }

    function renderNose(formeNez, skinShadow) {
        var svg = '';
        switch (formeNez) {
            case 'Droit':
                svg += '<line x1="100" y1="100" x2="100" y2="118" stroke="' + skinShadow + '" stroke-width="2" stroke-linecap="round"/>';
                svg += '<path d="M95 118 Q100 122 105 118" stroke="' + skinShadow + '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
                break;
            case 'Retrousse':
                svg += '<path d="M100 100 Q100 112 97 115 Q100 120 103 115 Q100 112 100 100" stroke="' + skinShadow + '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
                svg += '<circle cx="95" cy="117" r="2" fill="' + skinShadow + '" opacity="0.4"/>';
                svg += '<circle cx="105" cy="117" r="2" fill="' + skinShadow + '" opacity="0.4"/>';
                break;
            case 'Aquilin':
                svg += '<path d="M100 98 Q103 108 102 114 Q100 120 98 116" stroke="' + skinShadow + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
                svg += '<path d="M94 118 Q100 122 106 118" stroke="' + skinShadow + '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
                break;
            case 'Large':
                svg += '<line x1="100" y1="100" x2="100" y2="116" stroke="' + skinShadow + '" stroke-width="2" stroke-linecap="round"/>';
                svg += '<path d="M92 118 Q100 124 108 118" stroke="' + skinShadow + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
                break;
            case 'Fin':
                svg += '<line x1="100" y1="100" x2="100" y2="118" stroke="' + skinShadow + '" stroke-width="1.5" stroke-linecap="round"/>';
                svg += '<path d="M97 118 Q100 121 103 118" stroke="' + skinShadow + '" stroke-width="1" fill="none" stroke-linecap="round"/>';
                break;
            default:
                svg += '<line x1="100" y1="100" x2="100" y2="118" stroke="' + skinShadow + '" stroke-width="2" stroke-linecap="round"/>';
                svg += '<path d="M95 118 Q100 122 105 118" stroke="' + skinShadow + '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
        }
        return svg;
    }

    function renderMouth(formeBouche, genre) {
        var svg = '';
        var lipColor = genre === 'Feminin' ? '#c0392b' : '#a0665a';
        var lipColorLight = genre === 'Feminin' ? '#e74c3c' : '#b8776b';

        switch (formeBouche) {
            case 'Fine':
                svg += '<path d="M88 132 Q100 137 112 132" stroke="' + lipColor + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
                break;
            case 'Moyenne':
                svg += '<path d="M87 130 Q100 128 113 130" stroke="' + lipColor + '" stroke-width="1.5" fill="' + lipColorLight + '" opacity="0.6" stroke-linecap="round"/>';
                svg += '<path d="M87 130 Q100 138 113 130" stroke="' + lipColor + '" stroke-width="1.5" fill="' + lipColor + '" opacity="0.5" stroke-linecap="round"/>';
                break;
            case 'Pleine':
                svg += '<path d="M85 130 Q100 126 115 130" stroke="' + lipColor + '" stroke-width="1.5" fill="' + lipColorLight + '" opacity="0.7" stroke-linecap="round"/>';
                svg += '<path d="M85 130 Q100 142 115 130" stroke="' + lipColor + '" stroke-width="1.5" fill="' + lipColor + '" opacity="0.6" stroke-linecap="round"/>';
                break;
            case 'En coeur':
                svg += '<path d="M87 130 Q93 126 100 129 Q107 126 113 130" stroke="' + lipColor + '" stroke-width="1.5" fill="' + lipColorLight + '" opacity="0.6" stroke-linecap="round"/>';
                svg += '<path d="M87 130 Q100 140 113 130" stroke="' + lipColor + '" stroke-width="1.5" fill="' + lipColor + '" opacity="0.5" stroke-linecap="round"/>';
                break;
            default:
                svg += '<path d="M88 132 Q100 138 112 132" stroke="' + lipColor + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
        }
        return svg;
    }

    function renderHair(genre, hairColor) {
        var svg = '';
        var hairDark = darkenColor(hairColor, 25);

        if (genre === 'Feminin') {
            svg += '<ellipse cx="100" cy="72" rx="48" ry="22" fill="' + hairColor + '"/>';
            svg += '<rect x="54" y="70" width="18" height="70" rx="9" fill="' + hairColor + '"/>';
            svg += '<rect x="128" y="70" width="18" height="70" rx="9" fill="' + hairColor + '"/>';
            svg += '<ellipse cx="100" cy="62" rx="44" ry="16" fill="' + hairDark + '" opacity="0.4"/>';
            svg += '<path d="M62 78 Q75 58 100 62 Q125 58 138 78" fill="' + hairColor + '"/>';
            svg += '<path d="M68 78 Q80 65 100 68 Q120 65 132 78" fill="' + hairDark + '" opacity="0.3"/>';
        } else if (genre === 'Masculin') {
            svg += '<ellipse cx="100" cy="70" rx="44" ry="20" fill="' + hairColor + '"/>';
            svg += '<path d="M60 80 Q65 50 100 48 Q135 50 140 80" fill="' + hairColor + '"/>';
            svg += '<path d="M65 78 Q70 55 100 53 Q130 55 135 78" fill="' + hairDark + '" opacity="0.3"/>';
            svg += '<rect x="56" y="72" width="12" height="25" rx="6" fill="' + hairColor + '"/>';
            svg += '<rect x="132" y="72" width="12" height="25" rx="6" fill="' + hairColor + '"/>';
        } else {
            svg += '<ellipse cx="100" cy="70" rx="46" ry="22" fill="' + hairColor + '"/>';
            svg += '<path d="M58 80 Q68 52 100 50 Q132 52 142 80" fill="' + hairColor + '"/>';
            svg += '<path d="M64 78 Q74 58 100 56 Q126 58 136 78" fill="' + hairDark + '" opacity="0.3"/>';
            svg += '<rect x="55" y="72" width="14" height="45" rx="7" fill="' + hairColor + '"/>';
            svg += '<rect x="131" y="72" width="14" height="45" rx="7" fill="' + hairColor + '"/>';
        }

        return svg;
    }

    function generateAvatar(genre, couleurPeau, couleurYeux, couleurCheveux, formeYeux, formeNez, formeBouche) {
        var skinColor = SKIN_COLORS[couleurPeau] || '#FDDCB5';
        var eyeColor = EYE_COLORS[couleurYeux] || '#4A90D9';
        var hairColor = HAIR_COLORS[couleurCheveux] || '#6B4226';
        var skinShadow = darkenColor(skinColor, 20);

        var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">';

        // Background
        svg += '<defs>';
        svg += '<radialGradient id="bg-grad" cx="50%" cy="50%" r="50%">';
        svg += '<stop offset="0%" style="stop-color:#2a2a4a;stop-opacity:1"/>';
        svg += '<stop offset="100%" style="stop-color:#1a1a2e;stop-opacity:1"/>';
        svg += '</radialGradient>';
        svg += '</defs>';
        svg += '<rect width="200" height="200" fill="url(#bg-grad)" rx="16"/>';

        // Neck
        svg += '<rect x="85" y="140" width="30" height="25" rx="5" fill="' + skinColor + '"/>';
        svg += '<rect x="85" y="145" width="30" height="20" rx="3" fill="' + skinShadow + '" opacity="0.3"/>';

        // Shoulders
        svg += '<ellipse cx="100" cy="185" rx="55" ry="30" fill="#3a3a5c"/>';

        // Face
        svg += renderFace(genre, skinColor, skinShadow);

        // Ears
        svg += '<ellipse cx="58" cy="105" rx="8" ry="12" fill="' + skinColor + '"/>';
        svg += '<ellipse cx="58" cy="105" rx="5" ry="8" fill="' + skinShadow + '" opacity="0.3"/>';
        svg += '<ellipse cx="142" cy="105" rx="8" ry="12" fill="' + skinColor + '"/>';
        svg += '<ellipse cx="142" cy="105" rx="5" ry="8" fill="' + skinShadow + '" opacity="0.3"/>';

        // Eyes
        svg += renderEyes(formeYeux, eyeColor, genre);

        // Eyebrows
        svg += renderEyebrows(genre, hairColor);

        // Nose
        svg += renderNose(formeNez, skinShadow);

        // Mouth
        svg += renderMouth(formeBouche, genre);

        // Hair
        svg += renderHair(genre, hairColor);

        svg += '</svg>';
        return svg;
    }

    function updatePreview() {
        var container = document.getElementById('avatar-preview');
        if (!container) return;

        var genre = (document.getElementById('genre') || {}).value || '';
        var couleurPeau = (document.getElementById('couleur_peau') || {}).value || '';
        var couleurYeux = (document.getElementById('couleur_yeux') || {}).value || '';
        var couleurCheveux = (document.getElementById('couleur_cheveux') || {}).value || '';
        var formeYeux = (document.getElementById('forme_yeux') || {}).value || '';
        var formeNez = (document.getElementById('forme_nez') || {}).value || '';
        var formeBouche = (document.getElementById('forme_bouche') || {}).value || '';

        // Show placeholder if not all fields are filled
        if (!genre || !couleurPeau || !couleurYeux || !couleurCheveux || !formeYeux || !formeNez || !formeBouche) {
            container.innerHTML =
                '<div class="text-muted p-4">' +
                '<i class="bi bi-person-bounding-box display-3"></i>' +
                '<p class="mt-2 small">Remplissez tous les champs d\'apparence pour voir l\'apercu</p>' +
                '</div>';
            return;
        }

        container.innerHTML = generateAvatar(genre, couleurPeau, couleurYeux, couleurCheveux, formeYeux, formeNez, formeBouche);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var fields = ['genre', 'couleur_peau', 'couleur_yeux', 'couleur_cheveux', 'forme_yeux', 'forme_nez', 'forme_bouche'];

        fields.forEach(function (fieldId) {
            var el = document.getElementById(fieldId);
            if (el) {
                el.addEventListener('change', updatePreview);
            }
        });

        // Initial render
        updatePreview();
    });
})();
