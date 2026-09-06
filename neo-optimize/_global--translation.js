// Preparation of a translation system that will soon provide translations of all languages worldwide in the correct context.
export function neo__(enStr, deStr) {
    if (!window.wp?.i18n?.getLocaleData) { return enStr; }
    if ((window.wp?.i18n?.getLocaleData?.()?.[""]?.lang ?? "").startsWith("de")) { return deStr; }
    return enStr;
}

export function toFixed(number, digits = 0) {
    return Number(number).toLocaleString(neo__("en-US", "de-DE"), { minimumFractionDigits: digits, maximumFractionDigits: digits });
}
