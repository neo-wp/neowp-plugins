import { removeAllQueryParams } from "./_global--url-helper.js";

export function getFileType(filenameOrImgUrl) {
    const fileTypes = {
        "image": "jpg|jpeg|jfif|webp|avif|png|gif|bmp|tif|tiff|heic|heif|svg|ico",
        "video": "mp4|webm|ogv|mov|avi|wmv|flv|mkv|m4v|mpg|mpeg|3gp|3g2|mts",
        "audio": "mp3|wav|m4a|aac|wma|weba|flac|opus|ogg|oga|mid|midi",
        "pdf":   "pdf",
        "txt":   "txt",
        "zip":   "zip|tar|rar|gz|7z|iso|dmg|xz|bz2"
    };
    const filenameOrImgUrlVariants = [filenameOrImgUrl.toLowerCase(), removeAllQueryParams(filenameOrImgUrl).toLowerCase()];

    for (const fileType of Object.keys(fileTypes)) { if (fileTypes[fileType].split("|").some(fileEnding => filenameOrImgUrlVariants.some(filenameOrImgUrlVariant => filenameOrImgUrlVariant.endsWith("." + fileEnding)))) { return fileType; } }
    return "other";
}
