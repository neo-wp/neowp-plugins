function hashStringForObserver(str) { const hashDjb2 = (str) => { let hash = 5381; for (let i = 0; i < str.length; i++) { hash = ((hash << 5) + hash) + str.charCodeAt(i); } hash = Math.abs(hash).toString(16).padStart(8, "0"); return hash; }; const hash1 = hashDjb2(str); const hash2 = hashDjb2(hash1 + str); return hash1 + hash2; }

export function observeOnce(selector, callback, { domRoot = document.documentElement, callbackSalt = "" } = {}) {
    if (!callback) { return new Promise(resolve => observeOnce(selector, resolve, { domRoot, callbackSalt: Math.random().toString() })); }

    const uniqueKey = hashStringForObserver(`${selector}|${callback.toString()}|${callbackSalt}`);
    observeOnce.observerRegistries ??= new WeakMap(); if (!observeOnce.observerRegistries.has(domRoot)) { observeOnce.observerRegistries.set(domRoot, []); }
    if (observeOnce.observerRegistries.get(domRoot).some(entry => entry.uniqueKey === uniqueKey)) { return; }
    const newObserverEntry = { uniqueKey, selector, callback, domRoot, processedNodes: new Set() }; observeOnce.observerRegistries.get(domRoot).push(newObserverEntry);

    for (const node of domRoot.querySelectorAll(selector)) { newObserverEntry.processedNodes.add(node); callback(node); }

    observeOnce.observersExistingForDomRoots ??= new WeakSet(); if (observeOnce.observersExistingForDomRoots.has(domRoot)) { return; } observeOnce.observersExistingForDomRoots.add(domRoot);
    new MutationObserver(async mutations => {
        observeOnce.semaphores ??= new WeakMap(); observeOnce.semaphores.set(domRoot, true);
        await new Promise(requestAnimationFrame);
        if (!observeOnce.semaphores.get(domRoot)) { return; } observeOnce.semaphores.set(domRoot, false);

        for (const registryEntry of observeOnce.observerRegistries.get(domRoot)) {
            for (const node of domRoot.querySelectorAll(registryEntry.selector)) {
                if (registryEntry.processedNodes.has(node)) { continue; }
                registryEntry.processedNodes.add(node);
                registryEntry.callback(node);
            }
        }
    }).observe(domRoot, { childList: true, subtree: true });
}

export function observeInViewportOnce(node, callback, rootMargin = "0px") {
    if (typeof node === "string") { observeOnce(node, (node) => { observeInViewportOnce(node, callback, rootMargin); }, { callbackSalt: hashStringForObserver(callback.toString()) }); return; }

    new IntersectionObserver((entries, observer) => { entries.forEach(entry => {
        if (entry.isIntersecting) { callback(entry.target); observer.disconnect(); }
    }); }, { rootMargin: rootMargin }).observe(node);
}

export function observeAttributes(node, callback) {
    if (typeof node === "string") { observeOnce(node, (node) => { observeAttributes(node, callback); }, { callbackSalt: hashStringForObserver(callback.toString()) }); return; }
    new MutationObserver(mutations => { callback(node); }).observe(node, { attributes: true, childList: false, subtree: false, characterData: false });
    callback(node);
}

export function observeNode(node, callback) {
    if (typeof node === "string") { observeOnce(node, (node) => { observeNode(node, callback); }, { callbackSalt: hashStringForObserver(callback.toString()) }); return; }
    new MutationObserver(mutations => { callback(node); }).observe(node, { attributes: true, childList: true, subtree: true, characterData: true });
    callback(node);
}

export function observeResize(node, callback) {
    if (typeof node === "string") { observeOnce(node, (node) => { observeResize(node, callback); }, { callbackSalt: hashStringForObserver(callback.toString()) }); return; }
    let nodeWidth, nodeHeight;
    new ResizeObserver((entries) => { entries.forEach(async (entry) => {
        const { width, height } = entry.contentRect;
        if (nodeWidth === width && nodeHeight === height) { return; }
        nodeWidth = width; nodeHeight = height;
        callback(node, { width, height });

        await new Promise(requestAnimationFrame);
        while (entry.target.getAnimations().some(a => a.playState === "running")) {
            const boundingRect = entry.target.getBoundingClientRect();
            if (nodeWidth === boundingRect.width && nodeHeight === boundingRect.height) { break; }
            nodeWidth = boundingRect.width; nodeHeight = boundingRect.height;
            callback(entry.target, { width: boundingRect.width, height: boundingRect.height });
            await new Promise(requestAnimationFrame);
        }
    });}).observe(node);
}

export function observeClick(node, callback) {
    if (typeof node === "string") { observeOnce(node, (node) => { observeClick(node, callback); }, { callbackSalt: hashStringForObserver(callback.toString()) }); return; }
    node.addEventListener("click", (event) => callback(node, event));
}

function _addEventListenerWithObserverSupport(node, eventType, callback) {
    if (typeof node === "string") { observeOnce(node, (node) => { _addEventListenerWithObserverSupport(node, eventType, callback); }, { callbackSalt: hashStringForObserver(callback.toString()) }); return; }
    node.addEventListener(eventType, callback);
}
export function addEventListenerWithInitialCall(node, eventType, callback) {
    _addEventListenerWithObserverSupport(node, eventType, callback);
    callback({ target: node });
}
export function addEventListenerWithInitialCallMultiple(nodesAndEventTypes, callback) {
    for (const [node, eventType] of nodesAndEventTypes) { _addEventListenerWithObserverSupport(node, eventType, callback); }
    callback();
}

export async function domLoaded(callback) {
    if (document.readyState === "loading") { await new Promise(resolve => window.addEventListener("DOMContentLoaded", resolve)); }
    if (callback) { callback(); }
}

export async function delay(time, callback) {
    await domLoaded();
    await new Promise(resolve => setTimeout(resolve, time * 1000));
    if (callback) { callback(); }
}
