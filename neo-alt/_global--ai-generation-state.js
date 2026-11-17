if (!document.querySelector("#neo-global--ai-generation-state-css")) {
    const linkNode = document.createElement("link");
    linkNode.id = "neo-global--ai-generation-state-css"; linkNode.rel = "stylesheet"; linkNode.href = new URL("./_global--ai-generation-state.css", import.meta.url).href; document.head.appendChild(linkNode);
}

export function setAiGenerationState({ fieldNode, buttonNode, generating }) {
    if (!(fieldNode && buttonNode)) { return; }
    fieldNode.toggleAttribute("data-neo-global--ai-generating", generating); buttonNode.toggleAttribute("data-neo-global--ai-generating", generating); buttonNode.setAttribute("aria-busy", String(generating)); fieldNode.querySelector(":scope > [data-neo-global--ai-generation-effect]")?.remove();
    if (!generating) { return; }
    const effectNode = document.createElement("span");
    effectNode.setAttribute("data-neo-global--ai-generation-effect", ""); fieldNode.appendChild(effectNode);
}
