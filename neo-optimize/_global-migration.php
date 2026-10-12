<?php
namespace NeoOptimize\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\val("neo_global__migrations", []);
\NeoOptimize\NeoGlobal\val("neo_global__migration_registration_closed", false);
function register_migration($introduction_date, $callback) {
    if (\NeoOptimize\NeoGlobal\val("neo_global__migration_registration_closed")) { \NeoOptimize\NeoGlobal\throw_global_exception("Migration registered too late. Please call register_migration() while loading the PHP file, before the neo_init:0 migration runner starts."); }
    if (!is_string($introduction_date)) { \NeoOptimize\NeoGlobal\throw_global_exception("Migration introduction date must be a string"); }
    if (!\NeoOptimize\NeoGlobal\preg_match_better("/^\d{4}-\d{2}-\d{2}$/", $introduction_date)) { \NeoOptimize\NeoGlobal\throw_global_exception("Migration introduction date must use YYYY-MM-DD format"); }
    if (!$callback instanceof \Closure) { \NeoOptimize\NeoGlobal\throw_global_exception("Migration callback must be a Closure"); }
    \NeoOptimize\NeoGlobal\val("neo_global__migrations")[] = ["date" => $introduction_date, "callback" => $callback];
}

\NeoOptimize\NeoGlobal\add_action_hook("neo_init:0", function () {
    \NeoOptimize\NeoGlobal\val("neo_global__migration_registration_closed", true);
    \NeoOptimize\NeoGlobal\synclock_dir(\NeoOptimize\NeoGlobal\cache_path(), function () {
        $stored_plugin_timestamp  = \NeoOptimize\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_optimize();
        $current_plugin_timestamp = \NeoOptimize\NeoGlobal\plugin_timestamp();
        \NeoOptimize\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_optimize($current_plugin_timestamp);
        if (!($stored_plugin_timestamp > 0 && $current_plugin_timestamp > $stored_plugin_timestamp)) { return; }
        $old_plugin_date = \NeoOptimize\NeoGlobal\utc_date_string("Y-m-d", intval($stored_plugin_timestamp));
        $migrations = \NeoOptimize\NeoGlobal\val("neo_global__migrations");
        usort($migrations, function ($migration_a, $migration_b) { return strcmp($migration_a["date"], $migration_b["date"]); });
        foreach ($migrations as $migration) {
            if (!($migration["date"] >= $old_plugin_date)) { continue; }
            try { $migration["callback"](); } catch (\Throwable $error) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("_global-migration", "Migration failed for " . $migration["date"] . ": " . $error->getMessage() . " in " . $error->getTraceAsString()); }
        }
    }, scope: "migration-" . \NeoOptimize\NeoGlobal\plugin_slug() . "-" . \NeoOptimize\NeoGlobal\plugin_edition());
});
