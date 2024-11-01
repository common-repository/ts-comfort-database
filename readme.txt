=== TS Comfort DB ===
Contributors: tsinf
Tags: database, sql, manager, query, mysql, export, backup, csv, download, table
Requires at least: 5.6
Tested up to: 6.5
Stable Tag: 5.6
Creation time: 04.11.2017
Last updated time: 16.08.2021

Database Manager for WordPress Database with all functions to need to administrate your current used database.

== Description ==
TS Comfort DB is a database manager to manage your current used WordPress database in WordPress Backend.
Do you know? You're a WordPress developer and theres no PHPMyAdmin or something available or you do not have access to this tool?
But you want to debug something or check if your currently developed plugin saves the test data ordinary?
Therefore i created TS Comfort DB.
It has basic database functionality but it`s restricted as much as needed that it has only influence at the current WordPress database.

Features:
* Full Text Search
* Create, Edit and Delete Datasets
* Get Meta Information like Primary Key or Field Type
* Sort by Column
* Table Filter-Functionality
* Backend Post Search
* SQL Exporter
* CSV Exporter
* Unserialize Table-Cells
* JSON Decode Table-Cells

**Since Version 1.0.9 there is an Adminbar Menu, that you can activate in the options to quick access database tables.**

**Since Version 2.0.4 these is possibility change look and feel with another skin and to speed up table overview with de-activating or caching meta information**

== Installation ==
Copy the TS Comfort DB Plugin Folder to your WordPress Plugin Folder and activate it.

== Screenshots ==


== Changelog ==
* 2.0.7
Fix "no error message" when submit fails dataset editor
Remember input data when submit fails dataset editor
Add different Page Titles on different locations

* 2.0.6
Fix wrong download url in file manager again

* 2.0.5
Fix wrong download url in file manager
Fix PHP Warning for calling not static function

* 2.0.4
Implement de-activation of meta-data in table overview. Now loading metadata in table overview can be disabled in the settings
If meta data in table-overview was loaded it is now cached in the browser session storage (as long as tab is open), so when you go back to table overview it is faster loaded
Add Skin Selection and WordPress Skin

* 2.0.3
Fix little graphical issues and add js improvements

* 2.0.2
Add missing menu image

* 2.0.1
Fix problems with folder creation

* 2.0.0
Add table cell context menu
Add SQL-Exporter
Add CSV-Exporter
Changes in JS to provide jQuery 3.x Support

* 1.0.10
Fix errors in table search and table pagination
Fix errors in database core class

* 1.0.9
Add Adminbar Menu

* 1.0.8
Fix problems with NULL Value

* 1.0.7
Add Table Filter in Table Overveiw
Prepare CSS for upcoming features
Add Plugin Version Number to Table Overview Headline
Add PHP Version Number to Table Overview Subheadline


* 1.0.6
Add Global Post Search Functionality
Add Meta Data to Table Overview

* 1.0.5
Add textareas in full text search table for a better data view

* 1.0.4
Add textareas in table for a better data view

* 1.0.3
Fix Table Search when using strings

* 1.0.2
Fix ajax-admin.php Access Error because of restrictions on admin_init "create options" method

* 1.0.1
Fix some Errors in internal SQL






