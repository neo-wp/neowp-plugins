import { parseMetadata } from "./_global-image-metadata.js";

import { neoLoadInterfaceFunc } from "./_global--interface.js";

export async function interfaceIsNeoDrawImageAnimatedSuppressErrorPopup20260607(imgNode) {
    return imgNode.getAttribute("src").includes("neo-animate--animated=true");
}
export async function interfaceIsNeoAnimateAnimationStyleNodeSuppressErrorPopup20260609(node) {
    return node.getAttribute("data-neo-animate--animation-style") === "true";
}
export async function interfaceStartNeoDrawSvgAnimationSuppressErrorPopup20260607({ svgNode }) {
    const neodrawMetadata = parseMetadata(svgNode.outerHTML);
    if (!neodrawMetadata.isAnimated) { return; }
    const animationFrames = neodrawMetadata.animation.frames;
    const animationTrigger = neodrawMetadata.animation.trigger;
    svgNode.classList.add("neo-animate--unpacked-svg");
    const animationPlayer = new (await neoLoadInterfaceFunc("neo-animate", "neo-animate--player.js", "InterfaceAnimationPlayer20250302"))();
    const playAnimation = () => animationPlayer.play(svgNode, animationFrames);
    const preparePausedAnimation = async () => { playAnimation(); await animationPlayer.waitUntilReady(); animationPlayer.pause(); };
    await preparePausedAnimation();
    animationPlayer.setPlayPercentage(0);
    let played = false;
    let lastIsImageHovered = false;
    let isScrollListenerActive = false;
    let isHoverListenerActive = false;
    let isRepeating = false;
    const syncToScrollPositionY = () => {
        const boundingRect = svgNode.getBoundingClientRect();
        const imgCenterY = boundingRect.y + boundingRect.height / 2;
        const scrollPercentage = 1 - (imgCenterY / window.innerHeight);
        animationPlayer.setPlayPercentage(scrollPercentage);
    };

    const playOnImageHoverEvent = () => {
        const isImageHovered = svgNode.matches(":hover");
        if (lastIsImageHovered === isImageHovered) { return; }
        lastIsImageHovered = isImageHovered;
        if (isImageHovered) { playAnimation(); } else { animationPlayer.pause(); animationPlayer.setPlayPercentage(0); }
    };
    const replay = async () => {
        if (!isRepeating) { return; }
        await playAnimation();
        requestAnimationFrame(replay);
    };
    new IntersectionObserver(async entries => {
        const isIntersecting = entries[0].isIntersecting;
        const isIntersectingFirstTime = isIntersecting && !played;
        if (isIntersectingFirstTime) { played = true; }
        if (animationTrigger === "start-when-visible") {
            if (isIntersecting) { playAnimation(); } else { animationPlayer.stop(); }
        }
        if (animationTrigger === "start-once-when-visible") {
            if (isIntersectingFirstTime) { playAnimation(); }
        }
        if (animationTrigger === "scroll-position-y") {
            if (isIntersecting && !isScrollListenerActive) { await preparePausedAnimation(); window.addEventListener("scroll", syncToScrollPositionY, { passive: true }); isScrollListenerActive = true; syncToScrollPositionY(); } else if (!isIntersecting && isScrollListenerActive) { animationPlayer.stop(); window.removeEventListener("scroll", syncToScrollPositionY, { passive: true }); isScrollListenerActive = false; }
        }
        if (animationTrigger === "repeating-infinitely") {
            if (isIntersecting && !isRepeating) { isRepeating = true; replay(); } else if (!isIntersecting && isRepeating) { isRepeating = false; animationPlayer.stop(); }
        }
        if (animationTrigger === "on-image-hover") {
            if (isIntersecting && !isHoverListenerActive) { await preparePausedAnimation(); svgNode.addEventListener("mouseover", playOnImageHoverEvent, { passive: true }); svgNode.addEventListener("mouseout", playOnImageHoverEvent, { passive: true }); isHoverListenerActive = true; } else if (!isIntersecting && isHoverListenerActive) { animationPlayer.stop(); svgNode.removeEventListener("mouseover", playOnImageHoverEvent, { passive: true }); svgNode.removeEventListener("mouseout", playOnImageHoverEvent, { passive: true }); isHoverListenerActive = false; lastIsImageHovered = false; }
        }
    }, { threshold: 0 }).observe(svgNode);
}
