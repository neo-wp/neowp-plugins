<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// File system helper functions for convenience and as a central position for security policies
function fs_file_put_contents($filename, $data, $flags = 0) { return file_put_contents($filename, $data, $flags); }
function fs_file_get_contents($filename, $use_include_path = false, $context = null, $offset = 0, $length = null) { if ($context !== null) \NeoRename\NeoGlobal\throw_global_exception("fs_file_get_contents() doesn't support the \$context parameter."); return ($length === null) ? file_get_contents($filename, $use_include_path, null, $offset) : file_get_contents($filename, $use_include_path, null, $offset, $length); }
function fs_readfile($filename, $use_include_path = false) { return readfile($filename, $use_include_path); /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile */ }
function fs_file($filename, $flags = 0) { return file($filename, $flags); }
function fs_rename($oldname, $newname) { return rename($oldname, $newname); /* phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename */ }
function fs_copy($source, $dest) { return copy($source, $dest); }
function fs_unlink($filename) { return unlink($filename); /* phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink */ }
function fs_unlink_ignore_warnings($filename) { return @unlink($filename); /* phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink */ }
function fs_touch($filename, $time = null, $atime = null) { return ($time === null && $atime === null) ? touch($filename) : (($atime === null) ? touch($filename, $time) : touch($filename, $time, $atime)); /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch */ }
function fs_mkdir($directory, $permissions = 0777, $recursive = false) { return mkdir($directory, $permissions, $recursive); /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir */ }
function fs_rmdir($directory) { return rmdir($directory); } // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
function fs_chmod($filename, $permissions) { return chmod($filename, $permissions); /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod */ }
function fs_chown($filename, $user)        { return chown($filename, $user);        /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chown */ }
function fs_chgrp($filename, $group)       { return chgrp($filename, $group);       /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chgrp */ }

function fs_file_exists($filename) { return file_exists($filename); }
function fs_is_file($filename)     { return is_file($filename); }
function fs_is_dir($filename)      { return is_dir($filename); }
function fs_is_link($filename)     { return is_link($filename); }
function fs_is_readable($filename) { return is_readable($filename); }
function fs_is_writable($filename) { return is_writable($filename);   /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable */ }
function fs_filesize($filename)    { return filesize($filename); }
function fs_filemtime($filename)   { return filemtime($filename); }
function fs_filectime($filename)   { return filectime($filename); }
function fs_stat($filename)        { return stat($filename); }
function fs_clearstatcache($clear_realpath_cache = false, $filename = null) { return ($filename === null) ? clearstatcache($clear_realpath_cache) : clearstatcache($clear_realpath_cache, $filename); }

function fs_glob($pattern, $flags = 0) { return glob($pattern, $flags); }
function fs_scandir($directory, $sorting_order = SCANDIR_SORT_ASCENDING) { return scandir($directory, $sorting_order); }

function fs_symlink($target, $link) { return symlink($target, $link); }
function fs_readlink($path) { return readlink($path); }

function fs_fnmatch($pattern, $string, $flags = 0) {
    if (function_exists("fnmatch")) { return fnmatch($pattern, $string, $flags); }

    $FNM_PATHNAME=defined('FNM_PATHNAME')?FNM_PATHNAME:(1<<0);$FNM_PERIOD=defined('FNM_PERIOD')?FNM_PERIOD:(1<<2);$FNM_CASEFOLD=defined('FNM_CASEFOLD')?FNM_CASEFOLD:0x50;if(($flags&$FNM_PERIOD)&&($string[0]==".")&&($pattern[0]!="."))return false;$rxci=($flags&$FNM_CASEFOLD)?"i":"";$wild=($flags&$FNM_PATHNAME)?"[^/".DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR."]":".";static $cmp=[];$k=$pattern."+".$flags;if(isset($cmp[$k]))$rx=$cmp[$k];else{$rx=preg_quote($pattern);$rx=strtr($rx,["\\*"=>"$wild*?","\\?"=>"$wild","\\["=>"[","\\]"=>"]"]);$rx=str_replace("[\\!","[^",$rx);$rx="{^".$rx."$}".$rxci;if(count($cmp)>=50)$cmp=[];$cmp[$k]=$rx;}return \NeoRename\NeoGlobal\preg_match_better($rx,$string);
}

function fs_read_json_file($file_path, $missing_fallback = [], $gzip = false) {
    if ( $gzip && !str_ends_with($file_path, ".gz")) { \NeoRename\NeoGlobal\throw_global_exception("fs_read_json_file: GZIP is enabled, but file path does not end with .gz: $file_path"); }
    if (!$gzip &&  str_ends_with($file_path, ".gz")) { \NeoRename\NeoGlobal\throw_global_exception("fs_read_json_file: GZIP is disabled, but file path ends with .gz: $file_path"); }
    if (!fs_file_exists($file_path)) { return $missing_fallback; }
    $file_contents = fs_file_get_contents($file_path);
    if ($gzip) { $file_contents = gzdecode($file_contents); }
    return \NeoRename\NeoGlobal\json_decode_better($file_contents);
}

function fs_write_json_file($file_path, $data, $gzip = false) {
    $json_content = \NeoRename\NeoGlobal\json_encode_better($data, pretty_print: true);
    if ($gzip) { $json_content = gzencode($json_content); if ($json_content === false) { \NeoRename\NeoGlobal\throw_global_exception("fs_write_json_file: Gzip encode failed for file path: $file_path"); } }
    fs_file_put_contents($file_path, $json_content);
}

function path_join_rel($dir_rel, $filename) {
    $dir_rel = untrailingslashit(ltrim($dir_rel, "/")); $filename = ltrim($filename, "/");
    return ($dir_rel === "" ? "" : $dir_rel . "/") . $filename;
}

function is_cache_file_outdated($file_path, $max_age_seconds) {
    if (!fs_file_exists($file_path)) { return false; }
    try { $file_mtime = @(new \SplFileInfo($file_path))->getMTime(); } catch (\RuntimeException $e) { return false; } if (!$file_mtime) { return false; } $now = time();
    if ($file_mtime < $now - $max_age_seconds) { return true; }
    if ($file_mtime > $now + (5) * 60) { return true; }
    return false;
}

function fs_file_put_contents_better($file_path, $new_content) {
    if (fs_file_exists($file_path)) {
        $current_content = fs_file_get_contents($file_path);
        if ($current_content === $new_content) { return; }
    }
    $temp_file_path = \NeoRename\NeoGlobal\cache_path("neo-global--file-helpers") . "/" . str_replace("/", "__", $file_path);
    try {
        fs_file_put_contents($temp_file_path, $new_content);
        fs_rename($temp_file_path, $file_path);
    } finally {
        if (fs_file_exists($temp_file_path)) { fs_unlink($temp_file_path); }
    }
}

function sync_folder($src, $dest) {
    if (!fs_is_dir($src)) { \NeoRename\NeoGlobal\throw_global_exception('Source "' . $src . '" is not a directory or does not exist'); }
    if (str_starts_with($dest, $src . "/") || $dest === $src) { \NeoRename\NeoGlobal\throw_global_exception('Destination "' . $dest . '" is inside source "' . $src . '" or is the same as source'); }
    mkdir_better($dest);
    foreach (iterate_all_files($dest) as $file_path) { if (!fs_file_exists(\NeoRename\NeoGlobal\str_replace_start($dest, $src, $file_path)) && !fs_fnmatch(".lock*", basename($file_path))) { delete_all($file_path); } }

    $temp_file_path = \NeoRename\NeoGlobal\cache_path("neo-global--file-helpers") . "/sync-folder-temp-" . \NeoRename\NeoGlobal\unique_planck_date();
    try {
        copy_all($src, $temp_file_path);

        foreach (iterate_all_files($temp_file_path, with_folders: true) as $file_path) {
            $dest_path = \NeoRename\NeoGlobal\str_replace_start($temp_file_path, $dest, $file_path);
            $parent_dir = dirname($dest_path);
            mkdir_better($parent_dir);
            if (fs_is_dir($file_path)) { mkdir_better($dest_path); continue; }
            fs_rename($file_path, $dest_path);
        }
    } finally {
        delete_all($temp_file_path);
    }
}

function copy_all($src, $dest, $copy_filter_file_callback = null) {
    if (!fs_is_dir($src)) { \NeoRename\NeoGlobal\throw_global_exception('Source "' . $src . '" is not a directory or does not exist'); }
    if (str_starts_with($dest, $src . "/") || $dest === $src) { \NeoRename\NeoGlobal\throw_global_exception('Destination "' . $dest . '" is inside source "' . $src . '" or is the same as source'); }
    mkdir_better($dest);
    foreach (new \FilesystemIterator($src, \FilesystemIterator::SKIP_DOTS) as $file) {
        if ($file->isDir()) { copy_all($file->getPathname(), $dest . "/" . $file->getFilename(), $copy_filter_file_callback); continue; }

        if (!($copy_filter_file_callback == null || $copy_filter_file_callback($file->getPathname()))) { continue; }
        $success = fs_copy($file->getPathname(), $dest . "/" . $file->getFilename());
        if (!$success) { \NeoRename\NeoGlobal\throw_global_exception('Could not copy file "' . $file->getPathname() . '" to "' . $dest . "/" . $file->getFilename() . '"'); }
    }
}

function delete_all($path, $filter = null) {
    if (str_contains($path, "*")) { $success = true; foreach (fs_glob($path) as $file) { $success = $success && delete_all($file, $filter); } return $success; }
    if (!fs_file_exists($path) && !fs_is_link($path)) { return true; }
    if ($filter !== null && !$filter($path)) { return true; }
    if (fs_is_file($path) || fs_is_link($path)) {
        @fs_unlink($path);
        $delete_success = !fs_file_exists($path);
        return $delete_success;
    }
    if (!fs_file_exists($path) && !fs_is_link($path)) { return true; }

    $trash_dir_prefix = WP_CONTENT_DIR . "/cache/neowp_trash_"; /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
    if (!str_starts_with($path, $trash_dir_prefix) && !$filter) {
        $trash_path = $trash_dir_prefix . \NeoRename\NeoGlobal\unique_planck_date() . "_" . basename($path);
        $success = fs_rename($path, $trash_path);
        $trash_dirs = fs_glob($trash_dir_prefix . "*"); shuffle($trash_dirs); foreach ($trash_dirs as $trash_dir) { delete_all($trash_dir); }
        $success = $success && !fs_file_exists($trash_path);
        return $success;
    }

    foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME) as $subpath) {
        if (!delete_all($subpath, $filter)) { return false; }
    }

    if (!(fs_is_dir($path) && !(new \FilesystemIterator($path))->valid())) {
        if ($filter !== null) { return true; }
        return false;
    }

    @fs_rmdir($path);
    $delete_success = !fs_file_exists($path);
    return $delete_success;
}

function remove_empty_directories($directory) {
    if (!fs_is_dir($directory)) { return; }
    foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME) as $subpath) {
        if (fs_is_dir($subpath)) { remove_empty_directories($subpath); }
    }
    if (!(new \FilesystemIterator($directory))->valid()) { fs_rmdir($directory); }
}

function iterate_all_files($path, $with_folders = false): \Iterator {
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $file) {
        if (!$with_folders && !$file->isFile()) { continue; }
        yield $file->getPathname();
    }
}

function mkdir_better($path) {
    $path = untrailingslashit($path); if ($path === "") { $path = "/"; }
    if (!str_starts_with($path, "/")) { \NeoRename\NeoGlobal\throw_global_exception("Path must be absolute: $path"); }
    if (fs_is_dir($path)) { return; }
    if (fs_file_exists($path)) { \NeoRename\NeoGlobal\throw_global_exception("Path exists and is not a directory: $path"); }

    $segments = explode("/", $path); $current = "/"; $path_list = [];
    foreach ($segments as $segment) { if ($segment !== "") { $path_list[] = $current = $current === "/" ? "/$segment" : "$current/$segment"; } }
    $start_index = 0;
    for ($i = count($path_list) - 1; $i >= 0; $i--) {
        if (fs_is_dir($path_list[$i]) || fs_is_link($path_list[$i])) { $start_index = $i + 1; break; }
        if (fs_file_exists($path_list[$i])) { \NeoRename\NeoGlobal\throw_global_exception("Path exists and is not a directory: " . $path_list[$i]); }
    }

    for ($i = $start_index; $i < count($path_list); $i++) {
        $current = $path_list[$i];
        if (fs_is_dir($current) || fs_is_link($current)) { continue; }
        $success = fs_mkdir($current);
        if (!$success) { \NeoRename\NeoGlobal\throw_global_exception("Could not create directory \"$current\". Please check the permissions of the parent folder: " . dirname($current)); }
        $parent = dirname($current);
        $base   = realpath($parent) ?: $parent;
        $perm   = fileperms($base) & 0777;
        $success = fs_chmod($current, $perm);
        if (!$success) { \NeoRename\NeoGlobal\throw_global_exception("Could not set permissions on directory \"$current\""); }
    }
    fs_clearstatcache(true);
}
