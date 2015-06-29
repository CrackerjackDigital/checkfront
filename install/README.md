Installation
------------

To get things going some helper files are included to be copied on install, otherwise just cherry-pick the bits you want.

YML
===

Copy the 'checkfront.yml' file to your application/_config directory (e.g. 'mysite/_config') and
update the settings to match your dev/test/live accounts in checkfront. They could all
just use the same account if you want. Which config block gets loaded depends on the
constants defined either in your _ss_environment or in the _checkfront.php include file (see below).

PHP
===

Copy the _checkfront.php file to your application root (e.g. 'mysite/')
and include it from your _config.php (after ConfigureFromEnv if you are using it).

You can tailor:
 -  what 'modes' are used when (drives what blocks get loaded in checkfront.yml)
 -  the api version, by default '3.0'


