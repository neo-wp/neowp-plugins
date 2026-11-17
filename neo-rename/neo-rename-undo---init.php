<?php
namespace NeoRename\NeoRenameUndo; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_save_rename_for_undo_20250915($image_id, $old_path_rel, $old_title, $old_slug, $old_alt_text = "", $merge_event = null) {
    $undo_records = \NeoRename\NeoGlobal\option__neo_rename_undo__list();
    if (!($merge_event === null || in_array($merge_event, ["title", "alt-text", "both"]))) { \NeoRename\NeoGlobal\throw_global_exception("Invalid merge event for neoRename undo"); }
    $existing_merge_event = $undo_records[$image_id]["mergeEvent"] ?? null;
    $merge_complementary_events = ($existing_merge_event === "title" && $merge_event === "alt-text") || ($existing_merge_event === "alt-text" && $merge_event === "title");
    if ($merge_complementary_events) { $undo_records[$image_id]["mergeEvent"] = "both"; $undo_records[$image_id]["time"] = time(); } else { $undo_records[$image_id] = ["pathRel" => $old_path_rel, "title" => $old_title, "slug" => $old_slug, "altText" => $old_alt_text, "time" => time()]; if ($merge_event !== null) { $undo_records[$image_id]["mergeEvent"] = $merge_event; } }
    foreach ($undo_records as $id => $record) { if (isset($record["time"]) && ($record["time"] < (time() - (180) * 24 * 60 * 60))) { unset($undo_records[$id]); } }
    \NeoRename\NeoGlobal\option__neo_rename_undo__list($undo_records);
}

function interface_get_undo_data_20250915($image_id) {
    return null;
}
