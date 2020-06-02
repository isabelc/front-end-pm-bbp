Front End PM Modified for bbPress
=================================

This is a fork of Front End PM WordPress plugin cleaned up and slimmed down for bbPress. 

Main differences:

- Removed all "Announcements" features.
- Removed desktop notification feature.
- Removed the "play sound" feature.
- Better styling for notification emails that go to users.
- Added `fep_get_send_message_link()` to replace a broken shortcode (https://github.com/isabelc/front-end-pm-bbp/commit/fed8de39a96f18ce63ba89a3ba71c8a6df65ed7c).

Additionally

- Fixed a few bugs, such as https://github.com/isabelc/front-end-pm-bbp/commit/cf6bd162769f6f16e956a424eda5c15718b7d6fd and https://github.com/isabelc/front-end-pm-bbp/commit/3bb02fc4206502619a908d659e3a698a9bb1ad25.
- Removed some shortcodes.
- Removed many settings.
- Removed translations & many i18n functions.

See [commits](https://github.com/isabelc/front-end-pm-bbp/commits/master) for list of all changes.
