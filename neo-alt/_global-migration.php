<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAlt\NeoGlobal\val("neo_global__migrations", []);
\NeoAlt\NeoGlobal\val("neo_global__migration_registration_closed", false);
function register_migration($introduction_date, $callback) {
    if (\NeoAlt\NeoGlobal\val("neo_global__migration_registration_closed")) { \NeoAlt\NeoGlobal\throw_global_exception("Migration registered too late. Please call register_migration() while loading the PHP file, before the neo_init:0 migration runner starts."); }
    if (!is_string($introduction_date)) { \NeoAlt\NeoGlobal\throw_global_exception("Migration introduction date must be a string"); }
    if (!\NeoAlt\NeoGlobal\preg_match_better("/^\d{4}-\d{2}-\d{2}$/", $introduction_date)) { \NeoAlt\NeoGlobal\throw_global_exception("Migration introduction date must use YYYY-MM-DD format"); }
    if (!$callback instanceof \Closure) { \NeoAlt\NeoGlobal\throw_global_exception("Migration callback must be a Closure"); }
    \NeoAlt\NeoGlobal\val("neo_global__migrations")[] = ["date" => $introduction_date, "callback" => $callback];
}

\NeoAlt\NeoGlobal\add_action_hook("neo_init:0", function () {
    \NeoAlt\NeoGlobal\val("neo_global__migration_registration_closed", true);
    \NeoAlt\NeoGlobal\synclock_dir(\NeoAlt\NeoGlobal\cache_path(), function () {
        $stored_plugin_timestamp  = \NeoAlt\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_alt();
        $current_plugin_timestamp = \NeoAlt\NeoGlobal\plugin_timestamp();
        \NeoAlt\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_alt($current_plugin_timestamp);
        if (!($stored_plugin_timestamp > 0 && $current_plugin_timestamp > $stored_plugin_timestamp)) { return; }
        $old_plugin_date = \NeoAlt\NeoGlobal\utc_date_string("Y-m-d", intval($stored_plugin_timestamp));
        $migrations = \NeoAlt\NeoGlobal\val("neo_global__migrations");
        usort($migrations, function ($migration_a, $migration_b) { return strcmp($migration_a["date"], $migration_b["date"]); });
        foreach ($migrations as $migration) {
            if (!($migration["date"] >= $old_plugin_date)) { continue; }
            try { $migration["callback"](); } catch (\Throwable $error) { \NeoAlt\NeoGlobal\global_warn_with_module_name("_global-migration", "Migration failed for " . $migration["date"] . ": " . $error->getMessage() . " in " . $error->getTraceAsString()); }
        }
    }, scope: "migration-" . \NeoAlt\NeoGlobal\plugin_slug() . "-" . \NeoAlt\NeoGlobal\plugin_edition());
});
