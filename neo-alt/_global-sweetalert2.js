import { observeOnce } from "./_global--observer.js";
import { default as SwalThirdparty } from "./_global-sweetalert2-thirdparty/sweetalert2.esm.min.js";

if (!document.querySelector("#neo-global--sweetalert-css")) {
    const cssUrl = new URL("./_global-sweetalert2-thirdparty/sweetalert2.min.css", import.meta.url).href;
    const link = document.createElement("link"); link.id = "neo-global--sweetalert-css"; link.rel = "stylesheet"; link.href = cssUrl; document.head.appendChild(link);
}

if (!document.querySelector("#neo-global--swal-style")) {
    const neoSwalStyle = document.createElement("style");
    neoSwalStyle.setAttribute("id", "neo-global--swal-style");
    neoSwalStyle.textContent =
        `.neo-global--swal { box-sizing: border-box; width: min(var(--swal2-width), calc(100vw - 1.25em)); max-width: 100%; font-family: sans-serif; }
        .neo-global--swal h2.swal2-title { line-height: normal; }
        .neo-global--swal p { font-size: 16px; }
        ${new URLSearchParams(location.search).get("bricks") === "run" ? `div:where(.swal2-container) { z-index: 3002; }` : ""}`;
    (await observeOnce("body")).appendChild(neoSwalStyle);
}

const Swal = {
    fire: (params) => {
        params.customClass ??= {}; params.customClass.popup ??= ""; params.customClass.popup += " neo-global--swal"; params.customClass.popup = params.customClass.popup.trim();
        if (params.reverseButtons === undefined) { params.reverseButtons = true; }
        return SwalThirdparty.fire(params);
    },
    isVisible: SwalThirdparty.isVisible,
    close: SwalThirdparty.close,
    mixin: SwalThirdparty.mixin,
    DismissReason: SwalThirdparty.DismissReason,
    getConfirmButton: SwalThirdparty.getConfirmButton,
    getCancelButton: SwalThirdparty.getCancelButton,
    showValidationMessage: SwalThirdparty.showValidationMessage,
    isLoading: SwalThirdparty.isLoading,
};
export default Swal;
