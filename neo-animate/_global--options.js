import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";

export async function getOption(name) {
    return await fetchEndpoint("/wp-json/neo/option-get-neo-animate", { method: "GET", query: { name } }).then(extractJson);
}

export async function setOption(name, value) {
    await fetchEndpoint("/wp-json/neo/option-set-neo-animate", { method: "POST", body: { name, value } });
}
