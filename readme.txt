=== Projects M Membership ===
Contributors: projectsm
Requires at least: 4.9.0
Tested up to: 6.0
Requires PHP: 7.0
Stable tag: 1.4.4
License: 3-Clause BSD
License URI: https://opensource.org/licenses/BSD-3-Clause

Integration with the Projects M Membership system



== Description ==
This plugin provides integration with the Projects M membership system. This is a SaaS tool, that lets you manage your subscriptions inside your webapps.

https://membership.projects-m.de/

== Installation ==
Install the plugin.

Then go to your accounts page on https://membership.projects-m.de/account/, copy the API key and store it in the plugin\'s admin config.
Also you need to set your IP (v4 or v6) in your cloud\'s account page.

== Changelog ==

= 1.4.4 =
* Properly handle start date if the contract has a first day in the future.

= 1.4.3 =
* Use first of next month as official date.

= 1.4.2 =
* Only suggest Magicline "try again" link, if the entry was already exported.

= 1.4.1 =
* Fix invalid API URL

= 1.4.0 =
* Add support for user selectable contract start dates

= 1.3.1 =
* Fixed incomplete release

= 1.3.0 =
* Added support for Magicline integration

= 1.2.1 =
* Bugfixes and performance improvements

= 1.2.0 =
* Added support for `firstDay`

= 1.1.0 =
* Show contract name in admin sign up listing
* Add separate notification mails by contract

= 1.0.11 =
* Improve rendering of headline size in steps
* Improve scaling of runtime headlines
* Improve styling of "proceed" button on mobile

= 1.0.10 =
* Improve rendering of runtime selection

= 1.0.9 =
* Display headline in options screen

= 1.0.8 =
* Add PHP 7.0 compatibility
* Fix price formatting
* Remove first payment amount
* Add birthday of presentee

= 1.0.7 =
* Runtimes are sorted descendingly.
* Options are now fully optional.

= 1.0.5 =

Initial relelase.

== Upgrade Notice ==
Normally, you should just need to update the WordPress plugin. The generated data is stored outside the plugin directory and the database should auto-upgrade while keeping your existing data.

But please create a backup first.
