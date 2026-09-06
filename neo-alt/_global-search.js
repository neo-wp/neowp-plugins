export function matchesSearchText(searchText, values, exactValues = []) {
    const normalizedSearchText = String(searchText).toLowerCase().replace(/\s*\|\s*/g, "|").trim();
    if (normalizedSearchText === "") { return true; }
    const normalizedValues = values.map(value => String(value ?? "").toLowerCase());
    const normalizedExactValues = exactValues.map(value => String(value ?? "").toLowerCase().replace(/^https?:\/\//, ""));
    return normalizedSearchText.split(/\s+/).every(andPart => andPart.split("|").some(orPart => {
        const escapedWildcardParts = orPart.split("*").map(part => part.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
        const searchRegex = new RegExp(escapedWildcardParts.join(".*"), "i");
        return normalizedValues.some(value => value.match(searchRegex) !== null) || normalizedExactValues.includes(orPart.replace(/^https?:\/\//, ""));
    }));
}
