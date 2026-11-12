import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";

export async function fetchImageUsageLookup(imgUrl, timeoutSeconds = null) {
    const query = { "img-url": imgUrl };
    if (timeoutSeconds !== null) { query["timeout"] = timeoutSeconds; }
    return await fetchEndpoint("/wp-json/neo/db-entries-usage-neo-replace", { method: "GET", query }).then(extractJson);
}
