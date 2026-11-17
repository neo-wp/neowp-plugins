import { observeOnce } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";

export async function reloadPage(targetUrl) {
    const curtain = document.createElement("div");
    curtain.innerHTML = `
        <div style="position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.4);z-index:999999;display:flex;justify-content:center;align-items:center;flex-direction:column;">
            <div style="border:6px solid rgba(255,255,255,0.2);border-top:6px solid white;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;"></div>
            <div style="color:white;margin-top:16px;font-family:sans-serif;font-size:1.2em;text-align:center;user-select:none;transform:translateX(.2em);">${neo__("Reloading...", "Neu laden...")}</div>
            <style>@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}</style>
        </div>
    `;
    (await observeOnce("body")).appendChild(curtain);
    if (!targetUrl) { location.reload(); return; }

    const currentUrl = new URL(location.href); const nextUrl = new URL(targetUrl, location.href);
    if (currentUrl.origin === nextUrl.origin && currentUrl.pathname === nextUrl.pathname && currentUrl.search === nextUrl.search) {
        if (currentUrl.hash !== nextUrl.hash) { window.addEventListener("hashchange", () => location.reload(), { once: true }); location.href = nextUrl.toString(); return; }
        location.reload(); return;
    }

    location.href = nextUrl.toString();
}
