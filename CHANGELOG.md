# Changelog

## 1.1.2 — 2026-11-14

- **A replaced image now shows up.** An image replaced by a file with the same name (directly on the server, by a deployment or an import) kept showing the old picture: resized copies were made once and never compared with their original again, and clearing Magento's caches did not touch them. A copy older than its original is now made again on the next visit.
- **New URL when the picture changes.** The URL of a resized copy now ends in `?v=` followed by the original's modification time, so browsers and CDNs that cached the old picture fetch the new one. After updating, every resized image gets this suffix once: flush the full page cache (and Varnish) so cached pages use the new URLs.
- Resized copies are written to a temporary file and renamed into place, so a visitor never receives a half-written image while a copy is being made again.
- README: *Images and speed* and *I replaced an image but the old one still shows* describe the new behavior and the remaining cases (a cached page, a file copied with its old date kept); one *Support and license* section.

## 1.1.1 — 2026-11-08

- Related products under a post now load every attribute marked *Used in Product Listing*, as category pages do. Product-card badges and labels that read such attributes (a gift or pre-order flag, for example) could be missing on the post page, and a special price scheduled for later could show early.

## 1.1.0 — 2026-11-08

- Card images now **fill** their frame (16:9, square on phones), cropped from the top so heads stay in, instead of being shown whole with empty space around them. Wide and narrow pictures now look the same size, and every card has the same margin above and beside its picture. Applies to the blog lists and the Latest Posts widget.
- New setting **Card Images** (*Stores → Configuration → Slova → Blog → Display*): *Fill the frame* (default) or *Show the whole image*, the 1.0 behavior, for book covers or product photos that must not be cropped.

## 1.0.1 — 2026-11-08

- The note under **Show Featured Image as Hero** in the post editor now says that the banner is left out, even when switched on, if the same picture is the intro image or appears in the content. Until now the rule was only in the documentation, so a missing banner could look like a bug.
- README: a Troubleshooting section; installation and Quick start come first; the URL prefix warning is also in the Settings table; links to Magento, Hyvä and related Slova modules.

## 1.0.0

First public release, free under the MIT License.

- Posts and categories managed under **Content → Blog**: title, URL key, short description, WYSIWYG content, featured image, optional square thumbnail for phones, optional intro section (text and image side by side) instead of the hero, related products by SKU, publish date (a future date schedules the post), meta title, description, robots and Open Graph image.
- Editor buttons as in WordPress: **Preview** (unsaved changes in a new tab through a private one-hour link; a draft is saved first, a live post never), **Save Draft** (saves inactive, stays in the editor) and **View Post** (opens the live page).
- Widget **Slova Blog: Latest Posts** for the home page or any CMS page or block: the newest posts (all or from one category) as cards, with heading, number of posts, short description and "View all posts" link options.
- The image uploaders show the size of images saved earlier ("1080x460, 56 KB" instead of "NaN undefined").
- Uploading an image whose name is already used in `pub/media/slova/blog` keeps both files (the new one is renamed) instead of overwriting the other post's image. Related products keep their order when the post is saved again.
- Blog home, category pages and posts on Hyvä: card grid (one column on phones, two on tablets, three on desktop), pager on larger screens and infinite scroll on phones, categories with post counts (a list in the sidebar, a dropdown on smaller screens), previous/next links within the category, related products slider with Hyvä's product cards.
- URLs from your own prefix, per store view (`/blog`, `/news/blog`…), with a category prefix. Saving the settings rebuilds the URL rewrites; `bin/magento slova:blog:url-rewrites` does the same from the command line. Internal routes redirect (301) to the public URLs.
- SEO: canonical links, Article and BreadcrumbList structured data, Open Graph and Twitter Card tags, posts, categories and the blog home in the XML sitemap.
- Each image is shown once per post page: the hero is left out when its picture is also the intro image or is in the content, the intro image when it is in the content (same file, or the same photo under another name). Content images are always kept.
- Post content goes through the CMS filter, so images inserted from the editor's media gallery (`{{media url="…"}}`) work.
- Images resized for each screen and served as WebP with a JPEG fallback, including images inside the content, with width and height so nothing shifts while they load. `[youtube:VIDEO_ID]` shows a light thumbnail instead of a player.
- Styles in `<head>` for zero layout shift; colors from the Hyvä theme, an optional accent color, and CSS custom properties for anything else. No Tailwind rebuild. Works with Alpine's CSP build.
- Settings: blog title, URL prefixes, blog home meta data, posts per page, title band, accent color, sidebar CMS block, related products heading, image resizing.
- Translations: English, Romanian.
- `module:uninstall --remove-data` drops the blog tables and deletes its URL rewrites and settings.
