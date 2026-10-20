const { createElement: createReactElement, createPortal: createReactPortal, useEffect, useRef, useState } = wp.element;
import { neo__ } from "./_global--translation.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { preloadNeoDrawEditorScripts } from "./neo-draw--editor-dialog.js";
import { addQueryParam } from "./_global--url-helper.js";

export function NeoDrawGutenbergBlockComponent(props) {
    const { attributes, setAttributes, BlockEdit } = props;
    const neoDrawBlockWrapperNodeRef = useRef(null);
    const [neoDrawButtonContainerNode, setNeoDrawButtonContainerNode] = useState(null);
    useEffect(() => {
        setNeoDrawButtonContainerNode(props.isSelected ? attributes.url ? neoDrawBlockWrapperNodeRef.current?.querySelector("figure.wp-block-image") ?? null : neoDrawBlockWrapperNodeRef.current : null);
    }, [props.isSelected, attributes.url]);

    if (props.isSelected) { preloadNeoDrawEditorScripts(); }
    return createReactElement("div", {
        className: "neo-draw--gutenberg-block-edit",
        ref: neoDrawBlockWrapperNodeRef,
        children: [
            createReactElement(BlockEdit, {
                ...props,
                className: (props.className || "") + " neo-draw--img-editable neo-draw--checkerboard-background",
            }),

            props.isSelected && neoDrawButtonContainerNode ? createReactPortal(createReactElement("button", {
                type: "button",
                className: "neo-draw--gutenberg-block-button components-button is-primary " + (attributes.url ? "neo-draw--gutenberg-block-edit-button" : "neo-draw--gutenberg-block-create-button"),
                onClick: async () => {
                    new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))()
                        .on("save", async ({ imgUrl, imgId }) => {
                            const previewImgUrl = imgUrl
                                ? addQueryParam(imgUrl, "neo-draw--gutenberg-save-cachebust", Date.now())
                                : imgUrl;
                            setAttributes({
                                url: imgUrl,
                                id: imgId || attributes.id,
                                triggerReRenderInGutenberg: Math.random(),
                            });

                            const editorIframe = document.querySelector('iframe[name="editor-canvas"]');
                            const getComparableImageUrlParts = (imageUrl) => {
                                try {
                                    const url = new URL(imageUrl, location.href);
                                    return { hostPath: url.host + url.pathname, path: url.pathname };
                                } catch (error) {
                                    const cleanUrl = String(imageUrl ?? "").split("#")[0].split("?")[0];
                                    return { hostPath: cleanUrl.replace(/^https?:\/\//, ""), path: cleanUrl };
                                }
                            };
                            const savedUrlParts = getComparableImageUrlParts(imgUrl);
                            const reloadImagesWithSameSrc = () => {
                                const imgNodesWithSameSrc = [...(editorIframe?.contentDocument ?? document).querySelectorAll("img")].filter((img) => {
                                    const currentImgUrl = img.getAttribute("src");
                                    if (!currentImgUrl || !imgUrl) { return false; }
                                    const currentUrlParts = getComparableImageUrlParts(currentImgUrl);
                                    return currentUrlParts.hostPath === savedUrlParts.hostPath || currentUrlParts.path === savedUrlParts.path;
                                });
                                for (const img of imgNodesWithSameSrc) { img.setAttribute("src", previewImgUrl); }
                            };
                            reloadImagesWithSameSrc();
                            requestAnimationFrame(reloadImagesWithSameSrc);
                        })
                        .imgUrl(attributes.url || "")
                        .open();
                },
                children: [attributes.url ? neo__("Edit neoDraw", "neoDraw bearbeiten") : neo__("Create neoDraw", "neoDraw erstellen")],
            }), neoDrawButtonContainerNode) : null,
        ]
    });
}
