import { neoError } from "./_global--log.js";
import { neo__ } from "./_global--translation.js";
import { addQueryParam, getQueryParam, fitProtocolToFetchImgUrl, removeQueryParam } from "./_global--url-helper.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";

export function preloadNeoDrawEditorScripts() {
    if (preloadNeoDrawEditorScripts.preloadPromise) { return preloadNeoDrawEditorScripts.preloadPromise; }
    const scriptUrls = [pluginUrl() + "/neo-draw--excalidraw-thirdparty/react.js", pluginUrl() + "/neo-draw--excalidraw-thirdparty/react-dom.js", pluginUrl() + "/neo-draw--editor-xml-formatter-thirdparty/neo-draw--editor-xml-formatter.min.js", pluginUrl() + "/neo-draw--excalidraw-thirdparty/excalidraw.js", pluginUrl() + "/_global-web-component-importer.js", pluginUrl() + "/neo-draw--editor-bootstrap.js", pluginUrl() + "/neo-draw--editor-index.js"];
    preloadNeoDrawEditorScripts.preloadPromise = Promise.all(scriptUrls.map(async (scriptUrl) => {
        try {
            const response = await fetch(scriptUrl, { cache: "force-cache", credentials: "same-origin" });
            if (!response.ok) { return false; }
            await response.arrayBuffer();
            return true;
        } catch (error) {
            return false;
        }
    }));
    return preloadNeoDrawEditorScripts.preloadPromise;
}
export function interfacePreloadNeoDrawEditorScripts20260726() { return preloadNeoDrawEditorScripts(); }

export function interfaceConsumeNeoDrawCreateQueryParamSuppressErrorPopup20260811() {
    const shouldCreateNeoDraw = getQueryParam(location.href, "neo-draw--create") === "true";
    if (!shouldCreateNeoDraw) { return false; }
    history.replaceState({}, "", removeQueryParam(location.href, "neo-draw--create"));
    return true;
}

export class InterfaceEditorDialog20260826 {
    constructor() {
        this.eventListeners = {};
        this._imgUrl = "";
    }

    imgUrl(imgUrl) {
        this._imgUrl = imgUrl;
        return this;
    }
    suppressInsert()                { this._suppressInsert = true;      return this; }
    forceInsert()                   { this._forceInsert = true;         return this; }
    fullSize()                      { this._fullSize = true;            return this; }
    forceNew()                      { this._forceNew = true;            return this; }
    defaultFilename(filename)       { this._defaultFilename = filename; return this; }
    hidden()                        { this._hidden = true;              return this; }

    open() {
        let fullEditorUrl = jsVar("neoDrawEditorUrl");
        if (!this._imgUrl.startsWith("data:")) { fullEditorUrl = addQueryParam(fullEditorUrl, "img-url", this._imgUrl); }
        else { fullEditorUrl = addQueryParam(fullEditorUrl, "img-url", "base64"); }
        const editorOrigin = new URL(fullEditorUrl, location.href).origin;
        const postGetParam = parseInt(getQueryParam(location.href, "post"));
        if (Number.isInteger(postGetParam)) {
            fullEditorUrl = addQueryParam(fullEditorUrl, "post-id", postGetParam);
        }
        if (this._suppressInsert)    { fullEditorUrl = addQueryParam(fullEditorUrl, "suppress-insert", "true"); }
        if (this._forceInsert)       { fullEditorUrl = addQueryParam(fullEditorUrl, "force-insert", "true"); }
        if (this._forceNew)          { fullEditorUrl = addQueryParam(fullEditorUrl, "force-new", "true"); }
        if (this._defaultFilename)   { fullEditorUrl = addQueryParam(fullEditorUrl, "default-filename", this._defaultFilename); }
        fullEditorUrl = addQueryParam(fullEditorUrl, "is-hidden", "" + (this._hidden ?? false));

        this.dialogNode = document.createElement("div");
        this.dialogNode.setAttribute("class", "neo-draw--editor-dialog");
        if (this._fullSize) {
            this.dialogNode.classList.add("neo-draw--editor-dialog-full-size");
        }
        if (this._hidden) {
            this.dialogNode.style.visibility = "hidden";
        }

        this.dialogNode.innerHTML = `
            <div class="neo-draw--editor-dialog-box">
                <div class="neo-draw--editor-dialog-spinner"></div>
                <button type="button" class="neo-draw--editor-loading-close">${neo__("Cancel", "Abbrechen")}</button>
                <iframe class="neo-draw--editor-iframe" src="${fullEditorUrl}" frameborder="0"></iframe>
            </div>
        `;
        this.dialogNode.querySelector(".neo-draw--editor-loading-close").addEventListener("click", () => this._cleanUpAfterClose());
        document.body.appendChild(this.dialogNode);

        const loadCallback = () => {
            this.iframe.classList.add("neo-draw--editor-iframe-loaded");

            setTimeout(() => {
                const spinner = document.querySelector(".neo-draw--editor-dialog-spinner");
                if (spinner) { spinner.remove(); }

                const cancelButton = document.querySelector(".neo-draw--editor-loading-close");
                if (cancelButton) { cancelButton.remove(); }
            }, 1000);

            try {
                window.dev ??= {}; for (const key of Object.keys(this.iframe.contentWindow.dev)) { window.dev[key] = this.iframe.contentWindow.dev[key]; }
            } catch {
            }
        };
        const closeCallback = () => this._cleanUpAfterClose();
        this._internalEventListeners ??= []; this._internalEventListeners.push(["load", loadCallback], ["close", closeCallback]);
        this.on("load", loadCallback);

        this.on("close", closeCallback);

        this.iframe = this.dialogNode.querySelector(".neo-draw--editor-iframe");

        if (this._imgUrl.startsWith("data:")) {
            this._base64ReadyListener = (event) => {
                if (event.source !== this.iframe?.contentWindow || event.data?.action !== "readyForBase64Url") { return; }
                this.iframe.contentWindow.postMessage({ action: "loadBase64Url", data: this._imgUrl }, editorOrigin);
            };
            window.addEventListener("message", this._base64ReadyListener);
        }
        this._receiveMessageBoundToThis = this._receiveMessage.bind(this);
        window.addEventListener("message", this._receiveMessageBoundToThis);

        return this;
    }

    on(action, callback) {
        if (!this.eventListeners[action]) { this.eventListeners[action] = []; }
        this.eventListeners[action].push(callback);
        return this;
    }

    _receiveMessage(event) {
        this._messageQueue = (this._messageQueue ?? Promise.resolve()).catch(e => neoError(e)).then(async () => {
            if (!this.iframe?.contentWindow) { return; }
            if (event.source !== this.iframe.contentWindow) { return; }
            if (event.data.action === "save" && event.data.data?.imgUrl) {
                try { await fetch(fitProtocolToFetchImgUrl(event.data.data.imgUrl), { cache: "reload" }); } catch (e) { neoError(e); }
            }

            if (this.eventListeners[event.data.action]) {
                for (const callback of this.eventListeners[event.data.action]) {
                    await callback(event.data.data);
                }
            }
        });
        return this._messageQueue;
    }

    _cleanUpAfterClose() {
        this.dialogNode?.remove();
        this.dialogNode = undefined;
        this.iframe = undefined;
        for (const [action, callback] of this._internalEventListeners ?? []) { this.eventListeners[action] = (this.eventListeners[action] ?? []).filter(listener => listener !== callback); }
        this._internalEventListeners = [];
        window.removeEventListener("message", this._receiveMessageBoundToThis);
        window.removeEventListener("message", this._base64ReadyListener);
        this._base64ReadyListener = undefined;
    }

    save() {
        this.iframe.contentWindow.neoDrawSave({ usageCheck: false });
    }

    exportSvgString() {
        return this.iframe.contentWindow.neoDrawExportSvgAsString();
    }

    exportPngOrJpegOrWebp(mimeType = "image/png", maxSize = undefined) { return (async () => {
        return this.iframe.contentWindow.neoDrawExportPngOrJpgOrWebp(mimeType, maxSize);
    })(); }

    closeWithoutConfirmation() {
        this._cleanUpAfterClose();
    }
}
