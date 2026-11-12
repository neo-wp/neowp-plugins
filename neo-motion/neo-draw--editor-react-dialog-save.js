import { observeOnce } from "./_global--observer.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";
import { Dialog } from "./neo-draw--editor-react-dialog.js";

const img = new Image(); img.src = pluginUrl() + "/img/neo-draw--editor-dialog-save-icon.svg";
img.style.display = "none"; (await observeOnce("body")).appendChild(img);

export const FilenameDialog = ({ defaultFilename, onAnswer }) => {
    const [filename, setFilename] = React.useState(defaultFilename.replace(/\.[^/.]+$/, "") || "neo-draw");
    const inputRef = React.useRef(null);
    const closeWithoutSaving = React.useCallback(() => onAnswer(null), [onAnswer]);
    const answerWithFilename = React.useCallback(() => onAnswer(filename || "neo-draw"), [filename, onAnswer]);
    React.useEffect(() => {
        inputRef.current.focus(); inputRef.current.select();
    }, []);
    React.useEffect(() => {
        const answerOnDialogKey = (event) => {
            if (event.key === "Enter") { event.preventDefault(); event.stopImmediatePropagation(); answerWithFilename(); }
            if (event.key === "Escape") { event.preventDefault(); event.stopImmediatePropagation(); onAnswer(null); }
        };
        window.addEventListener("keydown", answerOnDialogKey, true);
        return () => window.removeEventListener("keydown", answerOnDialogKey, true);
    }, [answerWithFilename, onAnswer]);
    return React.createElement(
        Dialog,
        { onClose: closeWithoutSaving, boxClassName: "neo-draw--filename-dialog-box" },
        React.createElement("div", { className: "neo-draw--filename-dialog-title" }, neo__("Save new image", "Neues Bild speichern")),
        React.createElement("label", { className: "neo-draw--filename-dialog-label", htmlFor: "neo-draw--filename-dialog-input" }, neo__("Filename", "Dateiname")),
        React.createElement("input", { ref: inputRef, id: "neo-draw--filename-dialog-input", className: "neo-draw--filename-dialog-input", type: "text", value: filename, onChange: (event) => setFilename(event.target.value), autoComplete: "off", spellCheck: false, "data-lpignore": "true", "data-1p-ignore": "true", "data-bwignore": "true" }),
        React.createElement(
            "div",
            { className: "excalidraw neo-draw--dialog-button-container" },
            React.createElement("button", { className: "excalidraw-button neo-draw--dialog-button", onClick: closeWithoutSaving, tabIndex: 0 }, neo__("Cancel", "Abbrechen")),
            React.createElement("button", { className: "excalidraw-button neo-draw--dialog-button neo-draw--dialog-primary-button", onClick: answerWithFilename, tabIndex: 0 }, neo__("Save", "Speichern")),
        )
    );
};

export const SaveDialog = ({ onAnswer }) => {
    React.useEffect(() => {
        document.activeElement?.blur();
        const saveOnEnter = (event) => {
            if (event.key === "Enter")  { event.preventDefault(); event.stopImmediatePropagation(); onAnswer("save"); }
            if (event.key === "Escape") { event.preventDefault(); event.stopImmediatePropagation(); onAnswer("cancel"); }
        };
        window.addEventListener("keydown", saveOnEnter, true);
        return () => window.removeEventListener("keydown", saveOnEnter, true);
    }, [onAnswer]);

    return React.createElement(
        Dialog,
        { onClose: () => onAnswer("cancel") },
        React.createElement(
            "img",
            {
                className: "neo-draw--dialog-icon",
                src: pluginUrl() + "/img/neo-draw--editor-dialog-save-icon.svg",
                alt: "",
            }
        ),
        React.createElement(
            "div",
            null,
            neo__("Save changes?", "Änderungen speichern?"),
        ),
        React.createElement(
            "div",
            {
                className: "excalidraw neo-draw--dialog-button-container"
            },
            React.createElement(
                "button",
                {
                    className: "excalidraw-button neo-draw--dialog-button",
                    onClick: () => onAnswer("discard"),
                    tabIndex: 0
                },
                neo__("Discard", "Verwerfen")
            ),
            React.createElement(
                "button",
                {
                    className: "excalidraw-button neo-draw--dialog-button neo-draw--dialog-primary-button",
                    onClick: () => onAnswer("save"),
                    tabIndex: 0
                },
                neo__("Save", "Speichern")
            ),
        )
    );
};
