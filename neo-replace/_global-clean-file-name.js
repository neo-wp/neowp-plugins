import { extractJson } from "./_global--extract-json.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";

const fileExtensionRegex = /\.[a-zA-Z0-9]+$/;

function escapeRegExp(string) { return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"); }

function specialCharsToRegex(specialCharArray) { const specialCharToRegex = (specialChar) => "\\u{" + Array.from(specialChar).map(char => char.codePointAt(0).toString(16).padStart(4, "0")).join("}\\u{") + "}"; return new RegExp(specialCharArray.sort((a, b) => Array.from(b).length - Array.from(a).length).map(specialChar => specialCharToRegex(specialChar)).flat().join("|"), "gu"); }

export async function initCleanFileNameEmojiMap() {
    if (cleanPathRel.emojiMap) { return cleanPathRel.emojiMap; }
    if (sessionStorage.getItem("neo-global--clean-file-name--emoji-map")) {
        cleanPathRel.emojiMap = JSON.parse(sessionStorage.getItem("neo-global--clean-file-name--emoji-map"));
    } else {
        cleanPathRel.emojiMap = (await fetch(pluginUrl() + "/_global-emoji-names.json").then(extractJson)).emojis;
        sessionStorage.setItem("neo-global--clean-file-name--emoji-map", JSON.stringify(cleanPathRel.emojiMap));
    }
    cleanPathRel.emojiMap["☕"] = "coffee";
    return cleanPathRel.emojiMap;
}

export function cleanTitle(input, forceCapitalize = false) {
    input = input.replace(fileExtensionRegex, "");
    if (!cleanTitle.LEXICON) {
        let lex = [];
        lex = [...lex, ...["neoWP", "neoAll", "neoAnimate", "neoDuplicate", "neoDraw", "neoLazy", "neoLibrary", "neoMotion", "neoOptimize", "neoRename", "neoAlt", "neoReplace", "neoManager", "neoPlugins", "neoUniverse", "neoAI"].flatMap(p => [p, [`neo(?:[-_\\s])${p.replace("neo", "")}`, `neo ${p.replace("neo", "")}`]])];
        lex = [...lex, "WordPress", "WP", "WooCommerce"];
        cleanTitle.LEXICON_TITLECASE = ["a", "an", "and", "as", "at", "but", "by", "for", "from", "if", "in", "nor", "of", "on", "or", "so", "the", "to", "up", "via", "vs.", "with", "yet", "per", "than", "till", "upon"];
        lex = [...lex, ...cleanTitle.LEXICON_TITLECASE];
        lex = [...lex, ["(IMG|IMG_E|DSC|DSCN|DSCF|VID|MVI|MOV|PXL|PANO|CAM|LEI|LEICA|IMGP|RIMG|GOPR|GP|GH)([\\d_]+)", "$1$2"]];
        lex = [...lex, ["(\\d[\\d_\\-:./]+\\d)", "$1"]];
        lex = [...lex, "AirDrop", "AirPlay", "AirPods", "AirPods Max", "AirPods Pro", "AirTag", "AirTags", "AppKit", "Apple Arcade", "Apple Fitness+", "Apple ID", "Apple Music", "Apple News+", "Apple Pay", "Apple TV", "Apple TV+", "Apple Watch", "ARKit", "CarPlay", "CoreML", "FaceTime", "FinalCut", "GarageBand", "HomeKit", "iBooks", "iCloud", "iLife", "iMac", "iMessage", "iMovie", "iPad", "iPadOS", "iPhone", "iPhoto", "iPod", "iWatch", "iWork", "iOS", "LogicPro", "Mac mini", "MacBook", "MacBook Air", "MacBook Pro", "macOS", "MagSafe", "M1", "M2", "M3", "M4", "ProTools", "QuickTime", "RealityKit", "SceneKit", "SpriteKit", "SuperRetina", "SwiftUI", "T2", "TestFlight", "TimeMachine", "tvOS", "UIKit", "visionOS", "VisionPro", "watchOS"];
        lex = [...lex, "ARD", "ZDF", "C&A", "BRD", "DDR", "USA", "BSE", "HIV", "DRK", "GbR", "GmbH", "THX", "VHS", "FSK", "RAF", "LSD", "FKK", "DVU", "AKW", "KKK", "RHP", "USW", "LMAA", "PLZ", "DPD", "BMX", "BPM", "XTC", "EMI", "CBS", "BMG", "ADAC", "DLRG", "EKZ", "RTL", "DFB", "ABS", "TÜV", "BMW", "KMH", "PVC", "FCKW", "MFG", "HNO", "EKG", "LBS", "WKD", "IHK", "UKW", "NDW", "BTM", "BKA", "LTU", "TNT", "NTV", "THW", "DPA", "H&M", "BSB", "FDH", "SOS", "FDJ", "KDW", "BWL", "FDP", "EDV", "IBM", "HSV", "VfB", "ABC", "DAF", "OMD", "TM3", "A&O", "AEG", "UVA", "UVB", "THC", "OCB"];
        lex = [...lex, "OpenAI", "AI", "LLM", "ChatGPT", "GPT", "LLaMA", "DeepSeek", "HuggingFace", "PyTorch", "TensorFlow", "XGBoost", "LightGBM", "CatBoost", "FAISS", "LlamaIndex", "LangChain", "LangChainJS", "VectorDB", "Fine-tuning", "OpenCV", "NumPy", "SciPy", "JupyterLab", "CometML", "MLflow", "Weights & Biases"];
        lex = [...lex, "JavaScript", "TypeScript", "Node.js", "Next.js", "Nuxt.js", "Vue.js", "Three.js", "D3.js", "SvelteKit", "RxJS", "JSX", "TSX", "WebAssembly", "WebGL", "WebGPU", "WebRTC", "npm", "pnpm", "yarn", "C#", "C++", ".NET", "ASP.NET Core", "F#", "Objective-C", "OpenCL", "OpenGL", "DirectX", "API", "HTML", "CSS", "JSON", "SQL", "XML", "SDK", "CLI", "curl", "wget", "ffmpeg", "rsync", "grep", "awk", "sed", "tmux", "bash", "zsh", "fish"];
        lex = [...lex, "OAuth", "HTTP", "HTTPS", "Wi-Fi", "WiFi", "NFC", "LTE", "5G", "6G", "LAN", "WLAN", "DNS", "VPN", "SSID", "URL", "FTP", "QR-Code", "QRCode", "OCR", "SSL", "TTL", "IPv4", "IPv6", "UDP", "TCP", "NTP", "DHCP", "SMTP", "IMAP", "POP3", "NAT", "Content-Type", "Content-Length", "User-Agent", "Accept-Language", "Content-Encoding", "X-Forwarded-For", "Cache-Control", "Referrer-Policy", "Strict-Transport-Security", "Content-Security-Policy", "Access-Control-Allow-Origin"];
        lex = [...lex, "FIDO2", "WebAuthn", "SSO", "2FA", "TOTP", "PGP", "GPG", "SPF", "DKIM", "DMARC", "MTA", "MUA", "SHA-1", "SHA-256", "SHA-512", "MD5", "CRC32", "UUID"];
        lex = [...lex, "USB", "USB-C", "USB-A", "DisplayPort", "HDMI", "VGA", "DVI", "RJ45", "SSD", "HDD", "NVMe", "UFS", "microSD", "SDHC", "SDXC", "CFexpress", "eGPU", "CPU", "GPU", "PCIe", "NVIDIA RTX", "GeForce"];
        lex = [...lex, "HD", "FullHD", "Full HD", "HDR", "HDR10", "HDR10+", "IMAX", "sRGB", "Adobe RGB", "4K", "8K", "ProRAW", "ProRes", "EXIF", "CMOS", "LiDAR", "UHD"];
        lex = [...lex, "HEIC", "HEIF", "AVIF", "WebP", "JPEG", "JPG", "PNG", "SVG", "PDF", "reStructuredText", "MP3", "MP4", "MOV", "MKV", "AVI", "H.264", "H.265", "HEVC", "VP9", "AV1", "AAC", "FLAC", "ALAC", "WAV", "CSV", "TSV", "XLSX", "DOCX", "PPTX", "RTF", "YAML", "OBJ", "FBX", "STL", "JSON-LD", "OpenType", "TrueType", "PostScript", "ICC", "ICC Profile", "CMYK", "RGB"];
        lex = [...lex, "ISO 8601", "RFC 7231", "RFC 9110", "ISO/IEC", "IEEE 802.11", "IEEE 754", "IEEE", "W3C", "IETF", "UTF-8", "UTF-16", "UTF-32", "ASCII", "UTF-8-BOM", "CRLF", "LF", "UTC", "GMT", "CET", "CEST", "PST", "PDT", "EST", "EDT", "ISO 9001", "DIN EN 301549", "IEC 62304", "ISO 27001", "ms", "ns", "fps", "GHz", "MHz", "km/h", "m/s", "1D", "2D", "3D", "4D", "5D", "6D", "7D", "8D", "9D"];
        lex = [...lex, "AWS", "GCP", "VSCode", "JetBrains", "IntelliJ", "PyCharm", "WebStorm", "InDesign", "DaVinci Resolve", "YouTube", "GitHub", "GitLab", "StackOverflow", "WhatsApp", "TikTok", "LinkedIn", "WeChat", "X", "Apple TV+", "Paramount+", "HBO Max", "ProtonMail", "OneDrive", "AdWords", "AdSense", "AdMob", "eBook", "eBooks", "eMarket", "eMarketplace", "eMarketplaces", "eMarkets", "eReader", "eShop", "eShops", "eStore", "eStores", "E-commerce", "E-com"];
        lex = [...lex, "GDPR", "CCPA", "DMCA", "EULA", "FAQ", "NASDAQ", "NYSE", "S&P 500", "EBITDA", "M&A", "P&L", "CEO", "CFO", "CTO", "COO", "Inc.", "Corp.", "Ltd.", "LLC", "PLC", "S.A.", "S.p.A.", "GmbH", "e.V.", "e.K.", "SARL", "USA", "UNESCO", "UNICEF", "IRS", "FBI", "CIA", "NSA", "BKA", "BND", "BMF", "NASA", "GPL", "LGPL", "AGPL", "CC0", "CC-BY", "CC-BY-SA", "PhD", "MBA", "BSc", "MSc", "Dr.", "Prof.", "PayPal", "ETH", "BTC", "NFT", "FinTech"];
        lex = [...lex, "BMW", "Mercedes-Benz", "MotoGP", "NASCAR", "WRC", "DTM", "UnrealEngine", "Unity3D", "CryEngine", "PlayStation", "NintendoSwitch", "RPG", "MMORPG", "RTS", "VR", "VST", "FL Studio", "GarageBand", "LEGO", "IKEA", "GoPro", "BlaBlaCar"];
        lex = [...lex, "IoT", "SpaceX", "AC/DC", "HVAC", "Li-ion", "kWh", "mAh", "SARS-CoV-2", "COVID-19", "mRNA", "DNA", "RNA", "CRISPR", "HIV", "AIDS"];
        lex.sort((a, b) => { return (typeof a === "string" ? a : a[0]).toLowerCase() < (typeof b === "string" ? b : b[0]).toLowerCase() ? -1 : 1; });
        cleanTitle.LEXICON = lex;
    }
    cleanTitle.SEARCH_REGEX ??= new RegExp(cleanTitle.LEXICON.map(entry => Array.isArray(entry) ? entry[0] : escapeRegExp(entry)).map(word => "\\b" + word + "\\b").join("|") + "|(?:_|-|\\.|\\s)+|[a-zA-Zäöü0-9]+", "gi");
    let index = 0;
    let cleanedTitle = input.replace(cleanTitle.SEARCH_REGEX, (match) => {
        if (match.match(/^(?:_+|-+|\.|\s+)$/)) { return " "; }
        if (index++ === 0 && cleanTitle.LEXICON_TITLECASE.includes(match.toLowerCase())) { return match.charAt(0).toUpperCase() + match.slice(1).toLowerCase(); }
        if (!cleanTitle.PREP_LEXICON) {
            const fixed = Object.create(null), patterns = []; for (const entry of cleanTitle.LEXICON) { if (Array.isArray(entry)) { patterns.push([new RegExp("^" + entry[0] + "$", "i"), entry[1]]); } else { fixed[entry.toLowerCase()] = entry; } }
            cleanTitle.PREP_LEXICON = { fixed, patterns };
        }
        const { fixed, patterns } = cleanTitle.PREP_LEXICON;
        const fixedLowercaseMatch = fixed[match.toLowerCase()]; if (fixedLowercaseMatch) { return fixedLowercaseMatch; }
        for (const [regex, replacement] of patterns) { if (match.match(regex)) { return match.replace(regex, replacement); } }
        if (match.match(/.+[A-ZÄÖÜ].*/)) { return match; }
        if (input.match(/[A-ZÄÖÜ]/) && !forceCapitalize) { return match; }
        return match.charAt(0).toUpperCase() + match.slice(1).toLowerCase();
    });
    cleanedTitle = cleanedTitle.trim() || "Untitled";
    return cleanedTitle;
}

export function cleanPathRel(input, options = {}) {
    if (!cleanPathRel.emojiMap) { throw new Error("Emoji map is not loaded."); }
    const fileExtensionOriginal = (input.match(fileExtensionRegex) ?? [""])[0].toLowerCase();
    const fileExtension = options.keepExtension ? (fileExtensionOriginal === ".jpeg" ? ".jpg" : fileExtensionOriginal) : "";
    let text = input;
    text = text.replace(fileExtensionRegex, "");
    cleanPathRel.emojiRegex ??= specialCharsToRegex(Object.keys(cleanPathRel.emojiMap));
    text = text.replace(cleanPathRel.emojiRegex, match => cleanPathRel.emojiMap[match] ? (" " + cleanPathRel.emojiMap[match] + " ") : match);
    text = text.toLowerCase();
    const charMap = {
        "ä": "ae", "ä": "ae", "ö": "oe", "ö": "oe", "ü": "ue", "ü": "ue", "ß": "ss", "æ": "ae", "œ": "oe", "ø": "o", "å": "a",
        "à": "a", "á": "a", "â": "a", "ã": "a", "ā": "a", "ă": "a", "ą": "a", "è": "e", "é": "e", "ê": "e", "ë": "e", "ē": "e", "ė": "e", "ę": "e", "ì": "i", "í": "i", "î": "i", "ï": "i", "ī": "i", "į": "i", "ò": "o", "ó": "o", "ô": "o", "õ": "o", "ō": "o", "ő": "o", "ù": "u", "ú": "u", "û": "u", "ū": "u", "ů": "u", "ű": "u", "ç": "c", "ć": "c", "č": "c", "ĉ": "c", "ċ": "c", "ñ": "n", "ń": "n", "ň": "n", "ś": "s", "ş": "s", "š": "s", "ŝ": "s", "ž": "z", "ź": "z", "ż": "z", "ý": "y", "ÿ": "y", "ğ": "g", "đ": "d", "ð": "d", "þ": "th", "ł": "l", "ř": "r", "ć": "c",
        "€": "-euro-", "£": "-gbp-", "$": "-usd-", "¥": "-yen-",
        "§": "-paragraph-", "%": "-percent-", "=": "-is-", "@": "-at-", "µ": "u"
    };
    cleanPathRel.charMapRegex ??= specialCharsToRegex(Object.keys(charMap));
    text = text.replace(cleanPathRel.charMapRegex, char => charMap[char] ?? char);
    text = text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    text = text.replace(/[^a-z0-9\/]/g, "-").replace(/-+/g, "-").replace(/^-+|-+$/g, "");
    if (!text.split("/").pop()) { text = (text.includes("/") ? text.replace(/[^\/]*$/, "") : "") + "untitled"; }
    return text + fileExtension;
}

export function cleanSlug(input) {
    const cleanPath = cleanPathRel(input);
    const cleanSlugValue = cleanPath.split("/").pop() || "untitled";
    return cleanSlugValue;
}
