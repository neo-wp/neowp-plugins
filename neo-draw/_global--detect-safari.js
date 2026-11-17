export function isSafari() {
    return typeof window !== "undefined" && 'safari' in window && 'pushNotification' in window.safari || ('ongesturechange' in window && /Safari/.test(navigator.userAgent) && !/CriOS|FxiOS|EdgiOS|OPiOS/.test(navigator.userAgent));
}
