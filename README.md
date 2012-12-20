BP Wake Up Sleepers
===================

BP Wake Up Sleepers is a BuddyPress plugin to allow the admin of a community to manage sleeping users.

Sleeping users are :

+ Users that has not activated their account,
+ Users that has never logged in the website,
+ Users that has not logged in for 30 days.

Depending on your WordPress config, you will find the BP Wake Up Sleepers administration menu in the WordPress network admin or
regular WordPress admin. 4 tabs will help you to select the kind of sleeping users, compose a mail, send it and manage the users that
unsubscribed to mails.

You can watch a demo of the plugin on my vimeo [vimeo](http://vimeo.com/55975541)
Available in french and english.

For the french description of the plugin please visit my [blog](http://imath.owni.fr/2012/12/20/bp-wake-up-sleepers/)


Configuration needed
--------------------

+ WordPress 3.5 and BuddyPress 1.6.2
+ Working in WordPress 3.5 and BuddyPress 1.7 Bleeding


Installation
------------

Before activating the plugin, make sure all the files of the plugin are located in `/wp-content/plugins/bp-wake-up-sleepers` folder.


Customizing the behavior of the plugin
--------------------------------------

There's an option submenu to the BP Wake Up sleepers admin menu where you can choose from a light email template or a full one.
If you choose the full one and if you uploaded a custom header to your active theme, you'll be able to choose to use this custom header in this option menu.
The plugin also include some filters if you want to change some settings such as :

+ the number of days for the sleeping users 

```php
add_filter( 'bp_wus_list_sleeping_users_days', 'your_custom_days', 10, 1 );
function your_custom_days( $days ){
	return 10;
}
```

+ the subject of the message sent where $type can be : never_loggedin, unactivated, sleeping_buddies

```php
add_filter( 'bp_wus_email_subject_'.$type, 'your_custom_subject', 10, 1 );
function your_custom_subject( $subject ){
	return 'My new subject';
}
```

Special Thanks
--------------

Many thanks to Sean Powell for his [email boilerplate](https://github.com/seanpowell/Email-Boilerplate)
