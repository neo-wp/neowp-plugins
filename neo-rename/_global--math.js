export function epsilon(x) { return !Number.isFinite(x) ? NaN : x === 0 || Math.abs(x) < 2**-1022 ? 2**-1074 : 2 ** (Math.floor(Math.log2(Math.abs(x))) - 52); }
export function formatKb(sizeBytes) { return typeof sizeBytes === "number" ? `${Math.round(sizeBytes / 1000).toLocaleString()} kB` : "..."; }
