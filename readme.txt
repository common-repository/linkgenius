=== LinkGenius - The Best Affiliate Link Manager and Link Shortener Plugin ===
Contributors: allaffiliates
Tags: affiliate links, affiliate link manager, link branding, affiliate disclosure, link shortener
Donate link: https://all-affiliates.com/linkgenius/
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The #1 affiliate link manager. Easily brand, disclose, tag, or categorize your affiliate links. Customize appearance, manage rel attributes, and more!

== Description ==

LinkGenius is a powerful affiliate link management plugin designed to simplify the management of your affiliate links. It offers a range of benefits for affiliate marketers, ensuring a seamless user experience while complying with FTC guidelines and enhancing your affiliate marketing strategy.

Here are some specific benefits of using LinkGenius for affiliate marketing:

- **Effortless Link Management:** Easily add, edit, and delete affiliate links using LinkGenius. Keep your links organized and easily accessible by categorizing and tagging them as needed.

- **Compliance with FTC Guidelines:** LinkGenius helps you include clear affiliate disclosures in your content, ensuring compliance with FTC guidelines and building trust with your audience.

- **Customize link appearance:** Customize the appearance of your affiliate links with LinkGenius, including attributes like opening in a new window, adding classes, UGC and sponsored attributes, and custom rel attributes, to optimize click-through rates.

- **Boost SEO:** With LinkGenius, you can improve your website's SEO by creating clean, search engine friendly, and userfriendly links.

Let’s take a closer look at some of the key features of LinkGenius:

= Link Management: =
Manage your affiliate links with ease using LinkGenius. Add, edit, and delete affiliate links effortlessly, keeping them organized and accessible. Manage your affiliate link lists by categorizing and tagging your links.

= Easily Links to Posts or Pages =
Display individual links or a lists of affiliate links based on tags or categories to provide a cohesive representation of related links. Customize the layout and order of your list. LinkGenius supports creating links or link lists via multiple blocks, shortcodes, or by simply selecting text in the block editor.

= Find which posts or pages contain a specific link =
Quickly find which posts or pages contain a specific link using the Link Locator functionality. Remove your expired links easily from your content or replace them with new ones.

= Customizable Link Appearance =
Customize the appearance of your affiliate links. Add classes, or make a link open in a new window. Add UGC and sponsored attributes, or defined your own custom rel attribtues.

= Affiliate Disclosure =
Maintain compliance with FTC guidelines and build trust with your audience by adding an affiliate disclosure statement to your content. Alternatively, you can utilize a tooltip or appended text to clearly indicate which links are affiliate links.

= Link Branding =
Enhance the appearance and security of your affiliate links by branding them. Create shorter, branded, and more memorable links that are less likely to be blocked by ad-blockers or marked as spam. Customize link cloaking on a per-link basis.

= Multiple Redirect Types =
Take full control over how your users are redirected, including 301 (Permanent), 302 (Temporary), 307 (Temporary) redirects.

= Pro Features =
While LinkGenius is completely free, there is also a pro version with additional features available. Go to [All-affiliates.com](https://all-affiliates.com/linkgenius/pro/) to get more information on the pro version.

- **Automatic link insertion:** Automatically insert affiliate links into your content by linking specific keywords to affiliate URLs. Exclude certain pages or posts from automatic link insertion.

- **Automatic link replacement:** Automatically replace URLs in your content with either shortened or uncloaked LinkGenius (affiliate) links.

- **Expiring Links:** Set an expiration date on your links and redirect users to a specific location after clicking an expired link. You can also make links expire after a certain number of clicks.

- **Geotargeting:** Redirect visitors based on their country of origin to geographically appropriate affiliate URLs.

- **Device based redirects:** Redirect users to alternative URLs, based on their browser, operating system, or other information in their user agent.

- **Link tracking:** Track the performance of your affiliate links with ease, using LinkGenius's link tracking capabilities powered by Google Analytics. Gain valuable insights into link performance and optimize your strategy accordingly. The server-side Tracking option allows you to even track clicks from directly from social media.

- **Commission tracking:** Track the commission you earn from your affiliate links by using commission postback. Do not only track the clicks but also the commission you earn from them.

- **Link health checker:** Easily check the final URL of an affiliate link and store it for future comparison. Get notified if the URL changes or when URLs are unreachable.

== Installation ==

### Install LinkGenius from within WordPress

1. Visit the plugins page within your dashboard and select "Add New";
2. Search for "LinkGenius";
3. Activate LinkGenius from your Plugins page;
4. Navigate to the 'LinkGenius' menu in your WordPress dashboard to start managing your affiliate links.

### Install LinkGenius Manually
1. Upload the `linkgenius` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'LinkGenius' menu in your WordPress dashboard to start managing your affiliate links.

== Frequently Asked Questions ==

= How can I add affiliate links using LinkGenius? =

After installing and activating LinkGenius, navigate to the 'LinkGenius' menu in your WordPress dashboard. From there, you can easily add, edit, and delete your affiliate links.

= Can I customize the appearance of my affiliate links? =

Absolutely! LinkGenius allows you to customize the appearance of your affiliate links, including attributes such as opening in a new window, additional classes, UGC and sponsored attributes, and custom rel attributes.

= Is LinkGenius compliant with FTC guidelines? =

Yes, LinkGenius includes a feature to add an affiliate disclosure statement to your content, ensuring compliance with FTC guidelines and building trust with your audience.

= Can I organize my affiliate links into categories or tags? =

Yes, LinkGenius provides the functionality to categorize your affiliate links, making it easier to organize and display related links. You can create lists of affiliate links based on tags or categories, with customizable layouts and sorting options.

= What is link branding/cloaking, and why should I use it? =

Link branding or link cloaking is a feature in LinkGenius that creates shorter, branded, and more memorable affiliate links. It also helps to hide the original affiliate link, making it less likely to be blocked by ad-blockers or marked as spam.

= How can I track the performance of my affiliate links? =

LinkGenius Pro offers link tracking capabilities using Google Analytics. You can track link performance either via JavaScript or server-side (for social media use). Customize event names and add additional parameters to gain valuable insights.

== Screenshots ==
1. Add affiliate link
2. Link appearance
3. Link Disclosure
4. Manage links in content
5. Tooltip Disclosure
6. Disclosure Settings

== Changelog ==
= 1.2.1 =
- Fixed wrong shortcode being displayed when copying shortcodes for categories and tags. Was linkgenius instead of linkgenius-list.
- Disallowed the use of the LinkGenius Link Loop block inside the LinkGenius Link Loop block for clearer usage.

= 1.2.0 =
- Added Link Locator functionality to find links in content
- Added templating functionality for link lists allowing for inner blocks
- Added Custom fields for LinkGenius links that can be used in link lists
- Tested on WordPress 6.6

= 1.1.5 =
- Added ability to pass additional link-attributes to the linkgenius-link shortcode and tag
- Added and updated translations
- Removed potential notice of buffer close, when maximum memory limit is reached

= 1.1.4 =
- Added linkgenius_additional_headers filter to allow adding or modifying additional headers on redirect
- Improved copy to clipboard functionality for unusual permalinks
- Introduced variables for tracking and destinations
- Tested on WordPress 6.5

= 1.1.3 =
- Improved importing by adding fields for broader support
- Fixed issue of nofollow and sponsored headers not always being set correctly
- Improved Linkgenius post type constants
- Added css to support LinkHealth feature
- Tested on WordPress 6.4

= 1.1.2 =
- Added filtering links on Tag and Category in admin table
- Added importing function from CSV/XML

= 1.1.1 =
- Editor Fixes
- Testing on php 7.4 and WordPress 5.8

= 1.1.0 =
- Added capability management
- Added easy copying of branded URLs
- Improved layout affiliate disclosure tooltip
- Fixed default prefix setting mismatch displayed and applied

= 1.0.1 =
- Processed feedback of the Wordpress review team

= 1.0.0 =
- Initial release of LinkGenius plugin.

== Upgrade Notice ==

There is a new release of the LinkGenius plugin. Enjoy the powerful link management features to enhance your affiliate marketing strategies.

== License ==
LinkGenius is distributed under the terms of the GNU General Public License v2 or later: http://www.gnu.org/licenses/gpl-2.0.html

== Credits ==
LinkGenius is developed by [all-affiliates.com](https://all-affiliates.com).

