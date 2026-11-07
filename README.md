# Slova Blog — a free blog for Hyvä

A blog for Magento 2 stores on Hyvä that loads like the rest of your Hyvä store: no layout shift, WebP images sized for each screen, and the structured data search engines look for. Posts can point to your products, shown under the post with Hyvä's own product cards. Free, MIT-licensed, no account and no external service.

It is the blog that runs on [caruselbooks.ro](https://caruselbooks.ro), made generic.

## What it does

- **Posts and categories** under *Content → Blog*: WYSIWYG content, featured image, an optional square thumbnail for phones, publish date (a future date schedules the post), active/inactive.
- **An intro section** as an alternative to the hero image: a short text next to an image, for posts that open with a picture that is not wide.
- **Related products.** List SKUs on a post; they appear in a slider under it, with Hyvä's product cards (price, Add to Cart, wishlist).
- **Pages that work on every screen.** Cards in one column on phones, two on tablets, three on desktop. Phones load the next posts as the visitor scrolls (and keep them on Back); larger screens get a pager. Categories with post counts sit in the sidebar on desktop and become a dropdown above the posts on smaller screens.
- **Previous / next** links between posts of the same category.
- **Your own URLs.** `/blog`, `/news`, `/en/journal`… set per store view, with a category prefix (`/blog/category/tips`). Internal routes redirect to them with a 301.
- **SEO built in.** Canonical links, `Article` and `BreadcrumbList` JSON-LD, Open Graph and Twitter Card tags, meta title / description / robots per post, and the blog in Magento's XML sitemap.
- **Fast images.** Post images, including those inside the content, are resized and served as WebP with a JPEG fallback, with width and height set so nothing jumps while they load. `[youtube:VIDEO_ID]` in the content shows a thumbnail that links to the video: no YouTube script until the visitor asks for it.
- **Zero layout shift.** The layout styles are in `<head>`, so the first paint is the final one.
- **Built for Hyvä.** Follows your theme's colors, works with Alpine's CSP build, no Tailwind rebuild.
- **Translated** into English and Romanian.

### What it does not do

No authors, comments or tags; one set of posts for all store views (no per-store-view content or translations of a post); no Page Builder; no GraphQL; Luma storefronts are not supported.

## Requirements

| | |
|---|---|
| Magento Open Source | 2.4.8 (tested on 2.4.8-p5) |
| Hyvä Theme | 1.4 or newer (tested on 1.5.3) |
| PHP | 8.3 – 8.4, with the GD (WebP support) and intl extensions |

`hyva-themes/magento2-theme-module` comes from Hyvä's own Composer repository, which every Hyvä store already has configured. Adobe Commerce has not been tested yet.

## Installation

```bash
composer require slova/module-blog
bin/magento module:enable Slova_Blog
bin/magento setup:upgrade
bin/magento setup:di:compile          # production mode
bin/magento cache:flush
```

Then:

1. Create categories under **Content → Blog → Categories**.
2. Write posts under **Content → Blog → Posts**.
3. Review **Content → Blog → Settings** (the same page as *Stores → Configuration → Slova → Blog*).
4. Link to the blog from your menu or footer: `/blog`, or the prefix you chose.

### Upgrading

```bash
composer update slova/module-blog
bin/magento setup:upgrade
bin/magento cache:flush
```

### Uninstalling

```bash
bin/magento module:uninstall Slova_Blog --remove-data
```

This drops the blog tables and deletes the blog's URL rewrites and settings. Uploaded images stay in `pub/media/slova/blog` and resized copies in `pub/media/slova_blog_resized`; delete those folders yourself if you no longer need them.

## Settings

*Stores → Configuration → Slova → Blog*, per store view.

| Setting | Default | |
|---|---|---|
| Blog Title | "Blog", translated | Title band, browser tab, breadcrumbs. |
| URL Prefix | `blog` | The blog home URL; posts live under it. Several levels allowed (`news/blog`). |
| Category URL Prefix | `category` | Between the prefix and a category's URL key. |
| Meta Title, Meta Description | empty | For the blog home. |
| Posts per Page | 12 | |
| Show Title Band | Yes | A full-width band with the blog or category name. When off, the page shows its own heading. |
| Accent Color | empty (theme primary) | Hex color for the band, links, category labels and card borders. |
| Sidebar CMS Block | none | A CMS block under the categories (desktop). |
| Related Products Heading | "Featured in this post", translated | Above the product slider. |
| Resize and Convert to WebP | Yes | Turn off to serve the uploaded files as they are. |

## Writing posts

- **URL key** is made from the title when left empty, with accents turned into plain letters (`Știri și noutăți` → `stiri-si-noutati`).
- **Short description** is the text on the card and the default meta description.
- **Featured image** shows on the card and, unless *Show Featured Image as Hero* is off, as a 21:9 banner at the top of the post. A landscape image around 16:9 works best on cards.
- **Mobile thumbnail** (optional) replaces the featured image on cards below 768px; a square image works best.
- **Intro section**: plain text with `<strong>` and `<em>` (everything else is escaped); an empty line starts a new paragraph. With an image it shows as two columns.
- **Related products**: SKUs separated by commas or spaces, in the order you want them. Unknown SKUs are reported when you save and ignored. Only enabled products visible in the catalog are shown.
- **Categories**: a post can have several; the first one is its main category (breadcrumbs, highlighted category, previous/next links).
- **Published at**: empty means the creation date; a date in the future keeps the post hidden until then.
- **Videos**: `[youtube:VIDEO_ID]` in the content, or a YouTube / Vimeo embed, which is lazy-loaded.

## URLs

The public URLs are URL rewrites built from the prefixes: the blog home at `/{prefix}`, posts at `/{prefix}/{post-url-key}`, categories at `/{prefix}/{category-prefix}/{category-url-key}`. Saving a post or category writes its URLs for every store view; saving new prefixes in the settings rebuilds all of them. Old URLs then stop working, so set the prefix before you publish.

After adding a store view, or after changing a prefix with `bin/magento config:set`, rebuild the URLs:

```bash
bin/magento slova:blog:url-rewrites
```

The internal routes (`/blog/post/view/id/7`, and `/blog` when the prefix is something else) redirect to the public URLs with a 301.

## Customizing

### Colors and spacing

The styles use custom properties on `.slova-blog-page` (the `<body>` class of every blog page). Override them in your theme, for example in `web/tailwind/tailwind-source.css` or a CSS file of your own:

```css
.slova-blog-page {
    --slova-blog-accent: #0f766e;            /* links, labels, borders; default: --color-primary */
    --slova-blog-fg: #111827;                /* text; default: --color-fg */
    --slova-blog-muted: #6b7280;             /* secondary text; default: --color-fg-secondary */
    --slova-blog-surface: #fff;              /* cards and panels */
    --slova-blog-line: #e5e7eb;              /* separators */
    --slova-blog-radius: .75rem;
    --slova-blog-card-border: #d0e5e5;
    --slova-blog-card-border-hover: #0f766e;
    --slova-blog-band-bg: linear-gradient(90deg, #ecfeff, #0e7490, #ecfeff);
    --slova-blog-band-fg: #fff;
}
```

### Layout

Every blog page uses the layout handle `slova_blog_default`; the list pages add `slova_blog_post_list` and posts `slova_blog_post_view`. To put your own blocks in the sidebar, add them to `sidebar.additional` in your theme's `Slova_Blog/layout/slova_blog_default.xml`:

```xml
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="sidebar.additional">
            <block class="Magento\Framework\View\Element\Template"
                   name="my.blog.sidebar.promo"
                   template="Magento_Theme::my-blog-promo.phtml"
                   after="slova.blog.sidebar.cms"/>
        </referenceContainer>
    </body>
</page>
```

Templates can be overridden in your theme under `Slova_Blog/templates/` as usual. Keep the styles of the first screen in `<head>` (see `head/*.phtml`), or the page will shift.

### Latest posts elsewhere

`Slova\Blog\Block\PostList` can be created from your own code or layout to show the latest posts on another page: give it a `page_size` and a template of your own. Its helpers (`getPosts()`, `getPostUrl()`, `getFeaturedImagePath()`, `processImage()`, `getCategoriesForPost()`, `getFormattedDate()`) do the rest; page title and breadcrumbs are only touched when the `apply_page_meta` argument is true.

## Content Security Policy

The module allows `i.ytimg.com` (video thumbnails) and YouTube / Vimeo frames. Images from other domains inside your posts need to be allowed by your store's CSP, like anywhere else in Magento.

## License

MIT — see [LICENSE](LICENSE). © 2026 Carusel Multimedia SRL, trading as Slova.

Questions and bug reports: [slova.tech](https://slova.tech) · contact@slova.tech
