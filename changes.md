# Change log for the embed questions filter


## Changes in 2.1

* Fix the version number, which was wrong in the 2.0 release.


## Changes in 2.0

* A new option to change the language used for each embedded question. If your Moodle site
  has more than one language pack installed, then when you embed a question there is a
  new option for the language pack to use for that question. So, for example,
  in your "Beginner's French" course you can leave the standard Moodle UI language
  as English, but you can still have the UI buttons like "Submit" inside the embedded questions
  in French.
* Ensure that all character that might appear in the idnubmers of embedded questions work
  (Even though I would recommend keeping the idnumbers simple.)
* Support for the download features in the associated report.


## Changes in 1.9

* Now, if the embedding option for max-mark is changed, in-progress attempts pick up the new value.
* Question editors (those with 'use' capability on the question) get a 'Fill with correct' button.
* Changes to this plugin to support changes in the associated report.
* Fix occasional errors in cron from this plugin.
* Fix a bug where clicking the Edit question link too quickly the form open in the iframe.


## Changes in 1.8

* Allow media players inside embedded questions to go fullscreen.
* Fix embedding of questions whose ids are all digits.


## Changes in 1.7

* Rewrite of the internals to support the new report_embedquestions which stores
  users' interactions with embedded questions so they can be reviewed later.

## Changes in 1.6

* Fix the bug where embedding random questions from a category might show non-embeddable questions.

## Changes in 1.5

* This version works better Moodle 3.6+, where question and categories have a proper 'Id number'
  field on the editing form ([MDL-62708](https://tracker.moodle.org/browse/MDL-62708)).
  This is now used instead of the previous hack of putting the id in the name.
* As part of that, there is a database upgrade script which moves any Ids added
  following the old convention (in the name). Note, it will only do this if the idnumber
  is not already set.
* There is a new option to embed a question picked at random from the selected category,
  rather than picking one specific question.
* The rendering of the question in the ifrmame now goes through this plugin's renderer, so
  so you can override it in your own theme, if you need to. (We did that at the OU.)
* Fixed an accessibility bug where the question iframe did not have a title.

## Changes in 1.4

* Fix a bug where random questions were shown as embeddable.
* Fix a bug where if an error message was displayed (or something else made the browser
  display the iframe contents in quirks mode) the the embedded quiestion would grow without limit.

## Changes in 1.3

* Fix issue #2. In safari, the space taken by embedded questions would grow without limit.
* Performance improvement on pages with many filtered strings.


## Changes in 1.2

* Just coding style fixes


## Changes in 1.1

* Just coding style fixes


## Changes in 1.0

* Initial release of the plugin.
