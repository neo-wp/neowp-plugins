import { neoError } from "./_global--log.js";
import { observeOnce } from "./_global--observer.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";
import { exportAndDownload } from "./neo-draw--editor-communication.js";
import { Dialog } from "./neo-draw--editor-react-dialog.js";

const img = new Image(); img.src = pluginUrl() + "/img/neo-draw--editor-dialog-error-icon.svg";
img.style.display = "none"; (await observeOnce("body")).appendChild(img);

export const ErrorDialog = ({ message, onClose }) => {
    const [backupDownloadLoading, setBackupDownloadLoading] = React.useState(false);

    return React.createElement(
        Dialog,
        null,
        React.createElement(
            "img",
            {
                className: "neo-draw--dialog-icon neo-draw--editor-dialog-error-icon",
                src: pluginUrl() + "/img/neo-draw--editor-dialog-error-icon.svg",
                alt: "",
            }
        ),
        React.createElement(
            "div",
            { className: "neo-draw--error-dialog-content" },
            message
        ),
        React.createElement(
            "div",
            {
                className: "excalidraw neo-draw--dialog-button-container"
            },
            React.createElement(
                "button",
                {
                    className: "excalidraw-button neo-draw--dialog-button neo-draw--dialog-primary-button",
                    disabled: backupDownloadLoading,
                    onClick: async () => {
                        setBackupDownloadLoading(true);
                        try {
                            await exportAndDownload();
                        } catch (error) {
                            neoError(error);
                            alert(neo__("Backup generation failed.", "Backup-Generierung fehlgeschlagen.") + " " + error.message);
                        }
                        setBackupDownloadLoading(false);
                    },
                },
                backupDownloadLoading ?
                    neo__("Loading...", "Lädt...") :
                    neo__("Download backup", "Backup herunterladen")
            ),
            React.createElement(
                "button",
                {
                    className: "excalidraw-button neo-draw--dialog-button neo-draw--dialog-ok-button",
                    onClick: () => onClose(),
                },
                neo__("Close", "Schließen")
            )
        )
    );
};
