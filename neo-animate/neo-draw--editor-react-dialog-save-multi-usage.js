import { neo__ } from "./_global--translation.js";
import { observeOnce } from "./_global--observer.js";
import { Dialog } from "./neo-draw--editor-react-dialog.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { imageUsageLookupPageUrl } from "./_global-db-entries-usage-page.js";

const img = new Image(); img.src = pluginUrl() + "/img/neo-draw--editor-dialog-save-warn-icon.svg";
img.style.display = "none"; (await observeOnce("body")).appendChild(img);

export const SaveMultiUsageDialog = ({ onAnswer, imgUrl, countInCurrentPost }) => {
    const [hideForAllImages, setHideForAllImages] = React.useState(false);
    const answerAndSavePreference = React.useCallback((answer) => { localStorage.setItem("neo-draw--save-multi-usage-dialog-hidden", hideForAllImages ? "true" : "false"); onAnswer(answer); }, [hideForAllImages, onAnswer]);
    const answerWithDefaultButton = React.useCallback(() => answerAndSavePreference("changeAll"), [answerAndSavePreference]);
    React.useEffect(() => {
        document.activeElement?.blur();
        const answerOnDialogKey = (event) => {
            if (event.key === "Enter") { event.preventDefault(); event.stopImmediatePropagation(); answerWithDefaultButton(); }
            if (event.key === "Escape") { event.preventDefault(); event.stopImmediatePropagation(); onAnswer("cancel"); }
        };
        window.addEventListener("keydown", answerOnDialogKey, true);
        return () => window.removeEventListener("keydown", answerOnDialogKey, true);
    }, [answerWithDefaultButton, onAnswer]);
    return React.createElement(
        Dialog,
        { onClose: () => onAnswer("cancel"), boxClassName: "neo-draw--save-multi-usage-dialog-box" },
        React.createElement(
            "img",
            {
                className: "neo-draw--dialog-icon",
                src: pluginUrl() + "/img/neo-draw--editor-dialog-save-warn-icon.svg",
                alt: "",
            }
        ),
        React.createElement(
            "div",
            null,
            neo__("The image is used multiple times on the site.", "Das Bild wird auf der Seite mehrmals verwendet."),
            " ",
            React.createElement("a", {
                href: imageUsageLookupPageUrl({ imgUrl, countInCurrentPost }),
                target: "_blank",
                style: { color: "#444" }
            }, neo__("Show where it is used (in new tab)", "Alle Verwendungen anzeigen (in neuem Tab)"))
        ),
        React.createElement(
            "div",
            { className: "excalidraw neo-draw--dialog-button-container" },
            React.createElement(
                "input",
                {
                    type: "checkbox",
                    id: "hideUntilChanged",
                    checked: hideForAllImages,
                    onChange: (event) => setHideForAllImages(event.target.checked),
                    tabIndex: 0
                }
            ),
            React.createElement(
                "label",
                {
                    htmlFor: "hideUntilChanged",
                    style: { marginLeft: "0.5em" }
                },
                neo__("Always hide this message for all images", "Diese Meldung immer für alle Bilder ausblenden")
            )
        ),
        React.createElement(
            "div",
            { className: "excalidraw neo-draw--dialog-button-container" },
            React.createElement(
                "button",
                {
                    className: "excalidraw-button neo-draw--dialog-button",
                    onClick: () => answerAndSavePreference("createNew"),
                    tabIndex: 0
                },
                neo__("Keep others & save as copy", "Andere behalten & als Kopie speichern")
            ),
            React.createElement(
                "button",
                {
                    className: "excalidraw-button neo-draw--dialog-button neo-draw--dialog-primary-button",
                    onClick: answerWithDefaultButton,
                    tabIndex: 0
                },
                neo__("Change all", "Alle ändern")
            ),
        )
    );
};
