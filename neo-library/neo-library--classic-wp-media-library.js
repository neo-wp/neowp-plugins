import { neo__ } from "./_global--translation.js";
import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { setCookie } from "./_global--cookie.js";
import { addQueryParam } from "./_global--url-helper.js";
import { observeOnce } from "./_global--observer.js";

const neoLibraryButton = new DomNodeHelper(`<a href="${addQueryParam(location.pathname, "page", "neo-library")}" class="view-neo-library"><span class="screen-reader-text">${neo__("neoLibrary", "neoLibrary")}</span></a>`)
    .on("click", (evt) => {
        evt.preventDefault();
        history.replaceState({}, "", addQueryParam(location.href, "force-old-media-lib", "true"));
        setCookie("neo-library--use-neo-library", "true");
        location.href = evt.currentTarget.href;
    })
    .getNode();
(await observeOnce(".view-switch")).append(neoLibraryButton);
