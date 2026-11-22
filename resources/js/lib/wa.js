// Build a WhatsApp deep link to a member from their stored phone.
const DIAL = { EG: '20', SA: '966', AE: '971', KW: '965', QA: '974', BH: '973', OM: '968' };

export function waLink(phone, country, message) {
    let digits = String(phone || '').replace(/\D/g, '');
    if (!digits) return null;
    if (digits.startsWith('00')) digits = digits.slice(2);
    const code = DIAL[country] || '';
    if (code && digits.startsWith('0')) digits = code + digits.slice(1);
    return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`;
}
