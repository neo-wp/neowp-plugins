import { observeOnce } from "./_global--observer.js";
import { jsVar } from "./_global--enqueue-loader.js";

const webComponentLoadPromises = {};
function observeWebComponentsInDomRoot(domRoot) {
    for (const webComponentName of jsVar("neoGlobalWebComponentImporterWebComponents")) {
        if (webComponentLoadPromises[webComponentName]) { continue; }
        observeOnce(webComponentName, async (element) => {
            webComponentLoadPromises[webComponentName] ??= (async () => {
                const module = await import(`./_global-web-component-${webComponentName.replace("-neo-animate", "")}.js`)
                if (!customElements.get(webComponentName)) { customElements.define(webComponentName, module.default); }
                document.getElementById("neo-web-component-hide-neo-animate-" + webComponentName + "-inline-css")?.remove();
            })();
            await webComponentLoadPromises[webComponentName];
            if (element.shadowRoot) { observeWebComponentsInDomRoot(element.shadowRoot); }
        }, { domRoot });
    }
}
observeWebComponentsInDomRoot(document.documentElement);
