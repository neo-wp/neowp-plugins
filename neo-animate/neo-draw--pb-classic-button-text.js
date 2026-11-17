QTags.addButton('neodraw_qtag_button', "neoDraw", async function(element, canvas) {
    const { escapeHtml } = await import("./_global--dom-node-helper.js");

    const canvasValue = canvas.value;
    const selectionStart = canvas.selectionStart;
    const selectionEnd = canvas.selectionEnd;
    const selectedImgMatch = canvasValue.substring(selectionStart, selectionEnd).match(/<img\b[^>]*>/i);
    const imgStart = selectedImgMatch ? selectionStart + selectedImgMatch.index : canvasValue.toLowerCase().lastIndexOf("<img", selectionStart);
    const imgEnd = imgStart === -1 ? -1 : canvasValue.indexOf(">", imgStart);
    const imgCode = imgStart === -1 || imgEnd === -1 || (!selectedImgMatch && imgEnd < selectionStart) ? "" : canvasValue.substring(imgStart, imgEnd + 1);
    const srcMatch = imgCode.match(/(?<=\s)src\s*=\s*(["'])(.*?)\1/i);
    const imgUrl = srcMatch?.[2] ?? "";
    const isNew = imgUrl === "";
    new (await import("./neo-draw--editor-dialog.js")).InterfaceEditorDialog20260826()
        .imgUrl(imgUrl)
        .on("save", async ({ imgUrl: savedImgUrl, imgId: savedImgId }) => {
            let savedImgCode = isNew ? `<img src="${escapeHtml(savedImgUrl)}" />` : (imgCode.substring(0, srcMatch.index) + `src="${escapeHtml(savedImgUrl)}"` + imgCode.substring(srcMatch.index + srcMatch[0].length)).replace(/\s+(?:srcset|sizes)\s*=\s*(["']).*?\1/gi, "");

            if (!isNew && savedImgId && savedImgUrl !== imgUrl) {
                savedImgCode = savedImgCode.replace(/(\sclass\s*=\s*)(["'])(.*?)\2/gis, (match, prefix, quote, classes) => prefix + quote + classes.replace(/\bwp-image-\d+\b/g, `wp-image-${savedImgId}`) + quote);
            }
            if (isNew) {
                canvas.value = canvasValue.substring(0, selectionStart) + savedImgCode + canvasValue.substring(selectionStart);

                canvas.selectionStart = canvas.selectionEnd = selectionStart + savedImgCode.length;
            } else if (savedImgUrl !== imgUrl) {
                canvas.value = canvasValue.substring(0, imgStart) + savedImgCode + canvasValue.substring(imgEnd + 1);
                canvas.selectionStart = canvas.selectionEnd = imgStart + savedImgCode.length;
            }
        })
        .open();
});
