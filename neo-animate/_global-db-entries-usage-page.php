<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\register_backend_page("neo-global--db-entries-usage-neo-animate", fn () => \NeoAnimate\NeoGlobal\current_user_can__global_db_entries_usage(), function () {
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoGlobalDbEntriesUsageDefaultTimeoutSeconds", 10);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php \NeoAnimate\NeoGlobal\echo_neo__("Image in WordPress Database", "Bilder in WordPress Datenbank") ?></title>
        <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_start([]); ?>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                --page-header-height: 100px;
            }

            #pageHeader {
                position: fixed; top: 0; left: 0;
                width: 100%; height: var(--page-header-height);
                padding: 20px; box-sizing: border-box;
            }
            #pageHeader>img {
                position: absolute; top: 20px; right: 20px;
                max-width: calc(2 * var(--page-header-height)); height: var(--page-header-height); object-fit: contain;
            }
            #tablesContainer { margin-top: var(--page-header-height); }

            .tableContainer {
                margin-bottom: 30px; padding: 10px;
            }
            .tableContainer>h2 {
                margin-bottom: 8px; color: <?php echo esc_html(\NeoAnimate\NeoGlobal\transform_colors("#ff00ff")) ?>; font-size: 22px;
            }
            table {
                border-collapse: collapse;
            }
            th, td {
                padding: 8px;
                border-bottom: 1px solid #ddd;
                text-align: left; vertical-align: baseline;
                white-space: pre;
            }
            .highlight {
                background-color: <?php echo esc_html(\NeoAnimate\NeoGlobal\transform_colors("#00ff00")) ?>;
            }
            .loadingContainer { display: flex; align-items: center; gap: 14px; padding: 20px 10px; }
            .loadingSpinner { width: 42px; height: 42px; border-radius: 50%; border: 5px solid rgba(0, 0, 0, 0.2); border-top-color: rgba(0, 0, 0, 0.8); animation: loadingSpinnerRotate 1s linear infinite; }
            @keyframes loadingSpinnerRotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            .usageMessage { padding: 10px; }
        <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_end(); ?>
        <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start([]); ?>
            <?php echo \NeoAnimate\NeoGlobal\get_code_for_js_variables("backend") /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>; /* Output is properly escaped in get_code_for_js_variables() for security */
        <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?>
    </head>
    <body>
        <div id="pageHeader">
            <h1><?php \NeoAnimate\NeoGlobal\echo_neo__("Image in WordPress Database", "Bilder in WordPress Datenbank") ?></h1>
            <p><?php \NeoAnimate\NeoGlobal\echo_neo__("This page displays the database entries in WordPress that the image is using.", "Diese Seite zeigt die Datenbankeinträge in WordPress, die das Bild verwenden.") ?></p>
        </div>
        <div id="tablesContainer"></div>

        <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
            import { neo__ } from "<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global--translation.js";
            import { jsVar } from "<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global--enqueue-loader.js";
            import { fetchImageUsageLookup } from "<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global-image-usage-lookup.js";

            const queryParams = new URLSearchParams(location.search);
            const imgUrl = queryParams.get("img-url");
            const countInCurrentPost = queryParams.get("count-in-current-post") || "0";
            const timeoutSeconds = parseInt(queryParams.get("timeout") || jsVar("neoGlobalDbEntriesUsageDefaultTimeoutSeconds"), 10);
            const tablesContainer = document.getElementById("tablesContainer");
            const escapeRegex = (text) => text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
            const showMessage = (message) => { tablesContainer.innerHTML = ""; const messageNode = document.createElement("p"); messageNode.classList.add("usageMessage"); messageNode.textContent = message; tablesContainer.appendChild(messageNode); return messageNode; };

            if (imgUrl) { const imgPreview = document.createElement("img"); imgPreview.src = imgUrl; imgPreview.alt = ""; document.getElementById("pageHeader").appendChild(imgPreview); }

            const addCountInCurrentPostSection = () => {
                if (!(parseInt(countInCurrentPost) > 0)) { return; }
                const countInOwnPostSection = document.createElement("section");
                countInOwnPostSection.classList.add("tableContainer");
                const heading = document.createElement("h2"); heading.textContent = neo__("Count in own post", "Anzahl im eigenen Beitrag"); countInOwnPostSection.appendChild(heading);
                const countInOwnPostParagraph = document.createElement("p");
                countInOwnPostParagraph.textContent = neo__(`The image is used ${countInCurrentPost} times in the currently open post.`, `Das Bild wird ${countInCurrentPost} mal im aktuell offenen Beitrag verwendet.`);
                countInOwnPostSection.appendChild(countInOwnPostParagraph);
                tablesContainer.appendChild(countInOwnPostSection);
            };

            const renderDbEntries = (dbEntries) => {
                const dataGroupedByTable = {};
                for (const entry of dbEntries) { dataGroupedByTable[entry.tableName] ??= []; dataGroupedByTable[entry.tableName].push(entry.contentPreview); }
                tablesContainer.innerHTML = "";
                for (const tableName of Object.keys(dataGroupedByTable)) {
                    const tableSection = document.createElement("section");
                    tableSection.classList.add("tableContainer");
                    const tableHeading = document.createElement("h2"); tableHeading.textContent = tableName; tableSection.appendChild(tableHeading);

                    const tableNode = document.createElement("table");
                    const headerRow = document.createElement("tr");
                    const columnNames = Object.keys(dataGroupedByTable[tableName][0]);
                    for (const columnName of columnNames) {
                        const th = document.createElement("th");
                        th.textContent = columnName;
                        headerRow.appendChild(th);
                    }
                    tableNode.appendChild(headerRow);

                    for (const rowPreview of dataGroupedByTable[tableName]) {
                        const row = document.createElement("tr");
                        for (const previewColName of columnNames) {
                            const td = document.createElement("td");
                            td.textContent = rowPreview[previewColName];
                            const imgUrlExtension = imgUrl.match(/\.[^/.?]+(?=($|\?))/)?.[0];
                            if (imgUrlExtension) {
                                const imgUrlWithoutExtension = imgUrl.replace(/\.[^/.?]+(?=($|\?))/, "");
                                const imgUrlCandidates = [imgUrlWithoutExtension, imgUrlWithoutExtension.replaceAll("/", "\\/")];
                                const imgUrlOrSizeRegex = new RegExp(`(?:${imgUrlCandidates.map(escapeRegex).join("|")})(?:-[0-9]+x[0-9]+)?${escapeRegex(imgUrlExtension)}`, "gi");
                                td.innerHTML = td.innerHTML.replace(imgUrlOrSizeRegex, '<span class="highlight">$&</span>');
                            }
                            row.appendChild(td);
                        }
                        tableNode.appendChild(row);
                    }
                    tableSection.appendChild(tableNode);
                    tablesContainer.appendChild(tableSection);
                }
                if (Object.keys(dataGroupedByTable).length === 0) { showMessage(neo__("The image was not found in the database.", "Das Bild wurde nicht in der Datenbank gefunden.")); }
                addCountInCurrentPostSection();
            };

            if (!imgUrl) { showMessage(neo__("Missing image URL.", "Bild-URL fehlt.")); }
            else if (!jsVar("currentUserCanGlobalDbEntriesDetails")) { showMessage(neo__("Only admins can see database details about where this image is used.", "Nur Admins können Datenbank-Details zur Verwendung dieses Bildes sehen.")); addCountInCurrentPostSection(); }
            else {
                tablesContainer.innerHTML = `<div class="loadingContainer"><div class="loadingSpinner"></div><p>${neo__("Loading...", "Lädt...")}</p></div>`;
                try {
                    const response = await fetchImageUsageLookup(imgUrl, timeoutSeconds);
                    if (response.dbEntriesUsingImage === false) {
                        const timeoutNode = showMessage(neo__("Usage lookup timed out.", "Verwendungs-Suche hat zu lange gedauert."));
                        const longerSearchUrl = new URL(location.href); longerSearchUrl.searchParams.set("timeout", String((response.timeoutSeconds || timeoutSeconds) * 2));
                        timeoutNode.append(" ");
                        const longerSearchLink = document.createElement("a"); longerSearchLink.href = longerSearchUrl.toString(); longerSearchLink.textContent = neo__("Search longer", "Länger suchen"); timeoutNode.appendChild(longerSearchLink);
                        addCountInCurrentPostSection();
                    } else { renderDbEntries(response.dbEntriesUsingImage); }
                } catch (error) { showMessage(neo__("Could not load image usages.", "Bildverwendungen konnten nicht geladen werden.") + " " + error.message); }
            }
        <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?>
    </body>
    </html>
<?php });
