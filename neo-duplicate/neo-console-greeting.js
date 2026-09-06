import { jsVar } from "./_global--enqueue-loader.js";

const consoleGreeting = jsVar("neoConsoleGreeting");

if (window.self === window.top) {
    console.log("%c" + consoleGreeting.message + consoleGreeting.hideUrl, "background: linear-gradient(to bottom, #ff00ff, #00ff00), linear-gradient(to bottom, #ff00ff, #00ff00);background-size: 3px 100%, 3px 100%;background-repeat: no-repeat; background-position: left, right; padding: 0 8px;");
}
