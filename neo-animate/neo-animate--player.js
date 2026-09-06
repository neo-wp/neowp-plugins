export class InterfaceAnimationPlayer20250302 {
    constructor() {
        this.currentTime = 0;
    }

    _getPathDWithoutDoubleLines(pathNode) { return (async () => {
        const d = pathNode.getAttribute("d");
        const elemType = pathNode.getAttribute("data-neo-animate--element-type") ?? pathNode.closest("[data-neo-animate--element-type]")?.getAttribute("data-neo-animate--element-type");
        const isFreedraw = ["freedraw", "freehand"].includes(elemType);

        let firstSplit = d.split(/[\s,]+/);
        let commandParts = [];
        for (let part of firstSplit) {
            let match = part.match(/([a-zA-Z]+)([-\d.]+)/);
            if (match) {
                commandParts.push(match[1]);
                commandParts.push(match[2]);
            } else {
                commandParts.push(part);
            }
        }

        let currentPos = { x: 0, y: 0 };
        let commandLetter = "";
        let thisPartIndex = 0;
        let newPathD = "";
        const visitedPoints = [];

        while (thisPartIndex < commandParts.length) {
            if (/^[zZ]$/.test(commandParts[thisPartIndex])) {
                if (commandParts[thisPartIndex + 1] !== undefined && !/^[a-zA-Z]$/.test(commandParts[thisPartIndex + 1])) { throw new Error("Close path command with parameters is not supported by the neoAnimate path parser."); }
                if (isFreedraw) { thisPartIndex++; continue; }
                newPathD += commandParts[thisPartIndex] + " "; thisPartIndex++; continue;
            }
            if (/^[a-zA-Z]$/.test(commandParts[thisPartIndex])) {
                commandLetter = commandParts[thisPartIndex];
                thisPartIndex++; continue;
            }

            let nextPartIndex = thisPartIndex;

            switch (commandLetter) {
                case "M":
                case "L":
                case "T":
                    currentPos.x = parseFloat(commandParts[thisPartIndex + 0]);
                    currentPos.y = parseFloat(commandParts[thisPartIndex + 1]);
                    nextPartIndex += 2;
                    break;
                case "m":
                case "l":
                case "t":
                    currentPos.x += parseFloat(commandParts[thisPartIndex + 0]);
                    currentPos.y += parseFloat(commandParts[thisPartIndex + 1]);
                    nextPartIndex += 2;
                    break;
                case "H":
                    currentPos.x = parseFloat(commandParts[thisPartIndex + 0]);
                    nextPartIndex += 1;
                    break;
                case "h":
                    currentPos.x += parseFloat(commandParts[thisPartIndex + 0]);
                    nextPartIndex += 1;
                    break;
                case "V":
                    currentPos.y = parseFloat(commandParts[thisPartIndex + 0]);
                    nextPartIndex += 1;
                    break;
                case "v":
                    currentPos.y += parseFloat(commandParts[thisPartIndex + 0]);
                    nextPartIndex += 1;
                    break;
                case "C":
                    currentPos.x = parseFloat(commandParts[thisPartIndex + 4]);
                    currentPos.y = parseFloat(commandParts[thisPartIndex + 5]);
                    nextPartIndex += 6;
                    break;
                case "c":
                    currentPos.x += parseFloat(commandParts[thisPartIndex + 4]);
                    currentPos.y += parseFloat(commandParts[thisPartIndex + 5]);
                    nextPartIndex += 6;
                    break;
                case "S":
                    currentPos.x = parseFloat(commandParts[thisPartIndex + 2]);
                    currentPos.y = parseFloat(commandParts[thisPartIndex + 3]);
                    nextPartIndex += 4;
                    break;
                case "s":
                    currentPos.x += parseFloat(commandParts[thisPartIndex + 2]);
                    currentPos.y += parseFloat(commandParts[thisPartIndex + 3]);
                    nextPartIndex += 4;
                    break;
                case "Q":
                    currentPos.x = parseFloat(commandParts[thisPartIndex + 2]);
                    currentPos.y = parseFloat(commandParts[thisPartIndex + 3]);
                    nextPartIndex += 4;
                    break;
                case "q":
                    currentPos.x += parseFloat(commandParts[thisPartIndex + 2]);
                    currentPos.y += parseFloat(commandParts[thisPartIndex + 3]);
                    nextPartIndex += 4;
                    break;
                case "A":
                    currentPos.x = parseFloat(commandParts[thisPartIndex + 5]);
                    currentPos.y = parseFloat(commandParts[thisPartIndex + 6]);
                    nextPartIndex += 7;
                    break;
                case "a":
                    currentPos.x += parseFloat(commandParts[thisPartIndex + 5]);
                    currentPos.y += parseFloat(commandParts[thisPartIndex + 6]);
                    nextPartIndex += 7;
                    break;
                case "Z":
                case "z":
                    throw new Error("Close path command with parameters is not supported by the neoAnimate path parser.");
                default:
                    const { neoError } = await import("./_global--log.js");
                    neoError("Unknown animation player command: " + commandLetter, commandParts);
            }

            let skipCurrentPoint = false;
            if (isFreedraw) {
                skipCurrentPoint = thisPartIndex > commandParts.length / 2;
            } else {
                for (const visitedPoint of visitedPoints) {
                    const distance = Math.sqrt(Math.pow(visitedPoint.x - currentPos.x, 2) + Math.pow(visitedPoint.y - currentPos.y, 2));
                    if (distance < 10) {
                        skipCurrentPoint = true;
                        break;
                    }
                }

                const isLastCommand = nextPartIndex >= commandParts.length;
                const isLine = commandParts.filter(p => p === "M").length === 2 && commandParts.filter(p => p === "C").length === 2;
                if (isLastCommand && !isLine) { skipCurrentPoint = false; }
            }

            if (!skipCurrentPoint) {
                visitedPoints.push({ ...currentPos });
                let newCommandWithParams = commandLetter;

                for (const commandParamValue of commandParts.slice(thisPartIndex, nextPartIndex)) {
                    if (/^[a-zA-Z]$/.test(commandParamValue)) { throw ("Unexpected letter in the command parameters: " + commandParamValue); }
                    newCommandWithParams += " " + commandParamValue;
                }
                newPathD += newCommandWithParams + " ";
            }

            thisPartIndex = nextPartIndex;
        }

        return newPathD.trim();
    })(); }

    createAnimationsStyleNode(svgNode, frames) { return (async () => {
        let totalDuration = frames.reduce((acc, frame) => acc + frame.duration, 0);

        const css = [];

        for (const [frameIndex, frame] of frames.entries()) {
            const stillExistingFrameElements = frame.elements.filter(elemId => svgNode.querySelector(`[data-neo-animate--id="${elemId}"]`));
            for (const [elemIndex, neoDrawId] of stillExistingFrameElements.entries()) {
                css.push(`/* Frame element ${elemIndex} */`);

                let elementOffset;
                let elementDuration;

                if (elementOffset === undefined && elementDuration === undefined && frame.timing === "one-by-one") { elementOffset = 0; elementDuration = frame.duration; }
                if (elementOffset === undefined && elementDuration === undefined) { switch (frame.timing ?? "sync") {
                    case "sync":
                        elementOffset = 0;
                        elementDuration = frame.duration;
                        break;
                    case "delayed":
                        const delayFactor = 0.5;
                        elementDuration = delayFactor * frame.duration;
                        if (stillExistingFrameElements.length === 1) {
                            elementOffset = 0;
                        } else {
                            elementOffset = elemIndex * (frame.duration - delayFactor * frame.duration) / (stillExistingFrameElements.length - 1);
                        }
                        break;
                    case "overlapping":
                        const overlapFactor = 0.5;
                        elementDuration = frame.duration / (stillExistingFrameElements.length + overlapFactor - stillExistingFrameElements.length * overlapFactor);
                        elementOffset = elemIndex * (frame.duration - overlapFactor * elementDuration) / stillExistingFrameElements.length;
                        break;
                    case "instant":
                        elementDuration = 0;
                        elementOffset = 0;
                        break;
                } }

                const nodeIndexesToDrawStroke = [];
                const nodeIndexesToScale = [];
                const nodeIndexesToAppearAfter = [];

                const animatedRootNodeSelector = `[data-neo-animate--id="${neoDrawId}"]`;
                const animatedRootNode = svgNode.querySelector(animatedRootNodeSelector);

                const elemType = animatedRootNode.getAttribute("data-neo-animate--element-type");

                let childNodes = [...animatedRootNode.querySelectorAll(":not([data-neo-animate--is-editor-hover-click-helper]):not([data-neo-animate--is-fill-click-helper])")];
                if (elemType === "line") { childNodes = childNodes.filter(p => p.tagName.toLowerCase() !== "g"); }
                const isShape = ["rectangle", "diamond", "ellipse", "freedraw", "line"].includes(elemType);
                const isShapeStroke        = isShape && childNodes.length === 1;
                const isShapeFillAndStroke = isShape && childNodes.length === 2 && childNodes[1].getAttribute("stroke") !== "transparent";
                const isShapeFill          = isShape && childNodes.length === 2 && childNodes[1].getAttribute("stroke") === "transparent";
                const isArrow = ["arrow"].includes(elemType);
                const isText = ["text"].includes(elemType);
                const isImage = ["image"].includes(elemType);
                const isEmbeddable = ["embeddable"].includes(elemType);

                if (isShapeStroke)             { nodeIndexesToDrawStroke.push(1); }
                else if (isShapeFillAndStroke) { nodeIndexesToAppearAfter.push(1); nodeIndexesToDrawStroke.push(2); }
                else if (isShapeFill)          { nodeIndexesToScale.push(1); }
                else if (isArrow)              { nodeIndexesToDrawStroke.push(...childNodes.map((childNode, i) => childNode.tagName.toLowerCase() === "path" ? (i+1) : null).filter(i => i != null)); }
                else if (isText)               { nodeIndexesToScale.push(1); }
                else if (isImage)              { nodeIndexesToScale.push(1); }
                else if (isEmbeddable)         { nodeIndexesToScale.push(1); }
                else {
                    nodeIndexesToScale.push(0);
                    const { neoWarn } = await import("./_global--log.js");
                    neoWarn("Unexpected combination of element type and child paths in", svgNode, elemType, childNodes);
                    console.debug("Unexpected node:", animatedRootNode);
                }

                const currentFrameOffset = frames.slice(0, frames.indexOf(frame)).reduce((acc, frame) => acc + frame.duration, 0);
                let percentageOffset = (currentFrameOffset + elementOffset) / totalDuration * 100;
                let percentageDuration = elementDuration / totalDuration * 100;
                let percentageOffsetForAppearAfter = 0;
                let percentageDurationForAppearAfter = 0;
                const appearFactor = 0.2;
                if (nodeIndexesToAppearAfter.length > 0) {
                    percentageDuration *= (1.0 - appearFactor);
                    percentageOffsetForAppearAfter = percentageOffset + percentageDuration;
                    percentageDurationForAppearAfter = percentageDuration * appearFactor;
                }

                function nodeSelectorByChildIndex(childIndex) {
                    if (childIndex === 0) { return animatedRootNodeSelector; }
                    return `${animatedRootNodeSelector} :not(g):nth-child(${[...childNodes[childIndex - 1].parentNode.children].findIndex(child => child === childNodes[childIndex - 1]) + 1})`;
                }
                const epsilon = 0.0001;

                for (const childIndex of nodeIndexesToDrawStroke) {
                    const nodeSelector = nodeSelectorByChildIndex(childIndex);
                    const pathToDraw = svgNode.querySelector(nodeSelector);

                    let freedrawAnimationCss = "";
                    let resetFreeDrawAnimationCss = "";
                    if (!pathToDraw.getAttribute("stroke")) {
                        freedrawAnimationCss = ` stroke: ${pathToDraw.getAttribute("fill") }; ` +
                                                `fill: none; ` +
                                                `stroke-width: ${pathToDraw.getAttribute("data-neo-animate--stroke-width") * 2};`;
                        resetFreeDrawAnimationCss = ` stroke: ${pathToDraw.getAttribute("stroke") ?? "none"}; ` +
                                                     `fill: ${pathToDraw.getAttribute("fill") ?? "none"}; ` +
                                                     `stroke-width: ${pathToDraw.getAttribute("stroke-width") ?? pathToDraw.getAttribute("data-neo-animate--stroke-width") ?? "1"};`;
                    }

                    const originalPathD = pathToDraw.getAttribute("d");
                    const newPathDWithoutDoubleLines = await this._getPathDWithoutDoubleLines(pathToDraw);

                    const animationName = `neo-animate--animation-stroke-${neoDrawId}-${childIndex}`;

                    const clonedPath = pathToDraw.cloneNode(true);
                    clonedPath.setAttribute("d", newPathDWithoutDoubleLines);
                    const totalLength = clonedPath.getTotalLength();
                    const percentageEndForStroke = Math.max(percentageOffset, percentageOffset + percentageDuration - epsilon);
                    css.push(`@keyframes ${animationName} { ` +
                             `0% { d: var(--new-path-d-without-double-lines);${freedrawAnimationCss} stroke-dashoffset: ${totalLength}; } ` +
                             `${percentageOffset}% { d: var(--new-path-d-without-double-lines);${freedrawAnimationCss} stroke-dashoffset: ${totalLength}; } ` +
                             `${percentageEndForStroke}% { d: var(--new-path-d-without-double-lines);${freedrawAnimationCss} stroke-dashoffset: 0; } ` +
                             `${percentageOffset + percentageDuration}%, 100% { d: var(--original-path-d);${resetFreeDrawAnimationCss} } }`);
                    css.push(`${nodeSelector} { animation: ${animationName} ${totalDuration}ms linear 0ms infinite normal forwards running; stroke-dasharray: ${totalLength}; --original-path-d: path("${originalPathD}"); --new-path-d-without-double-lines: path("${newPathDWithoutDoubleLines}"); }`);
                }

                for (const childIndex of nodeIndexesToScale) {
                    const animationName = `neo-animate--animation-scale-${neoDrawId}-${childIndex}`;
                    const nodeSelector = nodeSelectorByChildIndex(childIndex);
                    css.push(`@keyframes ${animationName} { ` +
                             `0%, ${percentageOffset}% { transform: scale(0); } ` +
                             `${percentageOffset + percentageDuration}%, 100% { transform: scale(1); } }`);
                    css.push(`${nodeSelector} { `+
                        `animation: ${animationName} ${totalDuration}ms linear 0ms infinite normal forwards running; ` +
                        `transform-origin: center; transform-box: fill-box; }`);
                }

                for (const childIndex of nodeIndexesToAppearAfter) {
                    const animationName = `neo-animate--animation-appear-after-${neoDrawId}-${childIndex}`;
                    const nodeSelector = nodeSelectorByChildIndex(childIndex);
                    css.push(`@keyframes ${animationName} { ` +
                             `0%, ${percentageOffsetForAppearAfter}% { transform: scale(0); } ` +
                             `${percentageOffsetForAppearAfter + percentageDurationForAppearAfter}%, 100% { transform: scale(1); } }`);
                    css.push(`${nodeSelector} { ` +
                        `animation: ${animationName} ${totalDuration}ms linear 0ms infinite normal forwards running; ` +
                        `transform-origin: center; transform-box: fill-box; }`);
                }
            }
        }

        const styleNode = document.createElement("style");
        styleNode.setAttribute("data-neo-animate--animation-style", "true");
        styleNode.innerHTML = "\n" + css.join("\n") + "\n";
        return styleNode;
    })(); }

    get animationObjects() {
        const animationObjects = [this.dummyAnimationObjectForTimeline];
        if (this.svgNode.getAnimations().length === 0) { throw new Error("No animations found on SVG node. This should not happen."); }
        for (const child of this.svgNode.querySelectorAll("*")) {
            animationObjects.push(...child.getAnimations());
        }
        return animationObjects;
    }

    play(svgNode, frames, fromTime, toTime) { return (async () => {
        fromTime = fromTime ?? 0;
        toTime = toTime ?? frames.reduce((acc, frame) => acc + frame.duration, 0);
        let resolvePlayReadyPromise; let rejectPlayReadyPromise; this.playReadyPromise = new Promise((resolve, reject) => { resolvePlayReadyPromise = resolve; rejectPlayReadyPromise = reject; });

        if (this.originalSvgInnerHTML) {
            svgNode.innerHTML = this.originalSvgInnerHTML;
        }

        this.svgNode = svgNode;
        this.originalSvgInnerHTML = svgNode.innerHTML;

        let newAnimationStyleNode; try { newAnimationStyleNode = await this.createAnimationsStyleNode(svgNode, frames); } catch (error) { rejectPlayReadyPromise(error); throw error; }
        const existingAnimationStyleNode = svgNode.querySelector("[data-neo-animate--animation-style]");
        if (existingAnimationStyleNode) { existingAnimationStyleNode.textContent = newAnimationStyleNode.textContent; } else { svgNode.prepend(newAnimationStyleNode); }

        const totalDuration = frames.reduce((acc, frame) => acc + frame.duration, 0);
        this.dummyAnimationObjectForTimeline = svgNode.animate({}, { duration: totalDuration, fill: "forwards", easing: "linear" });

        this.currentTime = fromTime;
        this.fromTime = fromTime;
        this.toTime = toTime;

        for (let anim of this.animationObjects) {
            anim.currentTime = this.currentTime;
            anim.play();
        }
        resolvePlayReadyPromise();

        if (this.isPlaying) {
            return await this.playEndPromise;
        }

        this.isPlaying = true;
        this.playEndPromise = new Promise(resolve => this.resolvePlayEndPromise = resolve);
        let stopReason = "";
        this.playEndPromise.then(reason => stopReason = reason);

        while (this.isPlaying) {
            this.currentTime = this.dummyAnimationObjectForTimeline.currentTime;

            if (this.currentTime >= this.toTime) {
                this.stop("");
                break;
            }

            await new Promise(requestAnimationFrame);
        }

        return stopReason;
    })(); }
    waitUntilReady() { return this.playReadyPromise ?? Promise.resolve(); }

    setPlayPercentage(percentage) {
        percentage = Math.max(0.0, Math.min(1.0, percentage));
        this.currentTime = this.fromTime + percentage * (this.toTime - this.fromTime);

        for (let anim of this.animationObjects) {
            anim.currentTime = this.currentTime;
        }
    }

    pause() {
        if (!this.isPlaying) return;
        this.isPlaying = false;

        for (let anim of this.animationObjects) {
            anim.pause();
        }
    }

    stop(unusualReason = "userAbort") {
        if (!this.isPlaying) return;
        this.pause();
        this.setPlayPercentage(1.0);
        this.resolvePlayEndPromise(unusualReason);
        this.playEndPromise = null;
        this.resolvePlayEndPromise = null;
    }
}
