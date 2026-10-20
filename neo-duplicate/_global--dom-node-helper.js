window.neoDomNodeHelperEventListeners = [];

export class DomNodeHelper {
    constructor(sourceHtml) {
        if (!sourceHtml) { throw new Error("sourceHtml must be set!"); }
        this.sourceHtml = sourceHtml;
        this.eventListeners = {};
        this.classList = [];
    }

    withClass(classNames) {
        this.classList.push(...classNames.split(" "));
        return this;
    }

    withClasses(...classNames) {
        this.classList.push(...classNames);
        return this;
    }

    on(eventName, callback) {
        this.eventListeners[eventName] ??= [];
        this.eventListeners[eventName].push(callback);
        return this;
    }

    getNode() {
        const dummyContainer = document.createElement("div");
        dummyContainer.innerHTML = this.sourceHtml;
        if (dummyContainer.children.length !== 1) { console.error(dummyContainer.children); console.error("Error while creating DOM node from this HTML:", this.sourceHtml); throw new Error("HTML must have exactly one root element!"); }
        const node = dummyContainer.children[0];

        for (const className of this.classList) { node.classList.add(className); }

        for (const eventName in this.eventListeners) {
            for (const callback of this.eventListeners[eventName]) {
                node.addEventListener(eventName, (event) => { event.preventDefault(); callback(event) });
            }
        }
        return node;
    }

    getHTML() {
        if (this.eventListeners["click"]?.length > 1)                                                        { throw new Error("Only one click listener supported!"); }
        if (Object.keys(this.eventListeners).length > 1)                                                     { throw new Error("Only one event listener supported!"); }
        if (Object.keys(this.eventListeners).length >= 1 && Object.keys(this.eventListeners)[0] !== "click") { throw new Error("Only click event listener supported!"); }
        const clickCallback = this.eventListeners["click"][0];
        const clickCallbackIndex = window.neoDomNodeHelperEventListeners.length;
        window.neoDomNodeHelperEventListeners.push(clickCallback);

        return this.getNode().outerHTML.replace(">", ` onclick="window.neoDomNodeHelperEventListeners[${clickCallbackIndex}](event)">`);
    }
}

export function escapeHtml(value) { return String(value ?? "").replace(/[&<>"']/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char])); }
export function escapeInlineJsString(value) { return escapeHtml(String(value ?? "").replace(/\\/g, "\\\\").replace(/\r/g, "\\r").replace(/\n/g, "\\n").replace(/'/g, "\\'")); }
export function escapeCssSelectorString(value) { return String(value ?? "").replace(/\\/g, "\\\\").replace(/"/g, '\\"').replace(/\r/g, "\\D ").replace(/\n/g, "\\A "); }
