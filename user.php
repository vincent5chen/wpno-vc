<?php
$dir = wpnovc_is_mobile() ? 'mobile' : 'pc';
include get_template_directory() . "/{$dir}/user.php";
