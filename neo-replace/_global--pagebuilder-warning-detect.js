export async function isPagebuilderOpen() {
    const channel = new BroadcastChannel("neo-global--pagebuilder-warning-check-neo-replace");
    channel.postMessage({ type: "is-pagebuilder-open" });

    return await new Promise((resolve) => {
        const timeoutHandleId = setTimeout(() => { channel.close(); resolve(false); }, 0.2 * 1000);
        channel.onmessage = (event) => {
            if (event?.data?.type === "pagebuilder-response") {
                channel.close();
                clearTimeout(timeoutHandleId);
                resolve(true);
            }
        };
    });
}
