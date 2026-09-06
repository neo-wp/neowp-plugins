export function addQueryParams(url, params) {
    if (Object.keys(params).length === 0) { return url; }
    if (url.startsWith("blob:")) { throw new Error("Blob URLs cannot have query params."); }
    for (const key in params) { if (hasQueryParam(url, key)) { console.warn(`Query param "${key}" already exists in URL and will be replaced.`, url); url = removeQueryParam(url, key); } }
    params = {...params}; for (const key in params) { if (typeof params[key] === "object" && params[key] != null) { params[key] = JSON.stringify(params[key]); } }
    const queryParts = []; for (const [key, value] of Object.entries(params)) { if (value === null || value === undefined) { queryParts.push(encodeURIComponent(key)); } else if (value === "") { queryParts.push(encodeURIComponent(key) + "="); } else { queryParts.push(encodeURIComponent(key) + "=" + encodeURIComponent(String(value))); } }
    const queryString = queryParts.join("&");
    const [baseUrl, hash] = url.split("#");
    if (baseUrl.includes("?")) { url = baseUrl + "&" + queryString; }
    else                       { url = baseUrl + "?" + queryString; }
    if (hash) { url += "#" + hash; }
    return url;
}

export function addQueryParam(url, key, value = null) {
    return addQueryParams(url, {[key]: value});
}

export function addCacheBust(url, date = -1) {
    if (!date) { return url; }
    if (date === -1) { return addCacheBust(url, Date.now()); }
    if (url.startsWith("blob:")) { return url; }
    return addQueryParam(url, "cachebust", date);
}

export function getQueryParams(url) {
    if (!url.includes("?")) return {};
    const query = url.split("?")[1].split("#")[0];
    const params = new URLSearchParams(query);
    const result = {};

    for (const [key, value] of params.entries()) {
        if (query.split("&").some(part => part === key)) { result[key] = null; continue; }
        result[key] = value;
    }
    return result;
}

export function getQueryParam(url, key) {
    return getQueryParams(url)[key];
}

export function hasQueryParam(url, key) {
    if (!key) { throw new Error("Key must be provided for hasQueryParam."); }
    return Object.prototype.hasOwnProperty.call(getQueryParams(url), key);
}

export function removeQueryParam(url, key) {
    const params = getQueryParams(url);
    delete params[key];
    url = removeAllQueryParams(url);
    return addQueryParams(url, params);
}

export function removeAllQueryParams(url) {
    return url.split("?")[0] + (url.includes("#") ? "#" + url.split("#")[1] : "");
}

export function removeUrlFragment(url) {
    return url.split("#")[0];
}

export function fitProtocolToFetchImgUrl(url) {
    if (url.startsWith("http:") && location.protocol === "https:") { url = url.replace("http:", "https:"); }
    return url;
}

export function stripProtocol(url) {
    return url.replace(/^https?:\/\//, "");
}

export function getUrlFilename(url) {
    return url.split("/").pop().split("?")[0].split("#")[0];
}
