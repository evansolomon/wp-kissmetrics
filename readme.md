# Kissmetrics For Developers

This is a WordPress plugin.  It's intended to be used by developers, not end users.  There's no user interface.  Instead there is a class that interacts with the Kissmetrics API and a couple of helper functions.  If you want to do something simple, you should checkout the helper functions `kissmetrics_record()` and `kissmetrics_set()`.  If you want to do something more complicated, use the `WP_Kissmetrics` class.

## Installation

Although there's no user interface, this is just a normal plugin.  Install it in your `/wp-content/plugin/` directory.  You can also install it in `/wp-content/mu-plugins/`, depending on your use case.

## Usage

The only way to interact with this plugin is through code, it has no UI.  Before you can record any data, you have to tell Kissmetrics what your API key is.  You can do this either through the `kissmetrics_api_key` filter or the `WP_KISSMETRICS_API_KEY` constant.  Filters will override the constant if you use both.  The same API key constant and filter applies to queries from both PHP and JavaScript.