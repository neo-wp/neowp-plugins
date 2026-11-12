export function neoWarn(...message)  { (window.NeoLogWarnError?.neoWarn  || console.warn)(...message); }
export function neoError(...message) { (window.NeoLogWarnError?.neoError || console.error)(...message); }
