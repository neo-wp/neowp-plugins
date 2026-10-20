import { observeClick } from "./_global--observer.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";

observeClick("#neo-feedback--settings-button", async () => { await (await neoLoadInterfaceFunc("neo-rename", "neo-feedback.js", "interfaceOpenFeedbackDialog20260802"))(); });
