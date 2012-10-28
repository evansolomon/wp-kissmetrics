# Kissmetrics For Developers

This is a WordPress plugin.  It's intended to be used by developers, not end users.  There's no user interface.  Instead there is a class that interacts with the Kissmetrics API and a couple of helper functions.

## Installation

Although there's no user interface, this is just a normal plugin.  Install it in your `/wp-content/plugin/` directory.  You can also install it in `/wp-content/mu-plugins/`, depending on your use case.

## Usage

The only way to interact with this plugin is through code, it has no UI.  Before you can record any data, you have to tell Kissmetrics what your API key is.  You can do this either through the `kissmetrics_api_key` filter or the `WP_KISSMETRICS_API_KEY` constant.  Filters will override the constant if you use both.  The same API key constant and filter applies to queries from both PHP and JavaScript.

For most data you want to record in PHP, you should use the helper functions `kissmetrics_record()` and `kissmetrics_set()`.  If you need to do something more complicated you can use the `WP_Kissmetrics` class, but you probably don't need to.  JavaScript has its own set of helpers in the `wp.kissmetrics` object.  To enqueue the JavaScript file with the helpers, call `kissmetrics_js()` in PHP.  You can use the same function to record data in JavaScript on page load.