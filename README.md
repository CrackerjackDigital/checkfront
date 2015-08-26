Checkfront Integration for SilverStripe
---------------------------------------

This module provides interface to the Checkfront api for online booking etc. It is opinionated in that it has some
expectations on how inventory is set up in Checkfront (namely using packages), however if set up as expected then
functionas as follows.

Public packages
===============

Public packages allow anyone viewing the site to book them.

This module allows you to:

-   List available Checkfront packages on your website and link to a booking page for that package
-   Show child items for that package and allow quantity of each to be specified
-   Specify booking dates for the package
-   Create the booking in checkfront and go to the Checkfront hosted payment page

Private packages
================

Private packages are set up the same in checkfront however they differentiate between an 'organiser' and an
'individual' as to what is available for booking, dates etc.

Although they pull from the same Checkfront packages as those available publically they are accessed via generated links
which can be distrubuted to organisers and to individuals to access the package booking page on the site via an access
key.

This modules allows you to:

-   Choose a specific Checkfront package to link to
-   Specify information about the package such as availability dates and payment option (pay now or pay later)
-   Generate a pair of links and an access key to distribute to organisers and individuals.
-   Require entry of correct access key to access the package booking page on your website
-   List package items and booking form
-   Restrict what package items are shown to organisers and individuals (e.g. an individual may not be able to book a
	venue whereas an organiser can)
-   Allow the organiser to pre-book items by quantity and enter his own personal details
-   Allow individuals to book their own items and enter their own personal details
-   Create the booking in Checkfront
-   Either pay immediately via Checkfront hosted booking page for items booked, or 'pay later'

NB: Unfortunately due to limitation of Checkfront API as of release date we can't use event dates added to a 'parent'
package from Checkfront itself to govern availability dates on the site; it appears that you can either have event
dates or child items available via the api but not both at the same time. Mention checkfront support ticket #56403 to
Kris at checkfront support he may be able to update you with some progress on this.

Requirements
============

-   Typical SilverStripe install v3.1.x
-   Checkfront API module composer/composer from packagist (or internally bundled v3.0.0)

	composer require checkfront/checkfront:3.0.0

-   github.com/crackerjackdigital/silverstripe-checkfront (this module)
-   github.com/crackerjackdigital/silverstripe-cryptofier






