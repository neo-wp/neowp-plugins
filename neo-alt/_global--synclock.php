<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Synclock for e.g. the neoWP plugin cache folder
function synclock_dir($dir, $callback, $timeout = 2, $scope = null) {
    if (!\NeoAlt\NeoGlobal\fs_file_exists($dir)) { \NeoAlt\NeoGlobal\throw_global_exception("Directory does not exist in synclock_dir. Path: " . $dir . ". Scope: $scope" . "."); }
    if ($timeout < 2) { \NeoAlt\NeoGlobal\throw_global_exception("Timeout too low in synclock_dir. Minimum is 2 seconds because filemtime in FAT32 is only seconds precise and rounding errors require 2 seconds to get 1-2 seconds in real timeout."); }

    static $synclocked_dirs = [];
    $synclock_dir_and_scope = untrailingslashit($dir) . "@" . ($scope ?? "");
    if (isset($synclocked_dirs[$synclock_dir_and_scope])) { return $callback(); } $synclocked_dirs[$synclock_dir_and_scope] = true;
    try {
        static $ignore_all_warnings_and_errors = null; $ignore_all_warnings_and_errors ??= function ($callback) { if (function_exists('\NeoAlt\NeoGlobal\_synclock_ignore_all_warnings_and_errors')) { \NeoAlt\NeoGlobal\_synclock_ignore_all_warnings_and_errors($callback); } else { $callback(); } };

        try {
            $dir = untrailingslashit($dir);
            if ($scope && strlen($scope) > 16) { $scope = substr($scope, 0, 8) . "..." . substr($scope, -8) . "-" . substr(md5($scope), 0, 8); }
            $scope_filename_part = $scope === null ? "" : "-$scope";
            $lock_file = $dir . "/.lock$scope_filename_part-" . \NeoAlt\NeoGlobal\unique_planck_date();
            $lock_files_for_scope = function () use ($dir, $scope, $scope_filename_part) { $lock_files = \NeoAlt\NeoGlobal\fs_glob($scope === null ? $dir . "/.lock-*" : $dir . "/.lock$scope_filename_part-*") ?: []; return $scope === null ? \NeoAlt\NeoGlobal\array_filter_better($lock_files, fn ($filename) => substr_count(basename($filename), "-") === 1) : $lock_files; };
            $touch_success = \NeoAlt\NeoGlobal\fs_touch($lock_file);
            if (!$touch_success) { \NeoAlt\NeoGlobal\throw_global_exception("Could not create lock file in synclock_dir. Path: " . $lock_file . ". Scope: $scope" . "."); }

            while (basename($lock_file) !== basename((function ($files) { sort($files); return $files[0] ?? ""; })($lock_files_for_scope()))) {
                $touch_success = \NeoAlt\NeoGlobal\fs_touch($lock_file);
                if (!$touch_success) { \NeoAlt\NeoGlobal\throw_global_exception("Could not touch lock file in synclock_dir. Path: " . $lock_file . ". Scope: $scope" . "."); }

                $ignore_all_warnings_and_errors(function() use ($timeout, $lock_files_for_scope) {
                    foreach ($lock_files_for_scope() as $filename) { if (\NeoAlt\NeoGlobal\is_cache_file_outdated($filename, $timeout)) { \NeoAlt\NeoGlobal\fs_unlink($filename); } }
                });
                usleep((1) * 1000);
            }

            return $callback();
        } finally {
            if (!\NeoAlt\NeoGlobal\fs_file_exists($lock_file)) { \NeoAlt\NeoGlobal\global_warn_with_module_name("_global--synclock", "This PHP process took too long in synclock_dir. It was cleaned up by another PHP process. Path: $dir Scope: $scope"); }
            else { $ignore_all_warnings_and_errors(fn () => \NeoAlt\NeoGlobal\fs_unlink($lock_file)); }
        }
    } finally {
        unset($synclocked_dirs[$synclock_dir_and_scope]);
    }
}

function wait_until_no_synclock_locks_in_subdirs($dir, $timeout = 5) {
    $dir = untrailingslashit($dir);
    if (!\NeoAlt\NeoGlobal\fs_is_dir($dir)) { return true; }
    $end_time = microtime(true) + $timeout;
    while (true) {
        $lock_files = \NeoAlt\NeoGlobal\fs_glob($dir . "/*/.lock*") ?: [];
        if (empty($lock_files)) { return true; }
        if (microtime(true) >= $end_time) { \NeoAlt\NeoGlobal\global_warn_with_module_name("_global--synclock", "Synclock lock files still exist in cache subdirectories before clear_plugin_cache. Path: " . $dir . ". Lock files: " . implode(", ", array_map("basename", $lock_files))); return false; }
        usleep((50) * 1000);
    }
}
