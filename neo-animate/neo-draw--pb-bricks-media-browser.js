import { observeOnce } from "./_global--observer.js";
import { DomNodeHelper, escapeHtml } from "./_global--dom-node-helper.js";
import { neo__ } from "./_global--translation.js";
import { neoError } from "./_global--log.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { addCacheBust, fitProtocolToFetchImgUrl } from "./_global--url-helper.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";

observeOnce(".bricks-popup.browser .browser-popup", (browserNode) => {
    const pendingVnodes = [document.querySelector(".brx-body")._vnode];
    let browser = null;
    while (pendingVnodes.length) {
        const vnode = pendingVnodes.pop();
        if (vnode.component?.proxy?.$el === browserNode && vnode.component.proxy.updateMediaSelection) { browser = vnode.component.proxy; break; }
        if (vnode.component?.subTree) { pendingVnodes.push(vnode.component.subTree); }
        if (Array.isArray(vnode.children)) { pendingVnodes.push(...vnode.children.filter(child => child && typeof child === "object")); }
    }
    if (!browser) { neoError(new Error("Could not find the Bricks media browser component.")); return; }
    let editorOpen = false;
    const popupNode = browserNode.closest(".bricks-popup.browser");
    const createButton = new DomNodeHelper(`<button type="button" class="button neo-draw--bricks-create-button"><img alt="" src="${escapeHtml(pluginUrl())}/img/neo-draw--edit-icon.svg">${escapeHtml(neo__("Create neoDraw", "neoDraw erstellen"))}</button>`).getNode();
    browserNode.querySelector(":scope > header .title").after(createButton);
    createButton.parentElement.classList.add("neo-draw--bricks-heading");
    let returnFocusNode = createButton;

    const restoreBrowser = () => { popupNode.classList.remove("neo-draw--bricks-editor-open"); editorOpen = false; updateButtons(); if (returnFocusNode.isConnected) { returnFocusNode.focus(); } };

    const refreshPreviewImages = (imgUrl) => {
        for (const previewDocument of [document, document.querySelector("#bricks-builder-iframe")?.contentDocument].filter(Boolean)) {
            for (const img of previewDocument.querySelectorAll("img")) {
                if (fitProtocolToFetchImgUrl(img.src).split("?")[0] === fitProtocolToFetchImgUrl(imgUrl).split("?")[0]) { img.src = addCacheBust(imgUrl); }
            }
        }
    };

    const openEditor = async (attachmentId = null, automatic = false) => {
        if (editorOpen || browser.browserActionBusy || browser.mediaInspectorSaving) { return; }
        editorOpen = true;
        updateButtons();
        let savedImgId = null;
        try {
            const attachment = attachmentId ? wp.media.attachment(attachmentId) : null;
            if (attachment) { await attachment.fetch(); }
            if (automatic && !attachment.get("url").split("?")[0].toLowerCase().endsWith(".svg")) { editorOpen = false; updateButtons(); return; }
            if (!(await browser.flushActiveMediaUploadMetadata()) || !(await browser.closeActiveMediaInspector())) { restoreBrowser(); return; }
            const originalImgUrl = attachment?.get("url") ?? "";
            const dialog = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))().imgUrl(originalImgUrl);
            if (!browserNode.isConnected) { editorOpen = false; return; }
            if (automatic) { dialog.suppressInsert(); }
            dialog.on("error", (details) => { if (automatic && details?.message === "Inserting is suppressed in this editor.") { dialog.closeWithoutConfirmation(); restoreBrowser(); } });
            dialog.on("save", ({ imgId, imgUrl }) => { savedImgId = imgId; if (imgUrl === originalImgUrl) { refreshPreviewImages(imgUrl); } });
            dialog.on("close", async () => {
                try {
                    if (!savedImgId) { return; }
                    const savedAttachment = wp.media.attachment(savedImgId); await savedAttachment.fetch();
                    const savedData = savedAttachment.toJSON();
                    Object.assign(savedData, await (await neoLoadInterfaceFunc("neo-animate", "neo-image-cachebust--helper.js", "interfaceCachebustAttachmentImageUrls20260730"))(savedData));
                    if (browser.isMediaSelectionMode) {
                        const selectedImages = browser.mediaSelection.map(image => String(image.id) === String(attachmentId) ? savedData : image);
                        if (!selectedImages.some(image => String(image.id) === String(savedImgId))) { selectedImages.push(savedData); }
                        browser.updateMediaSelection(browser.mediaManager.multiple ? selectedImages : [savedData]);
                        if (!browser.mediaManager.multiple && (automatic || savedImgId !== attachmentId)) { await browser.completeMediaSelection(); return; }
                    }
                    await browser.$refs.popupPosts.viewUploadedMediaInLibrary(savedData);
                    if (!browser.isMediaSelectionMode) { await browser.$refs.popupPosts.openMediaInspector({ id: savedImgId }); }
                } catch (error) { neoError(error); }
                finally { restoreBrowser(); }
            });
            popupNode.classList.add("neo-draw--bricks-editor-open");
            dialog.open();
            dialog.dialogNode.querySelector(".neo-draw--editor-loading-close").addEventListener("click", restoreBrowser);
        } catch (error) { restoreBrowser(); neoError(error); }
    };
    createButton.addEventListener("click", (event) => { event.stopPropagation(); returnFocusNode = createButton; openEditor(); });

    const updateButtons = () => {
        const supportsSvg = !browser.mediaManager || ["image", "image/svg+xml", "any"].includes(browser.mediaManager.type);
        const posts = browser.$refs.popupPosts;
        createButton.hidden = !(supportsSvg && browser.activeItem === "attachment" && posts?.mediaView === "library" && posts.canUploadMedia);
        createButton.disabled = editorOpen || browser.browserActionBusy || browser.mediaInspectorSaving;
        for (const button of browserNode.querySelectorAll(".neo-draw--bricks-edit-button")) { button.hidden = !supportsSvg || posts?.mediaView !== "library"; button.disabled = createButton.disabled; }
    };
    browser.$watch(() => [browser.activeItem, browser.$refs.popupPosts?.mediaView, browser.browserActionBusy, browser.mediaInspectorSaving], updateButtons, { immediate: true, flush: "post" });
    observeOnce(".bricks-layout-item[data-post-id] .title-actions", (actionsNode) => {
        const imageId = Number(actionsNode.closest("[data-post-id]").getAttribute("data-post-id"));
        const image = browser.$refs.popupPosts?.postsQueryResults.find(post => Number(post.id) === imageId && post.postType === "attachment");
        if (!(image?.canEdit && image.mimeType?.startsWith("image/"))) { return; }
        const editButton = new DomNodeHelper(`<button type="button" class="button neo-draw--bricks-edit-button" aria-label="${escapeHtml(neo__("Edit with neoDraw", "Mit neoDraw bearbeiten"))}" data-balloon="${escapeHtml(neo__("Edit with neoDraw", "Mit neoDraw bearbeiten"))}" data-balloon-pos="top-left"><img alt="" src="${escapeHtml(pluginUrl())}/img/neo-draw--edit-icon.svg"></button>`).getNode();
        editButton.addEventListener("pointerdown", (event) => { event.preventDefault(); event.stopPropagation(); });
        editButton.addEventListener("click", (event) => { event.preventDefault(); event.stopPropagation(); returnFocusNode = editButton; openEditor(imageId); });
        actionsNode.appendChild(editButton); updateButtons();
    }, { domRoot: browserNode });
    if (browser.mediaManager && !browser.mediaManager.multiple && browser.mediaSelection.length === 1) { openEditor(browser.mediaSelection[0].id, true); }
});
