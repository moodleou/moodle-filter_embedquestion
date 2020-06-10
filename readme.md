# The embed questions filter [![Build Status](https://travis-ci.com/moodleou/moodle-filter_embedquestion.svg?branch=master)](https://travis-ci.com/moodleou/moodle-filter_embedquestion)

This Moodle text filter that displays questions from the question bank embedded
wherever you can input HTML.

[Here is a more detailed description of the functionality](https://github.com/moodleou/moodle-filter_embedquestion/blob/master/internaldoc/functionality.txt).


## Acknowledgements

This plugin was created by the Open University, UK. http://www.open.ac.uk/


## Installation and set-up

This plugin works best if you also install the associated Atto editor plugin.

### Install from the plugins database

Install from the Moodle plugins database
* https://moodle.org/plugins/filter_embedquestion.
* https://moodle.org/plugins/atto_embedquestion.

### Install using git

Or you can install using git. Type this commands in the root of your Moodle install

    git clone https://github.com/moodleou/moodle-filter_embedquestion.git filter/embedquestion/
    git clone https://github.com/moodleou/moodle-atto_embedquestion.git lib/editor/atto/plugins/embedquestion/
    echo '/filter/embedquestion/' >> .git/info/exclude
    echo '/lib/editor/atto/plugins/embedquestion/' >> .git/info/exclude

Then run the moodle update process
Site administration > Notifications

### Setup

The filter needs to be enabled. Go to
Site administration -> Plugins -> Filters -> Manage filters
and Enable the Embed questions filter.

You probably don't need to change the options on
Site administration -> Plugins -> Filters -> Embed questions,
but you can if necessary.

The Atto editor need to be configured to show the new button. Go to
Site administration > Plugins > Text editors > Atto HTML editor > Atto toolbar settings
and in the Toolbar config field, find the line that starts 'insert =',
and add 'embedquestion' to the list. 
