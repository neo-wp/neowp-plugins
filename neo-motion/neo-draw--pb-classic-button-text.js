QTags.addButton('neodraw_qtag_button', "neoDraw", async function(element, canvas) {
    const { escapeHtml } = await import("./_global--dom-node-helper.js");

    const canvasValue = canvas.value;
    const selectionStart = canvas.selectionStart;
    const selectionEnd = canvas.selectionEnd;
    const selectedImgMatch = canvasValue.substring(selectionStart, selectionEnd).match(/<img\b[^>]*>/i);
    const imgStart = selectedImgMatch ? selectionStart + selectedImgMatch.index : canvasValue.toLowerCase().lastIndexOf("<img", selectionStart);
    const imgEnd = imgStart === -1 ? -1 : canvasValue.indexOf(">", imgStart);
    const imgCode = imgStart === -1 || imgEnd === -1 || (!selectedImgMatch && imgEnd < selectionStart) ? "" : canvasValue.substring(imgStart, imgEnd + 1);
    const srcMatch = imgCode.match(/\bsrc\s*=\s*(["'])(.*?)\1/i);
    const imgUrl = srcMatch?.[2] ?? "";
    const isNew = imgUrl === "";
    new (await import("./neo-draw--editor-dialog.js")).InterfaceEditorDialog20260826()
        .imgUrl(imgUrl)
        .on("save", async ({ imgUrl: savedImgUrl }) => {
            const imgCode = `<img src="${escapeHtml(savedImgUrl)}" />`;
            if (isNew) {
                canvas.value = canvasValue.substring(0, selectionStart) + imgCode + canvasValue.substring(selectionStart);

                canvas.selectionStart = canvas.selectionEnd = selectionStart + imgCode.length;
            } else if (savedImgUrl !== imgUrl) {
                canvas.value = canvasValue.substring(0, imgStart) + imgCode + canvasValue.substring(imgEnd + 1);
                canvas.selectionStart = canvas.selectionEnd = imgStart + imgCode.length;
            }
        })
        .open();
});
