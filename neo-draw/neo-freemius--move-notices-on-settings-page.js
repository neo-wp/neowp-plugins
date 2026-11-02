import { observeOnce } from "./_global--observer.js";

observeOnce("#neo-freemius--notices", async (neoFreemiusNoticesContainer) => {
    observeOnce(".fs-notice", (node) => {
        node.setAttribute("class", "neo-freemius--notice");
        node.querySelectorAll('a[href="#"]').forEach((aNode) => { aNode.href = "javascript:void(0);" });
        neoFreemiusNoticesContainer.appendChild(node);
        document.getElementById("neo-freemius--hide-fs-notice-inline-css")?.remove();
    });
});
