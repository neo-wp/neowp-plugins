import { observeOnce } from "./_global--observer.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";

export async function interfaceRunNeoMotionPlaygroundDemo20260612() {
    await (await neoLoadInterfaceFunc("neo-motion", "neo-settings.js", "interfaceOpenSettingsSection20260603"))("neo-motion", { suppressAnimation: true });
    const motionCreateButton = await observeOnce(".neo-motion--create-starter-button");
    await new Promise(requestAnimationFrame);
    motionCreateButton.click();
}
