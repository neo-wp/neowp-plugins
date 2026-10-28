<?php
namespace NeoLibrary\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoLibrary\NeoGlobal\val("neo_global__migrations", []);
\NeoLibrary\NeoGlobal\val("neo_global__migration_registration_closed", false);
function register_migration($introduction_date, $callback) {
    if (\NeoLibrary\NeoGlobal\val("neo_global__migration_registration_closed")) { \NeoLibrary\NeoGlobal\throw_global_exception("Migration registered too late. Please call register_migration() while loading the PHP file, before the neo_init:0 migration runner starts."); }
    if (!is_string($introduction_date)) { \NeoLibrary\NeoGlobal\throw_global_exception("Migration introduction date must be a string"); }
    if (!\NeoLibrary\NeoGlobal\preg_match_better("/^\d{4}-\d{2}-\d{2}$/", $introduction_date)) { \NeoLibrary\NeoGlobal\throw_global_exception("Migration introduction date must use YYYY-MM-DD format"); }
    if (!$callback instanceof \Closure) { \NeoLibrary\NeoGlobal\throw_global_exception("Migration callback must be a Closure"); }
    \NeoLibrary\NeoGlobal\val("neo_global__migrations")[] = ["date" => $introduction_date, "callback" => $callback];
}

\NeoLibrary\NeoGlobal\add_action_hook("neo_init:0", function () {
    \NeoLibrary\NeoGlobal\val("neo_global__migration_registration_closed", true);
    \NeoLibrary\NeoGlobal\synclock_dir(\NeoLibrary\NeoGlobal\cache_path(), function () {
        $stored_plugin_timestamp  = \NeoLibrary\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_library();
        $current_plugin_timestamp = \NeoLibrary\NeoGlobal\plugin_timestamp();
        \NeoLibrary\NeoGlobal\option__neo_global__last_migration_plugin_timestamp_neo_library($current_plugin_timestamp);
        if (!($stored_plugin_timestamp > 0 && $current_plugin_timestamp > $stored_plugin_timestamp)) { return; }
        $old_plugin_date = \NeoLibrary\NeoGlobal\utc_date_string("Y-m-d", intval($stored_plugin_timestamp));
        $migrations = \NeoLibrary\NeoGlobal\val("neo_global__migrations");
        usort($migrations, function ($migration_a, $migration_b) { return strcmp($migration_a["date"], $migration_b["date"]); });
        foreach ($migrations as $migration) {
            if (!($migration["date"] >= $old_plugin_date)) { continue; }
            try { $migration["callback"](); } catch (\Throwable $error) { \NeoLibrary\NeoGlobal\global_warn_with_module_name("_global-migration", "Migration failed for " . $migration["date"] . ": " . $error->getMessage() . " in " . $error->getTraceAsString()); }
        }
    }, scope: "migration-" . \NeoLibrary\NeoGlobal\plugin_slug() . "-" . \NeoLibrary\NeoGlobal\plugin_edition());
});
