## Style Guide Component: How to use

1. Creates acf options page for style guide.
2. This has a field on it which allows you to select the page that will be the style guide. All logic hangs of this selection.
3. Template redirects users away from this page to homepage, if they are not logged in.
4. Adds no-index to wp_head on all style-guide pages (belt and braces with the above).
5. Filters ==site-main== component and adds a cards block to display style-guide parent-page children, if we are on the style guide page.
6. Filters ==page-header== component and adds args to the page header
    - Background color set to 'style-guide'
    - Prefix added to heading "Style Guide:" on child pages.
7. Admin column 'post_states' edited to show a "style guide" suffix in the admin list of pages.
8. Enqueues some stylesheets for admin pages and style guide pages - to style some of the features.
9. Removes these pages from internal site searches via the ==post\_\_not_in== query arg.

## Todo:

1. Some clients need to allow access to these pages. How best to do this.
2. Hide from internal search - done in a pre_get_posts / post_not_in function. If using adv component search, this should be done via filters.
