import { Dialog } from "./neo-draw--editor-react-dialog.js";

export const LibraryDialog = ({ url, onClose }) => {
    return React.createElement(
        Dialog,
        {
            boxClassName: "neo-draw--icon-library-dialog-box",
            fullSize: true,
            onClose
        },
        React.createElement(
            "iframe",
            {
                src: url
            }
        )
    );
};
