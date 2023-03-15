import * as convert from 'color-convert';

export function getTextColor(background) {
    if (typeof background === 'undefined' || background === null || background === 'transparent') {
        return 'black';
    }

    const hsl = convert.hex.hsl(background);
    if (hsl === null) {
        return 'black';
    }

    return hsl[2] > 50 ? 'black' : 'white';
}
