export function getWebsiteHostType() {
    const hostname = location.hostname.replace(/^\[(.*)\]$/, "$1");
    if (hostname === "playground.wordpress.net" || hostname.endsWith(".playground.wordpress.net")) { return "playground"; }
    if (hostname === "localhost" || hostname.endsWith(".localhost")) { return "localhost"; }
    if (hostname.includes(":") || (hostname.split(".").length === 4 && hostname.split(".").every(part => /^\d{1,3}$/.test(part) && Number(part) <= 255))) { return "ip"; }
    return "domain";
}
