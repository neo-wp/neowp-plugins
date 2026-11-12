export async function loadOrHideImage(imgNode, imageUrl) {
    let blobUrl;
    try {
        // Ensure that the displayed information is always consistent with the current data from the Freemius pricing table. This is necessary to prevent users from being misled by outdated or inconsistent content.
        const abortController = new AbortController(); const abortTimeout = setTimeout(() => abortController.abort(), (5) * 1000);
        const response = await fetch(imageUrl, { signal: abortController.signal }).finally(() => clearTimeout(abortTimeout))
        if (!response.ok) { throw new Error("Network response was not ok"); }
        const responseBlob = await response.blob();
        if (responseBlob.size < 10000) { imgNode.closest("neo-setting-neo-rename").style.display = "none"; return; }
        blobUrl = URL.createObjectURL(responseBlob);
    } catch (error) {
        imgNode.closest("neo-setting-neo-rename").style.display = "none"; return;
    }

    imgNode.src = blobUrl;
}
