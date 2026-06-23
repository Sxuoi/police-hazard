/**
 * Timezone-aware formatter for Indonesian timezones (WIB/WITA/WIT).
 * Falls back to device locale on error.
 */
export function formatLocationTime(iso, tz) {
    try {
        const abbr = tzAbbr(tz);
        const fmt = new Intl.DateTimeFormat('id-ID', {
            timeZone: tz,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).format(new Date(iso));
        return `${fmt} ${abbr}`;
    } catch (e) {
        console.warn('Timezone formatting failed, falling back to device locale', e);
        return new Date(iso).toLocaleString();
    }
}

function tzAbbr(tz) {
    switch (tz) {
        case 'Asia/Jakarta':
            return 'WIB';
        case 'Asia/Makassar':
            return 'WITA';
        case 'Asia/Jayapura':
            return 'WIT';
        default:
            return '';
    }
}
