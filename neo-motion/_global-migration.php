<?php
namespace NeoMotion\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\val("neo_global__migrations", []);
\NeoMotion\NeoGlobal\val("neo_global__migration_registration_closed", false);
function register_migration($introduction_date, $callback) {
    if (\NeoMotion\NeoGlobal\val("neo_global__migration_registration_closed")) { \NeoMotion\NeoGlobal\throw_global_exception("Migration registered too late. Please call register_migration() while loading the PHP file, before the neo_init:0 migration runner starts."); }
    if (!is_string($introduction_date)) { \NeoMotion\NeoGlobal\throw_global_exception("Migration introduction date must be a string"); }
    if (!\NeoMotion\NeoGlobal\preg_match_better("/^\d{4}-\d{2}-\d{2}$/", $introduction_date)) { \NeoMotion\NeoGlobal\throw_global_exception("Migration introduction date must use YYYY-MM-DD format"); }
    if (!$callback instanceof \Closure) { \NeoMotion\NeoGlobal\throw_global_exception("Migration callback must be a Closure"); }
    \NeoMotion\NeoGlobal\val("neo_global__migrations")[] = ["date" => $introduction_date, "callback" => $callback];
}

\NeoMotion\NeoGlobal\add_action_hook("neo_init:0", function () {
    \NeoMotion\NeoGlobal\val("neo_global__migration_registration_closed", true);
    \NeoMotion\NeoGlobal\synclock_dir(\NeoMotion\NeoGlobal\cache_path(), function () {
        $stored_plugin_timestamp  = \NeoMotion\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_motion();
        $current_plugin_timestamp = \NeoMotion\NeoGlobal\plugin_timestamp();
        \NeoMotion\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_motion($current_plugin_timestamp);
        if (!($stored_plugin_timestamp > 0 && $current_plugin_timestamp > $stored_plugin_timestamp)) { return; }
        $old_plugin_date = \NeoMotion\NeoGlobal\utc_date_string("Y-m-d", intval($stored_plugin_timestamp));
        $migrations = \NeoMotion\NeoGlobal\val("neo_global__migrations");
        usort($migrations, function ($migration_a, $migration_b) { return strcmp($migration_a["date"], $migration_b["date"]); });
        foreach ($migrations as $migration) {
            if (!($migration["date"] >= $old_plugin_date)) { continue; }
            try { $migration["callback"](); } catch (\Throwable $error) { \NeoMotion\NeoGlobal\global_warn_with_module_name("_global-migration", "Migration failed for " . $migration["date"] . ": " . $error->getMessage() . " in " . $error->getTraceAsString()); }
        }
    }, scope: "migration-" . \NeoMotion\NeoGlobal\plugin_slug() . "-" . \NeoMotion\NeoGlobal\plugin_edition());
});
