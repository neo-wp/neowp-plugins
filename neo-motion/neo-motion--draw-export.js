import { motionFrameCutPixels } from "./_global--motion-frame.js";

export function isNeoMotionFrameElement(element) { return element.type === "frame" && !element.isDeleted && (!element.name || element.name.toLowerCase().startsWith("frame")); }
export async function interfacePrepareNeoDrawMotionFramesSuppressErrorPopup20260607({ elements, frameId }) {
    const stopMotionAnimationFrameElements = structuredClone(elements).filter(isNeoMotionFrameElement);
    if (stopMotionAnimationFrameElements.length === 0 || frameId) { return { elements, elementsForSceneUpdate: null, stopMotionAnimationFrameElements }; }
    stopMotionAnimationFrameElements.sort((a, b) => Math.round(a.y / 20) * 20 - Math.round(b.y / 20) * 20 || Math.round(a.x / 20) * 20 - Math.round(b.x / 20) * 20);
    for (const [index, frame] of stopMotionAnimationFrameElements.entries()) { frame.name = `Frame ${index + 1}`; }
    const preparedElements = elements.map(element => element.type === "frame" ? (stopMotionAnimationFrameElements.find(frame => frame.id === element.id) || element) : element);
    return { elements: preparedElements, elementsForSceneUpdate: preparedElements, stopMotionAnimationFrameElements };
}
export async function interfacePrepareNeoDrawMotionExportSuppressErrorPopup20260607({ stopMotionAnimationFrameElements, frameId, preparedExcalidrawDefs, preparedSymbols, preparedSvgRest, preparedSvgHeader, metadata, exportSvgAsString, indent, unindent }) {
    let preparedMotionCssStyle = "";
    metadata.isMotion = false;
    if (stopMotionAnimationFrameElements.length > 0 && !frameId) {
        preparedExcalidrawDefs = ""; preparedSymbols = ""; preparedSvgRest = "";
        const cutPixels = metadata.motion?.cutPixels ?? motionFrameCutPixels(); metadata.motion = { ...(metadata.motion ?? {}), cutPixels };
        const croppedFrameWidth = Math.max(1, stopMotionAnimationFrameElements[0].width - 2 * cutPixels); const croppedFrameHeight = Math.max(1, stopMotionAnimationFrameElements[0].height - 2 * cutPixels);
        const addedFontFamilies = [];
        const randomIdSuffix = [...Array(10)].map(() => Math.floor(Math.random() * 16).toString(16)).join("");
        for (const [index, frame] of stopMotionAnimationFrameElements.entries()) {
            const frameSvgContent = await exportSvgAsString(frame.id);
            let frameExcalidrawDefs = frameSvgContent.match(/<!--\s*START - Excalidraw defs\s*-->\n([\s\S]*?)\n<!--\s*END - Excalidraw defs\s*-->/)[1];
            frameExcalidrawDefs = unindent(frameExcalidrawDefs.replace(/\s*<defs>\n/g, "").replace(/\s*<\/defs>\s*/g, ""));
            frameExcalidrawDefs = frameExcalidrawDefs.split("\n").filter(line => { const fontFamilyMatch = line.match(/font-family: '([^']+)'/); const fontWeightMatch = line.match(/font-weight: (\d+)/); const fontUnicodeRangeMatch = line.match(/unicode-range: ([^;]+);/); if (!(fontFamilyMatch && fontWeightMatch)) { return true; } const fontFamily = fontFamilyMatch[1]; const fontWeight = parseInt(fontWeightMatch[1]); const fontUnicodeRange = fontUnicodeRangeMatch ? fontUnicodeRangeMatch[1] : null; if (addedFontFamilies.find(f => f.fontFamily === fontFamily && f.fontWeight === fontWeight && f.fontUnicodeRange === fontUnicodeRange)) { return false; } addedFontFamilies.push({ fontFamily, fontWeight, fontUnicodeRange }); return true; }).join("\n");
            let frameSymbols = frameSvgContent.match(/<!--\s*START - Symbols\s*-->\n([\s\S]*?)\n<!--\s*END - Symbols\s*-->/)[1];
            frameSymbols = frameSymbols.split("\n").filter(line => !(line.includes("<symbol") && line.includes("</symbol>") && preparedSymbols.includes(line.trim()))).join("\n");
            frameSymbols = frameSymbols.trim() || "<!-- No symbols -->";
            const frameSvgNodes = frameSvgContent.match(/<!--\s*START - SVG Nodes\s*-->\n([\s\S]*?)\n<!--\s*END - SVG Nodes\s*-->/)[1];
            preparedExcalidrawDefs += `<!-- Frame ${index} -->\n` + unindent(frameExcalidrawDefs) + "\n";
            preparedSymbols += `<!-- Frame ${index} -->\n` + unindent(frameSymbols) + "\n";
            preparedSvgRest += `<!-- Frame ${index} -->\n` + `<g id="motion-frame-${index}-${randomIdSuffix}">\n${frameSvgNodes}\n</g>\n`;
        }
        preparedSvgRest = `<g id="motion-filmstrip-${randomIdSuffix}">\n${indent(preparedSvgRest)}\n</g>`;
        preparedExcalidrawDefs = preparedExcalidrawDefs.replace(/\s*<clipPath[\s\S]*?<\/clipPath>/g, "");
        preparedSvgRest = preparedSvgRest.replace(/\s*clip-path="url\(#.*?\)"/g, "");
        preparedExcalidrawDefs += `<!-- neoMotion clip path -->\n<clipPath id="motion-clip-path-${randomIdSuffix}">\n  <rect x="${cutPixels}" y="${cutPixels}" width="${croppedFrameWidth}" height="${croppedFrameHeight}"></rect>\n</clipPath>\n`;
        preparedSvgRest = preparedSvgRest.replace(/id="motion-frame-(\d+)-(.+?)"/g, `id="motion-frame-$1-$2" clip-path="url(#motion-clip-path-${randomIdSuffix})"`);
        preparedExcalidrawDefs = `<defs>\n${indent(preparedExcalidrawDefs.trimEnd())}\n</defs>`;
        if (stopMotionAnimationFrameElements.length > 1) {
            metadata.isMotion = true;
            const frameDuration = 0.1; const frameWidth = stopMotionAnimationFrameElements[0].width;
            preparedMotionCssStyle += `/* Move the neoMotion filmstrip to the left to show each frame after the other */\n`;
            preparedMotionCssStyle += `#motion-filmstrip-${randomIdSuffix} { animation: motion-filmstrip-${randomIdSuffix} ${frameDuration * 1000 * stopMotionAnimationFrameElements.length}ms steps(1, end) infinite; transform: translateX(0px); } `;
            preparedMotionCssStyle += `@keyframes motion-filmstrip-${randomIdSuffix} { `;
            for (let frameIndex = 0; frameIndex < stopMotionAnimationFrameElements.length; frameIndex++) {
                const keyframePercentageDecimalPrecision = 4;
                preparedMotionCssStyle += `${(frameIndex / stopMotionAnimationFrameElements.length * 100).toFixed(keyframePercentageDecimalPrecision)}% { transform: translateX(-${frameWidth * frameIndex}px); } `;
            }
            preparedMotionCssStyle += `}\n`;
            for (let frameIndex = 0; frameIndex < stopMotionAnimationFrameElements.length; frameIndex++) {
                preparedMotionCssStyle += `#motion-frame-${frameIndex}-${randomIdSuffix} { transform: translateX(${frameWidth * frameIndex}px); }\n`;
            }
            preparedMotionCssStyle = `<style>\n${indent(preparedMotionCssStyle.trimEnd())}\n</style>`;
        }
        preparedSvgHeader = preparedSvgHeader.replace(/viewBox="[^"]+"/, `viewBox="${cutPixels} ${cutPixels} ${croppedFrameWidth} ${croppedFrameHeight}"`);
        preparedSvgHeader = preparedSvgHeader.replace(/width="[^"]+"/, `width="${croppedFrameWidth}"`);
        preparedSvgHeader = preparedSvgHeader.replace(/height="[^"]+"/, `height="${croppedFrameHeight}"`);
        metadata.width = croppedFrameWidth; metadata.height = croppedFrameHeight;
    }
    return { preparedExcalidrawDefs, preparedSymbols, preparedSvgRest, preparedSvgHeader, preparedMotionCssStyle: preparedMotionCssStyle || "<!-- No stop motion -->", metadata };
}
