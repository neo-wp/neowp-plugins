import { observeAttributes } from "./_global--observer.js";

observeAttributes("img", async img => {
    if (!img.getAttribute("src")?.includes("neo-motion=true")) { return; }
    if (img.neoMotionIntersectionObserver) { return; }
    new IntersectionObserver(async entries => {
        for (const entry of entries) {
            if (!entry.isIntersecting) { continue; }
            const { isSafari } = await import("./_global--detect-safari.js");
            if (!isSafari()) {
                img.src = img.src.split("#")[0] + "#" + Date.now();
            } else {
                if (img.src.startsWith("data:image/svg+xml;base64,")) {
                    let decodedBase64 = atob(img.src.split(',')[1]);
                    decodedBase64 += " ";
                    img.src = "data:image/svg+xml;base64," + btoa(decodedBase64);
                } else {
                    await new Promise(requestAnimationFrame);
                    const reader = new FileReader(); reader.readAsDataURL(await (await fetch(img.src)).blob());
                    await new Promise(resolve => reader.onloadend = resolve);
                    img.src = reader.result;
                }
            }
        }
    }, { rootMargin: "0px" }).observe(img);
    img.neoMotionIntersectionObserver = true;
});
