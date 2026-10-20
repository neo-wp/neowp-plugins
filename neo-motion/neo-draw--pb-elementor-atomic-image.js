import Swal from "./_global-sweetalert2.js";
import { InterfaceEditorDialog20260826 } from "./neo-draw--editor-dialog.js";
import { extractJson } from "./_global--extract-json.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { neoError } from "./_global--log.js";
import { neo__ } from "./_global--translation.js";
import { addQueryParam } from "./_global--url-helper.js";

try {
    const unwrapAtomicValue = (value) => value && typeof value === "object" && Object.prototype.hasOwnProperty.call(value, "$$type") && Object.prototype.hasOwnProperty.call(value, "value") ? value.value : value;
    const getImageSrcValueFromImageValue = (imageValue) => unwrapAtomicValue(imageValue?.src ?? null);
    const getImageSizeValueFromImageValue = (imageValue) => unwrapAtomicValue(imageValue?.size ?? null) ?? "full";
    const getImageAltFromValue = (srcValue) => unwrapAtomicValue(srcValue)?.alt?.value ?? "";

    const createImageSrcValue = ({ imgId, imgUrl, alt }) => imgId
        ? { id: { $$type: "image-attachment-id", value: imgId }, url: null }
        : { id: null, url: window.elementorV2.editorProps.urlPropTypeUtil.create(imgUrl), alt: alt ? window.elementorV2.editorProps.stringPropTypeUtil.create(alt) : null };
    const createImageValue = ({ currentValue, imgId, imgUrl, alt }) => ({
        src: window.elementorV2.editorProps.imageSrcPropTypeUtil.create(createImageSrcValue({ imgId, imgUrl, alt })),
        size: window.elementorV2.editorProps.stringPropTypeUtil.create(getImageSizeValueFromImageValue(currentValue)),
    });

    const hasAtomicElementorApis = () => Boolean(window.elementorV2?.editorEditingPanel?.controlsRegistry?.register);
    const waitForAtomicElementor = async () => {
        for (let i = 0; i < 200; i++) {
            if (hasAtomicElementorApis()) { return true; }
            await new Promise(resolve => setTimeout(resolve, 50));
        }
        return false;
    };

    const getImageUrlFromSrcValue = ({ srcValue, attachment, placeholderAttachment }) => {
        const unwrappedSrcValue = unwrapAtomicValue(srcValue) ?? {};
        const imageUrl = attachment?.url ?? unwrappedSrcValue?.url?.value ?? (!unwrappedSrcValue?.id?.value ? placeholderAttachment?.url : "") ?? "";
        return imageUrl && !imageUrl.endsWith("assets/images/placeholder.png") ? imageUrl : "";
    };
    const syncAtomicSettingToPreview = ({ bound, value, propTypeUtil }) => {
        const selectedElement = window.elementorV2.editorElements.getSelectedElements()?.[0] ?? null;
        if (!selectedElement?.id || !bound.bind) { return; }
        window.elementorV2.editorElements.updateElementSettings({ id: selectedElement.id, props: { [bound.bind]: propTypeUtil.create(value) }, withHistory: false });
    };

    const getImgIdFromUrl = async (imgUrl) => {
        const { imgId } = await fetchEndpoint("/wp-json/neo/draw-editor-img-id", { query: { "img-url": imgUrl } }).then(extractJson);
        return imgId || null;
    };
    const refreshElementorPreviewImages = (imgUrl) => {
        const previewIframeBody = document.querySelector("#elementor-preview-iframe")?.contentWindow?.document?.body;
        if (!previewIframeBody) { return; }
        const imgUrlWithoutQuery = imgUrl.split("?")[0];
        const cachebustedImgUrl = imgUrlWithoutQuery + "?c=" + Date.now();
        for (const img of previewIframeBody.querySelectorAll("img")) {
            const currentImgUrlWithoutQuery = (img.getAttribute("src") ?? "").split("?")[0];
            if (!currentImgUrlWithoutQuery || !imgUrlWithoutQuery.endsWith(currentImgUrlWithoutQuery.replace("http://", "").replace("https://", "")) && !currentImgUrlWithoutQuery.endsWith(imgUrlWithoutQuery.replace("http://", "").replace("https://", ""))) { continue; }
            img.setAttribute("src", cachebustedImgUrl);
        }
    };

    const AtomicNeoDrawImageBaseControl = ({ sizes, label, isImageValue }) => {
        const React = window.React;
        const ui = window.elementorV2.ui;
        const controls = window.elementorV2.editorControls;
        const props = window.elementorV2.editorProps;
        const media = window.elementorV2.wpMedia;
        const bound = controls.useBoundProp(isImageValue ? props.imagePropTypeUtil : props.imageSrcPropTypeUtil);
        const [savedPreviewUrl, setSavedPreviewUrl] = React.useState("");
        const [savedPreviewBust, setSavedPreviewBust] = React.useState(0);
        const srcValue = isImageValue ? getImageSrcValueFromImageValue(bound.value) : bound.value ?? null;
        const placeholderSrcValue = isImageValue ? getImageSrcValueFromImageValue(bound.placeholder) : bound.placeholder ?? null;
        const { data: attachment, isFetching } = media.useWpMediaAttachment(srcValue?.id?.value || null);
        const { data: placeholderAttachment } = media.useWpMediaAttachment(placeholderSrcValue?.id?.value || null);
        const imgUrl = savedPreviewUrl || getImageUrlFromSrcValue({ srcValue, attachment, placeholderAttachment });
        const imgControlPreviewUrl = savedPreviewBust && imgUrl && !imgUrl.startsWith("data:") && !imgUrl.startsWith("blob:")
            ? addQueryParam(imgUrl, "neo-draw--pb-elementor-atomic-image-cachebust", savedPreviewBust)
            : imgUrl || attachment?.url || placeholderAttachment?.url || "";
        const setAtomicValue = ({ imgId, imgUrl, alt }) => {
            const nextValue = isImageValue ? createImageValue({ currentValue: bound.value, imgId, imgUrl, alt }) : createImageSrcValue({ imgId, imgUrl, alt });
            setSavedPreviewUrl(imgUrl);
            setSavedPreviewBust(Date.now());
            bound.setValue(nextValue);
            syncAtomicSettingToPreview({ bound, value: nextValue, propTypeUtil: isImageValue ? props.imagePropTypeUtil : props.imageSrcPropTypeUtil });
        };
        const { open } = media.useWpMediaFrame({
            mediaTypes: ["image", "svg"],
            multiple: false,
            selected: srcValue?.id?.value || null,
            allowUrlImport: true,
            onSelect: selected => setAtomicValue({ imgId: selected.id, imgUrl: selected.url, alt: getImageAltFromValue(srcValue) }),
            onSelectUrl: (url, alt) => setAtomicValue({ imgId: null, imgUrl: url, alt }),
        });
        const openNeoDraw = async () => {
            new InterfaceEditorDialog20260826().imgUrl(imgUrl).on("save", async (details) => {
                try {
                    const newImgUrl = details.imgUrl;
                    const imgId = details.imgId || await getImgIdFromUrl(newImgUrl);
                    setAtomicValue({ imgId, imgUrl: newImgUrl, alt: getImageAltFromValue(srcValue) });
                    refreshElementorPreviewImages(newImgUrl);
                } catch (err) {
                    neoError(err);
                    await Swal.fire({ icon: "error", title: neo__("neoDraw failed", "neoDraw fehlgeschlagen"), text: err.message });
                    throw err;
                }
            }).open();
        };
        const imageCard = React.createElement(ui.Card, { variant: "outlined", className: "neo-draw--atomic-image-control__card" },
            React.createElement(ui.CardMedia, { image: imgControlPreviewUrl, sx: { height: 150 } },
                isFetching ? React.createElement(ui.Stack, { justifyContent: "center", alignItems: "center", width: "100%", height: "100%" }, React.createElement(ui.CircularProgress, null)) : React.createElement(React.Fragment, null)
            ),
            React.createElement(ui.CardOverlay, null,
                React.createElement(ui.Stack, { gap: 1 },
                    React.createElement(ui.Button, { size: "tiny", color: "inherit", variant: "contained", className: "neo-draw--atomic-image-control__neodraw-button", onClick: openNeoDraw }, imgUrl ? neo__("Edit", "Bearbeiten") : neo__("Create neoDraw", "neoDraw erstellen")),
                    React.createElement(ui.Button, { size: "tiny", color: "inherit", variant: "outlined", onClick: () => open({ mode: "browse" }) }, neo__("Select image", "Bild auswählen")),
                    React.createElement(ui.Button, { size: "tiny", color: "inherit", variant: "text", onClick: () => open({ mode: "upload" }) }, neo__("Upload", "Hochladen")),
                    React.createElement(ui.Button, { size: "tiny", color: "inherit", variant: "text", onClick: () => open({ mode: "url", currentUrl: srcValue?.url?.value, currentAlt: getImageAltFromValue(srcValue) }) }, neo__("Insert from URL", "Von URL einfügen"))
                )
            )
        );
        return React.createElement(controls.PropProvider, bound,
            React.createElement(ui.Stack, { gap: 1.5, className: "neo-draw--atomic-image-control" },
                React.createElement(ui.Typography, { variant: "caption", color: "text.secondary", className: "neo-draw--atomic-image-control__description" }, neo__("Everything as usual, additionally with neoDraw.", "Alles wie gewohnt, zusätzlich mit neoDraw.")),
                isImageValue ? React.createElement(ui.Stack, { gap: 1.5 },
                    React.createElement(ui.Typography, { variant: "caption", color: "text.primary" }, label || neo__("Image", "Bild")),
                    imageCard,
                    React.createElement(ui.Grid, { container: true, gap: 1.5, alignItems: "center", flexWrap: "nowrap" },
                        React.createElement(ui.Grid, { item: true, xs: 6 }, React.createElement(ui.FormLabel, { size: "tiny" }, neo__("Resolution", "Auflösung"))),
                        React.createElement(ui.Grid, { item: true, xs: 6, sx: { overflow: "hidden" } }, React.createElement(controls.PropKeyProvider, { bind: "size" }, React.createElement(controls.SelectControl, { options: sizes ?? [] })))
                    )
                ) : imageCard
            )
        );
    };
    const AtomicNeoDrawImageControl = ({ sizes, label }) => window.React.createElement(AtomicNeoDrawImageBaseControl, { sizes, label, isImageValue: true });
    const AtomicNeoDrawImageSrcControl = () => window.React.createElement(AtomicNeoDrawImageBaseControl, { isImageValue: false });

    const registerAtomicControl = ({ controlsRegistry, type, component, propTypeUtil }) => {
        if (controlsRegistry.get(type) === component) { return; }
        if (controlsRegistry.get(type)) { controlsRegistry.unregister(type); }
        controlsRegistry.register(type, component, "custom", propTypeUtil);
    };
    const initAtomicNeoDraw = async () => {
        if (!await waitForAtomicElementor()) { return; }
        registerAtomicControl({ controlsRegistry: window.elementorV2.editorEditingPanel.controlsRegistry, type: "image", component: AtomicNeoDrawImageControl, propTypeUtil: window.elementorV2.editorProps.imagePropTypeUtil });
        registerAtomicControl({ controlsRegistry: window.elementorV2.editorEditingPanel.controlsRegistry, type: "image-src", component: AtomicNeoDrawImageSrcControl, propTypeUtil: window.elementorV2.editorProps.imageSrcPropTypeUtil });
    };
    await initAtomicNeoDraw();
} catch (err) {
    neoError(err);
}
