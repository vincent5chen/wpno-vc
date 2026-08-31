# wpno-vc 一个功能比较完善的小说阅读网站的WordPress主题

`wpno-vc` 是一个面向小说阅读站的 WordPress 主题，提供 PC / 移动端双端模板、付费阅读、小说批量导入、AI 内容与封面生成、SEO 结构化数据、订单与余额系统等能力。

当前版本：`1.2`  
License：`GPLv2 or later`

## 功能概览

- 响应式双端：根据 User-Agent 自动切换 `pc/` 和 `mobile/` 模板目录
- 小说层级：一级分类为小说类型，二级分类为书籍，三级分类为分卷，章节以普通文章挂载在二级分类下
- 付费阅读：余额、订单、商品、折扣、会员、推广佣金、提现申请等基础能力
- 多支付方式：微信支付、支付宝、PayPal、虎皮椒，支持标准支付和仅余额支付
- AI 生成：DeepSeek 生成简介、编辑推荐语和 SEO 信息；Qwen-Image 生成小说封面和 Banner；无 AI Key 时可用 GD 兜底生成封面
- 小说导入：单本导入和批量导入，支持章节解析、自动建分类、自动生成封面。导入功能需要开始php的上传文件限制。
- 阅读体验：书架、阅读历史、阅读进度、字号/背景/主题切换、章节排序，支持基于浏览器的webkitSpeechRecognition的文章朗读，支持朗读中自动翻页
- SEO：精确 `title`、`description`、`canonical`、Open Graph、Twitter Card、JSON-LD、`robots.txt` 和自定义 Sitemap
- 编辑运营：编辑推荐期次、首页轮播、广告位、首页分类和推荐小说配置
- 安全加固：安全响应头、隐藏 WordPress 版本、关闭 XML-RPC、阻止作者枚举和 REST 用户枚举

## 环境要求

- WordPress `6.0` 或更高版本，建议 `6.7+`
- PHP `8.0` 或更高版本
- MySQL / MariaDB
- 推荐开启 HTTPS
- 如需封面生成，建议启用 PHP GD 扩展
- 如需 AI 功能，需要可访问对应 API 的网络

## 安装

1. 将主题目录放到 `wp-content/themes/` 下，目录名建议为 `wpno-vc`：

   ```text
   wp-content/themes/wpno-vc/
   ```

2. 在 WordPress 后台 `外观 -> 主题` 中启用 `wpno-vc`。

3. 进入 `设置 -> 固定链接`，选择“文章名”或其他非朴素链接格式，然后保存。

4. 在后台 `WPNOVC -> 数据库管理` 中确认数据表已创建。主题激活时也会尝试自动创建以下表：

   ```text
   wp_wpnovc_goods
   wp_wpnovc_orders
   wp_wpnovc_balance_log
   ```

   表前缀 `wp_` 会根据站点实际配置变化。

5. 在 `外观 -> 菜单` 中分别创建并指定：

   ```text
   PC 端导航菜单
   移动端导航菜单
   ```

6. 在 `WPNOVC -> 外观设置` 中配置 Logo、首页分类、推荐小说、广告位和阅读选项。

## 快速使用

### 建立小说分类结构

分类建议使用三级结构：

```text
小说类型（一级分类）
  └── 书名（二级分类）
        └── 分卷（三级分类）
              └── 章节文章
```

书籍的封面、作者、简介、编辑推荐语、SEO 信息和付费设置会写入二级分类的 term meta。

### 导入小说

后台进入：

```text
WPNOVC -> 小说导入
WPNOVC -> 批量导入
```

导入时可以自动创建分类和章节，并可在导入时生成封面。若启用了 DeepSeek，还可以自动生成简介、编辑推荐语和 SEO 标题/描述/关键词。

### 配置 AI

在 `WPNOVC -> AI设置` 中填写 DeepSeek API Key：

```text
DeepSeek API Key
启用 AI 生成
```

在 `WPNOVC -> AI 图片设置` 中配置 Qwen-Image API Key。当前内置模型为：

```text
qwen-image-3.0
```

AI 图片能力通过 `inc/ai-image-adapter.php` 做统一适配，后续可在 `wpnovc_ai_image_models()` 中扩展其他模型。

### 配置支付

后台进入：

```text
商城系统 -> 支付设置
WPNOVC -> 付费设置
```

支持：

- 余额支付
- 微信支付
- 支付宝
- PayPal
- 虎皮椒

商品金额和余额单位统一为“分”，前端展示时除以 100 转为元。

## 目录结构

```text
wpno-vc/
├── assets/                 # CSS、JS、图片、字体
├── inc/                    # 核心业务模块
│   ├── admin-settings.php  # 后台设置入口
│   ├── admin-shop.php      # 商城后台
│   ├── payment-init.php    # 支付表、余额、订单
│   ├── ajax-handlers.php   # 前端 AJAX
│   ├── novel-meta.php      # 小说分类元数据
│   ├── rewrite-rules.php   # 虚拟路由
│   ├── seo.php             # SEO、Open Graph、Schema、Sitemap
│   ├── ai-generator.php    # DeepSeek 文本生成
│   ├── ai-image-adapter.php# AI 图片适配层
│   ├── cover-generator.php # 封面生成
│   ├── editor-picks.php    # 编辑推荐
│   └── home-banner.php     # 首页轮播
├── mobile/                 # 移动端模板
├── pc/                     # PC 端模板
├── style.css               # WordPress 主题识别文件
├── functions.php           # 主题入口和核心钩子
├── screenshot.jpg          # 后台主题预览图
└── lang/                   # 翻译文件目录
```

## 主要后台页面

主题安装后会创建以下后台菜单：

```text
WPNOVC
├── 外观设置
├── 付费设置
├── 订单列表
├── 小说导入
├── 批量导入
├── 数据库管理
├── 教程
├── 编辑导语
├── 封面生成
├── 首页轮播
├── AI 图片设置
├── 编辑推荐
└── AI设置

商城系统
└── 商品、订单、支付、活动、充值、会员、佣金等

会员中心
```

## SEO

主题内置完整 SEO 模块，主要文件为 `inc/seo.php`：

- 首页、分类页、书籍页、章节页和编辑推荐页分别生成标题
- 章节页、搜索页、分页、标签、日期和作者页按策略输出 `noindex, follow`
- 书籍页输出 `Book + Breadcrumb` Schema
- 首页输出 `WebSite + SearchAction` Schema
- 生成 `/sitemap.xml`，并在 `robots.txt` 中声明

如果站点同时启用 Rank Math 等 SEO 插件，建议只保留一套 SEO 输出，避免标题、描述或 canonical 重复。

## 安全说明

主题在 `functions.php` 中做了以下处理：

- 输出 HSTS、X-Frame-Options、X-Content-Type-Options、X-XSS-Protection、Referrer-Policy、Permissions-Policy 和 CSP
- 隐藏 WordPress 版本号
- 禁用 XML-RPC
- 阻止未登录用户通过 REST API 或 `author` 参数枚举用户名

注意：CSP 和 Google AdSense 脚本目前带有部分硬编码域名。正式上线前建议根据你的广告、统计和 CDN 域名调整 `Content-Security-Policy` 与 AdSense 客户端 ID。

## 上线前检查

- 更新 `style.css` 中的 `Theme URI` 和作者信息
- 替换 `functions.php` 中的 Google AdSense 客户端 ID
- 检查并调整 CDN：jQuery、Font Awesome 当前使用 `cdn.bootcdn.net`
- `php.ini` 是开发环境文件，生产环境请使用主机提供的 PHP 配置，不要直接依赖仓库内配置
- 在后台保存一次固定链接，确保 `/bookshelf/`、`/reading-history/`、`/search/`、`/editor-picks/` 等虚拟路由生效
- 确认支付回调地址、商户密钥和订单状态流转符合你的部署环境

## 演示网站

<a href="https://www.kknovels.com" target="_blank">快看小说网kknovels.com</a>

## License

本项目遵循 `GPLv2 or later` 许可证。
