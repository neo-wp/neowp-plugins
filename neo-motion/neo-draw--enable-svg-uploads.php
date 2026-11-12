<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_enable_svg_uploads_20250302() {
    \NeoMotion\NeoGlobal\add_filter_hook("upload_mimes", function ($mimes) {
        $mimes["svg"] = "image/svg+xml";
        return $mimes;
    });
}
