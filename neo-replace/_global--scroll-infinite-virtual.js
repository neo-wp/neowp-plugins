import { neoWarn } from "./_global--log.js";
import { epsilon } from "./_global--math.js";
import { observeResize } from "./_global--observer.js";
import morphdom from "./_global--scroll-infinite-virtual-morphdom-esm-thirdparty/morphdom-esm.js";

export function infiniteVirtualScroll(containerNode, callbackRowHeight, callbackRowNode, scrollNode = containerNode) {
    if (containerNode.children.length > 0) { throw new Error("Container node must be empty"); }
    containerNode.style.overflowAnchor = "none";

    const dummyDiv = document.createElement("div"); dummyDiv.classList.add("neo-global--line-number-dummy"); containerNode.appendChild(dummyDiv);
    const spacerAbove = document.createElement("div"); spacerAbove.setAttribute("aria-hidden","true"); spacerAbove.style.height = "0px"; containerNode.appendChild(spacerAbove);
    const spacerBelow = document.createElement("div"); spacerBelow.setAttribute("aria-hidden","true"); spacerBelow.style.height = "0px"; containerNode.appendChild(spacerBelow);

    const pageScroll = scrollNode === document.scrollingElement; const scrollEventNode = pageScroll ? window : scrollNode;
    const getViewportHeight = () => pageScroll ? window.innerHeight : scrollNode.clientHeight;
    const getScrollTopWithinContainer = () => scrollNode === containerNode ? Math.max(0, scrollNode.scrollTop) : Math.max(0, (pageScroll ? 0 : scrollNode.getBoundingClientRect().top) - containerNode.getBoundingClientRect().top);
    let containerHeight = getViewportHeight();
    let renderStart = NaN, renderEnd = NaN;

    let positions = [0];
    const updatePositionsCache = () => {
        positions = [0];
        let i = 0; while (true) {
            if (i > (1_000_000)) { neoWarn("infiniteVirtualScroll: Reached maximum row count while building positions cache. Possible infinite rows?"); break; }
            const rowHeight = callbackRowHeight(i);
            if (rowHeight == undefined) { break; }
            positions.push(positions[positions.length - 1] + rowHeight);
            i ++;
        }
    };
    updatePositionsCache();
    const binarySearchInPositionsList = (y) => {
        if (positions.length <= 1) { return 0; }
        let lo = 0, hi = positions.length - 1;
        while (lo < hi) {
            const mid = (lo + hi) >> 1;
            if (positions[mid + 1] <= y) { lo = mid + 1; }
            else { hi = mid; }
        }
        return lo;
    };

    const updateVisibleRows = async (forceUpdate) => {
        if (!forceUpdate) { if (updateVisibleRows.queued) { return; } updateVisibleRows.queued = true; await new Promise(requestAnimationFrame); updateVisibleRows.queued = false; }

        const top = getScrollTopWithinContainer();
        const pad = Math.max(0, containerHeight / 2);
        const startY = Math.max(0, top - pad);
        const endY   = top + containerHeight + pad;

        let newStart = binarySearchInPositionsList(startY);
        let newEnd = Math.min(positions.length - 1, binarySearchInPositionsList(endY - epsilon(endY)) + 1);
        newStart = Math.max(0,        Math.min(newStart, positions.length - 1));
        newEnd   = Math.max(newStart, Math.min(newEnd,   positions.length - 1));
        if (newStart % 2 !== 0) { newStart --; }

        const removeFront = Math.max(0, Math.min(renderEnd - renderStart, newStart - renderStart));
        for (let k = 0; k < removeFront; k++) { getRowNode(renderStart).remove(); renderStart ++; }

        const removeBack = Math.max(0, Math.min(renderEnd - renderStart, renderEnd - newEnd));
        for (let k = 0; k < removeBack; k++) { getRowNode(renderEnd - 1).remove(); renderEnd --; }

        if (renderStart === renderEnd) { renderStart = NaN; renderEnd = NaN; }

        if (newStart < renderStart) { for (let i = renderStart - 1; i >= newStart; i--) { spacerAbove.after(callbackRowNode(i)); } renderStart = newStart; }

        if (newEnd > renderEnd) { for (let i = renderEnd; i < newEnd; i++) { spacerBelow.before(callbackRowNode(i)); } renderEnd = newEnd; }

        if (isNaN(renderStart) || isNaN(renderEnd)) { for (let i = newStart; i < newEnd; i++) { spacerBelow.before(callbackRowNode(i)); } renderStart = newStart; renderEnd = newEnd; }

        spacerAbove.style.height = Math.max(0, positions[renderStart]) + "px";
        spacerBelow.style.height = Math.max(0, positions[positions.length - 1] - positions[renderEnd]) + "px";
    };
    updateVisibleRows(false);
    scrollEventNode.addEventListener("scroll", () => updateVisibleRows(false), { passive: true });
    observeResize(scrollNode, () => { containerHeight = getViewportHeight(); updateVisibleRows(false); });
    if (pageScroll) { window.addEventListener("resize", () => { containerHeight = getViewportHeight(); updateVisibleRows(false); }, { passive: true }); }

    const updateRowData = async () => {
        for (let i = renderStart; i < renderEnd; i++) {
            morphdom(getRowNode(i), callbackRowNode(i), {
                onBeforeElUpdated(fromEl, toEl) { return !fromEl.isEqualNode(toEl); },
            });

            const newHeight = callbackRowHeight(i);
            if (newHeight == null) { continue; }
            if (positions[i + 1] - positions[i] !== newHeight) {
                const diff = newHeight - (positions[i + 1] - positions[i]);
                for (let pIndex = i + 1; pIndex < positions.length; pIndex++) { positions[pIndex] += diff; }
            }
        }
        updateVisibleRows();
    };

    const rerenderList = async () => {
        const scrollTopBefore = scrollNode.scrollTop;
        updatePositionsCache();
        for (let i = renderStart; i < renderEnd; i++) { getRowNode(i).remove(); renderStart ++; }
        renderStart = 0; renderEnd = 0;
        await updateVisibleRows(true);
        scrollNode.scrollTop = pageScroll ? scrollTopBefore : Math.min(scrollTopBefore, Math.max(0, positions[positions.length - 1] - containerHeight));
        scrollEventNode.dispatchEvent(new Event("scroll"));
    };

    const scrollToRowIndex = (i) => {
        if (i < 0 || i >= positions.length - 1) { throw new Error("Row index out of bounds"); }
        scrollNode.scrollTop = pageScroll ? containerNode.getBoundingClientRect().top + scrollNode.scrollTop + positions[i] : positions[i];
    };

    const getFirstFullyVisibleRowIndex = () => {
        const scrollTopWithinContainer = getScrollTopWithinContainer();
        if (scrollTopWithinContainer <= 0) { return 0; }
        return binarySearchInPositionsList(scrollTopWithinContainer - epsilon(scrollTopWithinContainer)) + 1;
    };

    const getRowNode = (i) => { if (!(i >= renderStart && i < renderEnd)) { return undefined; } return containerNode.children[i - renderStart + 2]; }

    return { updateRowData, rerenderList, scrollToRowIndex, getFirstFullyVisibleRowIndex, getRowNode };
}

export async function deleteAnimation(node) {
    node.style.transition = "transform 300ms ease-in"; node.style.transformOrigin = "center center";
    requestAnimationFrame(() => node.style.transform = "scale(0)");
    await new Promise(resolve => {
        function onTransitionEnd(event) {
            if (!(event.propertyName === "transform")) { return; }
            node.removeEventListener("transitionend", onTransitionEnd);
            resolve();
        }
        node.addEventListener("transitionend", onTransitionEnd);
    });
}
