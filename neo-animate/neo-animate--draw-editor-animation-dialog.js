import { jsVar } from "./_global--enqueue-loader.js";
export async function interfaceAddNeoAnimatePluginVersionToAnimationMetaSuppressErrorPopup20260611(animationMeta) {
    return { ...animationMeta, pluginVersion: jsVar("neoAnimatePluginVersion") };
}
export async function interfaceNeoDrawAnimationDialogSuppressErrorPopup20260611() {
    const neoAnimateEditorUrl = jsVar("neoAnimateEditorUrl");
    return ({ Dialog, svg, animationMeta, onChange, onRemoveAnimation, onClose, onSave, SaveCloseButtons, isDirty, saveLoading, closeTitle }) => {
        let iframeRef = React.useRef(null);
        const [isIframeReady, setIsIframeReady] = React.useState(false);
        const neoAnimateEditorOrigin = new URL(neoAnimateEditorUrl, location.href).origin;
        const postSvgToIframe = () => { if (!svg) { return; } iframeRef?.current?.contentWindow?.postMessage({ action: "loadSvg", svg, animationMeta }, neoAnimateEditorOrigin); };
        React.useEffect(() => {
            const onWindowMessage = (evt) => {
                if (evt.origin !== neoAnimateEditorOrigin || evt.source !== iframeRef?.current?.contentWindow) { return; }
                if (evt.data?.action === "readyForSvg") { postSvgToIframe(); }
                if (evt.data?.action === "svgLoaded") { setIsIframeReady(true); }
                if (evt.data?.action === "changeAnimation") {
                    onChange(evt.data.animation);
                    iframeRef?.current.contentWindow.focus();
                }
                if (evt.data?.action === "removeAnimation") { onRemoveAnimation(); }
            };
            window.addEventListener("message", onWindowMessage);
            return () => {
                window.removeEventListener("message", onWindowMessage);
            };
        }, [svg, animationMeta, iframeRef]);
        React.useEffect(() => { postSvgToIframe(); }, [svg, animationMeta]);
        return React.createElement(
            Dialog,
            { fullSize: true, loading: !isIframeReady, topRightButtons: React.createElement(SaveCloseButtons, { isDirty, saveLoading, onSave, onClose, closeTitle }), onClose },
            React.createElement("iframe", { src: neoAnimateEditorUrl, ref: iframeRef, onLoad: postSvgToIframe })
        );
    };
}
