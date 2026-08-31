<?php
/**
 * 主题根模板 - WordPress 模板层级的最终回退
 * 
 * 通过 template_include 过滤器，实际模板从 pc/ 或 mobile/ 子目录加载。
 * 如果过滤器未触发，这里直接加载对应目录的 index.php。
 *
 * @package wpno-vc
 */
$dir = wpnovc_is_mobile() ? 'mobile' : 'pc';
include get_template_directory() . "/{$dir}/index.php";
