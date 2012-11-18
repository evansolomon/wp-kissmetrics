window._kmq = window._kmq || [];

(function($, global) {
  global.kissmetrics = {
    _kms: function(url) {
      return setTimeout(function() {
        var d, f, s;
        d = document;
        f = d.getElementsByTagName('script')[0];
        s = d.createElement('script');
        $.extend(s, {
          type: 'text/javascript',
          async: true,
          src: url
        });
        return f.parentNode.insertBefore(s, f);
      }, 1);
    },
    recordEvent: function(name, properties) {
      var prefixedProperties;
      if (properties == null) {
        properties = {};
      }
      prefixedProperties = {};
      $.each(properties, function(property, value) {
        return prefixedProperties["" + name + "|" + property] = value;
      });
      return _kmq.push(['record', name, prefixedProperties]);
    },
    setProperty: function(property) {
      return _kmq.push(['set', property]);
    },
    init: function() {
      var events, kissmetrics, properties, queries;
      kissmetrics = window.kissmetrics_api || {};
      queries = window.kissmetrics_queries || {};
      events = queries.events || {};
      properties = queries.properties || {};
      if (!kissmetrics) {
        return null;
      }
      this._kms('//i.kissmetrics.com/i.js');
      this._kms("//doug1izaerwt3.cloudfront.net/" + kissmetrics.api_key + ".1.js");
      if (kissmetrics.username) {
        _kmq.push(['identify', kissmetrics.username]);
      }
      $.each(events, function() {
        return global.kissmetrics.recordEvent(this.name, this.properties);
      });
      return $.each(properties, function() {
        return global.kissmetrics.setProperty(this);
      });
    }
  };
  return $($.proxy(global.kissmetrics.init, global.kissmetrics));
})(jQuery, window);