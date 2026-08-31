<?php
/**
 * 批量为已有小说生成编辑推荐（CLI 执行）
 * 用法: php wp-content/themes/wpno-vc/inc/batch-editor-note.php
 */
require_once __DIR__ . '/../../../../wp-load.php';

echo "=== 批量生成编辑推荐 ===\n\n";

$api_key = wpnovc_option('deepseek_api_key', '');
if (empty($api_key)) {
    die("错误: 未配置 DeepSeek API Key\n");
}

// 获取所有二级分类（小说）
$top_cats = get_terms(['taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false]);
$novels = [];
foreach ($top_cats as $top) {
    $children = get_terms(['taxonomy' => 'category', 'parent' => $top->term_id, 'hide_empty' => false]);
    foreach ($children as $child) {
        $novels[] = $child;
    }
}

echo "找到 " . count($novels) . " 本小说\n\n";

$updated = 0;
foreach ($novels as $novel) {
    $meta = wpnovc_get_novel_meta($novel->term_id);
    $author = $meta['xs_author'] ?? '佚名';
    echo "  GENERATE: {$novel->name} (作者: {$author}) ... ";
    
    $editor_note = wpnovc_ai_generate_editor_note($novel->name, $author);
    if ($editor_note) {
        $meta['editor_note'] = $editor_note;
        wpnovc_update_novel_meta($novel->term_id, $meta);
        echo "OK\n";
        $updated++;
    } else {
        echo "FAIL\n";
    }
    
    // 避免 API 限流
    sleep(1);
}

echo "\n完成！更新了 {$updated} 本书。\n";

/**
 * 单独调用 AI 生成编辑推荐
 */
function wpnovc_ai_generate_editor_note($novel_name, $author) {
    $api_key = wpnovc_option('deepseek_api_key', '');
    if (empty($api_key)) return false;

    $prompt = <<<PROMPT
你是一个资深小说编辑。请为以下小说写一篇1000字以上的编辑推荐，必须不少于1000字。
内容包括：故事背景介绍、主要人物分析、情节亮点点评、写作风格评价、适合读者群体推荐。
输出格式：纯文本推荐语，不要JSON，不要其他任何文字，确保字数达到1000字以上。

小说名：《{$novel_name}》
作者：{$author}
PROMPT;

    $response = wp_remote_post('https://api.deepseek.com/chat/completions', [
        'timeout' => 60,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.8,
            'max_tokens' => 5000,
        ]),
    ]);

    if (is_wp_error($response)) return false;

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = trim($body['choices'][0]['message']['content'] ?? '');
    
    return $content ? sanitize_textarea_field($content) : false;
}
