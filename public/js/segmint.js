/**
 * Segmint SDK v2.0.0
 *
 * Lightweight client for event tracking and audience segmentation.
 *
 * Usage (script tag):
 *
 *   <script src="https://your-segmint-host/js/segmint.js"></script>
 *   <script>
 *     Segmint.init({ token: 'your-project-token', autoTrack: true })
 *       .then(() => {
 *         if (Segmint.visitor.hasSegment('returning-buyer')) {
 *           document.getElementById('hero').innerHTML = 'Welcome back!';
 *         }
 *       });
 *   </script>
 *
 * Usage (ES module):
 *
 *   import Segmint from 'https://your-segmint-host/js/segmint.js';
 *   await Segmint.init({ token: 'your-project-token', autoTrack: true });
 *   if (Segmint.visitor.hasSegment('vip')) showVipBanner();
 *
 * Namespaces:
 *
 *   Segmint.visitor.*  — about the current visitor (track events, read matches)
 *   Segmint.fetch.*    — about the project (retrieve segment catalogue)
 */
(function (root, factory) {
  var sdk = factory();
  root.Segmint = sdk;
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Internal state
  // ---------------------------------------------------------------------------
  var _config = {
    endpoint: null,    // Auto-detected from script src, or set manually
    origin: null,      // Base origin for API calls
    token: null,
    autoTrack: false,  // Automatically send a page-view on init
    debug: false,
    visitorIdKey: 'segmint_vid',
  };

  var _segments = [];        // Cached segment list from the latest response
  var _ready = false;        // True after the first visitor.event() resolves
  var _readyCallbacks = [];  // Queued onReady callbacks

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Get or create a persistent visitor ID stored in localStorage.
   */
  function getVisitorId() {
    var key = _config.visitorIdKey;
    var id;
    try {
      id = localStorage.getItem(key);
    } catch (e) { /* localStorage blocked */ }

    if (!id) {
      id = generateId();
      try {
        localStorage.setItem(key, id);
      } catch (e) { /* ignore */ }
    }
    return id;
  }

  /**
   * Generate a pseudo-random unique ID.
   */
  function generateId() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
      return crypto.randomUUID();
    }
    // Fallback
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (Math.random() * 16) | 0;
      var v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  /**
   * Extract UTM parameters from the current URL.
   */
  function getUtms() {
    var params = new URLSearchParams(window.location.search);
    return {
      utm_source: params.get('utm_source'),
      utm_medium: params.get('utm_medium'),
      utm_campaign: params.get('utm_campaign'),
      utm_content: params.get('utm_content'),
      utm_term: params.get('utm_term'),
    };
  }

  /**
   * Collect page and device metadata.
   */
  function getMetadata() {
    var params = new URLSearchParams(window.location.search);
    var meta = {
      path: window.location.pathname,
      url: window.location.href,
      query: Object.fromEntries(params.entries()),
      title: document.title,
      referrer: document.referrer || null,
      screen: {
        width: window.screen.width,
        height: window.screen.height,
      },
      viewport: {
        width: window.innerWidth,
        height: window.innerHeight,
      },
      device: {
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        platform: navigator.platform,
        user_agent: navigator.userAgent,
        touch: navigator.maxTouchPoints > 0,
      },
      timestamp: new Date().toISOString(),
    };

    // Connection info (if supported)
    if (navigator.connection) {
      meta.connection = {
        effective_type: navigator.connection.effectiveType,
        downlink: navigator.connection.downlink,
        rtt: navigator.connection.rtt,
      };
    }

    return meta;
  }

  /**
   * Auto-detect the API origin from the script's src attribute.
   */
  function detectOrigin() {
    try {
      var scripts = document.querySelectorAll('script[src*="segmint"]');
      for (var i = 0; i < scripts.length; i++) {
        var src = scripts[i].src;
        if (src && src.indexOf('/js/segmint') !== -1) {
          var url = new URL(src);
          return url.origin;
        }
      }
    } catch (e) { /* ignore */ }
    return null;
  }

  /**
   * Update the internal segment cache and fire ready callbacks.
   */
  function updateSegments(segments) {
    _segments = Array.isArray(segments) ? segments : [];
    if (!_ready) {
      _ready = true;
      for (var i = 0; i < _readyCallbacks.length; i++) {
        try { _readyCallbacks[i](_segments); } catch (e) { /* ignore */ }
      }
      _readyCallbacks = [];
    }
  }

  function log() {
    if (_config.debug && typeof console !== 'undefined') {
      console.log.apply(console, ['[Segmint]'].concat(Array.prototype.slice.call(arguments)));
    }
  }

  // ---------------------------------------------------------------------------
  // Visitor namespace — about the current visitor's session
  // ---------------------------------------------------------------------------
  var visitor = {

    /**
     * Send a tracking event and receive matched segments.
     *
     * The returned segments are also cached internally — read them any time
     * with visitor.segments() or visitor.hasSegment().
     *
     * @param  {string} [eventType='page-view']    - The event type.
     * @param  {Object} [eventProperties={}]       - Custom event properties.
     * @return {Promise<Object>} Response with status and matched segments.
     */
    event: function (eventType, eventProperties) {
      eventType = eventType || 'page-view';
      eventProperties = eventProperties || {};

      if (!_config.token) {
        return Promise.reject(new Error('Segmint: call init() before visitor.event().'));
      }

      var payload = {
        token: _config.token,
        visitor_id: getVisitorId(),
        type: eventType,
        url: window.location.href,
        path: window.location.pathname,
        referrer: document.referrer || null,
        title: document.title,
        utms: getUtms(),
        event_properties: eventProperties,
        metadata: getMetadata(),
      };

      log('Tracking', payload);

      return fetch(_config.endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Segmint: HTTP ' + response.status);
          }
          return response.json();
        })
        .then(function (result) {
          updateSegments(result.segments);
          log('Segments', _segments);
          return result;
        })
        .catch(function (err) {
          log('Error', err);
          throw err;
        });
    },

    /**
     * Send a tracking event without blocking. Prefers navigator.sendBeacon
     * for reliable delivery on page unload, falls back to fetch.
     *
     * Note: beacon does not return a response, so segments are not updated.
     *
     * @param {string} [eventType='page-view']
     * @param {Object} [eventProperties={}]
     */
    beacon: function (eventType, eventProperties) {
      eventType = eventType || 'page-view';
      eventProperties = eventProperties || {};

      if (!_config.token) {
        log('Error: call init() before visitor.beacon().');
        return;
      }

      var payload = {
        token: _config.token,
        visitor_id: getVisitorId(),
        type: eventType,
        url: window.location.href,
        path: window.location.pathname,
        referrer: document.referrer || null,
        title: document.title,
        utms: getUtms(),
        event_properties: eventProperties,
        metadata: getMetadata(),
      };

      log('Beacon', payload);

      if (typeof navigator.sendBeacon === 'function') {
        var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        navigator.sendBeacon(_config.endpoint, blob);
      } else {
        fetch(_config.endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
          keepalive: true,
        }).catch(function () {});
      }
    },

    /**
     * Get the cached list of matched segments for this visitor.
     *
     * @return {Object[]} Array of segment objects from the latest event response.
     */
    segments: function () {
      return _segments.slice();
    },

    /**
     * Check whether the current visitor matches a specific segment.
     *
     * @param  {string} slug - Segment slug to check.
     * @return {boolean}
     */
    hasSegment: function (slug) {
      for (var i = 0; i < _segments.length; i++) {
        if (_segments[i] === slug || (_segments[i] && _segments[i].slug === slug)) {
          return true;
        }
      }
      return false;
    },

    /**
     * Get the current visitor ID.
     *
     * @return {string}
     */
    id: function () {
      return getVisitorId();
    },

    /**
     * Reset the visitor ID and clear cached segments (e.g. on logout).
     */
    reset: function () {
      try {
        localStorage.removeItem(_config.visitorIdKey);
      } catch (e) { /* ignore */ }
      _segments = [];
      _ready = false;
      log('Visitor reset');
    },
  };

  // ---------------------------------------------------------------------------
  // Fetch namespace — about the project (read-only)
  // ---------------------------------------------------------------------------
  var fetchNs = {

    /**
     * Retrieve all active segments defined for the project.
     *
     * This is a read-only call to the Segments API. It does not affect
     * the visitor's matched segment cache.
     *
     * @return {Promise<Object[]>} Array of segment objects.
     */
    segments: function () {
      if (!_config.token) {
        return Promise.reject(new Error('Segmint: call init() before fetch.segments().'));
      }

      var url = _config.origin + '/api/segments?token=' + encodeURIComponent(_config.token);

      log('Fetching segments', url);

      return fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Segmint: HTTP ' + response.status);
          }
          return response.json();
        })
        .then(function (segments) {
          log('Available segments', segments);
          return segments;
        })
        .catch(function (err) {
          log('Error', err);
          throw err;
        });
    },
  };

  // ---------------------------------------------------------------------------
  // Public API
  // ---------------------------------------------------------------------------
  var Segmint = {

    /**
     * Initialise the SDK.
     *
     * When autoTrack is true, init() returns a Promise that resolves after the
     * initial page-view is tracked and segments are cached — so you can
     * personalise content immediately:
     *
     *   await Segmint.init({ token: '...', autoTrack: true });
     *   if (Segmint.visitor.hasSegment('vip')) showVipBanner();
     *
     * @param {Object} options
     * @param {string}  options.token      - Project access token (required).
     * @param {string}  [options.endpoint] - Full URL of the tracking API.
     *                                       Defaults to auto-detected from script src.
     * @param {boolean} [options.autoTrack=false] - Send a page-view event immediately.
     * @param {boolean} [options.debug=false]     - Log to console.
     * @param {string}  [options.visitorIdKey]    - localStorage key for visitor ID.
     * @return {Promise<Object>|void} When autoTrack is true, returns a Promise
     *                                 with the tracking result.
     */
    init: function (options) {
      if (!options || !options.token) {
        throw new Error('Segmint.init() requires a token.');
      }

      var origin = options.endpoint
        ? new URL(options.endpoint).origin
        : detectOrigin();

      _config.token = options.token;
      _config.origin = origin;
      _config.endpoint = options.endpoint || (origin ? origin + '/api/event-log/track' : null);
      _config.autoTrack = options.autoTrack || false;
      _config.debug = options.debug || false;
      _segments = [];
      _ready = false;

      if (options.visitorIdKey) {
        _config.visitorIdKey = options.visitorIdKey;
      }

      if (!_config.endpoint) {
        throw new Error(
          'Segmint: could not detect API endpoint. ' +
          'Pass options.endpoint or load the script from your Segmint host.'
        );
      }

      log('Initialised', { endpoint: _config.endpoint, origin: _config.origin, token: _config.token });

      if (_config.autoTrack) {
        return this.visitor.event();
      }
    },

    /**
     * Register a callback that fires once segments are available.
     * If segments are already cached, the callback fires immediately.
     *
     * @param {function} callback - Receives the segments array.
     */
    onReady: function (callback) {
      if (typeof callback !== 'function') return;
      if (_ready) {
        callback(_segments);
      } else {
        _readyCallbacks.push(callback);
      }
    },

    /**
     * Whether the SDK has completed its first visitor.event() call.
     *
     * @return {boolean}
     */
    isReady: function () {
      return _ready;
    },

    /** Visitor namespace — current visitor's session. */
    visitor: visitor,

    /** Fetch namespace — project-level read-only queries. */
    fetch: fetchNs,
  };

  return Segmint;
});
