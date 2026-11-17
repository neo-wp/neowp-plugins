import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";

export async function duplicateImage(imgUrl) {
    return (await duplicateMediaFileData(imgUrl)).imgUrl;
}
export async function duplicateMediaFileData(imgUrl) {
    const data = await fetchEndpoint("/wp-json/neo/duplicate", {
        method: "POST", body: { "img-url": imgUrl }
    }).then(extractJson);
    return data;
}
export async function interfaceDuplicate20250302(imgUrl) { return duplicateImage(imgUrl); }
