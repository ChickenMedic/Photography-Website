# Personal Website — TODO

Last updated: 2026-08-16

## Projects page expansion (the big one)

Goal: turn the projects section into proper write-ups, including showing how
Claude is used to develop ideas and build each project.

### Article page (project.php)
- [ ] Show the cover image at the top of the article
- [ ] Show a date (and maybe "last updated") on articles
- [ ] Add tags / tech-stack chips per project
- [ ] "Next project" / related projects links at the bottom (currently a dead end)
- [ ] Syntax highlighting for code blocks (highlight.js, self-hosted, dark theme)

### Homepage project cards (index.php)
- [ ] Add date and tech hints to cards so builds have a sense of scale
- [ ] Consider a "Built with Claude" series using the existing series_name grouping

### Claude-collaboration content blocks (style.css + usable in Summernote)
- [ ] "Conversation" block styled like a chat exchange (my prompt / the response)
      to show how an idea evolved
- [ ] Callout box, e.g. for "what Claude caught that I missed" moments
- [ ] Before/after code comparison block

### Admin / writing workflow
- [ ] Decide: add raw HTML or markdown editing mode alongside Summernote
      (WYSIWYG mangles pasted code; painful for long technical posts)

### Content to write (lives in the live server DB, not git)
- [ ] Review existing project articles and touch them up
- [ ] Adopt a recurring structure per article:
      The idea → Shaping it with Claude → The build (real code) → Result / what's next
- [ ] Write up this website itself as a project (galleries, admin, favicon set,
      WebP pipeline are all good material)

## Notes / context for picking this up cold
- Projects are DB-backed: `projects` table (title, description, cover_image,
  url slug, series_name, content as raw Summernote HTML). Articles are NOT in git.
- `.htaccess` rewrites any non-file URL to `project.php?url=<slug>`.
- Code blocks already get styled wrappers + copy buttons via main.js.
- Recently done: circle gallery layout removed, polaroid layout sized like real
  prints, Surprise Me lightbox has flip arrows, favicon rotates through hairline
  camera icons (black, no gradient).
