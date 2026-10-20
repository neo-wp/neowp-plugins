import { neoError } from "./_global--log.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { getQueryParam } from "./_global--url-helper.js";
import { neo__ } from "./_global--translation.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { pricingUrl } from "./_global-pricing-url.js";

import Swal from "./_global-sweetalert2.js";

class Frame {
    constructor(fromObject) {
        fromObject = fromObject ?? {};
        this.elements = fromObject.elements ?? [];
        this.duration = fromObject.duration ?? 1000;
        this.timing = fromObject.timing ?? "sync";
    }
}
let anim = {
    frames: [new Frame()],
    trigger: "start-when-visible",
    isDefault: true,
};
let selectedFrameIndex = 0;

const animationPlayer = new (await neoLoadInterfaceFunc("neo-animate", "neo-animate--player.js", "InterfaceAnimationPlayer20250302"))();
await customElements.whenDefined("neo-select-neo-animate");

function getPreviewSvgNode() {
    return document.querySelector("image-container svg");
}
function getTimelineContainer() {
    return document.querySelector("timeline-container");
}

function getFrameNodeFromIndex(frameIndex) {
    const frameNode = getTimelineContainer().querySelectorAll(".frame")[frameIndex];
    if (!frameNode) { throw new Error(`Frame ${frameIndex} not found`); }
    return frameNode;
}
function getFrameIndexFromNode(frameNode) {
    return [...getTimelineContainer().querySelectorAll(".frame")].indexOf(frameNode);
}

function saveAnimation() {
    if (anim.isDefault) { return; }

    let animToSave = { ...anim };
    if (animToSave.frames.length === 1 && animToSave.frames[0].elements.length === 0) {
        animToSave.frames = [];
    }

    window.parent.postMessage({
        action: "changeAnimation",
        animation: animToSave,
    }, location.origin);
}

function getSelectedFrameIndex() {
    return selectedFrameIndex;
}

function setSelectedFrameIndex(frameIndex) {
    selectedFrameIndex = frameIndex;
    animationPlayer.currentTime = anim.frames.slice(0, frameIndex).reduce((acc, frame) => acc + frame.duration, 0);
}

function selectFrame(frameNodeOrIndex) {
    let frameIndexToSelect;
    if (typeof frameNodeOrIndex === "number") {
        frameIndexToSelect = frameNodeOrIndex;
    } else {
        frameIndexToSelect = getFrameIndexFromNode(frameNodeOrIndex);
    }
    if (!(typeof frameIndexToSelect === "number" && frameIndexToSelect >= 0)) { throw new Error(`Frame index ${frameIndexToSelect} is not a number`); }

    animationPlayer.stop();
    setSelectedFrameIndex(frameIndexToSelect);

    updateDurationUI();
    document.getElementById("timing").value = anim.frames[frameIndexToSelect].timing;
    updateHighlightsWithinPreviews();

    for (let frameNode of getTimelineContainer().querySelectorAll(".frame")) {
        frameNode.classList.remove("selected");
        if (getFrameIndexFromNode(frameNode) === frameIndexToSelect) {
            frameNode.classList.add("selected");
        }
    }

    getFrameNodeFromIndex(frameIndexToSelect).scrollIntoView({
        behavior: "smooth", block: "nearest", inline: "center"
    });
}

function setDuration(duration) {
    duration = parseFloat(duration);
    if (!(typeof duration === "number" && duration >= 0)) {
        duration = anim.frames[getSelectedFrameIndex()].duration / 1000;
    }

    duration = Math.max(0.1, Math.min(10000, duration));

    const currentFrameIndex = getSelectedFrameIndex();
    anim.frames[currentFrameIndex].duration = duration * 1000;
    setSelectedFrameIndex(currentFrameIndex);
    saveAnimation();
}

setDuration(document.querySelector(".duration input").value);

function updateDurationUI() {
    const duration = anim.frames[getSelectedFrameIndex()].duration / 1000;
    document.querySelector(".duration input").value = duration.toFixed(1);
    const secondsLabel = document.querySelector("label .seconds");
    secondsLabel.innerText = neo__("seconds", "Sekunden");
    if (duration === 1.0) { secondsLabel.innerText = neo__("second", "Sekunde"); }
}

document.querySelector(".duration input").addEventListener("input", evt => {
    animationPlayer.stop();
    setDuration(evt.target.value);
});
document.querySelector(".duration input").addEventListener("blur", () => {
    updateDurationUI();
});

document.querySelector(".duration input").addEventListener("wheel", evt => {
    evt.preventDefault();
    animationPlayer.stop();
    const delta = evt.deltaY > 0 ? -0.1 : 0.1;
    setDuration(parseFloat(evt.target.value) + delta);
    updateDurationUI();
    playFrame(getSelectedFrameIndex(), { skipEmptyFrames: true });
});

document.querySelector(".duration input").addEventListener("keydown", evt => {
    evt.stopPropagation();
    if (evt.key === "Enter" || evt.key === "Escape") {
        evt.target.blur();
    }
});

function createFrameNode(svgNodeTemplate) {
    const frameNode = document.createElement("div");
    frameNode.classList.add("frame");
    frameNode.appendChild(new DomNodeHelper(svgNodeTemplate.outerHTML).getNode());

    const deleteButton = document.createElement("button");
    deleteButton.classList.add("delete");
    const deleteImg = document.createElement("img");
    deleteImg.src = pluginUrl() + "/img/neo-animate--delete-icon.svg";
    deleteImg.alt = "Delete Frame";
    deleteButton.appendChild(deleteImg);
    deleteButton.addEventListener("click", evt => {
        evt.stopPropagation();
        deleteFrame(getFrameIndexFromNode(frameNode));
    });
    frameNode.appendChild(deleteButton);

    const stopButton = document.createElement("button");
    stopButton.classList.add("stop");
    const stopImg = document.createElement("img");
    stopImg.src = pluginUrl() + "/img/neo-animate--pause-icon.svg";
    stopImg.alt = "Stop Animation";
    stopButton.appendChild(stopImg);
    frameNode.appendChild(stopButton);

    const addButton = document.createElement("div");
    addButton.classList.add("addFrameBetween");
    const innerAddButton = document.createElement("button");
    innerAddButton.classList.add("addFrameBetweenInner");
    addButton.appendChild(innerAddButton);
    const addImg = document.createElement("img");
    addImg.src = pluginUrl() + "/img/neo-animate--add-icon.svg";
    addImg.alt = neo__("Add Frame", "Frame hinzufügen");
    innerAddButton.appendChild(addImg);
    addButton.addEventListener("click", evt => {
        evt.stopPropagation();
    });
    innerAddButton.addEventListener("click", evt => {
        evt.stopPropagation();
        addNewFrame(getFrameIndexFromNode(frameNode));
    });
    frameNode.appendChild(addButton);

    return frameNode;
}

function toggleFramePlayStop(frameNode) {
    if (animationPlayer.isPlaying && frameNode.classList.contains("selected")) {
        animationPlayer.stop();
    } else {
        animationPlayer.stop();
        playFrame(frameNode, { skipEmptyFrames: true });
    }
}

function addNewFrame(index) {
    animationPlayer.stop();
    const newFrameIndex = index ?? (getSelectedFrameIndex() + 1);

    if (anim.frames.length === 0) {
        anim.frames.push(new Frame());
    } else {
        anim.frames.splice(newFrameIndex, 0, new Frame());
    }

    const firstFrameSvg = getTimelineContainer().querySelector(".frame svg");
    const frameNode = createFrameNode(firstFrameSvg);
    frameNode.addEventListener("click", () => toggleFramePlayStop(frameNode));

    getTimelineContainer().insertBefore(frameNode, getFrameNodeFromIndex(newFrameIndex));
    selectFrame(frameNode);
    saveAnimation();
}

async function deleteFrame(frameIndexToDelete) {
    animationPlayer.stop();

    if (anim.frames.length <= 1) {
        const confirmResult = await Swal.fire({ icon: "warning", title: neo__("Remove animation?", "Animation entfernen?"), text: neo__("This is the last frame. Do you want to remove the animation from the drawing?", "Dies ist das letzte Frame. Soll die Animation aus der Zeichnung entfernt werden?"), showCancelButton: true, confirmButtonText: neo__("Remove animation", "Animation entfernen"), cancelButtonText: neo__("Cancel", "Abbrechen") });
        if (!confirmResult.isConfirmed) { return; }
        window.parent.postMessage({ action: "removeAnimation" }, location.origin);
        return;
    }

    getTimelineContainer().removeChild(getFrameNodeFromIndex(frameIndexToDelete));

    anim.frames.splice(frameIndexToDelete, 1);

    const newFrameIndex = Math.min(frameIndexToDelete, anim.frames.length - 1);
    setSelectedFrameIndex(newFrameIndex);
    selectFrame(newFrameIndex);

    saveAnimation();
}

function updateHighlightsWithinPreviews() {
    const selectedFrameIndex = getSelectedFrameIndex();

    const indexesAndNodesToUpdate = [
        [selectedFrameIndex, getPreviewSvgNode()],
        [selectedFrameIndex, document.querySelector(".neo-animate--ghost")],
        ...anim.frames.map((_, frameIndex) => [frameIndex, getFrameNodeFromIndex(frameIndex).querySelector("svg")]),
        [anim.frames.length, document.querySelector(".not-animated svg")]
    ];
    for (let [frameIndex, svgNode] of indexesAndNodesToUpdate) {
        for (let elem of svgNode.querySelectorAll("[data-neo-animate--animatable]")) {
            let state = "none";
            for (let [i, frame] of anim.frames.entries()) {
                if (frame.elements.includes(elem.getAttribute("data-neo-animate--id"))) {
                    state = i === frameIndex ? "current" : i < frameIndex ? "past" : "future";
                    break;
                }
            }

            for (let c of elem.classList) {
                if (c.startsWith("neo-animate--animation-")) {
                    elem.classList.remove(c);
                }
            }
            elem.classList.add("neo-animate--animation-" + state);
        }
    }
}

function updatePlayButtonDisabledState() {
    document.querySelector("button.play").disabled = anim.frames.every(frame => frame.elements.length === 0);
}

function addClickListenersToSvgPaths(svgNode) {
    for (let elem of svgNode.querySelectorAll("path,text,use,[data-neo-animate--is-editor-hover-click-helper]")) {
        elem.addEventListener("click", evt => {
            evt.stopPropagation();
            const elem = evt.target;

            let clickedElem;
            const helperElem = elem.closest("[data-neo-animate--is-editor-hover-click-helper],[data-neo-animate--is-fill-click-helper]");
            if (helperElem) {
                clickedElem = svgNode.querySelector(`[data-neo-animate--id=${ helperElem.getAttribute("data-for-elem-id") }]`);
            } else {
                clickedElem = elem;
            }
            if (!clickedElem) {
                neoError("No element found for click event", evt);
                return;
            }

            let elemId = clickedElem.getAttribute("data-neo-animate--id");
            if (!elemId && clickedElem.parentElement) {
                elemId = clickedElem.parentElement.getAttribute("data-neo-animate--id");
            }
            if (!elemId && clickedElem.parentElement && clickedElem.parentElement.parentElement) {
                elemId = clickedElem.parentElement.parentElement.getAttribute("data-neo-animate--id");
            }
            if (!elemId) {
                neoError(elem);
                throw new Error("No ID found for element " + elem);
            }

            let isAlreadyAnimatedInOtherFrame = null;
            const frame = anim.frames[getSelectedFrameIndex()];
            for (let otherFrame of anim.frames) {
                if (otherFrame === frame) continue;
                if (otherFrame.elements.includes(elemId)) {
                    isAlreadyAnimatedInOtherFrame = otherFrame;
                    break;
                }
            }

            if (isAlreadyAnimatedInOtherFrame) {
                if (evt.altKey) {
                    isAlreadyAnimatedInOtherFrame.elements = isAlreadyAnimatedInOtherFrame.elements.filter(id => id !== elemId);
                } else {
                    return;
                }
            }

            if (frame.elements.includes(elemId)) {
                frame.elements = frame.elements.filter(id => id !== elemId);
            } else {
                frame.elements.push(elemId);
            }
            updateHighlightsWithinPreviews();
            updatePlayButtonDisabledState();
            saveAnimation();
        });
    }
}

function updateAltOptClass(altKey) {
    if (altKey) {
        document.querySelector("image-container").classList.add("alt-key-pressed");
    } else {
        document.querySelector("image-container").classList.remove("alt-key-pressed");
    }
}

document.addEventListener("keydown", event => {
    if (event.key === "Alt") {
        updateAltOptClass(true);
    }
}, true);
document.addEventListener("keyup", event => {
    if (event.key === "Alt") {
        updateAltOptClass(false);
    }
}, true);

document.addEventListener("pointermove", event => updateAltOptClass(event.altKey));

let originalPreviewSvg;

async function playFrame(frameNodeOrIndex, options = { skipEmptyFrames: false }) {
    const { skipEmptyFrames } = options;
    selectFrame(frameNodeOrIndex);

    if (skipEmptyFrames) {
        if (anim.frames[getSelectedFrameIndex()].elements.length === 0) {
            return "emptyFrame";
        }
    }

    const startTime = anim.frames.filter((_, i) => i < getSelectedFrameIndex()).reduce((acc, f) => acc += f.duration, 0);
    const endTime = startTime + anim.frames[getSelectedFrameIndex()].duration;

    getPreviewSvgNode().innerHTML = originalPreviewSvg.innerHTML;
    addClickListenersToSvgPaths(getPreviewSvgNode());
    updateHighlightsWithinPreviews();
    getPreviewSvgNode().classList.add("neo-animate--animating");

    const frameNode = getFrameNodeFromIndex(getSelectedFrameIndex());
    frameNode.classList.add("playing");

    const TIME_EPSILON = 0.000000001;
    const stopReason = await animationPlayer.play(getPreviewSvgNode(), anim.frames, startTime, endTime - TIME_EPSILON);

    frameNode.classList.remove("playing");
    getPreviewSvgNode().classList.remove("neo-animate--animating");
    addClickListenersToSvgPaths(getPreviewSvgNode());
    updateHighlightsWithinPreviews();
    return stopReason;
}

async function playAllFrames() {
    animationPlayer.stop();
    for (let frameIndex = 0; frameIndex < anim.frames.length; frameIndex++) {
        const unusualStopReason = await playFrame(frameIndex, { skipEmptyFrames: false });
        if (unusualStopReason) {
            break;
        }
    }
}

async function togglePlayStop() {
    if (animationPlayer.isPlaying) {
        animationPlayer.stop();
    } else {
        document.querySelector("button.play img").src = pluginUrl() + "/img/neo-animate--pause-icon.svg";
        await playAllFrames();
        document.querySelector("button.play img").src = pluginUrl() + "/img/neo-animate--play-icon.svg";
    }
}

document.getElementById("timing").addEventListener("change", evt => {
    const currentFrame = anim.frames[getSelectedFrameIndex()];
    if (evt.target.value === "one-by-one") { window.open(pricingUrl(), "_blank", "noopener"); evt.target.value = currentFrame.timing; return; } 
    currentFrame.timing = evt.target.value;
    playFrame(getSelectedFrameIndex(), { skipEmptyFrames: true });
    saveAnimation();
});

document.getElementById("trigger").addEventListener("change", evt => {
    if (evt.target.value === "mouse-position-y") { window.open(pricingUrl(), "_blank", "noopener"); evt.target.value = anim.trigger; return; } 
    anim.trigger = evt.target.value;
    saveAnimation();
});

function loadSvg(svgCodeToLoad, animationMeta) {
    document.querySelector("image-container").replaceChildren();
    getTimelineContainer().replaceChildren();
    anim = animationMeta ?? {
        frames: [],
        trigger: "start-when-visible",
    };

    if (anim.frames.length === 0) {
        anim.frames.push({});
    }

    anim.frames = anim.frames.map(frameData => new Frame(frameData));
    setSelectedFrameIndex(Math.max(0, Math.min(selectedFrameIndex, anim.frames.length - 1)));

    const previewSvg = new DomNodeHelper(svgCodeToLoad).withClass("neo-animate--preview").getNode();

    for (let elem of previewSvg.querySelectorAll("[data-neo-animate--animatable]")) {
        const paths = [];
        if (elem.tagName === "path") {
            paths.push(elem);
        } else {
            paths.push(...elem.querySelectorAll("path"));
        }
        for (let path of paths) {
            const strokeWidth = previewSvg.viewBox.baseVal.width * 0.02;
            const stroke = document.createElementNS("http://www.w3.org/2000/svg", "path");
            stroke.setAttribute("d", path.getAttribute("d"));
            stroke.setAttribute("stroke", "transparent");
            stroke.setAttribute("stroke-width", strokeWidth);
            stroke.setAttribute("fill", "transparent");
            stroke.setAttribute("data-neo-animate--is-editor-hover-click-helper", "true");
            stroke.setAttribute("data-ignore", "true");
            stroke.setAttribute("data-for-elem-id", elem.getAttribute("data-neo-animate--id"));
            path.parentNode.insertBefore(stroke, path);
        }
    }
    addClickListenersToSvgPaths(previewSvg);
    originalPreviewSvg = previewSvg.cloneNode(true);

    const imgContainer = document.querySelector("image-container");
    const ghostSvgNode = new DomNodeHelper(svgCodeToLoad).withClass("neo-animate--ghost").getNode();
    imgContainer.appendChild(previewSvg);
    imgContainer.appendChild(ghostSvgNode);

    for (let i = 0; i < anim.frames.length; i++) {
        const frameNode = createFrameNode(previewSvg);
        if (i === selectedFrameIndex) {
            frameNode.classList.add("selected");
        }

        frameNode.addEventListener("click", () => toggleFramePlayStop(frameNode));
        getTimelineContainer().appendChild(frameNode);
    }

    const notAnimatedFrame = createFrameNode(previewSvg);
    notAnimatedFrame.classList.add("not-animated");
    notAnimatedFrame.querySelector(".delete").disabled = true;
    const notAnimatedHint = document.createElement("span");
    notAnimatedHint.innerText = neo__("Not animated yet:", "Noch nicht animiert:");
    notAnimatedHint.classList.add("not-animated-hint");
    notAnimatedFrame.appendChild(notAnimatedHint);

    const addMissingButton = document.createElement("button");
    addMissingButton.classList.add("button", "addMissing");
    addMissingButton.innerText = neo__("Add all", "Alle hinzufügen");
    addMissingButton.addEventListener("click", () => {
        addNewFrame(anim.frames.length);
        const allElementIds = [...new Set([...previewSvg.querySelectorAll("[data-neo-animate--animatable][data-neo-animate--id]")].map(elem => elem.getAttribute("data-neo-animate--id")))];
        const missingElementIds = allElementIds.filter(id => !anim.frames.some(frame => frame.elements.includes(id)));
        anim.frames[anim.frames.length - 1].elements = missingElementIds;
        updateHighlightsWithinPreviews();
        updatePlayButtonDisabledState();
        saveAnimation();
    });
    notAnimatedFrame.appendChild(addMissingButton);
    getTimelineContainer().appendChild(notAnimatedFrame);

    updateHighlightsWithinPreviews();
    updatePlayButtonDisabledState();
    updateDurationUI();

    document.getElementById("timing").value = anim.frames[0].timing;
    document.getElementById("trigger").value = anim.trigger;
    window.parent.postMessage({ action: "svgLoaded" }, location.origin);
}

window.addEventListener("message", event => {
    if (event.origin !== location.origin || event.source !== window.parent) { return; }
    if (event.data?.action === "loadSvg") {
        loadSvg(event.data.svg, event.data.animationMeta);
    }
});
window.parent.postMessage({ action: "readyForSvg" }, location.origin);

const imgUrl = getQueryParam(location.href, "img-url");
if (imgUrl) {
    const imgUrlObject = new URL(imgUrl, location.href);
    if (imgUrlObject.origin !== location.origin) { throw new Error("Debug image URL must use the same origin as the editor."); }
    fetch(imgUrlObject.href)
        .then(response => { if (!response.ok) { throw new Error("Failed to fetch image"); } return response; })
        .then(response => response.text())
        .then(svg => loadSvg(svg, null));
}

document.querySelector("image-container").addEventListener("click", () => {
    animationPlayer.stop();
});
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        animationPlayer.stop();
        return;
    }

    if (event.key === "ArrowLeft" && !event.shiftKey) {
        selectFrame(Math.max(0, getSelectedFrameIndex() - 1));
        return;
    } else if (event.key === "ArrowRight" && !event.shiftKey) {
        selectFrame(Math.min(anim.frames.length - 1, getSelectedFrameIndex() + 1));
        return;
    }

    if (["ArrowLeft", "ArrowRight"].includes(event.key) && event.shiftKey) {
        event.preventDefault();

        const frameToMove = anim.frames[getSelectedFrameIndex()];
        const frameToMoveNode = getFrameNodeFromIndex(getSelectedFrameIndex());

        let targetFrameIndex = getSelectedFrameIndex();
        if (event.key === "ArrowLeft") targetFrameIndex--;
        if (event.key === "ArrowRight") targetFrameIndex++;
        if (targetFrameIndex < 0) return;
        if (targetFrameIndex >= anim.frames.length) return;

        anim.frames.splice(getSelectedFrameIndex(), 1);
        anim.frames.splice(targetFrameIndex, 0, frameToMove);

        if (event.key === "ArrowLeft") { frameToMoveNode.parentNode.insertBefore(frameToMoveNode, frameToMoveNode.previousSibling); }
        if (event.key === "ArrowRight") { frameToMoveNode.parentNode.insertBefore(frameToMoveNode, frameToMoveNode.nextSibling.nextSibling); }

        setSelectedFrameIndex(targetFrameIndex);

        updateHighlightsWithinPreviews();
        saveAnimation();
        return;
    }

    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        const currentDuration = parseFloat(anim.frames[getSelectedFrameIndex()].duration) / 1000;
        if (event.key === "ArrowDown") {
            setDuration(currentDuration - 0.1);
        } else if (event.key === "ArrowUp") {
            setDuration(currentDuration + 0.1);
        }
        updateDurationUI();
        return;
    }

    if (event.key === "Delete" || event.key === "Backspace") {
        deleteFrame(getSelectedFrameIndex());
        return;
    }

    if (event.key === " ") {
        toggleFramePlayStop(getFrameNodeFromIndex(getSelectedFrameIndex()));

        event.preventDefault();
        return;
    }
});

window.neoAnimateTogglePlayStop = togglePlayStop;
