<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\val("neo_global__migrations", []);
\NeoRename\NeoGlobal\val("neo_global__migration_registration_closed", false);
function register_migration($introduction_date, $callback) {
    if (\NeoRename\NeoGlobal\val("neo_global__migration_registration_closed")) { \NeoRename\NeoGlobal\throw_global_exception("Migration registered too late. Please call register_migration() while loading the PHP file, before the neo_init:0 migration runner starts."); }
    if (!is_string($introduction_date)) { \NeoRename\NeoGlobal\throw_global_exception("Migration introduction date must be a string"); }
    if (!\NeoRename\NeoGlobal\preg_match_better("/^\d{4}-\d{2}-\d{2}$/", $introduction_date)) { \NeoRename\NeoGlobal\throw_global_exception("Migration introduction date must use YYYY-MM-DD format"); }
    if (!$callback instanceof \Closure) { \NeoRename\NeoGlobal\throw_global_exception("Migration callback must be a Closure"); }
    \NeoRename\NeoGlobal\val("neo_global__migrations")[] = ["date" => $introduction_date, "callback" => $callback];
}

\NeoRename\NeoGlobal\add_action_hook("neo_init:0", function () {
    \NeoRename\NeoGlobal\val("neo_global__migration_registration_closed", true);
    \NeoRename\NeoGlobal\synclock_dir(\NeoRename\NeoGlobal\cache_path(), function () {
        $stored_plugin_timestamp  = \NeoRename\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_rename();
        $current_plugin_timestamp = \NeoRename\NeoGlobal\plugin_timestamp();
        \NeoRename\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_rename($current_plugin_timestamp);
        if (!($stored_plugin_timestamp > 0 && $current_plugin_timestamp > $stored_plugin_timestamp)) { return; }
        $old_plugin_date = \NeoRename\NeoGlobal\utc_date_string("Y-m-d", intval($stored_plugin_timestamp));
        $migrations = \NeoRename\NeoGlobal\val("neo_global__migrations");
        usort($migrations, function ($migration_a, $migration_b) { return strcmp($migration_a["date"], $migration_b["date"]); });
        foreach ($migrations as $migration) {
            if (!($migration["date"] >= $old_plugin_date)) { continue; }
            try { $migration["callback"](); } catch (\Throwable $error) { \NeoRename\NeoGlobal\global_warn_with_module_name("_global-migration", "Migration failed for " . $migration["date"] . ": " . $error->getMessage() . " in " . $error->getTraceAsString()); }
        }
    }, scope: "migration-" . \NeoRename\NeoGlobal\plugin_slug() . "-" . \NeoRename\NeoGlobal\plugin_edition());
});
