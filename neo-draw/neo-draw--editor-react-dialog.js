import { observeOnce } from "./_global--observer.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";

const closeImg = new Image();
closeImg.src = pluginUrl() + "/img/_global-close-icon.svg";
const saveImg = new Image();
saveImg.src = pluginUrl() + "/img/neo-draw--editor-save-icon.svg";
closeImg.style.display = "none";
saveImg.style.display = "none";
(await observeOnce("body")).append(closeImg, saveImg);

export const Dialog = (props) => {
    React.useEffect(() => {
        document.activeElement?.blur();
        const closeDialogBoxOnEsc = (event) => {
            if (event.key === "Escape" && props.onClose) { props.onClose(); }
        };
        window.addEventListener("keydown", closeDialogBoxOnEsc);
        return () => {
            window.removeEventListener("keydown", closeDialogBoxOnEsc);
        };
    }, [props.onClose]);
    return React.createElement(
        "div",
        {
            className: "neo-draw--dialog",
        },
        React.createElement(
            "div",
            {
                className: "neo-draw--backdrop",
                onClick: props.onClose,
            },
        ),
        React.createElement(
            "div",
            {
                className: "neo-draw--dialog-box" + (props.fullSize ? " neo-draw--dialog-box-full-size" : "") + (props.loading ? " neo-draw--dialog-loading-active" : "") + (props.boxClassName ? " " + props.boxClassName : ""),
            },
            props.onClose && !props.topRightButtons && React.createElement(
                "button",
                {
                    className: "neo-draw--dialog-close",
                    style: props.closeStyle,
                    onClick: props.onClose,
                },
                React.createElement("img", {
                    src: pluginUrl() + "/img/_global-close-icon.svg",
                    alt: neo__("Close", "Schließen"),
                }),
            ),
            props.topRightButtons && React.createElement(
                "div",
                {
                    className: "neo-draw--dialog-top-right-buttons",
                },
                props.topRightButtons,
            ),
            props.loading && React.createElement(
                "div",
                {
                    className: "neo-draw--dialog-loading",
                },
                React.createElement(
                    "div",
                    {
                        className: "neo-draw--dialog-loading-spinner",
                    },
                ),
            ),
            props.children,
        ),
    );
};

export const NeoDrawSaveCloseButtons = ({ isDirty, saveLoading, onSave, onClose, closeTitle = neo__("Close (Hold Alt/Option to save & close)", "Schließen (Alt/Option gedrückt halten, um zu speichern & schließen)") }) => {
    return React.createElement(
        "div",
        {
            className: "excalidraw neo-draw--save-close-buttons " + (isDirty ? "neo-draw--dirty" : "") + (saveLoading ? " neo-draw--saving" : ""),
            key: "save-close-buttons",
        },
        React.createElement(
            "button",
            {
                className: "excalidraw-button neo-draw--editor-button neo-draw--save-button",
                disabled: !isDirty,
                key: "save-button",
                onClick: onSave,
                title: neo__("Save (Ctrl/Cmd+S)", "Speichern (Strg/Cmd+S)"),
            },
            React.createElement(
                "img",
                {
                    src: pluginUrl() + "/img/neo-draw--editor-save-icon.svg",
                    alt: neo__("Save", "Speichern"),
                }
            ),
            React.createElement(
                "div",
                {
                    className: "neo-draw--spinner",
                }
            )
        ),
        React.createElement(
            "button",
            {
                className: "excalidraw-button neo-draw--editor-button neo-draw--close-button",
                key: "close-button",
                onClick: onClose,
                disabled: saveLoading,
                title: closeTitle,
            },
            React.createElement(
                "img",
                {
                    src: pluginUrl() + "/img/_global-close-icon.svg",
                }
            )
        ),
    );
};
