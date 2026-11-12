import { getLibraryTable } from "./neo-library--globals.js";
import { observeOnce } from "./_global--observer.js";

export function animateRowShine(row) {
    row.classList.add("neo-library--animate-new");
    setTimeout(() => row.classList.remove("neo-library--animate-new"), 3000);
}

let lastHoveredRow = null;

observeOnce(".neo-library--row", (row) => {
    row.addEventListener("mouseenter", () => {
        if (row.isEqualNode(lastHoveredRow)) { return; }
        lastHoveredRow = row;
        document.querySelectorAll(".neo-library--row.neo-library--row-is-below-last-hovered").forEach(row => row.classList.remove("neo-library--row-is-below-last-hovered"));

        let rowBelow = row;
        for (let i = 0; i < 3; i++) {
            rowBelow = rowBelow.nextElementSibling;
            if (!rowBelow) { break; }
            rowBelow.classList.add("neo-library--row-is-below-last-hovered");
        }
    });
}, { domRoot: await getLibraryTable() });
