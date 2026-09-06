export function parseMetadata(svgString) {
    svgString = svgString.replace(/\n/g, "");

    const startTag = "<!-- START - neoDraw metadata -->";
    const endTag = "<!-- END - neoDraw metadata -->";
    const startIndex = svgString.indexOf(startTag);
    const endIndex = svgString.indexOf(endTag);
    if (startIndex === -1 || endIndex === -1) { throw new Error("neoDraw metadata not found");  }
    const raw = svgString.substring(startIndex + startTag.length, endIndex);

    const cleaned = raw
        .replace(/^\s*<!--\s*/, "")
        .replace(/\s*-->\s*$/, "");
    return JSON.parse(cleaned);
}

export function isNeoDrawImage(svgContent) {
    return svgContent.includes("<!-- Created with neoDraw");
}
