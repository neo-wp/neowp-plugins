const { createElement: createReactElement, useState, useEffect } = wp.element;

let NeoDrawGutenbergBlockComponent;
wp.hooks.addFilter(
    "editor.BlockEdit",
    "neodraw/neodraw",
    (BlockEdit) => function (props) {
        if (props.name !== "core/image") { return createReactElement(BlockEdit, props); }
        const [isImportLoading, setIsImportLoading] = useState(true);
        useEffect(() => {
            let isMounted = true;
            (async () => {
                NeoDrawGutenbergBlockComponent ??= (await import("./neo-draw--pb-gutenberg-block-component.js")).NeoDrawGutenbergBlockComponent;
                if (!isMounted) { return; }
                setIsImportLoading(false);
            })();
            return () => { isMounted = false; };
        }, []);
        if (isImportLoading) {
            return createReactElement("div", { className: "neo-draw--gutenberg-block-edit-loading" });
        }
        return createReactElement(NeoDrawGutenbergBlockComponent, { ...props, BlockEdit });
    }
);
wp.hooks.addFilter(
    "blocks.registerBlockType",
    "neodraw/change-image-settings",
    (settings, name) => name !== "core/image" ? settings : { ...settings, title: "Image + neoDraw" }
);
