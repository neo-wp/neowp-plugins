import { addQueryParams } from "./_global--url-helper.js";
import { jsVar } from "./_global--enqueue-loader.js";

export function imageUsageLookupPageUrl({ imgUrl, countInCurrentPost = null, timeoutSeconds = null } = {}) {
    const query = { "img-url": imgUrl };
    if (countInCurrentPost !== null) { query["count-in-current-post"] = countInCurrentPost; }
    if (timeoutSeconds !== null) { query["timeout"] = timeoutSeconds; }
    return addQueryParams(jsVar("neoGlobalDbEntriesUsageUrl"), query);
}
