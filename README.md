# Airstory WordPress Plugin

This plugin enables [Airstory](http://www.airstory.co/) users to connect their WordPress sites, enabling authors to leverage the exceptional editorial experience of Airstory with the powerful publishing of WordPress.

## Requirements

* An [Airstory](http://www.airstory.co/) account. Not already an Airstory user? [Get one project free for life, just by signing up!](http://www.airstory.co/pricing/)


## Installation

After installing and activating the Airstory WordPress plugin, WordPress authors are able to connect their Airstory accounts through the user profiles:

1. In Airstory, [navigate to the "My Account" panel](https://app.airstory.co/projects?overlay=account) by clicking the avatar in the bottom-left corner of the screen and copy the "User token" to your clipboard.
2. Within WordPress, navigate to "Users &rsaquo; Your Profile" and scroll to "Airstory Configuration". In the "User Token" field, paste your Airstory user token, then click the "Update Profile" button at the bottom of the profile page to save your changes.

If your token has been verified successfully, the "Airstory Configuration" section of your profile will show details about your Airstory account. You're all set to start publishing!


## Usage

Once Airstory is connected with WordPress, your site name will appear as an export destination within Airstory.

Exporting to WordPress will create a new *draft* post, enabling you to set post thumbnails, publish dates, categories, and anything else your post might need before publishing.


## Actions and filters

For developers, the Airstory WordPress plugin contains a number of [actions and filters that can be used to modify its default behavior](https://codex.wordpress.org/Plugin_API):

### Actions



### Filters

#### airstory_before_insert_content

This filter is applied to the Airstory document content before inserting it into the wp_insert_post() array.

<dl>
	<dt>(string) $document</dt>
	<dd>The compiled, HTML response from Airstory.</dd>
</dl>


#### airstory_before_insert_post

Filter arguments for new posts from Airstory before they're inserted into the database. For a full list of available parameters, please [see `wp_insert_post()`](https://developer.wordpress.org/reference/functions/wp_insert_post/#parameters).

<dl>
	<dt>(array) $post</dt>
	<dd>An array of arguments for wp_insert_post().</dd>
</dl>

##### Example:

```php
/**
 * Set the default post status for Airstory posts to "pending".
 *
 * @param array $post An array of arguments for wp_insert_post().
 * @return array The filtered $post array.
 */
function mytheme_set_airstory_post_status( $post ) {
	$post['post_status'] = 'pending';

	return $post;
}
add_filter( 'airstory_before_insert_post', 'mytheme_set_airstory_post_status' );
```
