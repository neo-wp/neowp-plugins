import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { jsVar, jsVarExists } from "./_global--enqueue-loader.js";
import { isNeoMotionFrameElement } from "./neo-motion--draw-export.js";
const { loadFromBlob } = window.ExcalidrawLib;

export async function interfaceNeoDrawMotionPreviewButtonSuppressErrorPopup20260611() {
    return ({ elements, exportSvgAsString, onError, buttonClassName, buttonLoadingClassName, spinnerClassName, previewPopupId }) => {
        const [motionPreviewDialogLoading, setMotionPreviewDialogLoading] = React.useState(false);
        const [motionPreviewDialogUrl, setMotionPreviewDialogUrl] = React.useState(null);
        const hasMotionPreviewFrames = elements.some(isNeoMotionFrameElement);
        React.useEffect(() => () => { document.querySelector("#" + previewPopupId)?.remove(); }, [previewPopupId]);
        React.useEffect(() => {
            if (!motionPreviewDialogUrl) { return; }
            const motionPreviewPopup = document.querySelector("#" + previewPopupId) ?? document.body.appendChild(Object.assign(document.createElement("neo-media-preview-popup-neo-motion"), { id: previewPopupId }));
            if (motionPreviewPopup?.open) { motionPreviewPopup.open(motionPreviewDialogUrl); return; }
            motionPreviewPopup?.setAttribute("src", motionPreviewDialogUrl); motionPreviewPopup?.setAttribute("open", "");
        }, [motionPreviewDialogUrl, previewPopupId]);
        React.useEffect(() => () => { if (motionPreviewDialogUrl) { URL.revokeObjectURL(motionPreviewDialogUrl); } }, [motionPreviewDialogUrl]);
        if (!hasMotionPreviewFrames) { return null; }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "button",
                {
                    className: buttonClassName + (motionPreviewDialogLoading ? " " + buttonLoadingClassName : ""),
                    key: "motion-preview-button",
                    title: neo__("neoMotion preview", "neoMotion Vorschau"),
                    onClick: async () => {
                        setMotionPreviewDialogLoading(true);
                        try {
                            const motionPreviewBlobUrl = URL.createObjectURL(new Blob([await exportSvgAsString()], { type: "image/svg+xml" }));
                            setMotionPreviewDialogUrl((oldMotionPreviewDialogUrl) => { if (oldMotionPreviewDialogUrl) { URL.revokeObjectURL(oldMotionPreviewDialogUrl); } return motionPreviewBlobUrl; });
                        } catch (e) {
                            onError(e);
                        }
                        setMotionPreviewDialogLoading(false);
                    },
                },
                React.createElement(
                    "img",
                    { src: pluginUrl() + "/img/neo-motion--play-icon.svg" }
                ),
                React.createElement(
                    "div",
                    {
                        key: "motion-preview-button-spinner",
                        className: spinnerClassName,
                    }
                )
            )
        );
    };
}
export async function interfaceNeoDrawMotionStarterHintSuppressErrorPopup20260616() {
    return ({ elements, excalidrawAPI, onError }) => {
        const [starterHintDismissed, setStarterHintDismissed] = React.useState(false);
        const [starterHintLoading, setStarterHintLoading] = React.useState(false);
        const hasVisibleElements = elements.some(element => !element.isDeleted);
        const showHintVariableExists = jsVarExists("neoMotionShowDrawStarterHint");
        const showHintVariableValue = showHintVariableExists ? jsVar("neoMotionShowDrawStarterHint") : null;
        React.useEffect(() => { if (hasVisibleElements) { setStarterHintDismissed(true); } }, [hasVisibleElements]);
        if (!showHintVariableExists || !showHintVariableValue) { return null; }
        if (starterHintDismissed || hasVisibleElements || !excalidrawAPI) { return null; }
        return React.createElement(
            "div",
            {
                className: "motion-starter-hint",
                key: "motion-starter-hint",
            },
            React.createElement(
                "button",
                {
                    className: "motion-starter-hint-close",
                    type: "button",
                    title: neo__("Close", "Schließen"),
                    onClick: () => setStarterHintDismissed(true),
                },
                "×"
            ),
            React.createElement(
                "div",
                {
                    className: "motion-starter-hint-text",
                },
                neo__("To create a neoMotion animation, start with the sample file.", "Um eine neoMotion-Animation zu erstellen, starte mit der Beispieldatei.")
            ),
            React.createElement(
                "button",
                {
                    className: "motion-starter-hint-button",
                    type: "button",
                    disabled: starterHintLoading,
                    onClick: async () => {
                        setStarterHintLoading(true);
                        try {
                            const starterImageBlob = await fetch(jsVar("neoMotionStarterImageUrl")).then(response => response.blob());
                            const starterSceneData = await loadFromBlob(starterImageBlob, null, null, null);
                            excalidrawAPI.addFiles(Object.values(starterSceneData.files ?? {}));
                            excalidrawAPI.updateScene({...starterSceneData, commitToStore: true});
                            excalidrawAPI.scrollToContent(undefined, { fitToViewport: true, viewportZoomFactor: 0.7 });
                            const editorUrl = new URL(location.href); editorUrl.searchParams.set("force-new", "true"); editorUrl.searchParams.set("default-filename", "neo-motion.svg"); history.replaceState(null, "", editorUrl.toString());
                            setStarterHintDismissed(true);
                        } catch (e) {
                            onError(e);
                        }
                        setStarterHintLoading(false);
                    },
                },
                starterHintLoading ? neo__("Loading...", "Lädt...") : neo__("Load sample", "Beispiel laden")
            )
        );
    };
}
