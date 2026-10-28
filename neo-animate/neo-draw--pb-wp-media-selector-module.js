import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { neo__ } from "./_global--translation.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { fitProtocolToFetchImgUrl, addCacheBust } from "./_global--url-helper.js";
import { observeOnce } from "./_global--observer.js";
import { neoError } from "./_global--log.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";

export async function wpMediaSelectorOpen() {
    if (this.neoDrawMediaSelectorInitialized) { wp.media.view.MediaFrame.Select.__super__.open.apply(this, arguments); return; }
    this.neoDrawMediaSelectorInitialized = true;
    const getMediaSelectionDialogNode = () => this.views?.parent?.el ?? this.modal?.el ?? this.el?.closest?.(".media-modal") ?? this.el ?? null;
    let hiddenMediaSelectionDialogNode = null;
    const hideMediaSelectionDialog = () => { hiddenMediaSelectionDialogNode = getMediaSelectionDialogNode(); hiddenMediaSelectionDialogNode?.style.setProperty("display", "none"); document.querySelector(".media-modal-backdrop")?.style.setProperty("display", "none"); };
    const showMediaSelectionDialog = () => { (hiddenMediaSelectionDialogNode ?? getMediaSelectionDialogNode())?.style.removeProperty("display"); hiddenMediaSelectionDialogNode = null; document.querySelector(".media-modal-backdrop")?.style.removeProperty("display"); };
    const refreshNeoDrawPreviewImages = (imgUrl) => {
        const imgUrlWithoutQuery = imgUrl.split("?")[0];
        const cachebustedImgUrl = addCacheBust(imgUrlWithoutQuery);
        for (const img of document.querySelectorAll("img")) {
            if (fitProtocolToFetchImgUrl(img.src).split("?")[0] === fitProtocolToFetchImgUrl(imgUrlWithoutQuery)) { img.src = cachebustedImgUrl; }
        }
        for (const img of this.el.querySelectorAll(".attachment-preview img, .attachment-details .details-image")) {
            if ((img.getAttribute("src") ?? "").split("?")[0] === imgUrlWithoutQuery) { img.setAttribute("src", cachebustedImgUrl); }
        }
        for (const iframe of document.querySelectorAll("iframe")) {
            let iframeDocument = null;
            try { iframeDocument = iframe.contentDocument; } catch (error) { iframeDocument = null; }
            if (!iframeDocument) { continue; }
            for (const img of iframeDocument.querySelectorAll("img")) {
                if (fitProtocolToFetchImgUrl(img.src).split("?")[0] === fitProtocolToFetchImgUrl(imgUrlWithoutQuery)) { img.src = cachebustedImgUrl; }
            }
        }
    };
    const selectSavedNeoDrawCopy = async (savedImgId) => {
        try { await wp.media.attachment(savedImgId).fetch(); }
        catch (error) {
            alert(neo__("Metadata of the created image could not be fetched for the media selection dialog.", "Metadaten des erstellten Bildes konnten nicht für den Medien-Auswahldialog geladen werden."));
            throw error;
        }
        const newImgLibraryAttachment = wp.media.attachment(savedImgId);
        this.state().get("library").add(newImgLibraryAttachment);
        this.state().get("selection").reset();
        this.state().get("selection").add(newImgLibraryAttachment);
        this.state().trigger("select", newImgLibraryAttachment);
        showMediaSelectionDialog();
        this.modal.close();
    };
    const selectEditedNeoDrawOriginal = async (savedImgId) => {
        try { await wp.media.attachment(savedImgId).fetch(); }
        catch (error) {
            alert(neo__("Metadata of the edited image could not be fetched for the media selection dialog.", "Metadaten des bearbeiteten Bildes konnten nicht für den Medien-Auswahldialog geladen werden."));
            throw error;
        }
        const editedImgLibraryAttachment = wp.media.attachment(savedImgId);
        this.state().get("selection").reset();
        this.state().get("selection").add(editedImgLibraryAttachment);
        showMediaSelectionDialog();
    };

    const openAttachmentInNeoDraw = async (attachment) => {
        const originalImgUrl = attachment.get("url");
        let savedImgUrl = null; let savedImgId = null; hideMediaSelectionDialog();
        const dialog = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))().imgUrl(originalImgUrl);
        dialog.on("save", ({ imgUrl, imgId }) => {
            savedImgUrl = imgUrl; savedImgId = imgId;
            if (imgUrl === originalImgUrl) { refreshNeoDrawPreviewImages(imgUrl); }
        });
        dialog.on("close", async () => {
            if (savedImgId && savedImgUrl !== originalImgUrl) { await selectSavedNeoDrawCopy(savedImgId); return; }
            if (savedImgId && !this.options.multiple) { await selectEditedNeoDrawOriginal(savedImgId); return; }
            showMediaSelectionDialog();
        });
        dialog.open();
    };

    this.on("open", () => { this.content.mode("browse"); });

    this.once("open", async () => {
        if (this.options.multiple) { return; }
        const selectedAttachments = this.state().get("selection").models;
        if (selectedAttachments.length !== 1) { return; }
        const selectedAttachment = wp.media.attachment(selectedAttachments[0].id);
        try { if (!selectedAttachment.get("url")) { await selectedAttachment.fetch(); } }
        catch (error) { neoError(error); return; }
        const originalImgUrl = selectedAttachment.get("url");
        if (!(typeof originalImgUrl === "string" && originalImgUrl.split("?")[0].toLowerCase().endsWith(".svg"))) { return; }
        let savedImgUrl = null; let savedImgId = null; let mediaSelectionDialogRestored = false;
        const restoreMediaSelectionDialog = () => {
            if (mediaSelectionDialogRestored) { return; }
            mediaSelectionDialogRestored = true;
            const library = this.state().get("library");
            library.add(selectedAttachment);
            const selection = this.state().get("selection");
            selection.reset(); selection.add(selectedAttachment);
            showMediaSelectionDialog();
            observeOnce(`#${this.el.id} .attachment[data-id="${selectedAttachment.id}"]`).then((attachmentNode) => attachmentNode.scrollIntoView({ block: "center" }));
        };
        const dialog = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))().imgUrl(originalImgUrl).suppressInsert();
        dialog.on("error", (details) => {
            if (details?.message !== "Inserting is suppressed in this editor.") { return; }
            dialog.closeWithoutConfirmation();
            restoreMediaSelectionDialog();
        });
        dialog.on("save", ({ imgUrl, imgId }) => {
            savedImgUrl = imgUrl; savedImgId = imgId;
            if (imgUrl === originalImgUrl) { refreshNeoDrawPreviewImages(imgUrl); }
        });
        dialog.on("close", async () => {
            if (savedImgId && savedImgUrl !== originalImgUrl) { await selectSavedNeoDrawCopy(savedImgId); return; }
            if (savedImgId) {
                const cachebustDate = Date.now();
                const originalAttachmentUrl = selectedAttachment.get("url"); const originalAttachmentSizes = selectedAttachment.get("sizes");
                const cachebustedAttachmentData = await (await neoLoadInterfaceFunc("neo-animate", "neo-image-cachebust--helper.js", "interfaceCachebustAttachmentImageUrls20260730"))({url: originalAttachmentUrl, sizes: originalAttachmentSizes}, cachebustDate);
                selectedAttachment.set("url", cachebustedAttachmentData.url); if (cachebustedAttachmentData.sizes) { selectedAttachment.set("sizes", cachebustedAttachmentData.sizes); }
                this.state().trigger("select", this.state().get("selection"));
                selectedAttachment.set("url", originalAttachmentUrl); if (originalAttachmentSizes) { selectedAttachment.set("sizes", originalAttachmentSizes); }
                showMediaSelectionDialog(); this.modal.close(); return;
            }
            restoreMediaSelectionDialog();
        });
        hideMediaSelectionDialog();
        dialog.open();
    });
    const openCreateNeoDrawEditor = async () => {
        const dialog = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))().open();
        hideMediaSelectionDialog();
        let savedImgId = null;
        dialog.on("save", async ({ imgId }) => { savedImgId = imgId; });
        dialog.on("close", async () => {
            const selection = this.state().get("selection");
            selection.reset();
            if (!savedImgId) { showMediaSelectionDialog(); return; }
            try { await wp.media.attachment(savedImgId).fetch(); }
            catch (error) { alert(neo__("Metadata of the created image could not be fetched for the media selection dialog.", "Metadaten des erstellten Bildes konnten nicht für den Medien-Auswahldialog geladen werden.")); throw error; }
            const newImgLibraryAttachment = wp.media.attachment(savedImgId);
            this.state().get("library").add(newImgLibraryAttachment);
            selection.add(newImgLibraryAttachment);
            this.state().trigger("select", newImgLibraryAttachment);
            showMediaSelectionDialog();
            this.modal.close();
        });
    };

    const uniqueCreateNeoDrawSelectionId = -1 * Math.floor(Math.random() * 1000000);
    this.on("open", async () => {
        if (this.options.multiple) { return; }
        const mediaLibrary = this.state().get("library");
        try { await mediaLibrary.more(); } catch (error) { neoError(error); }

        mediaLibrary.add(new wp.media.model.Attachment({
            id: uniqueCreateNeoDrawSelectionId,
            title: neo__("Create neoDraw", "neoDraw erstellen"),
            caption: "", alt: "",
            url: pluginUrl() + "/img/neo-draw--edit-icon.svg",
            type: "image"
        }), { at: 0 });

        observeOnce(`#${this.el.id} li.attachment[data-id="${uniqueCreateNeoDrawSelectionId}"]`, (createButton) => {
            createButton.addEventListener("pointerdown", (event) => { event.preventDefault(); event.stopImmediatePropagation(); }, true);
            createButton.addEventListener("click", async (event) => { event.preventDefault(); event.stopImmediatePropagation(); await openCreateNeoDrawEditor(); }, true);
        });
    });

    this.on("open", () => {
        if (this.options.multiple) { return; }
        observeOnce(`#${this.el.id} .media-sidebar .attachment-details .attachment-info .details`, async (detailsNode) => {
            const selectedAttachment = this.state().get("selection").single();
            if (!(selectedAttachment && selectedAttachment.id > 0)) { return; }
            const attachment = wp.media.attachment(selectedAttachment.id);
            try { if (!attachment.get("url") || attachment.get("type") == null || attachment.get("is_neodraw") == null) { await attachment.fetch(); } }
            catch (error) { neoError(error); return; }
            if (!(attachment.get("type") === "image" && attachment.get("url"))) { return; }
            const editButton = document.createElement("button"); editButton.type = "button"; editButton.className = "button button-small neo-draw--media-selector-sidebar-edit-button"; editButton.textContent = attachment.get("is_neodraw") ? neo__("neoEdit", "neoEdit") : neo__("Copy & neoEdit", "Kopieren & neoEdit");
            editButton.addEventListener("pointerdown", (event) => { event.preventDefault(); event.stopImmediatePropagation(); }, true);
            editButton.addEventListener("click", async (event) => { event.preventDefault(); event.stopImmediatePropagation(); await openAttachmentInNeoDraw(attachment); }, true);
            detailsNode.appendChild(editButton);
        });
    });

    if (!document.querySelector("style#neo-draw--media-select-styles")) { document.head.appendChild(new DomNodeHelper('<style id="neo-draw--media-select-styles"></style>').getNode()); }
    document.querySelector("style#neo-draw--media-select-styles").textContent = `
    .media-modal-content .attachment[data-id="${uniqueCreateNeoDrawSelectionId}"] { --vertical-center-correction: -8%; }
    .media-modal-content .attachment[data-id="${uniqueCreateNeoDrawSelectionId}"] .thumbnail::before { content: "${neo__("Create neoDraw", "neoDraw erstellen")}"; font-weight: bold; position: absolute; left: 0; right: 0; margin: 0 auto; bottom: calc(8px - var(--vertical-center-correction)); }
    .media-modal-content .attachment[data-id="${uniqueCreateNeoDrawSelectionId}"] .filename { display: none; }
    .media-modal-content .attachment[data-id="${uniqueCreateNeoDrawSelectionId}"]::after { content: none; display: none; }
    .media-modal-content .attachment[data-id="${uniqueCreateNeoDrawSelectionId}"] .thumbnail img { width: 50%; height: 50%; margin-top: var(--vertical-center-correction); }
    .media-modal-content .attachment .neo-draw--media-selector-edit-button{position:absolute;top:50%;left:50%;z-index:20;width:42px;height:42px;min-height:42px;padding:0;border:1px solid #8c8f94;border-radius:6px;background:rgba(255,255,255,.8) url("${pluginUrl()}/img/neo-draw--edit-icon.svg") center/27px 27px no-repeat;box-shadow:0 2px 8px rgba(0,0,0,.24);opacity:0;transform:translate(-50%,-50%);transition:opacity .08s ease,transform .08s ease}
    .media-modal-content .attachment.neo-draw--media-selector-editable:hover .neo-draw--media-selector-edit-button,.media-modal-content .attachment .neo-draw--media-selector-edit-button:focus{opacity:1}
    .media-modal-content .attachment .neo-draw--media-selector-edit-button:hover{transform:translate(-50%,-50%) scale(1.08);background-color:rgba(246,247,247,.8)}
    `;

    this.on("open", () => {
        observeOnce(`#${this.el.getAttribute("id")} .attachment[data-id]`, async (attachmentNode) => {
            const attachmentId = parseInt(attachmentNode.getAttribute("data-id"), 10);
            if (!Number.isInteger(attachmentId) || attachmentId < 1) { return; }
            const attachment = wp.media.attachment(attachmentId);
            try { if (attachment.get("is_neodraw") == null || !attachment.get("url")) { await attachment.fetch(); } } catch (error) { neoError(error); return; }
            if (!attachment.get("is_neodraw")) { return; }
            attachmentNode.querySelector("img").src = addCacheBust(attachment.get("url"), attachment.get("neo_draw__last_modified"));
            const previewNode = attachmentNode.querySelector(".attachment-preview");
            if (!previewNode || previewNode.querySelector(".neo-draw--media-selector-edit-button")) { return; }
            attachmentNode.classList.add("neo-draw--media-selector-editable");
            const editButton = document.createElement("button"); editButton.type = "button"; editButton.className = "button neo-draw--media-selector-edit-button"; editButton.title = neo__("Edit neoDraw", "neoDraw bearbeiten"); editButton.setAttribute("aria-label", neo__("Edit neoDraw", "neoDraw bearbeiten"));
            editButton.addEventListener("pointerdown", (event) => { event.preventDefault(); event.stopImmediatePropagation(); }, true);
            editButton.addEventListener("click", async (event) => { event.preventDefault(); event.stopImmediatePropagation(); await openAttachmentInNeoDraw(attachment); }, true);
            previewNode.appendChild(editButton);
        });
    });

    wp.media.view.MediaFrame.Select.__super__.open.apply(this, arguments);
}
