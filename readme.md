# Unlisted Posts

A WordPress plugin that adds an "Unlisted" visibility option.  Based from [preliminary work](https://gist.github.com/nacin/4f4bc2d18a66c1eff93a) by nacin.

Requires WordPress 4.4+, PHP 5.6+.

### How to use?

1. Activate plugin.
2. Edit any post, and to make a post unlisted:
- For the Block Editor, click on the "Unlisted?" checkbox under the "Status & Visibility" section in the sidebar.
- For the Classic Editor, select the "Private, Unlisted" option under the "Visibility" options dropdown panel.

**Notes**

* An unlisted post is technically a private post, but allows users with the direct link to view the item.
* Comments left on unlisted posts are added to the comment moderation queue, and not approved automatically. (todo: perhaps remove this check?)
* Degrades well if plugin is deactivated.  If plugin is deactivated, post still remains private.


### License

GPLv2 or later.