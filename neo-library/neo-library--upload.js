import { neoError } from "./_global--log.js";
import { extractJson } from "./_global--extract-json.js";
import { neo__ } from "./_global--translation.js";
import { getLibraryTable, addRow } from "./neo-library--globals.js";
import { updateRowAttribute } from "./neo-library--render.js";
import { animateRowShine } from "./neo-library--animations.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { fetchEndpoint, refreshNonceIfOutdated } from "./_global--endpoint.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { observeOnce } from "./_global--observer.js";
import { isModuleAvailable } from "./_global--interface.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { cleanTitle, cleanPathRel, initCleanFileNameEmojiMap } from "./_global-clean-file-name.js";

const filesToUpload = [];
async function handleFiles(files) {
    const filesArray = [...files];
    (await getLibraryTable()).classList.remove("neo-library--dropover-animation");
    if (filesArray.length === 0) { return; }
    try { await initCleanFileNameEmojiMap(); } catch (error) { neoError(error); Swal.fire({ icon: "error", title: neo__("Error", "Fehler"), text: neo__("Could not load emoji list!", "Konnte Emoji-Liste nicht laden!") + " " + error.message, confirmButtonText: neo__("OK", "OK") }); return; }

    const newFilesToUpload = [];
    for (const [index, file] of filesArray.entries()) {
        const cleanFilename = cleanPathRel(file.name, { keepExtension: true });
        const cleanUploadTitle = cleanTitle(file.name);
        const uploadFile = new File([file], cleanFilename, { type: file.type, lastModified: file.lastModified });
        const droppedRow = await addRow();

        updateRowAttribute(droppedRow, "data-img-url", URL.createObjectURL(uploadFile));
        if (/\.(heic|heif)$/i.test(uploadFile.name)) { updateRowAttribute(droppedRow, "data-thumbnail-img-url", pluginUrl() + "/img/_global-placeholder-image-icon.svg"); }
        updateRowAttribute(droppedRow, "data-title", cleanUploadTitle);
        updateRowAttribute(droppedRow, "data-filename", uploadFile.name);
        updateRowAttribute(droppedRow, "data-message", `<span class="neo-library--hourglass-animation"><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/hourglass.svg" alt=""></span> ${neo__("In the queue...", "In der Warteschlange...")}`);
        updateRowAttribute(droppedRow, "data-posts-using-image", JSON.stringify([]));
        updateRowAttribute(droppedRow, "data-file-sizes", JSON.stringify({ raw: { size: uploadFile.size } }));
        updateRowAttribute(droppedRow, "data-state", "upload-pending");
        droppedRow.classList.add("neo-library--dropped-row-animation");
        droppedRow.style.setProperty("--neo-library--dropped-row-animation-delay", "" + (index / filesArray.length * 1000) + "ms");
        newFilesToUpload.push({ file: uploadFile, row: droppedRow, title: cleanUploadTitle });
    }

    await new Promise(resolve => setTimeout(resolve, (filesArray.length > 1 ? 1000 : 0) + 300));

    for (const row of newFilesToUpload.map(f => f.row)) { row.classList.remove("neo-library--dropped-row-animation"); }

    filesToUpload.push(...newFilesToUpload);
    uploadPendingFilesWorkerLoop();
}

async function uploadPendingFilesWorkerLoop() {
    const numberOfParallelWorkers = 4;
    uploadPendingFilesWorkerLoop.numberOfRunningWorkers ??= 0;
    if (uploadPendingFilesWorkerLoop.numberOfRunningWorkers >= Math.min(numberOfParallelWorkers, filesToUpload.length)) { return; }
    uploadPendingFilesWorkerLoop.numberOfRunningWorkers++;
    for (let i = 0; i < filesToUpload.length; i++) { uploadPendingFilesWorkerLoop(); }

    const getPendingFilesToUpload = () => filesToUpload.filter(f => f.row.getAttribute("data-state") === "upload-pending");
    while (getPendingFilesToUpload().length > 0) {
        const fileToUpload = getPendingFilesToUpload()[0];

        updateRowAttribute(fileToUpload.row, "data-state", "uploading"); updateRowAttribute(fileToUpload.row, "data-message", `<span class="neo-library--rocket-animation"><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/send.svg" alt=""></span> ${neo__("Uploading...", "Hochladen...")}`);
        let uploadErrorMessage = null;
        let editorDialog;
        let editorObjectUrl = null;
        try {
            if (!(fileToUpload.file.name.toLowerCase().endsWith(".svg") || fileToUpload.file.name.toLowerCase().endsWith(".png"))) { throw new Error("neoDraw editor can only open SVG or PNG directly"); }
            if (!isModuleAvailable("neo-draw")) { throw new Error("neoDraw editor is not available"); }
            editorObjectUrl = URL.createObjectURL(fileToUpload.file);
            editorDialog = new (await neoLoadInterfaceFunc("neo-library", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))();

            editorDialog
                .imgUrl(editorObjectUrl)
                .defaultFilename(fileToUpload.file.name)
                .forceNew()
                .suppressInsert()
                .hidden()
                .open();
            await new Promise((resolve, reject) => {
                editorDialog.on("load", resolve);
                editorDialog.on("error", reject);
            });

            const onSavePromise = new Promise((resolve, reject) => editorDialog.on("save", resolve).on("error", reject));
            editorDialog.save();
            const { imgUrl } = await onSavePromise;
            try { await fetchEndpoint("/wp-json/neo/library-update-title", { method: "POST", body: { "img-url": imgUrl, "title": fileToUpload.title } }); } catch (error) { neoError(error); }

            updateRowAttribute(fileToUpload.row, "data-message", `<span class="neo-library--check-animation"><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/circle-check.svg" alt=""></span> ` + neo__("Uploaded", "Hochgeladen"));
            updateRowAttribute(fileToUpload.row, "data-img-url", imgUrl);
            updateRowAttribute(fileToUpload.row, "data-is-neodraw", "true");
            updateRowAttribute(fileToUpload.row, "data-upload-date", new Date().toISOString().replace("T", " ").substring(0, 19));
            updateRowAttribute(fileToUpload.row, "data-modified-date", new Date().toISOString().replace("T", " ").substring(0, 19));
            updateRowAttribute(fileToUpload.row, "data-state", "download-pending");
            animateRowShine(fileToUpload.row);
        } catch (error) {
            uploadErrorMessage = error.message;
        } finally {
            editorDialog?.closeWithoutConfirmation();
            if (editorObjectUrl) { setTimeout(() => URL.revokeObjectURL(editorObjectUrl)); }
        }

        if (uploadErrorMessage) {
            try {
                let imgUrl = null;
                if (/\.(heic|heif)$/i.test(fileToUpload.file.name) && window.wp?.uploadMedia?.MediaUploadProvider && window.wp?.mediaUtils?.uploadMedia) {
                    const sourceMimeType = fileToUpload.file.name.toLowerCase().endsWith(".heif") ? "image/heif" : "image/heic"; const sourceFile = new File([fileToUpload.file], fileToUpload.file.name, { type: sourceMimeType, lastModified: fileToUpload.file.lastModified });

                    uploadPendingFilesWorkerLoop.mediaProviderReadyPromise ??= new Promise(resolve => {
                        const mediaSideload = async ({ file, attachmentId, additionalData = {}, signal, onSuccess, onError }) => {
                            const formData = new FormData(); formData.append("file", file, file.name); Object.entries(additionalData).forEach(([key, value]) => formData.append(key, value));
                            try { onSuccess(await window.wp.apiFetch({ path: `/wp/v2/media/${attachmentId}/sideload`, method: "POST", body: formData, signal })); } catch (error) { onError(error); }
                        };
                        const providerNode = document.createElement("div");
                        window.wp.element.createRoot(providerNode).render(window.wp.element.createElement(window.wp.uploadMedia.MediaUploadProvider, { useSubRegistry: false, settings: { mediaUpload: args => window.wp.mediaUtils.uploadMedia({ ...args, additionalData: { ...args.additionalData, generate_sub_sizes: true }, wpAllowedMimeTypes: jsVar("neoLibraryAllowedMimeTypes"), maxUploadFileSize: jsVar("neoLibraryMaxUploadFileSize") }), mediaSideload, mediaFinalize: async (attachmentId, subSizes) => window.wp.mediaUtils.transformAttachment(await window.wp.apiFetch({ path: `/wp/v2/media/${attachmentId}/finalize`, method: "POST", data: { sub_sizes: subSizes } })), mediaDelete: attachmentId => window.wp.apiFetch({ path: `/wp/v2/media/${attachmentId}?force=true`, method: "DELETE" }), allowedMimeTypes: jsVar("neoLibraryAllowedMimeTypes"), maxUploadFileSize: jsVar("neoLibraryMaxUploadFileSize"), allImageSizes: {} } }));
                        requestAnimationFrame(() => requestAnimationFrame(resolve));
                    });
                    await uploadPendingFilesWorkerLoop.mediaProviderReadyPromise;
                    const attachment = await new Promise((resolve, reject) => window.wp.data.dispatch(window.wp.uploadMedia.store).addItems({ files: [sourceFile], allowedTypes: ["image"], additionalData: { title: fileToUpload.title }, onSuccess: ([uploadedAttachment]) => resolve(uploadedAttachment), onError: reject }));
                    imgUrl = attachment.url;
                } else {
                    const formData = new FormData();
                    formData.append("file", fileToUpload.file);
                    formData.append("clean-uploaded-title", fileToUpload.title);
                    try { await refreshNonceIfOutdated(); } catch (error) { }
                    const respText = await new Promise((resolve, reject) => {
                        let xhr = new XMLHttpRequest();
                        xhr.open("POST", jsVar("neoLibraryUploadEndpointUrl"), true);
                        xhr.setRequestHeader("X-WP-Nonce", fetchEndpoint.nonce ?? jsVar("neoLibraryUploadEndpointNonce"));
                        xhr.setRequestHeader("X-Neo-Library", "true");
                        xhr.upload.onprogress = event => {
                            if (event.total > 0) { updateRowAttribute(fileToUpload.row, "data-message", `<span class="neo-library--rocket-animation"><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/send.svg" alt=""></span> ${neo__("Uploading...", "Hochladen...")} ${Math.round(event.loaded / event.total * 100)}%`); }
                        };
                        xhr.upload.onload = () => updateRowAttribute(fileToUpload.row, "data-message", `<span class="neo-library--processing-animation" aria-hidden="true"></span> ${neo__("Processing...", "Verarbeite...")}`);
                        xhr.onload = () => {
                            if (xhr.status === 200) { resolve(xhr.responseText); }
                            else {
                                const dummyNode = document.createElement("div");
                                dummyNode.innerHTML = xhr.responseText;
                                dummyNode.querySelectorAll("style, script").forEach(node => node.remove());
                                let errorText = dummyNode.textContent;
                                try { const errorJson = extractJson(errorText); errorText = errorJson.message || errorText; } catch (err) { }
                                reject(new Error(errorText));
                            }
                        };
                        xhr.onerror = () => reject(new Error(neo__("Network error", "Netzwerkfehler")));
                        xhr.send(formData);
                    });
                    imgUrl = extractJson(respText).imgUrl;
                }

                updateRowAttribute(fileToUpload.row, "data-message", `<span class="neo-library--check-animation"><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/circle-check.svg" alt=""></span> ` + neo__("Uploaded", "Hochgeladen"));
                updateRowAttribute(fileToUpload.row, "data-img-url", imgUrl);
                updateRowAttribute(fileToUpload.row, "data-thumbnail-img-url", null);
                updateRowAttribute(fileToUpload.row, "data-is-neodraw", "false");
                updateRowAttribute(fileToUpload.row, "data-upload-date", new Date().toISOString().replace("T", " ").substring(0, 19));
                updateRowAttribute(fileToUpload.row, "data-modified-date", new Date().toISOString().replace("T", " ").substring(0, 19));
                updateRowAttribute(fileToUpload.row, "data-state", "download-pending");
                uploadErrorMessage = null;
                animateRowShine(fileToUpload.row);
            } catch (err) {
                uploadErrorMessage = err.message;
            }
        }

        if (!uploadErrorMessage) {
            const imgUrlsInOrder = filesToUpload.map(f => f.row.getAttribute("data-img-url"));
            await fetchEndpoint("/wp-json/neo/library-correct-upload-order", { method: "POST", body: { "img-urls": imgUrlsInOrder } })
                .catch(err => { neoError(err), uploadErrorMessage = neo__("Error while correcting upload order", "Fehler beim Korrigieren der Upload-Reihenfolge"); throw err; });
        }

        if (uploadErrorMessage) {
            neoError(uploadErrorMessage); updateRowAttribute(fileToUpload.row, "data-message", `<span class="neo-library--upload-error-message"><span><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/circle-x.svg" alt=""></span> ` + neo__("Error", "Fehler") + ": " + uploadErrorMessage + `</span>`);
        }
    }
    uploadPendingFilesWorkerLoop.numberOfRunningWorkers--;
}

observeOnce("#neo-library--file-selector", (fileSelectorNode) => fileSelectorNode.addEventListener("change", (evt) => { handleFiles(evt.target.files); evt.target.value = ""; }, false));
observeOnce("#neo-library--upload-row", (uploadRowNode) => uploadRowNode.addEventListener("click", async () => (await observeOnce("#neo-library--file-selector")).click(), false));

let resetDropOverClassTimeout = null;
function canBeDropped(evt) {
    return evt.dataTransfer.types.includes("Files") || evt.dataTransfer.types.includes("text/html");
}

(await observeOnce("body")).addEventListener("dragover",
    async (evt) => {
        evt.preventDefault();
        if (!canBeDropped(evt)) { return; }
        clearTimeout(resetDropOverClassTimeout); resetDropOverClassTimeout = setTimeout(async () => (await getLibraryTable()).classList.remove('neo-library--dropover-animation'), 400);
        if ((await getLibraryTable()).classList.contains("neo-library--dropover-animation")) { return; }
        (await getLibraryTable()).classList.add("neo-library--dropover-animation");
        if (window.scrollY > 0) { window.scrollTo({ top: 0, behavior: "smooth" }); }
    },
    false
);

(await observeOnce("body")).addEventListener("drop", (evt) => { evt.preventDefault(); if (canBeDropped(evt)) { handleFiles(evt.dataTransfer.files); } }, false);
