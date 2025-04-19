Akeeba Backup 10.0.3
================================================================================
+ Support for tables with backticks in their names
~ Restoration: Eliminate deprecation notices under PHP 8.4
# [HIGH] Restoration: lack of otherwise optional mbstring would result in an error
# [HIGH] CLI restoration: error about the DB port being out of range
# [MEDIUM] Some configuration settings are inherited from the default profile when a profile is reset or created afresh
# [MEDIUM] System - Page Cache prevents the JSON API from working properly on some sites

Akeeba Backup 10.0.2
================================================================================
# [HIGH] Front-end legacy and API backup modes don't work when Strict Routing enabled in the System - SEF plugin
# [MEDIUM] Restoration: PHP error when the server reports the site's root as the filesystem root (chroot jail)
# [MEDIUM] Joomla restoration: mail online setting not respected in the web interface
# [LOW] Possible PHP error trying to parse invalid URLs
# [LOW] Deleting the items of the last page in Manage Backups page results in an empty display you can't easily get out of

Akeeba Backup 10.0.1
================================================================================
~ Automatically exclude the .cagefs directory present in some cPanel installations
# [HIGH] PHP Error resetting Joomla! 4 MFA
# [MEDIUM] Possible restoration issues if the upgrade code does not execute when installing the update
# [LOW] Restoration: PHP Deprecated warnings when checking for legacy magic quotes features on PHP 7

Akeeba Backup 10.0.0
================================================================================
+ New restoration script framework, with a minimum requirement of PHP 7.2
~ Maximum batch row size for database backup is now 10000 by default, with a maximum of 1000000
~ Fixed dark mode display of Configuration Wizard on Joomla! 5.2
# [HIGH] Box: cannot refresh the authentication token
# [LOW] The list of tables was no longer output
# [LOW] WebDAV: deleting backups may file on some servers

Akeeba Backup 9.9.11
================================================================================
~ Make accurate PHP CLI path detection optional
# [HIGH] Some OneDrive multipart uploads fail

Akeeba Backup 9.9.10
================================================================================
# [HIGH] Error in Manage Backups page: extra tab in layout file

================================================================================
! Could not work with MySQL 5.x and MariaDB 10.x

Akeeba Backup 9.9.7
================================================================================
+ Multi-row select and row show/hide in the Manage Backups and Profiles pages
+ Support for dumping MySQL EVENTs
+ More accurate information about PHP CLI in the Schedule Automatic Backups page
+ Improved database dump engine
# [LOW] Cannot transfer files to DreamObjects and Google Storage using the S3 API

Akeeba Backup 9.9.6
================================================================================
# [HIGH] Custom OAuth2 token refresh did not work reliably
# [HIGH] Custom OAuth2 set up could be blocked by missing files due to a packaging issue

Akeeba Backup 9.9.5
================================================================================
+ Edit and reset the cache directory (Joomla! 5.1+) on restoration
+ Remove MariaDB MyISAM option PAGE_CHECKSUM from the database dump
~ Improve database dump with table names similar to default values
~ Change the wording of the message when navigating to an off-site directory in the directory browser
~ Workaround for PRE element styling in Joomla! 5.1
~ Workaround for Joomla! 5.1 CSS in alert DIVs
~ Disable migration from Akeeba Backup 8 on Joomla! 5.0 and later
~ PHP 8.4 compatibility: MD5 and SHA-1 functions are deprecated
# [MEDIUM] Tables or databases named `0` can cause the database dump to stop prematurely, or not execute at all
# [MEDIUM] Some akeeba:profile CLI commands had non-functional options
# [LOW] The [PROFILENAME] tag in backup completion emails returns no or wrong labels
# [LOW] Backup on Update showed in more Joomla! Update pages than intended

Akeeba Backup 9.9.4
================================================================================
+ Option to avoid using `flush()` on broken servers

Akeeba Backup 9.9.3
================================================================================
- Remove the deprecated, ineffective CURLOPT_BINARYTRANSFER flag
+ New style for the Backup-on-Update information
+ Alternate Configuration page saving method which doesn't hit maximum POST parameter count limits
+ Improved support for Joomla! 5.1's backend colour schemes
# [HIGH] Custom OAuth2 helpers: cannot use refresh tokens with non-alphanumeric characters (e.g. slashes, plus sign, etc)

Akeeba Backup 9.9.2
================================================================================
! Packaging problem prevented new installations from working correctly

Akeeba Backup 9.9.1
================================================================================
+ Self-hosted OAuth2 helpers
~ Dark Mode color improvements for Joomla! 5.1
# [LOW] Restoration error when you have a newer Admin Tools version installed
# [LOW] Deprecation notice in Configuration Wizard
# [LOW] Exporting a backup profile doesn't automatically save the JSON file

Akeeba Backup 9.9.0
================================================================================
~ Prevent backup failure on push notification error
+ Separate remote and local quota settings
+ Expert options for the Upload to Amazon S3 configuration
+ Upload to OneDrive (app-specific folder)

Akeeba Backup 9.8.5
================================================================================
+ Joomla restoration: allows you to change the robots (search engine) option
# [LOW] CLI commands: fixed import of new profiles
# [LOW] Backup on Update status toggled after saving Joomla Update's Options

Akeeba Backup 9.8.4
================================================================================
+ Automatically downgrade utf8mb4_900_* collations to utf8mb4_unicode_520_ci on MariaDB
+ Updated environment stats collection code
# [MEDIUM] PHP error trying to use WebPush for the first time
# [LOW] Cannot delete backup archive from CLI
# [LOW] PHP 8.3 deprecated notice in ComponentParameters service (no functional issue)

Akeeba Backup 9.8.3
================================================================================
~ Identical to 9.8.2. Re-released as 9.8.3 because of an issue with the update server serving stale information.

Akeeba Backup 9.8.2
================================================================================
# [MEDIUM] Using WebPush leads to PHP error under Joomla! 5
# [LOW] The --force flag in akeeba:option:set was not working

Akeeba Backup 9.8.1
================================================================================
~ Joomla 5 Dark Mode workarounds
+ Support for Joomla 5 custom public folder
+ Restoration: Support Joomla 5 custom public folder
+ Restoration: Use transactions to speed up large table restoration
# [HIGH] The Quick Icon plugin does not show anything in the Joomla! control panel
# [HIGH] The (deprecated) JSON API command to export the configuration fails when the configuration is encrypted
# [HIGH] The akeeba:backup:delete CLI command threw an error due to a typo

Akeeba Backup 9.8.0
================================================================================
+ Use Composer to load all internal dependencies (backup engine, S3 library, WebPush library)
# [LOW] Joomla 5: Manage Backups page does not work when the b/c plugin is disabled.

Akeeba Backup 9.7.1
================================================================================
# [LOW] Possible PHP error when updating this along other extensions using the same post-installation script

Akeeba Backup 9.7.0
================================================================================
+ Notice about Joomla 4 End of Service
+ Workaround for Wasabi S3v4 signatures
+ Support for uploading to Shared With Me folders in Google Drive
- Remove the non-functional “Hide toolbar” option from the Backup Now backend menu item
~ Changed the plugins' namespace
~ Joomla 5 preparation: Use DatabaseInterface instead of DatabaseDriver
~ Joomla 5 preparation: Work around backwards incompatible changes in core plugin events
~ Joomla 5 preparation: Normalise plugin event calling
~ Joomla 5 preparation: Loading form data MUST NOT return a Table anymore
~ Improved error reporting, removing the unhelpful "(HTML containing script tags)" message
~ Improved mixed– and upper–case database prefix support at backup time
~ Normalised view names
# [MEDIUM] Upload to S3 would always use v2 signatures with a custom endpoint.
# [MEDIUM] Visiting the Control Panel page would always try to save the Output Directory, replacing most variables
# [MEDIUM] Resetting corrupt backups can cause a crash of the Control Panel page
# [LOW] Trying to delete a profile which cannot be deleted results in error page instead of an above-the-table error message
# [LOW] Cannot save an edited backup record leaving the comment blank
# [LOW] Users are not prompted to run the Configuration Wizard on new installation

Akeeba Backup 9.6.2
================================================================================
~ Block uninstallation of child extensions
# [LOW] CLI backups no longer record an end date and time due to a change in Joomla's behavior
# [LOW] Backup On Update: Would always use profile 1
# [LOW] Backup On Update: Inversion of logic of the switches in its options page

Akeeba Backup 9.6.1
================================================================================
# [MEDIUM] HTTP PUT might fail on some servers
# [LOW] opcache_invalidate may not invalidate a file
# [LOW] Would not work on 32-bit versions of PHP

Akeeba Backup 9.6.0
================================================================================
+ Support for files and archives over 2GiB (JPA file format 1.3)
+ New JSON API endpoint, using the Joomla API Application
~ Disabled deprecated API methods
~ Improve the Schedule Automatic Backups page
# [MEDIUM] JSON API: deleteFiles method throws an exception due to a typo

Akeeba Backup 9.5.1
================================================================================
+ Restoration: handle Joomla 4.2+ MFA options
# [MEDIUM] Plugins not enabled on clean installation
# [MEDIUM] JSON API cannot delete backup records and profiles

Akeeba Backup 9.5.0
================================================================================
+ Option to treat failed uploads as a backup error

Akeeba Backup 9.4.8
================================================================================
! A packaging issue broke the restoration script in backup archives

Akeeba Backup 9.4.7
================================================================================
# [MEDIUM] Fixed drive selection for Google Drive post processing engine

Akeeba Backup 9.4.6
================================================================================
# [HIGH] Some password managers prevent successful submission of the Site Setup page (you get an error about a missing email address)
# [LOW] Wrong grammatical case (nominative instead of genitive) in months in some languages e.g. Greek
# [LOW] Push messages may be untranslated strings when a backup is taken over the API or the frontend backup URL

Akeeba Backup 9.4.5
================================================================================
# [HIGH] Unexpected behaviour in the backend when Joomla cache is enabled
# [HIGH] BackBlaze B2 single file uploads were broken

Akeeba Backup 9.4.4
================================================================================
+ ALICE button in the log view
# [HIGH] Migration from Akeeba Backup 8 fails since 9.4.0 added an access setting in backup profiles

Akeeba Backup 9.4.3
================================================================================
# [HIGH] Migration from Akeeba Backup 8 always shows an erroneous message that no compatible version has been detected.
# [MEDIUM] Restoration. Administrator email appears as "undefined" in the Site Setup page
# [LOW] Restoration: Wrong message about the emial address when the administrator passwords don't match

Akeeba Backup 9.4.2
================================================================================
! No access control applied in Include and Exclude Information features
# [HIGH] Class not found errors when trying to access some pages in Akeeba Backup

Akeeba Backup 9.4.1
================================================================================
! Immediate error on PHP 7.4 due to a missing method in the released version

Akeeba Backup 9.4.0
================================================================================
~ Requires Joomla 4.2 or later
~ Requires PHP 7.4.0 or later
~ Much simpler message if you try to run Akeeba Backup on an unsupported (too low) version of PHP.
~ Changed all warnings to much more compact DETAILS elements
+ Access levels in backup profiles
+ Option about including the latest backup in remote quotas
- Removed the PHP version warning. Joomla already warns you about EOL versions of PHP.
# [HIGH] Site Transfer Wizard will fail on a target site using PHP 8.1 or later by default
# [LOW] ZIP Archiver, invalid CRC32 calculated for some small files in the installation folder

Akeeba Backup 9.3.4
================================================================================
# [LOW] ZIP Archiver, invalid CRC32 calculated for some small files in the installation folder

Akeeba Backup 9.3.3
================================================================================
~ Better warnings about CRC32 for ZIP files on 32-bit versions of PHP
# [HIGH] Quota settings and emails are not processed at the end of the backup process
# [HIGH] Joomla Scheduled Tasks for Akeeba Backup may fail with a PHP error

Akeeba Backup 9.3.2
================================================================================
~ PHP notices are now only logged when Debug Site is enabled
~ Notify the user when the server does not support Web Push instead of just failing to subscribe to push notifications
# [MEDIUM] WebPush code tries to run when not selected resulting in an annoying, but harmless, warning
# [MEDIUM] Possible PHP fatal error if the server does not meet the Web Push minimum requirements
# [LOW] PHP 8 deprecated notices from the WebPush library

Akeeba Backup 9.3.1
================================================================================
+ Push notifications through the browser's Push API
+ ANGIE for Joomla: reset session and cache options in Site Setup
+ Support for ShowOn to conditionally show options in the Configuration page
~ Save and Save & Close buttons are now separate, as per Joomla 4.2 UI guidelines
# [HIGH] Single part uploads to Azure stopped working
# [LOW] “Field 'extra_query' doesn't have a default value” error on some broken installations
# [LOW] PHP warning about undefined $id in the Manage Backups page on some versions of PHP

Akeeba Backup 9.3.0
================================================================================
+ Upload to Swift: Support for Keystone v3
# [HIGH] Joomla broke database-aware models under the CLI. Working around the latest Joomla borkage, as we have always done.
# [MEDIUM] Command line options overrides don't work because of a typo
# [LOW] PHP 8.1 deprecated notice when checking if FOF is still installed
# [LOW] "Test FTP connection" button was not correctly applying the passive mode
# [LOW] CLI akeeba:profile:list was broken

Akeeba Backup 9.2.7
================================================================================
+ More informative error messages for database connection issues during restoration
~ Workaround for utf8_encode and _decode being deprecated in PHP 8.2
# [LOW] Restoration: You were shown separate port and socket options which were not taken into account
# [MEDIUM] Restoration: Using a custom port or socket might result in the wrong hostname being written in the restored site's configuration file
# [MEDIUM] Possible infinite loop on PHP 8 during DB restoration if a SQL file is missing
# [LOW] Invalid SQL dump if we cannot get the create commands for a function, procedure or trigger

Akeeba Backup 9.2.6
================================================================================
+ Restoration: Warn about missing mysqli / PDO MySQL and REFUSE to proceed
# [HIGH] Cannot download file from Amazon S3
# [LOW] PHP Warning when backing up a database (purely cosmetic issue)
# [LOW] Missing language strings from the CLI commands

Akeeba Backup 9.2.5
================================================================================
+ Restoration: Warn about missing mysqli / PDO MySQL and REFUSE to proceed
# [HIGH] Cannot download file from Amazon S3
# [LOW] PHP Warning when backing up a database (purely cosmetic issue)
# [LOW] Missing language strings from the CLI commands

Akeeba Backup 9.2.4
================================================================================
# [HIGH] Cannot connect to databases on localhost using the default named pipe
# [MEDIUM] Custom Amazon S3 regions would not work with custom endpoints

Akeeba Backup 9.2.3
================================================================================
+ Support for custom Amazon S3 regions
+ Support for MySQL SSL/TLS connections for backed up sites
+ Add Show Inline Help support in component options for Joomla 4.1
# [LOW] Weird interface for the CLI backup Scheduled Task type

Akeeba Backup 9.2.2
================================================================================
+ Improved Smart Search table filtering
+ Much improved FTP functions for uploading backup archives and transferring sites
+ Upload to Azure BLOB Storage now supports chunked uploads, files up to 190.7TB (up from 64Mb)
+ OneDrive for Business: you can now use Drives other than your personal
~ Ignore whitespace in the new site's URL in the Site Transfer Wizard
~ Stricter conditions for determining when to show the “Manage remotely stored files” button in Manage Backups
# [LOW] Upload to Remote Storage would transfer the first part file twice
# [MEDIUM] Fixed download of remote archives back to the server
# [MEDIUM] OneDrive: Uploads may fail if they are between 4Mb and 100Mb

Akeeba Backup 9.2.1
================================================================================
+ Restoration: ANGIE now applies very high memory and execution time limits to prevent some timeout / memory outage issues on most hosts.
+ Restoration: ANGIE now warns you if you leave the database connection information empty
+ Option to set a really large PHP memory limit during backup
~ Show an error if the temp file cannot be opened when importing from S3
# [HIGH] Sometimes you would not see the error when the Upload to Remote Storage failed
# [MEDIUM] ALICE would not list any logs, even for failed backups
# [LOW] The JPS archiver would show warnings about unreadable files when archiving directories without any files in them.
# [LOW] The directory browser in the Configuration page doesn't open the defined folder when it contains variables
# [LOW] Extra whitespace in the Upload to Remote Storage pages
# [LOW] Configure and Export in the backup profiles manager do not work because of backwards incompatible changes in Joomla 4.1.1

Akeeba Backup 9.2.0
================================================================================
+ Integration with Joomla 4.1's Scheduled Tasks
# [HIGH] Uploading to OVH is broken on many servers not using a proxy
# [LOW] Popover content does not display in the Configuration page

Akeeba Backup 9.1.1
================================================================================
# [HIGH] Wrong RewriteBase set up in the .htaccess Maker when restoring a Joomla site with Admin Tools Professional installed

Akeeba Backup 9.1.0
================================================================================
+ Allow using [REMOTESTATUS] in the email subject, not just the body
+ Warn about the Console – Akeeba Backup plugin being disabled in the Schedule Automatic Backups page
+ Joomla restoration: modify domains in the Admin Tools' Allowed Domains and server config maker features if necessary
~ Force the Quickicon plugin to always show in the Notifications area instead of the 3rd Party area
# [HIGH] Problems restoring if a table name ends in 0 when another table with an identical name EXCEPT the trailing zero is also being backed up
# [HIGH] Backing up to SQL: indices would not have the correct table name prefix
# [HIGH] Backing up as SQL: the query for finder_taxonomy does not use the correct prefix
# [MEDIUM] Log Priorities global configuration option got mangled restoring a Joomla 4 site
# [LOW] Restore backup admin menu does not work correctly with multiple backup profiles
# [LOW] RackSpace CloudFiles: some hosts change the case of HTTP headers
# [LOW] Test FTP button was not working
# [LOW] Fixed displaying multi-line backup comments in the Manage Backups page

Akeeba Backup 9.0.11
================================================================================
+ Support for MySQL 8 invisible columns
# [LOW] Rare type error under PHP 8 during restoration
# [LOW] Wrong translation string in backend menu item type
# [LOW] Wrong controls in Backup and Restore backend menu item types

Akeeba Backup 9.0.10
================================================================================
- Remove piecon (pie graph favicon showing the backup progress)
~ JSON API: Forcibly use the ‘json’ origin everywhere
~ JSON API: Throw an error if the backup ID sent to stepBackup does not exist
~ JSON API: Improved backup IDs prevent a number of JSON API issues
~ Auto–publish the Console plugin in the Professional version
# [LOW] JSON API: The wrong origin (‘frontend’ instead of ‘json’) was recorded
# [LOW] Manage Backups: The View Log button didn't take you to the correct log file

Akeeba Backup 9.0.9
================================================================================
- Removed iDriveSync; the service has been discontinued by the provider.
- Removed the “Archive integrity check” feature.
~ Ensure the correct collation of all database tables and columns used by the extension
~ Dropbox connector updated to require TLS v1.2
+ API requests: Prevent server cache
+ Better support for custom database drivers provided by third party extensions
# [LOW] Bootstrap 5.1.2 included in Joomla 4.0.4 broke the CSS for Control Panel icons
# [LOW] Check failed backups: All Super Users were notified even when an email was supplied

Akeeba Backup 9.0.8
================================================================================
# [MEDIUM] Wrong ACL check wouldn't allow non–Super User accounts from accessing the component
# [LOW] PHP 8 error if the output directory is empty

Akeeba Backup 9.0.7
================================================================================
~ Remove dash from automatically generated random values for archive naming
~ Adjusted padding in download backup modal
+ Increase the maximum Size Quota limit to 1Pb
+ Support for Joomla proxy configuration
# [MEDIUM] Cannot restore on PHP 8 if Two Factor Authentication is enabled in any user account
# [HIGH] Backing up to Box, Dropbox, Google Drive or OneDrive may not be possible if you are using an add-on Download ID

Akeeba Backup 9.0.6
================================================================================
# [HIGH] Legacy front-end backup fails to execute when stepping through the backup with a 404 error
# [MEDIUM] Could not enable encryption for configuration settings
# [LOW] The usage statistics model is not loaded in the control panel page
# [LOW] PHP Warning from the TriggerEvent trait
# [LOW] Added back button after backup completion
# [LOW] Wrong use of double quotes in CLI language file

Akeeba Backup 9.0.5
================================================================================
+ You are given the option to rerun the migration or uninstall Akeeba Backup 8 (with a nifty link) after migrating settings from Akeeba Backup 8.
+ Migration now also imports the Download ID from Akeeba Backup 8
# [HIGH] JavaScript errors due to strict mode in Configuration, Database Filters, Include Folders, Restoration, S3 Import and Transfer Wizard pages

Akeeba Backup 9.0.4
================================================================================
+ CLI Migration command
~ Completely removing the use of the Joomla CMS Filesystem API for writing / copying / moving files because it's too buggy
# [MEDIUM] JSON API getProfiles returns an empty array
# [HIGH] CLI backups always run with profile #1, even if you use the --profile parameter
# [LOW] Downgrading from Pro to Core didn't work correctly
# [LOW] Warning in Manage Backups page if you have deleted the backup profile used to take a backup listed there

Akeeba Backup 9.0.3
================================================================================
# [MEDIUM] Joomla Filesystem API (File / Folder) doesn't work on some servers; preferring native PHP functions instead.
# [HIGH] Does not work on Windows on the latest Joomla 4 RC versions
# [HIGH] Some internal links do not work because of lower/uppercase mix in file names

Akeeba Backup 9.0.2
================================================================================
~ Prevent installation on Joomla 3.
# [HIGH] Core version, regression: Call to a member function rebaseFiltersToSiteDirs() on bool
# [HIGH] yet another last minute, undocumented, backwards incompatible change in Joomla is breaking things.
# [MEDIUM] Extensions not enabled automatically on installation.

Akeeba Backup 9.0.1
================================================================================
# [HIGH] Akeeba Backup Core: immediate error coming from the Dispatcher

Akeeba Backup 9.0.0
================================================================================
! Rewritten with Joomla 4 Core MVC and Bootstrap 5 styling
+ Reset the configuration and filters of backup profiles from the Profiles page
