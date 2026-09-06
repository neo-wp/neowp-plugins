import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";

window.neo__ = neo__;

await new Promise((resolve, reject) => {
    const scriptNode = document.createElement("script"); scriptNode.src = pluginUrl() + "/neo-draw--excalidraw-thirdparty/excalidraw.js";
    scriptNode.onload = resolve; scriptNode.onerror = () => reject(new Error("Failed to load the Excalidraw bundle."));
    document.head.appendChild(scriptNode);
});

await import("./neo-draw--editor-index.js");
