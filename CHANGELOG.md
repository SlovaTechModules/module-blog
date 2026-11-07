# Changelog

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
