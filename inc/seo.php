<?php
/**
 * SEO 优化（完整版 SEO 模块）
 *
 * 本文件是站点的完整 SEO 方案，设计为替代 Rank Math。
 * 部署顺序：先上传本文件 → 验证 → 再停用 Rank Math，不留空窗。
 *
 * 本文件负责：
 *   1. <title>               —— pre_get_document_title 精确控制各页面类型
 *   2. meta description      —— 单条输出（Rank Math 激活时不输出，避免双 description）
 *   3. canonical             —— 单条输出（并移除 WP 核心 rel_canonical）
 *   4. robots meta           —— 章节/搜索/分页/标签/日期/作者页 → noindex, follow
 *   5. Open Graph + Twitter  —— og:locale/type/title/description/url/site_name/image + twitter:card
 *   6. JSON-LD Schema        —— 首页 WebSite+SearchAction / 书籍页 Book+Breadcrumb / 普通文章 Article
 *   7. robots.txt            —— 追加 Sitemap 指向（/sitemap.xml）
 *   8. XML Sitemap           —— 自定义 sitemap（无 wp- 前缀），只含可索引页面
 *
 * 关键数据结构：
 *   - 章节   = 挂载在"类型→书名"二级分类下的普通 post（wpnovc_get_second_level_cat() 非空）
 *   - 书籍页 = 二级分类页；首页 = is_front_page()；编辑推荐 = editor_pick 类型
 *   - 小说 meta（term_meta）：xs_author / xs_cover / xs_description / seo_title / seo_description / seo_keywords
 *   - 章节页已 noindex，不再输出 schema（noindex 页面不展示富媒体）
 *
 * @package wpno-vc
 */

// ── 1. <title> 精确控制 ────────────────────────────────
// 返回当前页面的完整标题字符串；未知类型返回空串（交给 WP 默认）。
function wpnovc_get_document_title_string() {
    $site = get_bloginfo( 'name' );

    if ( is_home() || is_front_page() ) {
        // 关键词版首页 title（与 H1 呼应），不再依赖后台副标题
        $paged = (int) get_query_var( 'paged' );
        return $site . ' - 免费小说在线阅读网' . ( $paged > 1 ? ' - 第' . $paged . '页' : '' );
    }

    if ( is_category() ) {
        $term  = get_queried_object();
        $paged = (int) get_query_var( 'paged' );
        // 分页 title 加页码，避免与第 1 页完全重复（浏览器标签/历史记录可区分）
        if ( $paged > 1 ) {
            return $term->name . ' - 第' . $paged . '页 - ' . $site;
        }
        $meta = wpnovc_get_novel_meta( $term->term_id );
        if ( ! empty( $meta['seo_title'] ) ) {
            return $meta['seo_title'];
        }
        return $term->name . ' - ' . $site;
    }

    if ( is_single() ) {
        $second = wpnovc_get_second_level_cat( get_the_ID() );
        // 章节页：章节名 - 书名 - 站名（书名帮用户在标签页/历史记录中识别）
        if ( $second ) {
            return get_the_title() . ' - ' . $second->name . ' - ' . $site;
        }
        // 普通文章：走后台模板
        $tpl   = wpnovc_option( 'single_title_tpl', '{title} - {site_title}' );
        $novel = '';
        $top   = '';
        return str_replace(
            [ '{title}', '{novel_name}', '{category}', '{site_title}' ],
            [ get_the_title(), $novel, $top, $site ],
            $tpl
        );
    }

    // 编辑推荐虚拟路由（/editor-picks/ 与 /editor-picks/{slug}/，非 post_type_archive）
    $wpnovc_vp = get_query_var( 'wpnovc_page' );
    if ( $wpnovc_vp === 'editor_picks' ) {
        $paged = (int) get_query_var( 'paged' );
        return '编辑推荐' . ( $paged > 1 ? ' - 第' . $paged . '页' : '' ) . ' - ' . $site;
    }
    if ( $wpnovc_vp === 'editor_pick_detail' ) {
        $slug = get_query_var( 'wpnovc_issue_slug' );
        $pick = $slug ? wpnovc_get_editor_pick_by_slug( $slug ) : null;
        return ( $pick ? $pick->post_title : '编辑推荐' ) . ' - ' . $site;
    }

    if ( is_post_type_archive( 'editor_pick' ) ) {
        return '编辑推荐 - ' . $site;
    }

    if ( is_singular( 'editor_pick' ) ) {
        return get_the_title() . ' - ' . $site;
    }

    // 独立页面（/about/ /contact/ 等）
    if ( is_page() ) {
        return get_the_title() . ' - ' . $site;
    }

    // 搜索页（主题虚拟路由 /search/?keyword=）
    if ( is_search() || get_query_var( 'wpnovc_page' ) === 'search' ) {
        $keyword = isset( $_GET['keyword'] ) ? trim( $_GET['keyword'] ) : '';
        return ( $keyword ? '搜索：' . $keyword : '搜索' ) . ' - ' . $site;
    }

    return '';
}

add_filter( 'pre_get_document_title', function ( $title ) {
    $custom = wpnovc_get_document_title_string();
    return $custom !== '' ? $custom : $title;
}, 9999 );

// ── 2. meta description（单条） ─────────────────────────
function wpnovc_get_meta_description() {
    if ( is_home() || is_front_page() ) {
        // 注意：后台若存过空字符串，get_option 不走默认值，必须自己做兜底。
        $desc = wpnovc_option( 'home_description', '' );
        return $desc ?: get_bloginfo( 'description' );
    }

    if ( is_category() ) {
        $term = get_queried_object();
        $meta = wpnovc_get_novel_meta( $term->term_id );
        if ( ! empty( $meta['seo_description'] ) ) {
            return $meta['seo_description'];
        }
        $desc = ! empty( $meta['xs_description'] ) ? $meta['xs_description'] : $term->description;
        if ( $desc ) {
            return wpnovc_strimwidth( $desc, 160, '…' );
        }
        // 一级分类（类型频道，如「玄幻小说」）无简介时生成兜底描述，避免 meta description 缺失
        if ( empty( $term->parent ) ) {
            return $term->name . '免费在线阅读 - 快看小说网收录海量' . $term->name
                . '，支持全文免费阅读，最新章节每日更新，快来发现好看的小说吧。';
        }
        return '';
    }

    // 编辑推荐虚拟路由（/editor-picks/ 与 /editor-picks/{slug}/，非原生单页）
    $wpnovc_vp = get_query_var( 'wpnovc_page' );
    if ( $wpnovc_vp === 'editor_picks' ) {
        return '快看小说网编辑推荐，精选优质热门小说书单与阅读指南，帮你发现值得一读的好书。';
    }
    if ( $wpnovc_vp === 'editor_pick_detail' ) {
        $slug = get_query_var( 'wpnovc_issue_slug' );
        $pick = $slug ? wpnovc_get_editor_pick_by_slug( $slug ) : null;
        if ( $pick ) {
            $ex = get_the_excerpt( $pick );
            if ( $ex ) {
                return wpnovc_strimwidth( $ex, 160, '…' );
            }
            return '快看小说网「' . $pick->post_title . '」编辑推荐书单，精选优质小说，免费在线阅读。';
        }
        return '';
    }

    if ( is_single() ) {
        $post_meta = get_post_meta( get_the_ID(), 'wpnovc_seo', true );
        if ( ! empty( $post_meta['description'] ) ) {
            return $post_meta['description'];
        }
        $second = wpnovc_get_second_level_cat( get_the_ID() );
        if ( $second ) {
            $meta = wpnovc_get_novel_meta( $second->term_id );
            if ( ! empty( $meta['seo_description'] ) ) {
                return $meta['seo_description'];
            }
        }
        return wpnovc_strimwidth( get_the_excerpt(), 160, '…' );
    }

    if ( is_singular( 'editor_pick' ) || is_post_type_archive( 'editor_pick' ) ) {
        return wpnovc_strimwidth( get_the_excerpt(), 160, '…' );
    }

    // 独立页面：后台 SEO 字段 → 摘要 → 正文截断
    if ( is_page() ) {
        $post_meta = get_post_meta( get_the_ID(), 'wpnovc_seo', true );
        if ( ! empty( $post_meta['description'] ) ) {
            return $post_meta['description'];
        }
        $excerpt = get_the_excerpt();
        if ( $excerpt ) {
            // 去掉摘要自动生成的 [...] / [&hellip;] 尾巴，meta description 里不美观
            $excerpt = trim( preg_replace( '/\s*\[[^\]]*\]$/', '', $excerpt ) );
            return wpnovc_strimwidth( $excerpt, 160, '…' );
        }
        $content = wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) );
        return $content ? wpnovc_strimwidth( $content, 160, '…' ) : '';
    }

    return '';
}

add_action( 'wp_head', function () {
    // Rank Math 激活时由其输出 description，避免双输出；停用后本文件接管。
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }
    $description = wpnovc_get_meta_description();
    if ( $description ) {
        echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
    }
    // keywords（仅后台配置了才输出）
    $keywords = '';
    if ( is_home() || is_front_page() ) {
        $keywords = wpnovc_option( 'home_keywords', '' );
    } elseif ( is_category() ) {
        $term     = get_queried_object();
        $meta     = wpnovc_get_novel_meta( $term->term_id );
        $keywords = $meta['seo_keywords'] ?: '';
    } elseif ( is_single() ) {
        $post_meta = get_post_meta( get_the_ID(), 'wpnovc_seo', true );
        $keywords  = $post_meta['keywords'] ?? '';
    }
    if ( $keywords ) {
        echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '" />' . "\n";
    }
}, 1 );

// ── 3. canonical ───────────────────────────────────────
// 移除 WP 核心 canonical，由本文件统一输出单条。
remove_action( 'wp_head', 'rel_canonical' );

function wpnovc_get_canonical_url() {
    if ( is_front_page() || is_home() ) {
        return home_url( '/' );
    }
    if ( is_category() ) {
        return get_category_link( get_queried_object_id() );
    }
    if ( is_singular() ) {
        return get_permalink();
    }
    // 编辑推荐虚拟路由（/editor-picks/ 及详情页）
    $wpnovc_vp = get_query_var( 'wpnovc_page' );
    if ( $wpnovc_vp === 'editor_picks' ) {
        $paged = (int) get_query_var( 'paged' );
        return $paged > 1 ? home_url( '/editor-picks/page/' . $paged . '/' ) : home_url( '/editor-picks/' );
    }
    if ( $wpnovc_vp === 'editor_pick_detail' ) {
        $slug = get_query_var( 'wpnovc_issue_slug' );
        return $slug ? home_url( '/editor-picks/' . $slug . '/' ) : null;
    }
    return null;
}

add_action( 'wp_head', function () {
    // Rank Math 激活时由其输出 canonical（其值正确），避免双条；停用后本文件接管。
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }
    $url = wpnovc_get_canonical_url();
    if ( $url ) {
        echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
    }
}, 10 );

// ── 4. robots meta（统一 noindex 策略） ─────────────────
// 返回 'noindex, follow' 或空串（空=保持 index）。
function wpnovc_get_robots_value() {
    if ( is_404() ) {
        return 'noindex, follow';
    }
    // 章节页（二级分类下的文章）
    // noindex + nofollow：章节不索引，且不引导爬虫沿章节内链继续深爬（10 万章节全爬会打垮低配服务器）
    if ( is_single() && wpnovc_is_chapter_post( get_the_ID() ) ) {
        return 'noindex, nofollow';
    }
    // 搜索页（虚拟路由 + 原生 ?s=）
    if ( is_search() || get_query_var( 'wpnovc_page' ) === 'search' || ( isset( $_GET['keyword'] ) && trim( $_GET['keyword'] ) !== '' ) ) {
        return 'noindex, follow';
    }
    // 分页页（分类/首页的 /page/N/）
    if ( is_paged() ) {
        return 'noindex, follow';
    }
    // 低价值归档页
    if ( is_tag() || is_date() || is_author() ) {
        return 'noindex, follow';
    }
    return '';
}

// 章节判断：挂在"类型→书名"二级分类下的文章即章节。
function wpnovc_is_chapter_post( $post_id ) {
    return wpnovc_get_second_level_cat( $post_id ) !== null;
}

// Rank Math 激活时：改写其 robots 输出（保证单条干净的 meta）
// 注意：Rank Math 的 $robots 是 ['index'=>'index','follow'=>'follow',...] 字符串数组，
// 必须覆写既有键的值，不能新增布尔键（会被打印成 "1"）。
add_filter( 'rank_math/frontend/robots', function ( $robots ) {
    if ( 'noindex, follow' === wpnovc_get_robots_value() ) {
        $robots['index']  = 'noindex';
        $robots['follow'] = 'follow';
    }
    return $robots;
} );

// Rank Math 停用时：直接输出兜底
add_action( 'wp_head', function () {
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }
    $value = wpnovc_get_robots_value();
    if ( $value ) {
        echo '<meta name="robots" content="' . esc_attr( $value ) . '" />' . "\n";
    }
}, 5 );

// 禁用 WordPress 原生搜索（使用主题内置 /search/?keyword= 路由）
add_action( 'parse_query', function ( $query ) {
    if ( $query->is_search && ! is_admin() && ! get_query_var( 'wpnovc_page' ) ) {
        $query->is_search = false;
        $query->set( 's', '' );
    }
} );

// ── 5. Open Graph + Twitter Card ───────────────────────
function wpnovc_get_og_image() {
    if ( is_single() ) {
        $second = wpnovc_get_second_level_cat( get_the_ID() );
        if ( $second ) {
            $meta = wpnovc_get_novel_meta( $second->term_id );
            if ( ! empty( $meta['xs_cover'] ) ) {
                return $meta['xs_cover'];
            }
        }
        $thumb = get_the_post_thumbnail_url( get_the_ID(), 'full' );
        if ( $thumb ) {
            return $thumb;
        }
    }
    if ( is_category() ) {
        $meta = wpnovc_get_novel_meta( get_queried_object_id() );
        if ( ! empty( $meta['xs_cover'] ) ) {
            return $meta['xs_cover'];
        }
    }
    // 兜底：站点图标（后台 设置→常规→站点图标），避免页面/搜索页分享无图
    $icon = get_site_icon_url( 512 );
    if ( $icon ) {
        return $icon;
    }
    // 最后兜底：主题 logo（建议后台设置 512×512 站点图标以获得更佳分享效果）
    return get_template_directory_uri() . '/assets/img/logo-58.png';
}

add_action( 'wp_head', function () {
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }
    $title = wpnovc_get_document_title_string();
    $desc  = wpnovc_get_meta_description();
    $url   = wpnovc_get_canonical_url() ?: home_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/' );
    $image = wpnovc_get_og_image();
    $type = ( is_single() || is_singular( 'editor_pick' ) || get_query_var( 'wpnovc_page' ) === 'editor_pick_detail' ) ? 'article' : 'website';

    echo '<meta property="og:locale" content="zh_CN" />' . "\n";
    echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";
    if ( $title ) {
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
    }
    if ( $desc ) {
        echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
    if ( $image ) {
        echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
    } else {
        echo '<meta name="twitter:card" content="summary" />' . "\n";
    }
    if ( $title ) {
        echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
    }
    if ( $desc ) {
        echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";
    }
}, 15 );

// ── 6. JSON-LD Schema ──────────────────────────────────
function wpnovc_breadcrumb_schema( $items ) {
    $list = [];
    foreach ( $items as $it ) {
        $el = [ '@type' => 'ListItem', 'position' => $it[0], 'name' => $it[1] ];
        if ( ! empty( $it[2] ) ) {
            $el['item'] = $it[2];
        }
        $list[] = $el;
    }
    return [ '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list ];
}

function wpnovc_article_schema( $post_id ) {
    $s = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Article',
        'headline'    => get_the_title( $post_id ),
        'url'         => get_permalink( $post_id ),
        'inLanguage'  => 'zh-CN',
    ];
    $excerpt = get_the_excerpt( $post_id );
    if ( $excerpt ) {
        $s['description'] = wpnovc_strimwidth( $excerpt, 200, '…' );
    }
    $published = get_the_date( 'c', $post_id );
    $modified  = get_the_modified_date( 'c', $post_id );
    if ( $published ) {
        $s['datePublished'] = $published;
        $s['dateModified']  = $modified ?: $published;
    }
    $author = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
    if ( $author ) {
        $s['author'] = [ '@type' => 'Person', 'name' => $author ];
    }
    $img = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( $img ) {
        $s['image'] = $img;
    }
    return $s;
}

add_action( 'wp_head', function () {
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }
    $site_url = home_url( '/' );
    $schemas  = [];

    if ( is_front_page() || is_home() ) {
        // 站点级 WebSite + 站内搜索
        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => get_bloginfo( 'name' ),
            'url'             => $site_url,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => $site_url . 'search/?keyword={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    } elseif ( is_category() ) {
        $term = get_queried_object();
        $meta = wpnovc_get_novel_meta( $term->term_id );

        // 面包屑：首页 → 类型 → 书名
        $items = [ [ 1, '首页', $site_url ] ];
        if ( $term->parent ) {
            $parent = get_category( $term->parent );
            if ( $parent && ! is_wp_error( $parent ) ) {
                $items[] = [ 2, $parent->name, get_category_link( $parent->term_id ) ];
            }
        }
        $items[] = [ count( $items ) + 1, $term->name, get_category_link( $term->term_id ) ];
        $schemas[] = wpnovc_breadcrumb_schema( $items );

        // 二级分类 = 书籍主页 → Book schema
        if ( $term->parent != 0 ) {
            $book = [
                '@context'   => 'https://schema.org',
                '@type'      => 'Book',
                'name'       => $term->name,
                'url'        => get_category_link( $term->term_id ),
                'inLanguage' => 'zh-CN',
                'bookFormat' => 'EBook',
            ];
            if ( ! empty( $meta['xs_author'] ) ) {
                $book['author'] = [ '@type' => 'Person', 'name' => $meta['xs_author'] ];
            }
            if ( ! empty( $meta['xs_cover'] ) ) {
                $book['image'] = $meta['xs_cover'];
            }
            $desc = ! empty( $meta['xs_description'] ) ? $meta['xs_description'] : $term->description;
            if ( $desc ) {
                $book['description'] = wpnovc_strimwidth( $desc, 300, '…' );
            }
            // 类型（顶级分类名），如「玄幻小说」
            if ( $term->parent ) {
                $parent_cat = get_category( $term->parent );
                if ( $parent_cat && ! is_wp_error( $parent_cat ) ) {
                    $book['genre'] = $parent_cat->name;
                }
            }
            // 最新章节时间 → dateModified（时效性信号；主循环第 1 篇即最新章节，零额外查询）
            global $wp_query;
            if ( ! empty( $wp_query->posts ) && isset( $wp_query->posts[0]->post_modified ) && $wp_query->posts[0]->post_modified ) {
                $book['dateModified'] = mysql2date( 'c', $wp_query->posts[0]->post_modified );
            }
            $schemas[] = $book;
        } else {
            // 一级分类（类型频道，如「玄幻小说」）→ CollectionPage + ItemList 列出旗下书籍
            $children = get_terms( [
                'taxonomy'   => 'category',
                'parent'     => $term->term_id,
                'hide_empty' => false,
                'number'     => 20,
            ] );
            if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
                $list = [];
                foreach ( $children as $i => $c ) {
                    $list[] = [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'url'      => get_category_link( $c->term_id ),
                        'name'     => $c->name,
                    ];
                }
                $schemas[] = [
                    '@context'  => 'https://schema.org',
                    '@type'     => 'CollectionPage',
                    'name'      => $term->name . '小说',
                    'url'       => get_category_link( $term->term_id ),
                    'mainEntity' => [
                        '@type' => 'ItemList',
                        'itemListElement' => $list,
                    ],
                ];
            }
        }
    } elseif ( is_single() && ! wpnovc_is_chapter_post( get_the_ID() ) ) {
        // 普通独立文章（非章节）
        $schemas[] = wpnovc_article_schema( get_the_ID() );
    } else {
        // 编辑推荐虚拟路由（/editor-picks/ 与 /editor-picks/{slug}/，非原生 singular）
        $wpnovc_vp = get_query_var( 'wpnovc_page' );
        if ( $wpnovc_vp === 'editor_pick_detail' ) {
            $slug = get_query_var( 'wpnovc_issue_slug' );
            $pick = $slug ? wpnovc_get_editor_pick_by_slug( $slug ) : null;
            if ( $pick ) {
                $s = [
                    '@context'     => 'https://schema.org',
                    '@type'        => 'Article',
                    'headline'     => $pick->post_title,
                    'url'          => function_exists( 'wpnovc_get_editor_pick_url' ) ? wpnovc_get_editor_pick_url( $pick->ID ) : get_permalink( $pick->ID ),
                    'inLanguage'   => 'zh-CN',
                    'datePublished' => get_the_date( 'c', $pick ),
                    'dateModified'  => get_the_modified_date( 'c', $pick ) ?: get_the_date( 'c', $pick ),
                    'publisher'    => [ '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ],
                ];
                $excerpt = get_the_excerpt( $pick );
                if ( $excerpt ) {
                    $s['description'] = wpnovc_strimwidth( $excerpt, 200, '…' );
                }
                $img = wpnovc_get_og_image();
                if ( $img ) {
                    $s['image'] = $img;
                }
                $schemas[] = $s;
                $schemas[] = wpnovc_breadcrumb_schema( [
                    [ 1, '首页', $site_url ],
                    [ 2, '编辑推荐', $site_url . 'editor-picks/' ],
                    [ 3, $pick->post_title ],
                ] );
            }
        } elseif ( $wpnovc_vp === 'editor_picks' ) {
            $schemas[] = wpnovc_breadcrumb_schema( [
                [ 1, '首页', $site_url ],
                [ 2, '编辑推荐', $site_url . 'editor-picks/' ],
            ] );
            // 列出各期推荐（ItemList 富媒体）
            if ( function_exists( 'wpnovc_get_editor_pick_issues' ) ) {
                $issues = wpnovc_get_editor_pick_issues( 1, 20 );
                if ( ! empty( $issues ) ) {
                    $list = [];
                    foreach ( $issues as $i => $iss ) {
                        $item = [
                            '@type'    => 'ListItem',
                            'position' => $i + 1,
                            'url'      => function_exists( 'wpnovc_get_editor_pick_url' ) ? wpnovc_get_editor_pick_url( $iss->ID ) : get_permalink( $iss->ID ),
                            'name'     => $iss->post_title,
                        ];
                        // D 优化：ListItem 补一句话描述（后台「摘录」或自动生成整期书单摘要）
                        $iss_ex = $iss->post_excerpt;
                        if ( ! $iss_ex && function_exists( 'wpnovc_get_editor_pick_novels' ) ) {
                            $names = array();
                            foreach ( wpnovc_get_editor_pick_novels( $iss->ID ) as $bid ) {
                                $t = get_term( $bid, 'category' );
                                if ( $t && ! is_wp_error( $t ) ) {
                                    $names[] = $t->name;
                                }
                            }
                            if ( $names ) {
                                $shown = implode( '、', array_slice( $names, 0, 6 ) );
                                $iss_ex = '本期编辑推荐 ' . count( $names ) . ' 部小说：' . $shown . ( count( $names ) > 6 ? ' 等' : '' ) . '，全站免费在线阅读。';
                            }
                        }
                        if ( $iss_ex ) {
                            $item['description'] = wpnovc_strimwidth( wp_strip_all_tags( $iss_ex ), 100, '…' );
                        }
                        $list[] = $item;
                    }
                    $schemas[] = [
                        '@context'       => 'https://schema.org',
                        '@type'          => 'ItemList',
                        'itemListElement' => $list,
                    ];
                }
            }
        } elseif ( is_singular( 'editor_pick' ) ) {
            // 理论不可达兜底（原生 singular）
            $schemas[] = wpnovc_article_schema( get_the_ID() );
        }
    }

    foreach ( $schemas as $schema ) {
        echo '<script type="application/ld+json">' . "\n"
            . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n"
            . '</script>' . "\n";
    }
}, 20 );

// ── 7. robots.txt 输出 Sitemap 指向 ────────────────────
add_action( 'do_robots', function () {
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }
    echo "\nSitemap: " . home_url( '/sitemap.xml' ) . "\n";
}, 100 );

// ── 7.5 旧 sitemap 地址 301 到自定义 sitemap ───────────
// /wp-sitemap.xml 是 WP 核心地址、/sitemap_index.xml 是 Rank Math 遗留地址，
// 两者都 301 到自定义 /sitemap.xml（见第 8 节），保持地址连续性。
add_action( 'template_redirect', function () {
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }
    $path = '';
    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        $path = (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
    }
    $path = strtolower( $path );
    if ( in_array( $path, [ '/wp-sitemap.xml', '/sitemap_index.xml' ], true ) ) {
        wp_redirect( home_url( '/sitemap.xml' ), 301 );
        exit;
    }
}, 0 );

// ── 8. 自定义 XML Sitemap（/sitemap.xml，无 wp- 前缀） ──
// 核心 sitemap 地址带 wp- 前缀且在本站路由环境下表现异常，
// 这里直接关闭核心 sitemap，改由本文件输出：
//   /sitemap.xml          索引
//   /sitemap-cats-N.xml   分类页（小说类型 + 书籍主页——最重要的页面）
//   /sitemap-pages-N.xml  独立页面（/about/ 等）
//   /sitemap-posts-N.xml  普通文章（不含章节）
//   /sitemap-picks-N.xml  编辑推荐
// 章节（noindex）绝不进入 sitemap。URL 列表缓存 6 小时。
add_filter( 'wp_sitemaps_enabled', '__return_false' );

function wpnovc_sitemap_entry( $loc, $lastmod = '' ) {
    $xml = '<url><loc>' . esc_url( $loc ) . '</loc>';
    if ( $lastmod ) {
        $xml .= '<lastmod>' . esc_html( $lastmod ) . '</lastmod>';
    }
    return $xml . '</url>';
}

// 收集所有"二级分类"（类型→书名）的 term_id，章节文章全部挂在二级分类下。
function wpnovc_get_all_chapter_category_ids() {
    $top = get_terms( [
        'taxonomy'   => 'category',
        'parent'     => 0,
        'hide_empty' => false,
        'fields'     => 'ids',
    ] );
    if ( is_wp_error( $top ) || empty( $top ) ) {
        return [];
    }
    $ids = [];
    foreach ( (array) $top as $tid ) {
        $children = get_terms( [
            'taxonomy'   => 'category',
            'parent'     => (int) $tid,
            'hide_empty' => false,
            'fields'     => 'ids',
        ] );
        if ( ! is_wp_error( $children ) ) {
            $ids = array_merge( $ids, (array) $children );
        }
    }
    return array_values( array_unique( array_map( 'intval', $ids ) ) );
}

// 获取某类型 URL 总数（不加载 URL 列表，避免 OOM）
function wpnovc_sitemap_count( $type ) {
    global $wpdb;
    if ( 'pages' === $type ) {
        $counts = wp_count_posts( 'page' );
        $pages  = isset( $counts->publish ) ? (int) $counts->publish : 0;
        return $pages + 1; // 首页
    }
    if ( 'posts' === $type ) {
        $exclude = wpnovc_get_all_chapter_category_ids();
        if ( ! empty( $exclude ) ) {
            $ids = implode( ',', array_map( 'intval', $exclude ) );
            $sql = "SELECT COUNT(DISTINCT p.ID)
                    FROM {$wpdb->posts} p
                    WHERE p.post_type = 'post'
                      AND p.post_status = 'publish'
                      AND p.ID NOT IN (
                          SELECT tr.object_id
                          FROM {$wpdb->term_relationships} tr
                          WHERE tr.term_taxonomy_id IN (
                              SELECT tt.term_taxonomy_id
                              FROM {$wpdb->term_taxonomy} tt
                              WHERE tt.term_id IN ({$ids})
                          )
                      )";
        } else {
            $sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish'";
        }
        return (int) $wpdb->get_var( $sql );
    }
    if ( 'picks' === $type ) {
        $counts = wp_count_posts( 'editor_pick' );
        return isset( $counts->publish ) ? (int) $counts->publish : 0;
    }
    if ( 'cats' === $type ) {
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy='category' AND count > 0"
        );
    }
    return 0;
}

// 获取某类型某一页的 URL，仅查询当前页需要的数据
function wpnovc_sitemap_urls_slice( $type, $offset, $per ) {
    $urls = [];
    $common = [
        'post_status'            => 'publish',
        'numberposts'            => $per,
        'offset'                 => max( 0, intval( $offset ) ),
        'orderby'                => 'modified',
        'order'                  => 'DESC',
        'update_post_term_cache' => false,
    ];

    if ( 'pages' === $type ) {
        if ( $offset <= 0 ) {
            $urls[] = [ home_url( '/' ), '' ];
        }
        $args = array_merge( $common, [
            'post_type' => 'page',
            'offset'    => max( 0, $offset - 1 ),
        ] );
        foreach ( get_posts( $args ) as $p ) {
            $urls[] = [ get_permalink( $p ), get_post_modified_time( 'c', true, $p ) ];
        }
    } elseif ( 'posts' === $type ) {
        $args = array_merge( $common, [
            'post_type'           => 'post',
            'ignore_sticky_posts' => true,
        ] );
        $exclude = wpnovc_get_all_chapter_category_ids();
        if ( $exclude ) {
            $args['category__not_in'] = $exclude;
        }
        foreach ( get_posts( $args ) as $p ) {
            $permalink = get_permalink( $p );
            if ( $permalink && preg_match( '#/' . (int) $p->ID . '/?$#', $permalink ) ) {
                continue;
            }
            $urls[] = [ $permalink, get_post_modified_time( 'c', true, $p ) ];
        }
    } elseif ( 'picks' === $type ) {
        $args = array_merge( $common, [
            'post_type' => 'editor_pick',
        ] );
        foreach ( get_posts( $args ) as $p ) {
            if ( function_exists( 'wpnovc_get_editor_pick_url' ) ) {
                $urls[] = [ wpnovc_get_editor_pick_url( $p->ID ), get_post_modified_time( 'c', true, $p ) ];
            }
        }
    } elseif ( 'cats' === $type ) {
        $terms = get_terms( [
            'taxonomy'   => 'category',
            'hide_empty' => true,
            'number'     => $per,
            'offset'     => max( 0, intval( $offset ) ),
        ] );
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $t ) {
                $urls[] = [ get_category_link( $t->term_id ), '' ];
            }
        }
    }
    return $urls;
}

add_action( 'template_redirect', function () {
    $path = '';
    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        $path = (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
    }
    $path = strtolower( $path );

    // 索引：/sitemap.xml
    if ( '/sitemap.xml' === $path ) {
        status_header( 200 );
        header( 'Content-Type: application/xml; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $per = 1000;
        foreach ( [ 'cats', 'pages', 'posts', 'picks' ] as $type ) {
            $total = wpnovc_sitemap_count( $type );
            $pages = (int) ceil( $total / $per );
            for ( $i = 1; $i <= $pages; $i++ ) {
                echo '<sitemap><loc>' . esc_url( home_url( "/sitemap-{$type}-{$i}.xml" ) ) . '</loc></sitemap>' . "\n";
            }
        }
        echo '</sitemapindex>';
        exit;
    }

    // 子图：/sitemap-{type}-{N}.xml
    if ( preg_match( '#^/sitemap\-(cats|pages|posts|picks)\-(\d+)\.xml$#', $path, $m ) ) {
        $type  = $m[1];
        $page  = max( 1, (int) $m[2] );
        $per   = 1000;
        $offset = ( $page - 1 ) * $per;
        $slice  = wpnovc_sitemap_urls_slice( $type, $offset, $per );
        if ( empty( $slice ) ) {
            status_header( 404 );
            header( 'Content-Type: text/plain; charset=utf-8' );
            echo 'Sitemap not found';
            exit;
        }
        header( 'Content-Type: application/xml; charset=utf-8' );
        status_header( 200 );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( $slice as $u ) {
            echo wpnovc_sitemap_entry( $u[0], $u[1] ?? '' ) . "\n";
        }
        echo '</urlset>';
        exit;
    }
}, 0 );
