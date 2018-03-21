=== Airstory ===
Contributors: liquidweb, airstory, stevegrunwell
Donate link: http://airstory.co
Tags: blogging, writing, import, airstory, content, publishing
Requires at least: 4.7
Tested up to: 4.9.4
Requires PHP: 5.3
Stable tag: 1.1.5
License: MIT
License URI: https://opensource.org/licenses/MIT

Send your blog posts from Airstory writing software to WordPress for publication.


== Description ==

Send your blog posts from [Airstory](http://www.airstory.co/) to WordPress.

=== Publish Better Content ===

Your blog visitors want great content from you, and search engines love content visitors love.

But the WordPress front-end editor doesn’t make it easy or fast to research, outline, write and edit great content. And the "locking" functions in WordPress make collaboration nearly impossible: no commenting, no co-editing.

That's why pro bloggers and content teams write in Airstory first then export each post to WordPress in a click, using this plugin.

=== Airstory + WordPress = Great Posts ===

Airstory is challenging the status quo for writing software - and we’re starting with cutting your blog-writing time in half. Use all of Airstory's time-saving features to draft your post. Then send it to your WordPress blog.

This plugin:

*   Imports text and images from your Airstory project
*   Keeps header tags, bolding, lists and other formatting
*   Uses your site's CSS for styling
*   Imports as a draft
*   Lets you import draft after draft to one unpublished URL
*   Supports multi-site if you have more than one WordPress blog

Airstory is a paid solution, which includes support and integrations, like this plugin.

=== How It Works ===

1. Install and activate the plugin
2. Inside Airstory, open a project
3. Choose the tab you’d like to send to WordPress as a post
4. Under Actions, select the blog to export to
5. In the browser tab that opens, you’ll see your draft in WordPress


=== Requirements ===

* This plugin requires an active [Airstory](http://www.airstory.co/) subscription.
	* Not already an Airstory user? [Sign up for a free two-week trial!](http://www.airstory.co/)
* PHP version 5.3 or higher, with the DOM and OpenSSL extensions active. Additionally, the Mcrypt PHP extension is required for PHP versions below 7.0.
* The WordPress site must have a valid SSL certificate in order for Airstory to publish content.


=== Usage ===

Once Airstory is connected with WordPress, your site name will appear as an export destination within Airstory.

Exporting to WordPress will create a new *draft* post, enabling you to set post thumbnails, publish dates, categories, and anything else your post might need before publishing.


=== Actions and filters ===

For developers, the Airstory WordPress plugin contains a number of [actions and filters that can be used to modify its default behavior](https://codex.wordpress.org/Plugin_API). For a full list of available filters, please [see the wiki in the plugin's GitHub repository](https://github.com/liquidweb/airstory-wp/wiki/Actions-and-Filters).


== Installation ==

After installing and activating the Airstory WordPress plugin, WordPress authors are able to connect their Airstory accounts through the user profiles:

1. In Airstory, [navigate to the "My Account" panel](https://app.airstory.co/projects?overlay=account) by clicking the avatar in the bottom-left corner of the screen and copy the "User token" to your clipboard.
2. Within WordPress, navigate to "Users &rsaquo; Your Profile" and scroll to "Airstory Configuration". In the "User Token" field, paste your Airstory user token, then click the "Update Profile" button at the bottom of the profile page to save your changes.

If your token has been verified successfully, the "Airstory Configuration" section of your profile will show details about your Airstory account. You're all set to start publishing!


== Frequently Asked Questions ==


= I have multiple blogs to publish to. Will this work? =

Yes, you’ll install this plugin under My Sites &rsaquo; Network Admin. Each member of your team can publish to any of the blogs you manage.


= Where do I find my user token in Airstory? =

In Airstory, go under Settings &rsaquo; My Account to find your user token.


= Where do I paste my user token in WordPress? =

In WordPress, go under Users &rsaquo; Your Profile to paste your Airstory user token.


= Our team is using Airstory. Can we each send our own drafts to the same WordPress blog? =

Yes! This plugin is installed at the Network Admin level. Each member of your team should update their WordPress profiles with their unique Airstory user tokens. This way, you can each send your own posts to your team blog, with authorship.


= Why is my post titled "document" in WordPress? =

This plugin names your post in WordPress using the label on the Airstory project _tab_ you exported. The first tab in Airstory is named "document," which is why your WordPress post is titled "document." You can change that in Airstory or in WordPress.


= Can I send content from any Airstory tab to WordPress? =

Yes! Go to the Airstory tab with the content you want to publish, and export to WordPress while you’re on that tab. Tabs cannot be combined into a single WordPress export.


= Can I send content from any Airstory project to WordPress? =

Yes! As long as it’s a project in your account.


= Will my blog's styling be preserved? =

Yes! When you send content from Airstory to WordPress, it will take the styling of your blog.


= Can I edit my post in WordPress before it gets published? =

Yes! The content you send from Airstory is exported as a draft in WordPress.


= The plugin won't activate, instead telling me "the Airstory plugin is missing one or more of its dependencies, so it's automatically been deactivated". How do I resolve this? =

This is a safety feature built into the plugin to avoid any unexpected behavior due to missing dependencies. The Airstory plugin relies on two common PHP extensions: "dom" (for <abbr title="Document Object Model">DOM</abbr> manipulation, used to clean up incoming content from Airstory) and "openssl" (used to securely encrypt your Airstory user token before storing it).

All modern hosts (Liquid Web, WP Engine, SiteGround, etc.) should support these extensions out of the box, but if you're running your own server you'll want to [ensure these extensions are both installed and activated](https://www.liquidweb.com/kb/how-to-check-php-modules-with-phpinfo/).


== Screenshots ==

1. The Airstory Configuration section of a user's profile, as seen when the plugin is first activated.
2. The Account Settings screen within Airstory, where the user retrieves their User Token.
3. The Airstory Configuration section of a user's profile after providing their User Token to connect to Airstory.
4. The user's WordPress site listed as an export target within Airstory.


== Changelog ==

For a full list of changes, please [view the change log on GitHub](https://github.com/liquidweb/airstory-wp/blob/develop/CHANGELOG.md).

= 1.1.5 =
* Explicitly set "Access-Control-Allow-Origin" headers for the Airstory webhook request.
* Plugin now attempts to resolve any redirects for the webhook URI before connecting to Airstory.
* Explicitly whitelist the Airstory webhook within [WP-SpamShield](https://www.redsandmarketing.com/plugins/wp-spamshield-anti-spam/).

= 1.1.4 =
* Ensure content is being consistently converted to UTF-8 before performing any operations on it, drastically reducing some of the special character issues that have been reported by users.
* Improved error handling if WordPress fails to authenticate with Airstory when saving the user token.

= 1.1.3 =
* Improved error handling throughout the plugin.
* Add fallback cipher algorithms for environments running older versions of OpenSSL.
* Remove requirement for libxml 2.7.8 or newer, which was introduced in version 1.1.1.

= 1.1.2 =
* Fix an issue with a missing file distributed with version 1.1.1.

= 1.1.1 =
* Improved UTF-8 support for accented and non-Latin character sets.
* Added compatibility check for outdated versions of libxml.

= 1.1.0 =
* Introduce two connectivity checks in the "Compatibility" table to help identify issues accessing either the Airstory API or the WP REST API.
* Improve error messaging around the webhook, making it easier to troubleshoot connection issues.
* Add the plugin version to the header of the Tools &rsaquo; Airstory page.
* Refactor the logic when saving user settings.

= 1.0.1 =
* Fixed bug where saving a user profile could fail without an Airstory user token.
* Fixed a small typo in the plugin's README file.

= 1.0.0 =
* Initial public release.


== Upgrade Notice ==

= 1.1.5 =
Fixes cases where the Airstory application was unable to communicate with WordPress.

= 1.1.4 =
Improved support for accented and non-Latin characters, along with better error handling when connecting to Airstory.

= 1.1.3 =
Improved error handling, wider support for older versions of OpenSSL and libxml.

= 1.1.2 =
Fixes an issue with the 1.1.1 release where a file that does not exist was being loaded.

= 1.1.1 =
Better support for accented and non-Latin characters, ensuring content imports cleanly.

= 1.1.0 =
Introduces connectivity checks and better error reporting, ensuring the best possible publishing experience.

= 1.0.1 =
Fixes bug where users could have problems saving their profiles.
