import { neoLoadInterfaceFunc } from "./_global--interface.js";

export async function interfacePrepareNeoDrawAnimationExportSuppressErrorPopup20260607({ metadata, exportedSvgNode }) {
    if (metadata.animation) {
        metadata.animation.frames = metadata.animation.frames.map(f => ({ ...f, elements: f.elements.filter(elemId => { const isElemUsed = Boolean(exportedSvgNode.querySelector(`[data-neo-animate--id="${elemId}"]`)); if (!isElemUsed) { console.log(`Removing element ${elemId} from animation because it is not used in the SVG.`); } return isElemUsed; }) }));
    }
    metadata.isAnimated = Boolean(metadata.animation?.frames?.some(f => f.elements.length > 0));
    if (!metadata.isAnimated) { return { metadata, preparedAnimatedCssStyle: "<!-- No animations -->" }; }
    return { metadata, preparedAnimatedCssStyle: (await (new (await neoLoadInterfaceFunc("neo-animate", "neo-animate--player.js", "InterfaceAnimationPlayer20250302"))()).createAnimationsStyleNode(exportedSvgNode, metadata.animation.frames)).outerHTML };
}
