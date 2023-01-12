/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "../../../../../../../node_modules/axios/index.js":
/*!********************************************************!*\
  !*** ../../../../../../../node_modules/axios/index.js ***!
  \********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

module.exports = __webpack_require__(/*! ./lib/axios */ "../../../../../../../node_modules/axios/lib/axios.js");

/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/adapters/xhr.js":
/*!*******************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/adapters/xhr.js ***!
  \*******************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");
var settle = __webpack_require__(/*! ./../core/settle */ "../../../../../../../node_modules/axios/lib/core/settle.js");
var cookies = __webpack_require__(/*! ./../helpers/cookies */ "../../../../../../../node_modules/axios/lib/helpers/cookies.js");
var buildURL = __webpack_require__(/*! ./../helpers/buildURL */ "../../../../../../../node_modules/axios/lib/helpers/buildURL.js");
var buildFullPath = __webpack_require__(/*! ../core/buildFullPath */ "../../../../../../../node_modules/axios/lib/core/buildFullPath.js");
var parseHeaders = __webpack_require__(/*! ./../helpers/parseHeaders */ "../../../../../../../node_modules/axios/lib/helpers/parseHeaders.js");
var isURLSameOrigin = __webpack_require__(/*! ./../helpers/isURLSameOrigin */ "../../../../../../../node_modules/axios/lib/helpers/isURLSameOrigin.js");
var createError = __webpack_require__(/*! ../core/createError */ "../../../../../../../node_modules/axios/lib/core/createError.js");
var defaults = __webpack_require__(/*! ../defaults */ "../../../../../../../node_modules/axios/lib/defaults.js");
var Cancel = __webpack_require__(/*! ../cancel/Cancel */ "../../../../../../../node_modules/axios/lib/cancel/Cancel.js");

module.exports = function xhrAdapter(config) {
  return new Promise(function dispatchXhrRequest(resolve, reject) {
    var requestData = config.data;
    var requestHeaders = config.headers;
    var responseType = config.responseType;
    var onCanceled;
    function done() {
      if (config.cancelToken) {
        config.cancelToken.unsubscribe(onCanceled);
      }

      if (config.signal) {
        config.signal.removeEventListener('abort', onCanceled);
      }
    }

    if (utils.isFormData(requestData)) {
      delete requestHeaders['Content-Type']; // Let the browser set it
    }

    var request = new XMLHttpRequest();

    // HTTP basic authentication
    if (config.auth) {
      var username = config.auth.username || '';
      var password = config.auth.password ? unescape(encodeURIComponent(config.auth.password)) : '';
      requestHeaders.Authorization = 'Basic ' + btoa(username + ':' + password);
    }

    var fullPath = buildFullPath(config.baseURL, config.url);
    request.open(config.method.toUpperCase(), buildURL(fullPath, config.params, config.paramsSerializer), true);

    // Set the request timeout in MS
    request.timeout = config.timeout;

    function onloadend() {
      if (!request) {
        return;
      }
      // Prepare the response
      var responseHeaders = 'getAllResponseHeaders' in request ? parseHeaders(request.getAllResponseHeaders()) : null;
      var responseData = !responseType || responseType === 'text' ||  responseType === 'json' ?
        request.responseText : request.response;
      var response = {
        data: responseData,
        status: request.status,
        statusText: request.statusText,
        headers: responseHeaders,
        config: config,
        request: request
      };

      settle(function _resolve(value) {
        resolve(value);
        done();
      }, function _reject(err) {
        reject(err);
        done();
      }, response);

      // Clean up request
      request = null;
    }

    if ('onloadend' in request) {
      // Use onloadend if available
      request.onloadend = onloadend;
    } else {
      // Listen for ready state to emulate onloadend
      request.onreadystatechange = function handleLoad() {
        if (!request || request.readyState !== 4) {
          return;
        }

        // The request errored out and we didn't get a response, this will be
        // handled by onerror instead
        // With one exception: request that using file: protocol, most browsers
        // will return status as 0 even though it's a successful request
        if (request.status === 0 && !(request.responseURL && request.responseURL.indexOf('file:') === 0)) {
          return;
        }
        // readystate handler is calling before onerror or ontimeout handlers,
        // so we should call onloadend on the next 'tick'
        setTimeout(onloadend);
      };
    }

    // Handle browser request cancellation (as opposed to a manual cancellation)
    request.onabort = function handleAbort() {
      if (!request) {
        return;
      }

      reject(createError('Request aborted', config, 'ECONNABORTED', request));

      // Clean up request
      request = null;
    };

    // Handle low level network errors
    request.onerror = function handleError() {
      // Real errors are hidden from us by the browser
      // onerror should only fire if it's a network error
      reject(createError('Network Error', config, null, request));

      // Clean up request
      request = null;
    };

    // Handle timeout
    request.ontimeout = function handleTimeout() {
      var timeoutErrorMessage = config.timeout ? 'timeout of ' + config.timeout + 'ms exceeded' : 'timeout exceeded';
      var transitional = config.transitional || defaults.transitional;
      if (config.timeoutErrorMessage) {
        timeoutErrorMessage = config.timeoutErrorMessage;
      }
      reject(createError(
        timeoutErrorMessage,
        config,
        transitional.clarifyTimeoutError ? 'ETIMEDOUT' : 'ECONNABORTED',
        request));

      // Clean up request
      request = null;
    };

    // Add xsrf header
    // This is only done if running in a standard browser environment.
    // Specifically not if we're in a web worker, or react-native.
    if (utils.isStandardBrowserEnv()) {
      // Add xsrf header
      var xsrfValue = (config.withCredentials || isURLSameOrigin(fullPath)) && config.xsrfCookieName ?
        cookies.read(config.xsrfCookieName) :
        undefined;

      if (xsrfValue) {
        requestHeaders[config.xsrfHeaderName] = xsrfValue;
      }
    }

    // Add headers to the request
    if ('setRequestHeader' in request) {
      utils.forEach(requestHeaders, function setRequestHeader(val, key) {
        if (typeof requestData === 'undefined' && key.toLowerCase() === 'content-type') {
          // Remove Content-Type if data is undefined
          delete requestHeaders[key];
        } else {
          // Otherwise add header to the request
          request.setRequestHeader(key, val);
        }
      });
    }

    // Add withCredentials to request if needed
    if (!utils.isUndefined(config.withCredentials)) {
      request.withCredentials = !!config.withCredentials;
    }

    // Add responseType to request if needed
    if (responseType && responseType !== 'json') {
      request.responseType = config.responseType;
    }

    // Handle progress if needed
    if (typeof config.onDownloadProgress === 'function') {
      request.addEventListener('progress', config.onDownloadProgress);
    }

    // Not all browsers support upload events
    if (typeof config.onUploadProgress === 'function' && request.upload) {
      request.upload.addEventListener('progress', config.onUploadProgress);
    }

    if (config.cancelToken || config.signal) {
      // Handle cancellation
      // eslint-disable-next-line func-names
      onCanceled = function(cancel) {
        if (!request) {
          return;
        }
        reject(!cancel || (cancel && cancel.type) ? new Cancel('canceled') : cancel);
        request.abort();
        request = null;
      };

      config.cancelToken && config.cancelToken.subscribe(onCanceled);
      if (config.signal) {
        config.signal.aborted ? onCanceled() : config.signal.addEventListener('abort', onCanceled);
      }
    }

    if (!requestData) {
      requestData = null;
    }

    // Send the request
    request.send(requestData);
  });
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/axios.js":
/*!************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/axios.js ***!
  \************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "../../../../../../../node_modules/axios/lib/utils.js");
var bind = __webpack_require__(/*! ./helpers/bind */ "../../../../../../../node_modules/axios/lib/helpers/bind.js");
var Axios = __webpack_require__(/*! ./core/Axios */ "../../../../../../../node_modules/axios/lib/core/Axios.js");
var mergeConfig = __webpack_require__(/*! ./core/mergeConfig */ "../../../../../../../node_modules/axios/lib/core/mergeConfig.js");
var defaults = __webpack_require__(/*! ./defaults */ "../../../../../../../node_modules/axios/lib/defaults.js");

/**
 * Create an instance of Axios
 *
 * @param {Object} defaultConfig The default config for the instance
 * @return {Axios} A new instance of Axios
 */
function createInstance(defaultConfig) {
  var context = new Axios(defaultConfig);
  var instance = bind(Axios.prototype.request, context);

  // Copy axios.prototype to instance
  utils.extend(instance, Axios.prototype, context);

  // Copy context to instance
  utils.extend(instance, context);

  // Factory for creating new instances
  instance.create = function create(instanceConfig) {
    return createInstance(mergeConfig(defaultConfig, instanceConfig));
  };

  return instance;
}

// Create the default instance to be exported
var axios = createInstance(defaults);

// Expose Axios class to allow class inheritance
axios.Axios = Axios;

// Expose Cancel & CancelToken
axios.Cancel = __webpack_require__(/*! ./cancel/Cancel */ "../../../../../../../node_modules/axios/lib/cancel/Cancel.js");
axios.CancelToken = __webpack_require__(/*! ./cancel/CancelToken */ "../../../../../../../node_modules/axios/lib/cancel/CancelToken.js");
axios.isCancel = __webpack_require__(/*! ./cancel/isCancel */ "../../../../../../../node_modules/axios/lib/cancel/isCancel.js");
axios.VERSION = (__webpack_require__(/*! ./env/data */ "../../../../../../../node_modules/axios/lib/env/data.js").version);

// Expose all/spread
axios.all = function all(promises) {
  return Promise.all(promises);
};
axios.spread = __webpack_require__(/*! ./helpers/spread */ "../../../../../../../node_modules/axios/lib/helpers/spread.js");

// Expose isAxiosError
axios.isAxiosError = __webpack_require__(/*! ./helpers/isAxiosError */ "../../../../../../../node_modules/axios/lib/helpers/isAxiosError.js");

module.exports = axios;

// Allow use of default import syntax in TypeScript
module.exports["default"] = axios;


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/cancel/Cancel.js":
/*!********************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/cancel/Cancel.js ***!
  \********************************************************************/
/***/ (function(module) {

"use strict";


/**
 * A `Cancel` is an object that is thrown when an operation is canceled.
 *
 * @class
 * @param {string=} message The message.
 */
function Cancel(message) {
  this.message = message;
}

Cancel.prototype.toString = function toString() {
  return 'Cancel' + (this.message ? ': ' + this.message : '');
};

Cancel.prototype.__CANCEL__ = true;

module.exports = Cancel;


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/cancel/CancelToken.js":
/*!*************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/cancel/CancelToken.js ***!
  \*************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var Cancel = __webpack_require__(/*! ./Cancel */ "../../../../../../../node_modules/axios/lib/cancel/Cancel.js");

/**
 * A `CancelToken` is an object that can be used to request cancellation of an operation.
 *
 * @class
 * @param {Function} executor The executor function.
 */
function CancelToken(executor) {
  if (typeof executor !== 'function') {
    throw new TypeError('executor must be a function.');
  }

  var resolvePromise;

  this.promise = new Promise(function promiseExecutor(resolve) {
    resolvePromise = resolve;
  });

  var token = this;

  // eslint-disable-next-line func-names
  this.promise.then(function(cancel) {
    if (!token._listeners) return;

    var i;
    var l = token._listeners.length;

    for (i = 0; i < l; i++) {
      token._listeners[i](cancel);
    }
    token._listeners = null;
  });

  // eslint-disable-next-line func-names
  this.promise.then = function(onfulfilled) {
    var _resolve;
    // eslint-disable-next-line func-names
    var promise = new Promise(function(resolve) {
      token.subscribe(resolve);
      _resolve = resolve;
    }).then(onfulfilled);

    promise.cancel = function reject() {
      token.unsubscribe(_resolve);
    };

    return promise;
  };

  executor(function cancel(message) {
    if (token.reason) {
      // Cancellation has already been requested
      return;
    }

    token.reason = new Cancel(message);
    resolvePromise(token.reason);
  });
}

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
CancelToken.prototype.throwIfRequested = function throwIfRequested() {
  if (this.reason) {
    throw this.reason;
  }
};

/**
 * Subscribe to the cancel signal
 */

CancelToken.prototype.subscribe = function subscribe(listener) {
  if (this.reason) {
    listener(this.reason);
    return;
  }

  if (this._listeners) {
    this._listeners.push(listener);
  } else {
    this._listeners = [listener];
  }
};

/**
 * Unsubscribe from the cancel signal
 */

CancelToken.prototype.unsubscribe = function unsubscribe(listener) {
  if (!this._listeners) {
    return;
  }
  var index = this._listeners.indexOf(listener);
  if (index !== -1) {
    this._listeners.splice(index, 1);
  }
};

/**
 * Returns an object that contains a new `CancelToken` and a function that, when called,
 * cancels the `CancelToken`.
 */
CancelToken.source = function source() {
  var cancel;
  var token = new CancelToken(function executor(c) {
    cancel = c;
  });
  return {
    token: token,
    cancel: cancel
  };
};

module.exports = CancelToken;


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/cancel/isCancel.js":
/*!**********************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/cancel/isCancel.js ***!
  \**********************************************************************/
/***/ (function(module) {

"use strict";


module.exports = function isCancel(value) {
  return !!(value && value.__CANCEL__);
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/Axios.js":
/*!*****************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/Axios.js ***!
  \*****************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");
var buildURL = __webpack_require__(/*! ../helpers/buildURL */ "../../../../../../../node_modules/axios/lib/helpers/buildURL.js");
var InterceptorManager = __webpack_require__(/*! ./InterceptorManager */ "../../../../../../../node_modules/axios/lib/core/InterceptorManager.js");
var dispatchRequest = __webpack_require__(/*! ./dispatchRequest */ "../../../../../../../node_modules/axios/lib/core/dispatchRequest.js");
var mergeConfig = __webpack_require__(/*! ./mergeConfig */ "../../../../../../../node_modules/axios/lib/core/mergeConfig.js");
var validator = __webpack_require__(/*! ../helpers/validator */ "../../../../../../../node_modules/axios/lib/helpers/validator.js");

var validators = validator.validators;
/**
 * Create a new instance of Axios
 *
 * @param {Object} instanceConfig The default config for the instance
 */
function Axios(instanceConfig) {
  this.defaults = instanceConfig;
  this.interceptors = {
    request: new InterceptorManager(),
    response: new InterceptorManager()
  };
}

/**
 * Dispatch a request
 *
 * @param {Object} config The config specific for this request (merged with this.defaults)
 */
Axios.prototype.request = function request(configOrUrl, config) {
  /*eslint no-param-reassign:0*/
  // Allow for axios('example/url'[, config]) a la fetch API
  if (typeof configOrUrl === 'string') {
    config = config || {};
    config.url = configOrUrl;
  } else {
    config = configOrUrl || {};
  }

  if (!config.url) {
    throw new Error('Provided config url is not valid');
  }

  config = mergeConfig(this.defaults, config);

  // Set config.method
  if (config.method) {
    config.method = config.method.toLowerCase();
  } else if (this.defaults.method) {
    config.method = this.defaults.method.toLowerCase();
  } else {
    config.method = 'get';
  }

  var transitional = config.transitional;

  if (transitional !== undefined) {
    validator.assertOptions(transitional, {
      silentJSONParsing: validators.transitional(validators.boolean),
      forcedJSONParsing: validators.transitional(validators.boolean),
      clarifyTimeoutError: validators.transitional(validators.boolean)
    }, false);
  }

  // filter out skipped interceptors
  var requestInterceptorChain = [];
  var synchronousRequestInterceptors = true;
  this.interceptors.request.forEach(function unshiftRequestInterceptors(interceptor) {
    if (typeof interceptor.runWhen === 'function' && interceptor.runWhen(config) === false) {
      return;
    }

    synchronousRequestInterceptors = synchronousRequestInterceptors && interceptor.synchronous;

    requestInterceptorChain.unshift(interceptor.fulfilled, interceptor.rejected);
  });

  var responseInterceptorChain = [];
  this.interceptors.response.forEach(function pushResponseInterceptors(interceptor) {
    responseInterceptorChain.push(interceptor.fulfilled, interceptor.rejected);
  });

  var promise;

  if (!synchronousRequestInterceptors) {
    var chain = [dispatchRequest, undefined];

    Array.prototype.unshift.apply(chain, requestInterceptorChain);
    chain = chain.concat(responseInterceptorChain);

    promise = Promise.resolve(config);
    while (chain.length) {
      promise = promise.then(chain.shift(), chain.shift());
    }

    return promise;
  }


  var newConfig = config;
  while (requestInterceptorChain.length) {
    var onFulfilled = requestInterceptorChain.shift();
    var onRejected = requestInterceptorChain.shift();
    try {
      newConfig = onFulfilled(newConfig);
    } catch (error) {
      onRejected(error);
      break;
    }
  }

  try {
    promise = dispatchRequest(newConfig);
  } catch (error) {
    return Promise.reject(error);
  }

  while (responseInterceptorChain.length) {
    promise = promise.then(responseInterceptorChain.shift(), responseInterceptorChain.shift());
  }

  return promise;
};

Axios.prototype.getUri = function getUri(config) {
  if (!config.url) {
    throw new Error('Provided config url is not valid');
  }
  config = mergeConfig(this.defaults, config);
  return buildURL(config.url, config.params, config.paramsSerializer).replace(/^\?/, '');
};

// Provide aliases for supported request methods
utils.forEach(['delete', 'get', 'head', 'options'], function forEachMethodNoData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, config) {
    return this.request(mergeConfig(config || {}, {
      method: method,
      url: url,
      data: (config || {}).data
    }));
  };
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, data, config) {
    return this.request(mergeConfig(config || {}, {
      method: method,
      url: url,
      data: data
    }));
  };
});

module.exports = Axios;


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/InterceptorManager.js":
/*!******************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/InterceptorManager.js ***!
  \******************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

function InterceptorManager() {
  this.handlers = [];
}

/**
 * Add a new interceptor to the stack
 *
 * @param {Function} fulfilled The function to handle `then` for a `Promise`
 * @param {Function} rejected The function to handle `reject` for a `Promise`
 *
 * @return {Number} An ID used to remove interceptor later
 */
InterceptorManager.prototype.use = function use(fulfilled, rejected, options) {
  this.handlers.push({
    fulfilled: fulfilled,
    rejected: rejected,
    synchronous: options ? options.synchronous : false,
    runWhen: options ? options.runWhen : null
  });
  return this.handlers.length - 1;
};

/**
 * Remove an interceptor from the stack
 *
 * @param {Number} id The ID that was returned by `use`
 */
InterceptorManager.prototype.eject = function eject(id) {
  if (this.handlers[id]) {
    this.handlers[id] = null;
  }
};

/**
 * Iterate over all the registered interceptors
 *
 * This method is particularly useful for skipping over any
 * interceptors that may have become `null` calling `eject`.
 *
 * @param {Function} fn The function to call for each interceptor
 */
InterceptorManager.prototype.forEach = function forEach(fn) {
  utils.forEach(this.handlers, function forEachHandler(h) {
    if (h !== null) {
      fn(h);
    }
  });
};

module.exports = InterceptorManager;


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/buildFullPath.js":
/*!*************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/buildFullPath.js ***!
  \*************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var isAbsoluteURL = __webpack_require__(/*! ../helpers/isAbsoluteURL */ "../../../../../../../node_modules/axios/lib/helpers/isAbsoluteURL.js");
var combineURLs = __webpack_require__(/*! ../helpers/combineURLs */ "../../../../../../../node_modules/axios/lib/helpers/combineURLs.js");

/**
 * Creates a new URL by combining the baseURL with the requestedURL,
 * only when the requestedURL is not already an absolute URL.
 * If the requestURL is absolute, this function returns the requestedURL untouched.
 *
 * @param {string} baseURL The base URL
 * @param {string} requestedURL Absolute or relative URL to combine
 * @returns {string} The combined full path
 */
module.exports = function buildFullPath(baseURL, requestedURL) {
  if (baseURL && !isAbsoluteURL(requestedURL)) {
    return combineURLs(baseURL, requestedURL);
  }
  return requestedURL;
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/createError.js":
/*!***********************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/createError.js ***!
  \***********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var enhanceError = __webpack_require__(/*! ./enhanceError */ "../../../../../../../node_modules/axios/lib/core/enhanceError.js");

/**
 * Create an Error with the specified message, config, error code, request and response.
 *
 * @param {string} message The error message.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The created error.
 */
module.exports = function createError(message, config, code, request, response) {
  var error = new Error(message);
  return enhanceError(error, config, code, request, response);
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/dispatchRequest.js":
/*!***************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/dispatchRequest.js ***!
  \***************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");
var transformData = __webpack_require__(/*! ./transformData */ "../../../../../../../node_modules/axios/lib/core/transformData.js");
var isCancel = __webpack_require__(/*! ../cancel/isCancel */ "../../../../../../../node_modules/axios/lib/cancel/isCancel.js");
var defaults = __webpack_require__(/*! ../defaults */ "../../../../../../../node_modules/axios/lib/defaults.js");
var Cancel = __webpack_require__(/*! ../cancel/Cancel */ "../../../../../../../node_modules/axios/lib/cancel/Cancel.js");

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
function throwIfCancellationRequested(config) {
  if (config.cancelToken) {
    config.cancelToken.throwIfRequested();
  }

  if (config.signal && config.signal.aborted) {
    throw new Cancel('canceled');
  }
}

/**
 * Dispatch a request to the server using the configured adapter.
 *
 * @param {object} config The config that is to be used for the request
 * @returns {Promise} The Promise to be fulfilled
 */
module.exports = function dispatchRequest(config) {
  throwIfCancellationRequested(config);

  // Ensure headers exist
  config.headers = config.headers || {};

  // Transform request data
  config.data = transformData.call(
    config,
    config.data,
    config.headers,
    config.transformRequest
  );

  // Flatten headers
  config.headers = utils.merge(
    config.headers.common || {},
    config.headers[config.method] || {},
    config.headers
  );

  utils.forEach(
    ['delete', 'get', 'head', 'post', 'put', 'patch', 'common'],
    function cleanHeaderConfig(method) {
      delete config.headers[method];
    }
  );

  var adapter = config.adapter || defaults.adapter;

  return adapter(config).then(function onAdapterResolution(response) {
    throwIfCancellationRequested(config);

    // Transform response data
    response.data = transformData.call(
      config,
      response.data,
      response.headers,
      config.transformResponse
    );

    return response;
  }, function onAdapterRejection(reason) {
    if (!isCancel(reason)) {
      throwIfCancellationRequested(config);

      // Transform response data
      if (reason && reason.response) {
        reason.response.data = transformData.call(
          config,
          reason.response.data,
          reason.response.headers,
          config.transformResponse
        );
      }
    }

    return Promise.reject(reason);
  });
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/enhanceError.js":
/*!************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/enhanceError.js ***!
  \************************************************************************/
/***/ (function(module) {

"use strict";


/**
 * Update an Error with the specified config, error code, and response.
 *
 * @param {Error} error The error to update.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The error.
 */
module.exports = function enhanceError(error, config, code, request, response) {
  error.config = config;
  if (code) {
    error.code = code;
  }

  error.request = request;
  error.response = response;
  error.isAxiosError = true;

  error.toJSON = function toJSON() {
    return {
      // Standard
      message: this.message,
      name: this.name,
      // Microsoft
      description: this.description,
      number: this.number,
      // Mozilla
      fileName: this.fileName,
      lineNumber: this.lineNumber,
      columnNumber: this.columnNumber,
      stack: this.stack,
      // Axios
      config: this.config,
      code: this.code,
      status: this.response && this.response.status ? this.response.status : null
    };
  };
  return error;
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/mergeConfig.js":
/*!***********************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/mergeConfig.js ***!
  \***********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

/**
 * Config-specific merge-function which creates a new config-object
 * by merging two configuration objects together.
 *
 * @param {Object} config1
 * @param {Object} config2
 * @returns {Object} New object resulting from merging config2 to config1
 */
module.exports = function mergeConfig(config1, config2) {
  // eslint-disable-next-line no-param-reassign
  config2 = config2 || {};
  var config = {};

  function getMergedValue(target, source) {
    if (utils.isPlainObject(target) && utils.isPlainObject(source)) {
      return utils.merge(target, source);
    } else if (utils.isPlainObject(source)) {
      return utils.merge({}, source);
    } else if (utils.isArray(source)) {
      return source.slice();
    }
    return source;
  }

  // eslint-disable-next-line consistent-return
  function mergeDeepProperties(prop) {
    if (!utils.isUndefined(config2[prop])) {
      return getMergedValue(config1[prop], config2[prop]);
    } else if (!utils.isUndefined(config1[prop])) {
      return getMergedValue(undefined, config1[prop]);
    }
  }

  // eslint-disable-next-line consistent-return
  function valueFromConfig2(prop) {
    if (!utils.isUndefined(config2[prop])) {
      return getMergedValue(undefined, config2[prop]);
    }
  }

  // eslint-disable-next-line consistent-return
  function defaultToConfig2(prop) {
    if (!utils.isUndefined(config2[prop])) {
      return getMergedValue(undefined, config2[prop]);
    } else if (!utils.isUndefined(config1[prop])) {
      return getMergedValue(undefined, config1[prop]);
    }
  }

  // eslint-disable-next-line consistent-return
  function mergeDirectKeys(prop) {
    if (prop in config2) {
      return getMergedValue(config1[prop], config2[prop]);
    } else if (prop in config1) {
      return getMergedValue(undefined, config1[prop]);
    }
  }

  var mergeMap = {
    'url': valueFromConfig2,
    'method': valueFromConfig2,
    'data': valueFromConfig2,
    'baseURL': defaultToConfig2,
    'transformRequest': defaultToConfig2,
    'transformResponse': defaultToConfig2,
    'paramsSerializer': defaultToConfig2,
    'timeout': defaultToConfig2,
    'timeoutMessage': defaultToConfig2,
    'withCredentials': defaultToConfig2,
    'adapter': defaultToConfig2,
    'responseType': defaultToConfig2,
    'xsrfCookieName': defaultToConfig2,
    'xsrfHeaderName': defaultToConfig2,
    'onUploadProgress': defaultToConfig2,
    'onDownloadProgress': defaultToConfig2,
    'decompress': defaultToConfig2,
    'maxContentLength': defaultToConfig2,
    'maxBodyLength': defaultToConfig2,
    'transport': defaultToConfig2,
    'httpAgent': defaultToConfig2,
    'httpsAgent': defaultToConfig2,
    'cancelToken': defaultToConfig2,
    'socketPath': defaultToConfig2,
    'responseEncoding': defaultToConfig2,
    'validateStatus': mergeDirectKeys
  };

  utils.forEach(Object.keys(config1).concat(Object.keys(config2)), function computeConfigValue(prop) {
    var merge = mergeMap[prop] || mergeDeepProperties;
    var configValue = merge(prop);
    (utils.isUndefined(configValue) && merge !== mergeDirectKeys) || (config[prop] = configValue);
  });

  return config;
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/settle.js":
/*!******************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/settle.js ***!
  \******************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var createError = __webpack_require__(/*! ./createError */ "../../../../../../../node_modules/axios/lib/core/createError.js");

/**
 * Resolve or reject a Promise based on response status.
 *
 * @param {Function} resolve A function that resolves the promise.
 * @param {Function} reject A function that rejects the promise.
 * @param {object} response The response.
 */
module.exports = function settle(resolve, reject, response) {
  var validateStatus = response.config.validateStatus;
  if (!response.status || !validateStatus || validateStatus(response.status)) {
    resolve(response);
  } else {
    reject(createError(
      'Request failed with status code ' + response.status,
      response.config,
      null,
      response.request,
      response
    ));
  }
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/core/transformData.js":
/*!*************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/core/transformData.js ***!
  \*************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");
var defaults = __webpack_require__(/*! ./../defaults */ "../../../../../../../node_modules/axios/lib/defaults.js");

/**
 * Transform the data for a request or a response
 *
 * @param {Object|String} data The data to be transformed
 * @param {Array} headers The headers for the request or response
 * @param {Array|Function} fns A single function or Array of functions
 * @returns {*} The resulting transformed data
 */
module.exports = function transformData(data, headers, fns) {
  var context = this || defaults;
  /*eslint no-param-reassign:0*/
  utils.forEach(fns, function transform(fn) {
    data = fn.call(context, data, headers);
  });

  return data;
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/defaults.js":
/*!***************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/defaults.js ***!
  \***************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "../../../../../../../node_modules/axios/lib/utils.js");
var normalizeHeaderName = __webpack_require__(/*! ./helpers/normalizeHeaderName */ "../../../../../../../node_modules/axios/lib/helpers/normalizeHeaderName.js");
var enhanceError = __webpack_require__(/*! ./core/enhanceError */ "../../../../../../../node_modules/axios/lib/core/enhanceError.js");

var DEFAULT_CONTENT_TYPE = {
  'Content-Type': 'application/x-www-form-urlencoded'
};

function setContentTypeIfUnset(headers, value) {
  if (!utils.isUndefined(headers) && utils.isUndefined(headers['Content-Type'])) {
    headers['Content-Type'] = value;
  }
}

function getDefaultAdapter() {
  var adapter;
  if (typeof XMLHttpRequest !== 'undefined') {
    // For browsers use XHR adapter
    adapter = __webpack_require__(/*! ./adapters/xhr */ "../../../../../../../node_modules/axios/lib/adapters/xhr.js");
  } else if (typeof process !== 'undefined' && Object.prototype.toString.call(process) === '[object process]') {
    // For node use HTTP adapter
    adapter = __webpack_require__(/*! ./adapters/http */ "../../../../../../../node_modules/axios/lib/adapters/xhr.js");
  }
  return adapter;
}

function stringifySafely(rawValue, parser, encoder) {
  if (utils.isString(rawValue)) {
    try {
      (parser || JSON.parse)(rawValue);
      return utils.trim(rawValue);
    } catch (e) {
      if (e.name !== 'SyntaxError') {
        throw e;
      }
    }
  }

  return (encoder || JSON.stringify)(rawValue);
}

var defaults = {

  transitional: {
    silentJSONParsing: true,
    forcedJSONParsing: true,
    clarifyTimeoutError: false
  },

  adapter: getDefaultAdapter(),

  transformRequest: [function transformRequest(data, headers) {
    normalizeHeaderName(headers, 'Accept');
    normalizeHeaderName(headers, 'Content-Type');

    if (utils.isFormData(data) ||
      utils.isArrayBuffer(data) ||
      utils.isBuffer(data) ||
      utils.isStream(data) ||
      utils.isFile(data) ||
      utils.isBlob(data)
    ) {
      return data;
    }
    if (utils.isArrayBufferView(data)) {
      return data.buffer;
    }
    if (utils.isURLSearchParams(data)) {
      setContentTypeIfUnset(headers, 'application/x-www-form-urlencoded;charset=utf-8');
      return data.toString();
    }
    if (utils.isObject(data) || (headers && headers['Content-Type'] === 'application/json')) {
      setContentTypeIfUnset(headers, 'application/json');
      return stringifySafely(data);
    }
    return data;
  }],

  transformResponse: [function transformResponse(data) {
    var transitional = this.transitional || defaults.transitional;
    var silentJSONParsing = transitional && transitional.silentJSONParsing;
    var forcedJSONParsing = transitional && transitional.forcedJSONParsing;
    var strictJSONParsing = !silentJSONParsing && this.responseType === 'json';

    if (strictJSONParsing || (forcedJSONParsing && utils.isString(data) && data.length)) {
      try {
        return JSON.parse(data);
      } catch (e) {
        if (strictJSONParsing) {
          if (e.name === 'SyntaxError') {
            throw enhanceError(e, this, 'E_JSON_PARSE');
          }
          throw e;
        }
      }
    }

    return data;
  }],

  /**
   * A timeout in milliseconds to abort a request. If set to 0 (default) a
   * timeout is not created.
   */
  timeout: 0,

  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',

  maxContentLength: -1,
  maxBodyLength: -1,

  validateStatus: function validateStatus(status) {
    return status >= 200 && status < 300;
  },

  headers: {
    common: {
      'Accept': 'application/json, text/plain, */*'
    }
  }
};

utils.forEach(['delete', 'get', 'head'], function forEachMethodNoData(method) {
  defaults.headers[method] = {};
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  defaults.headers[method] = utils.merge(DEFAULT_CONTENT_TYPE);
});

module.exports = defaults;


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/env/data.js":
/*!***************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/env/data.js ***!
  \***************************************************************/
/***/ (function(module) {

module.exports = {
  "version": "0.25.0"
};

/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/bind.js":
/*!*******************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/bind.js ***!
  \*******************************************************************/
/***/ (function(module) {

"use strict";


module.exports = function bind(fn, thisArg) {
  return function wrap() {
    var args = new Array(arguments.length);
    for (var i = 0; i < args.length; i++) {
      args[i] = arguments[i];
    }
    return fn.apply(thisArg, args);
  };
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/buildURL.js":
/*!***********************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/buildURL.js ***!
  \***********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

function encode(val) {
  return encodeURIComponent(val).
    replace(/%3A/gi, ':').
    replace(/%24/g, '$').
    replace(/%2C/gi, ',').
    replace(/%20/g, '+').
    replace(/%5B/gi, '[').
    replace(/%5D/gi, ']');
}

/**
 * Build a URL by appending params to the end
 *
 * @param {string} url The base of the url (e.g., http://www.google.com)
 * @param {object} [params] The params to be appended
 * @returns {string} The formatted url
 */
module.exports = function buildURL(url, params, paramsSerializer) {
  /*eslint no-param-reassign:0*/
  if (!params) {
    return url;
  }

  var serializedParams;
  if (paramsSerializer) {
    serializedParams = paramsSerializer(params);
  } else if (utils.isURLSearchParams(params)) {
    serializedParams = params.toString();
  } else {
    var parts = [];

    utils.forEach(params, function serialize(val, key) {
      if (val === null || typeof val === 'undefined') {
        return;
      }

      if (utils.isArray(val)) {
        key = key + '[]';
      } else {
        val = [val];
      }

      utils.forEach(val, function parseValue(v) {
        if (utils.isDate(v)) {
          v = v.toISOString();
        } else if (utils.isObject(v)) {
          v = JSON.stringify(v);
        }
        parts.push(encode(key) + '=' + encode(v));
      });
    });

    serializedParams = parts.join('&');
  }

  if (serializedParams) {
    var hashmarkIndex = url.indexOf('#');
    if (hashmarkIndex !== -1) {
      url = url.slice(0, hashmarkIndex);
    }

    url += (url.indexOf('?') === -1 ? '?' : '&') + serializedParams;
  }

  return url;
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/combineURLs.js":
/*!**************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/combineURLs.js ***!
  \**************************************************************************/
/***/ (function(module) {

"use strict";


/**
 * Creates a new URL by combining the specified URLs
 *
 * @param {string} baseURL The base URL
 * @param {string} relativeURL The relative URL
 * @returns {string} The combined URL
 */
module.exports = function combineURLs(baseURL, relativeURL) {
  return relativeURL
    ? baseURL.replace(/\/+$/, '') + '/' + relativeURL.replace(/^\/+/, '')
    : baseURL;
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/cookies.js":
/*!**********************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/cookies.js ***!
  \**********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs support document.cookie
    (function standardBrowserEnv() {
      return {
        write: function write(name, value, expires, path, domain, secure) {
          var cookie = [];
          cookie.push(name + '=' + encodeURIComponent(value));

          if (utils.isNumber(expires)) {
            cookie.push('expires=' + new Date(expires).toGMTString());
          }

          if (utils.isString(path)) {
            cookie.push('path=' + path);
          }

          if (utils.isString(domain)) {
            cookie.push('domain=' + domain);
          }

          if (secure === true) {
            cookie.push('secure');
          }

          document.cookie = cookie.join('; ');
        },

        read: function read(name) {
          var match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
          return (match ? decodeURIComponent(match[3]) : null);
        },

        remove: function remove(name) {
          this.write(name, '', Date.now() - 86400000);
        }
      };
    })() :

  // Non standard browser env (web workers, react-native) lack needed support.
    (function nonStandardBrowserEnv() {
      return {
        write: function write() {},
        read: function read() { return null; },
        remove: function remove() {}
      };
    })()
);


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/isAbsoluteURL.js":
/*!****************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/isAbsoluteURL.js ***!
  \****************************************************************************/
/***/ (function(module) {

"use strict";


/**
 * Determines whether the specified URL is absolute
 *
 * @param {string} url The URL to test
 * @returns {boolean} True if the specified URL is absolute, otherwise false
 */
module.exports = function isAbsoluteURL(url) {
  // A URL is considered absolute if it begins with "<scheme>://" or "//" (protocol-relative URL).
  // RFC 3986 defines scheme name as a sequence of characters beginning with a letter and followed
  // by any combination of letters, digits, plus, period, or hyphen.
  return /^([a-z][a-z\d+\-.]*:)?\/\//i.test(url);
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/isAxiosError.js":
/*!***************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/isAxiosError.js ***!
  \***************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

/**
 * Determines whether the payload is an error thrown by Axios
 *
 * @param {*} payload The value to test
 * @returns {boolean} True if the payload is an error thrown by Axios, otherwise false
 */
module.exports = function isAxiosError(payload) {
  return utils.isObject(payload) && (payload.isAxiosError === true);
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/isURLSameOrigin.js":
/*!******************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/isURLSameOrigin.js ***!
  \******************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs have full support of the APIs needed to test
  // whether the request URL is of the same origin as current location.
    (function standardBrowserEnv() {
      var msie = /(msie|trident)/i.test(navigator.userAgent);
      var urlParsingNode = document.createElement('a');
      var originURL;

      /**
    * Parse a URL to discover it's components
    *
    * @param {String} url The URL to be parsed
    * @returns {Object}
    */
      function resolveURL(url) {
        var href = url;

        if (msie) {
        // IE needs attribute set twice to normalize properties
          urlParsingNode.setAttribute('href', href);
          href = urlParsingNode.href;
        }

        urlParsingNode.setAttribute('href', href);

        // urlParsingNode provides the UrlUtils interface - http://url.spec.whatwg.org/#urlutils
        return {
          href: urlParsingNode.href,
          protocol: urlParsingNode.protocol ? urlParsingNode.protocol.replace(/:$/, '') : '',
          host: urlParsingNode.host,
          search: urlParsingNode.search ? urlParsingNode.search.replace(/^\?/, '') : '',
          hash: urlParsingNode.hash ? urlParsingNode.hash.replace(/^#/, '') : '',
          hostname: urlParsingNode.hostname,
          port: urlParsingNode.port,
          pathname: (urlParsingNode.pathname.charAt(0) === '/') ?
            urlParsingNode.pathname :
            '/' + urlParsingNode.pathname
        };
      }

      originURL = resolveURL(window.location.href);

      /**
    * Determine if a URL shares the same origin as the current location
    *
    * @param {String} requestURL The URL to test
    * @returns {boolean} True if URL shares the same origin, otherwise false
    */
      return function isURLSameOrigin(requestURL) {
        var parsed = (utils.isString(requestURL)) ? resolveURL(requestURL) : requestURL;
        return (parsed.protocol === originURL.protocol &&
            parsed.host === originURL.host);
      };
    })() :

  // Non standard browser envs (web workers, react-native) lack needed support.
    (function nonStandardBrowserEnv() {
      return function isURLSameOrigin() {
        return true;
      };
    })()
);


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/normalizeHeaderName.js":
/*!**********************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/normalizeHeaderName.js ***!
  \**********************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

module.exports = function normalizeHeaderName(headers, normalizedName) {
  utils.forEach(headers, function processHeader(value, name) {
    if (name !== normalizedName && name.toUpperCase() === normalizedName.toUpperCase()) {
      headers[normalizedName] = value;
      delete headers[name];
    }
  });
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/parseHeaders.js":
/*!***************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/parseHeaders.js ***!
  \***************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "../../../../../../../node_modules/axios/lib/utils.js");

// Headers whose duplicates are ignored by node
// c.f. https://nodejs.org/api/http.html#http_message_headers
var ignoreDuplicateOf = [
  'age', 'authorization', 'content-length', 'content-type', 'etag',
  'expires', 'from', 'host', 'if-modified-since', 'if-unmodified-since',
  'last-modified', 'location', 'max-forwards', 'proxy-authorization',
  'referer', 'retry-after', 'user-agent'
];

/**
 * Parse headers into an object
 *
 * ```
 * Date: Wed, 27 Aug 2014 08:58:49 GMT
 * Content-Type: application/json
 * Connection: keep-alive
 * Transfer-Encoding: chunked
 * ```
 *
 * @param {String} headers Headers needing to be parsed
 * @returns {Object} Headers parsed into an object
 */
module.exports = function parseHeaders(headers) {
  var parsed = {};
  var key;
  var val;
  var i;

  if (!headers) { return parsed; }

  utils.forEach(headers.split('\n'), function parser(line) {
    i = line.indexOf(':');
    key = utils.trim(line.substr(0, i)).toLowerCase();
    val = utils.trim(line.substr(i + 1));

    if (key) {
      if (parsed[key] && ignoreDuplicateOf.indexOf(key) >= 0) {
        return;
      }
      if (key === 'set-cookie') {
        parsed[key] = (parsed[key] ? parsed[key] : []).concat([val]);
      } else {
        parsed[key] = parsed[key] ? parsed[key] + ', ' + val : val;
      }
    }
  });

  return parsed;
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/spread.js":
/*!*********************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/spread.js ***!
  \*********************************************************************/
/***/ (function(module) {

"use strict";


/**
 * Syntactic sugar for invoking a function and expanding an array for arguments.
 *
 * Common use case would be to use `Function.prototype.apply`.
 *
 *  ```js
 *  function f(x, y, z) {}
 *  var args = [1, 2, 3];
 *  f.apply(null, args);
 *  ```
 *
 * With `spread` this example can be re-written.
 *
 *  ```js
 *  spread(function(x, y, z) {})([1, 2, 3]);
 *  ```
 *
 * @param {Function} callback
 * @returns {Function}
 */
module.exports = function spread(callback) {
  return function wrap(arr) {
    return callback.apply(null, arr);
  };
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/helpers/validator.js":
/*!************************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/helpers/validator.js ***!
  \************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var VERSION = (__webpack_require__(/*! ../env/data */ "../../../../../../../node_modules/axios/lib/env/data.js").version);

var validators = {};

// eslint-disable-next-line func-names
['object', 'boolean', 'number', 'function', 'string', 'symbol'].forEach(function(type, i) {
  validators[type] = function validator(thing) {
    return typeof thing === type || 'a' + (i < 1 ? 'n ' : ' ') + type;
  };
});

var deprecatedWarnings = {};

/**
 * Transitional option validator
 * @param {function|boolean?} validator - set to false if the transitional option has been removed
 * @param {string?} version - deprecated version / removed since version
 * @param {string?} message - some message with additional info
 * @returns {function}
 */
validators.transitional = function transitional(validator, version, message) {
  function formatMessage(opt, desc) {
    return '[Axios v' + VERSION + '] Transitional option \'' + opt + '\'' + desc + (message ? '. ' + message : '');
  }

  // eslint-disable-next-line func-names
  return function(value, opt, opts) {
    if (validator === false) {
      throw new Error(formatMessage(opt, ' has been removed' + (version ? ' in ' + version : '')));
    }

    if (version && !deprecatedWarnings[opt]) {
      deprecatedWarnings[opt] = true;
      // eslint-disable-next-line no-console
      console.warn(
        formatMessage(
          opt,
          ' has been deprecated since v' + version + ' and will be removed in the near future'
        )
      );
    }

    return validator ? validator(value, opt, opts) : true;
  };
};

/**
 * Assert object's properties type
 * @param {object} options
 * @param {object} schema
 * @param {boolean?} allowUnknown
 */

function assertOptions(options, schema, allowUnknown) {
  if (typeof options !== 'object') {
    throw new TypeError('options must be an object');
  }
  var keys = Object.keys(options);
  var i = keys.length;
  while (i-- > 0) {
    var opt = keys[i];
    var validator = schema[opt];
    if (validator) {
      var value = options[opt];
      var result = value === undefined || validator(value, opt, options);
      if (result !== true) {
        throw new TypeError('option ' + opt + ' must be ' + result);
      }
      continue;
    }
    if (allowUnknown !== true) {
      throw Error('Unknown option ' + opt);
    }
  }
}

module.exports = {
  assertOptions: assertOptions,
  validators: validators
};


/***/ }),

/***/ "../../../../../../../node_modules/axios/lib/utils.js":
/*!************************************************************!*\
  !*** ../../../../../../../node_modules/axios/lib/utils.js ***!
  \************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var bind = __webpack_require__(/*! ./helpers/bind */ "../../../../../../../node_modules/axios/lib/helpers/bind.js");

// utils is a library of generic helper functions non-specific to axios

var toString = Object.prototype.toString;

/**
 * Determine if a value is an Array
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Array, otherwise false
 */
function isArray(val) {
  return Array.isArray(val);
}

/**
 * Determine if a value is undefined
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if the value is undefined, otherwise false
 */
function isUndefined(val) {
  return typeof val === 'undefined';
}

/**
 * Determine if a value is a Buffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Buffer, otherwise false
 */
function isBuffer(val) {
  return val !== null && !isUndefined(val) && val.constructor !== null && !isUndefined(val.constructor)
    && typeof val.constructor.isBuffer === 'function' && val.constructor.isBuffer(val);
}

/**
 * Determine if a value is an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an ArrayBuffer, otherwise false
 */
function isArrayBuffer(val) {
  return toString.call(val) === '[object ArrayBuffer]';
}

/**
 * Determine if a value is a FormData
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an FormData, otherwise false
 */
function isFormData(val) {
  return toString.call(val) === '[object FormData]';
}

/**
 * Determine if a value is a view on an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a view on an ArrayBuffer, otherwise false
 */
function isArrayBufferView(val) {
  var result;
  if ((typeof ArrayBuffer !== 'undefined') && (ArrayBuffer.isView)) {
    result = ArrayBuffer.isView(val);
  } else {
    result = (val) && (val.buffer) && (isArrayBuffer(val.buffer));
  }
  return result;
}

/**
 * Determine if a value is a String
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a String, otherwise false
 */
function isString(val) {
  return typeof val === 'string';
}

/**
 * Determine if a value is a Number
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Number, otherwise false
 */
function isNumber(val) {
  return typeof val === 'number';
}

/**
 * Determine if a value is an Object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Object, otherwise false
 */
function isObject(val) {
  return val !== null && typeof val === 'object';
}

/**
 * Determine if a value is a plain Object
 *
 * @param {Object} val The value to test
 * @return {boolean} True if value is a plain Object, otherwise false
 */
function isPlainObject(val) {
  if (toString.call(val) !== '[object Object]') {
    return false;
  }

  var prototype = Object.getPrototypeOf(val);
  return prototype === null || prototype === Object.prototype;
}

/**
 * Determine if a value is a Date
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Date, otherwise false
 */
function isDate(val) {
  return toString.call(val) === '[object Date]';
}

/**
 * Determine if a value is a File
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a File, otherwise false
 */
function isFile(val) {
  return toString.call(val) === '[object File]';
}

/**
 * Determine if a value is a Blob
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Blob, otherwise false
 */
function isBlob(val) {
  return toString.call(val) === '[object Blob]';
}

/**
 * Determine if a value is a Function
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Function, otherwise false
 */
function isFunction(val) {
  return toString.call(val) === '[object Function]';
}

/**
 * Determine if a value is a Stream
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Stream, otherwise false
 */
function isStream(val) {
  return isObject(val) && isFunction(val.pipe);
}

/**
 * Determine if a value is a URLSearchParams object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a URLSearchParams object, otherwise false
 */
function isURLSearchParams(val) {
  return toString.call(val) === '[object URLSearchParams]';
}

/**
 * Trim excess whitespace off the beginning and end of a string
 *
 * @param {String} str The String to trim
 * @returns {String} The String freed of excess whitespace
 */
function trim(str) {
  return str.trim ? str.trim() : str.replace(/^\s+|\s+$/g, '');
}

/**
 * Determine if we're running in a standard browser environment
 *
 * This allows axios to run in a web worker, and react-native.
 * Both environments support XMLHttpRequest, but not fully standard globals.
 *
 * web workers:
 *  typeof window -> undefined
 *  typeof document -> undefined
 *
 * react-native:
 *  navigator.product -> 'ReactNative'
 * nativescript
 *  navigator.product -> 'NativeScript' or 'NS'
 */
function isStandardBrowserEnv() {
  if (typeof navigator !== 'undefined' && (navigator.product === 'ReactNative' ||
                                           navigator.product === 'NativeScript' ||
                                           navigator.product === 'NS')) {
    return false;
  }
  return (
    typeof window !== 'undefined' &&
    typeof document !== 'undefined'
  );
}

/**
 * Iterate over an Array or an Object invoking a function for each item.
 *
 * If `obj` is an Array callback will be called passing
 * the value, index, and complete array for each item.
 *
 * If 'obj' is an Object callback will be called passing
 * the value, key, and complete object for each property.
 *
 * @param {Object|Array} obj The object to iterate
 * @param {Function} fn The callback to invoke for each item
 */
function forEach(obj, fn) {
  // Don't bother if no value provided
  if (obj === null || typeof obj === 'undefined') {
    return;
  }

  // Force an array if not already something iterable
  if (typeof obj !== 'object') {
    /*eslint no-param-reassign:0*/
    obj = [obj];
  }

  if (isArray(obj)) {
    // Iterate over array values
    for (var i = 0, l = obj.length; i < l; i++) {
      fn.call(null, obj[i], i, obj);
    }
  } else {
    // Iterate over object keys
    for (var key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        fn.call(null, obj[key], key, obj);
      }
    }
  }
}

/**
 * Accepts varargs expecting each argument to be an object, then
 * immutably merges the properties of each object and returns result.
 *
 * When multiple objects contain the same key the later object in
 * the arguments list will take precedence.
 *
 * Example:
 *
 * ```js
 * var result = merge({foo: 123}, {foo: 456});
 * console.log(result.foo); // outputs 456
 * ```
 *
 * @param {Object} obj1 Object to merge
 * @returns {Object} Result of all merge properties
 */
function merge(/* obj1, obj2, obj3, ... */) {
  var result = {};
  function assignValue(val, key) {
    if (isPlainObject(result[key]) && isPlainObject(val)) {
      result[key] = merge(result[key], val);
    } else if (isPlainObject(val)) {
      result[key] = merge({}, val);
    } else if (isArray(val)) {
      result[key] = val.slice();
    } else {
      result[key] = val;
    }
  }

  for (var i = 0, l = arguments.length; i < l; i++) {
    forEach(arguments[i], assignValue);
  }
  return result;
}

/**
 * Extends object a by mutably adding to it the properties of object b.
 *
 * @param {Object} a The object to be extended
 * @param {Object} b The object to copy properties from
 * @param {Object} thisArg The object to bind function to
 * @return {Object} The resulting value of object a
 */
function extend(a, b, thisArg) {
  forEach(b, function assignValue(val, key) {
    if (thisArg && typeof val === 'function') {
      a[key] = bind(val, thisArg);
    } else {
      a[key] = val;
    }
  });
  return a;
}

/**
 * Remove byte order marker. This catches EF BB BF (the UTF-8 BOM)
 *
 * @param {string} content with BOM
 * @return {string} content value without BOM
 */
function stripBOM(content) {
  if (content.charCodeAt(0) === 0xFEFF) {
    content = content.slice(1);
  }
  return content;
}

module.exports = {
  isArray: isArray,
  isArrayBuffer: isArrayBuffer,
  isBuffer: isBuffer,
  isFormData: isFormData,
  isArrayBufferView: isArrayBufferView,
  isString: isString,
  isNumber: isNumber,
  isObject: isObject,
  isPlainObject: isPlainObject,
  isUndefined: isUndefined,
  isDate: isDate,
  isFile: isFile,
  isBlob: isBlob,
  isFunction: isFunction,
  isStream: isStream,
  isURLSearchParams: isURLSearchParams,
  isStandardBrowserEnv: isStandardBrowserEnv,
  forEach: forEach,
  merge: merge,
  extend: extend,
  trim: trim,
  stripBOM: stripBOM
};


/***/ }),

/***/ "./src/DashBoard/DashboardPage.js":
/*!****************************************!*\
  !*** ./src/DashBoard/DashboardPage.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _GridBlock__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./GridBlock */ "./src/DashBoard/GridBlock.js");




class DashboardPage extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let blocks = rsssl_settings.blocks;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, blocks.map((block, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_GridBlock__WEBPACK_IMPORTED_MODULE_1__["default"], {
      key: i,
      block: block,
      setShowOnBoardingModal: this.props.setShowOnBoardingModal,
      isApiLoaded: this.props.isAPILoaded,
      fields: this.props.fields,
      highLightField: this.props.highLightField,
      selectMainMenu: this.props.selectMainMenu,
      getFields: this.props.getFields
    })));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (DashboardPage);

/***/ }),

/***/ "./src/DashBoard/GridBlock.js":
/*!************************************!*\
  !*** ./src/DashBoard/GridBlock.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _ProgressBlock__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./ProgressBlock */ "./src/DashBoard/ProgressBlock.js");
/* harmony import */ var _ProgressBlockHeader__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./ProgressBlockHeader */ "./src/DashBoard/ProgressBlockHeader.js");
/* harmony import */ var _ProgressFooter__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ProgressFooter */ "./src/DashBoard/ProgressFooter.js");
/* harmony import */ var _SslLabs__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./SslLabs */ "./src/DashBoard/SslLabs.js");
/* harmony import */ var _SslLabsFooter__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./SslLabsFooter */ "./src/DashBoard/SslLabsFooter.js");
/* harmony import */ var _OtherPlugins__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./OtherPlugins */ "./src/DashBoard/OtherPlugins.js");
/* harmony import */ var _SecurityFeaturesBlock_SecurityFeaturesBlock__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./SecurityFeaturesBlock/SecurityFeaturesBlock */ "./src/DashBoard/SecurityFeaturesBlock/SecurityFeaturesBlock.js");
/* harmony import */ var _SecurityFeaturesBlock_SecurityFeaturesFooter__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./SecurityFeaturesBlock/SecurityFeaturesFooter */ "./src/DashBoard/SecurityFeaturesBlock/SecurityFeaturesFooter.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");













/*
 * Mapping of components, for use in the config array
 * @type {{SslLabs: JSX.Element}}
 */

var dynamicComponents = {
  "SecurityFeaturesBlock": _SecurityFeaturesBlock_SecurityFeaturesBlock__WEBPACK_IMPORTED_MODULE_9__["default"],
  "SecurityFeaturesFooter": _SecurityFeaturesBlock_SecurityFeaturesFooter__WEBPACK_IMPORTED_MODULE_10__["default"],
  "ProgressBlock": _ProgressBlock__WEBPACK_IMPORTED_MODULE_3__["default"],
  "ProgressHeader": _ProgressBlockHeader__WEBPACK_IMPORTED_MODULE_4__["default"],
  "ProgressFooter": _ProgressFooter__WEBPACK_IMPORTED_MODULE_5__["default"],
  "SslLabs": _SslLabs__WEBPACK_IMPORTED_MODULE_6__["default"],
  "SslLabsFooter": _SslLabsFooter__WEBPACK_IMPORTED_MODULE_7__["default"],
  "OtherPlugins": _OtherPlugins__WEBPACK_IMPORTED_MODULE_8__["default"]
};

class GridBlock extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.footerHtml = this.props.block.footer.data;
    this.highLightField = this.highLightField.bind(this);
    this.setBlockProps = this.setBlockProps.bind(this);
    let content = this.props.block.content.data;
    let footer = this.props.block.footer.data;
    this.state = {
      content: '',
      testDisabled: false,
      footerHtml: this.props.block.footer.html,
      BlockProps: [],
      content: content,
      footer: footer
    };
  }
  /*
   * Allow child blocks to set data on the gridblock
   * @param key
   * @param value
   */


  setBlockProps(key, value) {
    let {
      BlockProps
    } = this.state;

    if (!BlockProps.hasOwnProperty(key) || BlockProps[key] !== value) {
      BlockProps[key] = value;
      this.setState({
        BlockProps: BlockProps
      });
    }
  }

  highLightField(fieldId) {
    this.props.highLightField(fieldId);
  }

  render() {
    let {
      content,
      footer,
      BlockProps
    } = this.state;
    let blockData = this.props.block;
    let className = "rsssl-grid-item " + blockData.class + " rsssl-" + blockData.id;

    if (this.props.block.content.type === 'react') {
      content = this.props.block.content.data;
    }

    if (this.props.block.footer.type === 'react') {
      footer = this.props.block.footer.data;
    }

    let DynamicBlockProps = {
      getFields: this.props.getFields,
      saveChangedFields: this.props.saveChangedFields,
      setShowOnBoardingModal: this.props.setShowOnBoardingModal,
      setBlockProps: this.setBlockProps,
      BlockProps: BlockProps,
      runTest: this.runTest,
      fields: this.props.fields,
      isApiLoaded: this.props.isApiLoaded,
      highLightField: this.highLightField,
      selectMainMenu: this.props.selectMainMenu
    };
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: className
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
      className: "rsssl-grid-title rsssl-h4"
    }, blockData.title), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-controls"
    }, blockData.controls && blockData.controls.type === 'url' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: blockData.controls.data
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Instructions", "really-simple-ssl")), blockData.controls && blockData.controls.type === 'html' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-header-html",
      dangerouslySetInnerHTML: {
        __html: blockData.controls.data
      }
    }), blockData.controls && blockData.controls.type === 'react' && wp.element.createElement(dynamicComponents[blockData.controls.data], DynamicBlockProps))), blockData.content.type !== 'react' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content",
      dangerouslySetInnerHTML: {
        __html: content
      }
    }), blockData.content.type === 'react' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content"
    }, wp.element.createElement(dynamicComponents[content], DynamicBlockProps)), blockData.footer.type === 'html' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-footer",
      dangerouslySetInnerHTML: {
        __html: this.footerHtml
      }
    }), blockData.footer.type === 'react' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-footer"
    }, wp.element.createElement(dynamicComponents[footer], DynamicBlockProps)));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (GridBlock);

/***/ }),

/***/ "./src/DashBoard/OtherPlugins.js":
/*!***************************************!*\
  !*** ./src/DashBoard/OtherPlugins.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");






const OtherPlugins = props => {
  const [dataLoaded, setDataLoaded] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [dataUpdated, setDataUpdated] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [pluginData, setPluginData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!dataLoaded) {
      _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest('otherpluginsdata').then(response => {
        response.forEach(function (pluginItem, i) {
          response[i].pluginActionNice = pluginActionNice(pluginItem.pluginAction);
        });
        setPluginData(response);
        setDataLoaded(true);
      });
    }
  });

  const PluginActions = (slug, pluginAction, e) => {
    if (e) e.preventDefault();
    let data = {};
    data.slug = slug;
    data.pluginAction = pluginAction;
    let pluginItem = getPluginData(slug);

    if (pluginAction === 'download') {
      pluginItem.pluginAction = "downloading";
    } else if (pluginAction === 'activate') {
      pluginItem.pluginAction = "activating";
    }

    pluginItem.pluginActionNice = pluginActionNice(pluginItem.pluginAction);
    updatePluginData(slug, pluginItem);

    if (pluginAction === 'installed' || pluginAction === 'upgrade-to-premium') {
      return;
    }

    _utils_api__WEBPACK_IMPORTED_MODULE_2__.doAction('plugin_actions', data).then(response => {
      pluginItem = response;
      updatePluginData(slug, pluginItem);
      PluginActions(slug, pluginItem.pluginAction);
    });
  };

  const getPluginData = slug => {
    return pluginData.filter(pluginItem => {
      return pluginItem.slug === slug;
    })[0];
  };

  const updatePluginData = (slug, newPluginItem) => {
    pluginData.forEach(function (pluginItem, i) {
      if (pluginItem.slug === slug) {
        pluginData[i] = newPluginItem;
      }
    });
    setPluginData(pluginData);
    setDataUpdated(slug + newPluginItem.pluginAction);
  };

  const pluginActionNice = pluginAction => {
    const statuses = {
      'download': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Install", "really-simple-ssl"),
      'activate': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Activate", "really-simple-ssl"),
      'activating': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Activating...", "really-simple-ssl"),
      'downloading': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Downloading...", "really-simple-ssl"),
      'upgrade-to-premium': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Downloading...", "really-simple-ssl")
    };
    return statuses[pluginAction];
  };

  const otherPluginElement = (plugin, i) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: i,
      className: "rsssl-other-plugins-element rsssl-" + plugin.slug
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: plugin.wordpress_url,
      target: "_blank",
      title: plugin.title
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-bullet"
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-other-plugins-content"
    }, plugin.title)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-other-plugin-status"
    }, plugin.pluginAction === 'upgrade-to-premium' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      target: "_blank",
      href: plugin.upgrade_url
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Upgrade", "really-simple-ssl"))), plugin.pluginAction !== 'upgrade-to-premium' && plugin.pluginAction !== 'installed' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "#",
      onClick: e => PluginActions(plugin.slug, plugin.pluginAction, e)
    }, plugin.pluginActionNice)), plugin.pluginAction === 'installed' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Installed", "really-simple-ssl"))));
  };

  if (!dataLoaded) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], {
      lines: "3"
    });
  }

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-other-plugins-container"
  }, pluginData.map((plugin, i) => otherPluginElement(plugin, i))));
};

/* harmony default export */ __webpack_exports__["default"] = (OtherPlugins);

/***/ }),

/***/ "./src/DashBoard/ProgressBlock.js":
/*!****************************************!*\
  !*** ./src/DashBoard/ProgressBlock.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _TaskElement__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./TaskElement */ "./src/DashBoard/TaskElement.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");






class ProgressBlock extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.percentageCompleted = 0;
    this.progressText = '';
    this.filter = 'all';
    this.notices = null;
    this.progressLoaded = false;
    this.fields = this.props.fields;
    this.state = {
      progressText: '',
      filter: 'all',
      notices: null,
      percentageCompleted: 0,
      progressLoaded: false
    };
  }

  componentDidMount() {
    this.getProgressData = this.getProgressData.bind(this);
    this.onCloseTaskHandler = this.onCloseTaskHandler.bind(this);
    this.getProgressData();
  }

  componentDidUpdate() {
    //if a field has changed, we update the progress data as well.
    if (this.fields !== this.props.fields) {
      this.fields = this.props.fields;
      this.getProgressData();
    }
  }

  getStyles() {
    return Object.assign({}, {
      width: this.percentageCompleted + "%"
    });
  }

  getProgressData() {
    _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('progressData', 'refresh').then(response => {
      this.progressText = response.text;
      this.filter = response.filter;
      this.percentageCompleted = response.percentage;
      this.notices = response.notices;
      this.progressLoaded = true;
      this.setState({
        progressLoaded: this.progressLoaded,
        progressText: this.progressText,
        filter: this.filter,
        notices: this.notices,
        percentageCompleted: this.percentageCompleted
      });
      this.props.setBlockProps('notices', this.notices);
    });
  }

  onCloseTaskHandler(e) {
    let button = e.target.closest('button');
    let notice_id = button.getAttribute('data-id');
    let container = button.closest('.rsssl-task-element');

    container.animate({
      marginLeft: ["0px", "-1000px"]
    }, {
      duration: 500,
      easing: "linear",
      iterations: 1,
      fill: "both"
    }).onfinish = function () {
      container.parentElement.removeChild(container);
    };

    let notices = this.props.BlockProps.notices;
    notices = notices.filter(function (notice) {
      return notice.id !== notice_id;
    });
    this.props.setBlockProps('notices', notices);
    return _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('dismiss_task', notice_id).then(response => {
      this.percentageCompleted = response.percentage;
      this.setState({
        percentageCompleted: this.percentageCompleted
      });
    });
  }

  render() {
    let progressBarColor = '';

    if (this.percentageCompleted < 80) {
      progressBarColor += 'rsssl-orange';
    }

    if (!this.progressLoaded) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], {
        lines: "9"
      });
    }

    let filter = 'all';

    if (this.props.BlockProps && this.props.BlockProps.filterStatus) {
      filter = this.props.BlockProps.filterStatus;
    }

    let notices = this.notices;

    if (filter === 'remaining') {
      notices = notices.filter(function (notice) {
        return notice.output.status === 'open';
      });
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress-block"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress-bar"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: 'rsssl-bar ' + progressBarColor,
      style: this.getStyles()
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress-text"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
      className: "rsssl-progress-percentage"
    }, this.percentageCompleted, "%"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", {
      className: "rsssl-progress-text-span"
    }, this.progressText)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-scroll-container"
    }, notices.map((notice, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_TaskElement__WEBPACK_IMPORTED_MODULE_2__["default"], {
      key: i,
      index: i,
      notice: notice,
      getFields: this.props.getFields,
      onCloseTaskHandler: this.onCloseTaskHandler,
      highLightField: this.props.highLightField
    }))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (ProgressBlock);

/***/ }),

/***/ "./src/DashBoard/ProgressBlockHeader.js":
/*!**********************************************!*\
  !*** ./src/DashBoard/ProgressBlockHeader.js ***!
  \**********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);




class ProgressHeader extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.filter = 'all';
  }

  componentDidMount() {
    this.onClickHandler = this.onClickHandler.bind(this);
    this.setState({
      filter: this.filter
    });
  }

  onClickHandler(e) {
    let filter = e.target.getAttribute('data-filter');

    if (filter === 'all' || filter === 'remaining') {
      this.filter = filter;
      this.setState({
        filter: this.filter
      });
      this.props.setBlockProps('filterStatus', filter);
      sessionStorage.rsssl_task_filter = filter;
    }
  }

  render() {
    if (typeof Storage !== "undefined" && sessionStorage.rsssl_task_filter) {
      this.filter = sessionStorage.rsssl_task_filter;
    }

    let all_task_count = 0;
    let open_task_count = 0;
    let notices = [];

    if (this.props.BlockProps && this.props.BlockProps.notices) {
      notices = this.props.BlockProps.notices;
      all_task_count = notices.length;
      let openNotices = notices.filter(function (notice) {
        return notice.output.status === 'open' || notice.output.status === 'warning';
      });
      open_task_count = openNotices.length;
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-task-switcher-container rsssl-active-filter-" + this.filter
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-switcher rsssl-all-tasks",
      onClick: this.onClickHandler,
      htmlFor: "rsssl-all-tasks",
      "data-filter": "all"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("All tasks", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl_task_count"
    }, "(", all_task_count, ")")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-switcher rsssl-remaining-tasks",
      onClick: this.onClickHandler,
      htmlFor: "rsssl-remaining-tasks",
      "data-filter": "remaining"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Remaining tasks", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl_task_count"
    }, "(", open_task_count, ")")));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (ProgressHeader);

/***/ }),

/***/ "./src/DashBoard/ProgressFooter.js":
/*!*****************************************!*\
  !*** ./src/DashBoard/ProgressFooter.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");








const ProgressFooter = props => {
  const [certificateIsValid, setCertificateIsValid] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [sslDataLoaded, SetSslDataLoaded] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('ssl_status_data').then(response => {
      setCertificateIsValid(response.certificate_is_valid);
      SetSslDataLoaded(true);
    });
  }, []);

  const startModal = () => {
    props.setShowOnBoardingModal(true);
  };

  if (!sslDataLoaded) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }

  let redirectValue = props.fields.filter(field => field.id === 'redirect')[0].value;
  let sslEnabled = props.fields.filter(field => field.id === 'ssl_enabled')[0].value;
  let wpconfigFixRequired = rsssl_settings.wpconfig_fix_required;
  let hasMixedContentFixer = props.fields.filter(field => field.id === 'mixed_content_fixer')[0].value;
  let hasRedirect = redirectValue === 'wp_redirect' || redirectValue === 'htaccess';
  let sslStatusText = sslEnabled ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("SSL Activated", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("SSL not activated", "really-simple-ssl");
  let sslStatusIcon = sslEnabled ? 'circle-check' : 'circle-times';
  let sslStatusColor = sslEnabled ? 'green' : 'red';
  let redirectIcon = hasRedirect ? 'circle-check' : 'circle-times';
  let redirectColor = hasRedirect ? 'green' : 'red';
  let mixedContentIcon = hasMixedContentFixer ? 'circle-check' : 'circle-times';
  let mixedContentColor = hasMixedContentFixer ? 'green' : 'red';
  let disabled = wpconfigFixRequired ? 'disabled' : '';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, !sslEnabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    disabled: disabled,
    onClick: () => startModal(),
    className: "button button-primary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Activate SSL", "really-simple-ssl")), rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-footer-left"
  }, "Really Simple SSL Pro ", rsssl_settings.pro_version), !rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: rsssl_settings.upgrade_link,
    target: "_blank",
    className: "button button-default"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Go Pro", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-legend"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: sslStatusIcon,
    color: sslStatusColor
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, sslStatusText)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-legend"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: mixedContentIcon,
    color: mixedContentColor
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Mixed content", "really-simple-ssl"))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-legend"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: redirectIcon,
    color: redirectColor
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("301 redirect", "really-simple-ssl"))));
};

/* harmony default export */ __webpack_exports__["default"] = (ProgressFooter);

/***/ }),

/***/ "./src/DashBoard/SecurityFeaturesBlock/SecurityFeatureBullet.js":
/*!**********************************************************************!*\
  !*** ./src/DashBoard/SecurityFeaturesBlock/SecurityFeatureBullet.js ***!
  \**********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../utils/Icon */ "./src/utils/Icon.js");






class SecurityFeatureBullet extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  componentDidMount() {}

  render() {
    let field = this.props.field;
    let icon = [];
    icon['name'] = field.value == 1 ? 'circle-check' : 'circle-times';
    icon['color'] = field.value == 1 ? 'green' : 'red';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-new-feature"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: icon.name,
      color: icon.color
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-new-feature-label"
    }, field.value == 1 && field.new_features_block.active, field.value != 1 && field.new_features_block.readmore.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_1__["default"], {
      target: "_blank",
      text: field.new_features_block.inactive + ' - ' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("%sRead more%s", "really-simple-ssl"),
      url: field.new_features_block.readmore
    })));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (SecurityFeatureBullet);

/***/ }),

/***/ "./src/DashBoard/SecurityFeaturesBlock/SecurityFeaturesBlock.js":
/*!**********************************************************************!*\
  !*** ./src/DashBoard/SecurityFeaturesBlock/SecurityFeaturesBlock.js ***!
  \**********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _SecurityFeatureBullet__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SecurityFeatureBullet */ "./src/DashBoard/SecurityFeaturesBlock/SecurityFeatureBullet.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);








class SecurityFeaturesBlock extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  componentDidMount() {}

  render() {
    if (this.props.fields && this.props.fields.length == 0) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], null);
    }

    let fields = this.props.fields;
    fields = fields.filter(field => field.new_features_block);
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: 'rsssl-new-features'
    }, fields.map((field, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SecurityFeatureBullet__WEBPACK_IMPORTED_MODULE_2__["default"], {
      key: i,
      index: i,
      field: field,
      fields: fields
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-new-feature-desc"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("Improve WordPress security.", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_4__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("Check our %sdocumentation%s", "really-simple-ssl"),
      url: "https://really-simple-ssl.com/instructions/about-hardening-features"
    }), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_4__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("or use the %sWordPress forum%s.", "really-simple-ssl"),
      url: "https://wordpress.org/support/plugin/really-simple-ssl/"
    }))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (SecurityFeaturesBlock);

/***/ }),

/***/ "./src/DashBoard/SecurityFeaturesBlock/SecurityFeaturesFooter.js":
/*!***********************************************************************!*\
  !*** ./src/DashBoard/SecurityFeaturesBlock/SecurityFeaturesFooter.js ***!
  \***********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);





class SecurityFeaturesFooter extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "button button-default",
      href: "#settings"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Settings', 'really-simple-ssl'));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (SecurityFeaturesFooter);

/***/ }),

/***/ "./src/DashBoard/SslLabs.js":
/*!**********************************!*\
  !*** ./src/DashBoard/SslLabs.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");









const SslLabs = props => {
  const [sslData, setSslData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [endpointData, setEndpointData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [dataLoaded, setDataLoaded] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const hasRunOnce = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const clearCache = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const requestActive = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const intervalId = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!dataLoaded) {
      _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('ssltest_get').then(response => {
        if (response.hasOwnProperty('host')) {
          let data = processSslData(response);
          setSslData(data);
          setEndpointData(data.endpointData);
          setDataLoaded(true);
        }
      });
    }
  });

  const neverScannedYet = () => {
    return !sslData;
  };

  const isLocalHost = () => {
    //         return false;
    return window.location.host.indexOf('localhost') !== -1;
  };

  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    if (isLocalHost()) return;
    let status = props.BlockProps.hasOwnProperty('sslScan') ? props.BlockProps['sslScan'] : false;

    if (status === 'active' && sslData.summary && sslData.summary.progress >= 100) {
      clearCache.current = true;
      hasRunOnce.current = false;
      setSslData(false);
      setEndpointData(false);
    }

    if (status === 'active' && sslData.status === 'ERROR') {
      clearCache.current = true;
      setSslData(false);
      setEndpointData(false);
    }

    let scanInComplete = sslData && sslData.status !== 'READY';
    let userClickedStartScan = status === 'active';
    if (clearCache.current) scanInComplete = true;
    let hasErrors = sslData.errors || sslData.status === 'ERROR';
    let startScan = !hasErrors && (scanInComplete || userClickedStartScan);

    if (!requestActive.current && startScan) {
      props.setBlockProps('sslScan', 'active');
      requestActive.current = true;

      if (!hasRunOnce.current) {
        runSslTest();
        intervalId.current = setInterval(function () {
          runSslTest();
        }, 3000);
        hasRunOnce.current = true;
      }
    } else if (sslData && sslData.status === 'READY') {
      props.setBlockProps('sslScan', 'completed');
      clearInterval(intervalId.current);
    }
  });

  const runSslTest = () => {
    getSslLabsData().then(sslData => {
      if (sslData && sslData.status === 'ERROR') {
        sslData = processSslData(sslData);
        setSslData(sslData);
        props.setBlockProps('sslScan', 'completed');
        clearInterval(intervalId.current);
      } else if (sslData.endpoints && sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'Ready').length > 0) {
        let completedEndpoints = sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'Ready');
        let lastCompletedEndpointIndex = completedEndpoints.length - 1;
        let lastCompletedEndpoint = completedEndpoints[lastCompletedEndpointIndex];
        let ipAddress = lastCompletedEndpoint.ipAddress;
        getEndpointData(ipAddress).then(response => {
          if (!response.errors && endpointData) {
            //if the endpoint already is stored, replace it.
            let foundEndpoint = false;
            endpointData.forEach(function (endpoint, i) {
              if (endpoint.ipAddress === response.ipAddress) {
                endpointData[i] = response;
                foundEndpoint = true;
              }
            });

            if (!foundEndpoint) {
              endpointData[endpointData.length] = response;
            }

            setEndpointData(endpointData);
            sslData.endpointData = endpointData;
          }

          if (!sslData.errors) {
            _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('store_ssl_labs', sslData).then(response => {});
          }

          sslData = processSslData(sslData);
          setSslData(sslData);
          requestActive.current = false;
        });
      } else {
        //if there are no errors, this is the first request. We reset the endpoint data we have.
        setEndpointData([]);
        sslData.endpointData = endpointData;
        sslData = processSslData(sslData);
        setSslData(sslData);

        if (!sslData.errors) {
          _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('store_ssl_labs', sslData).then(response => {});
        }

        requestActive.current = false;
      }
    });
  };

  const processSslData = sslData => {
    let totalProgress = 100;
    let progress = sslData.progress ? sslData.progress : 0;
    let startTime = sslData.startTime ? sslData.startTime : '';
    let statusMessage = sslData.statusMessage ? sslData.statusMessage : '';
    let grade = sslData.grade ? sslData.grade : '?';
    let ipAddress = '';

    if (sslData.endpoints) {
      totalProgress = sslData.endpoints.length * 100;
      let completedEndpoints = sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'Ready');
      let completedEndpointsLength = completedEndpoints.length;
      let lastCompletedEndpoint = completedEndpoints[completedEndpointsLength - 1];
      let activeEndpoint = sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'In progress')[0];
      let activeEndpointProgress = 0;

      if (activeEndpoint) {
        activeEndpointProgress = activeEndpoint.progress ? activeEndpoint.progress : 0;
        statusMessage = activeEndpoint.statusDetailsMessage;
        ipAddress = activeEndpoint.ipAddress;
      }

      if (lastCompletedEndpoint) grade = lastCompletedEndpoint.grade;
      progress = (completedEndpointsLength * 100 + activeEndpointProgress) / sslData.endpoints.length;
    }

    if (sslData.errors) {
      grade = '?';
      statusMessage = sslData.errors[0].message;
      progress = 100;
    }

    let summary = {};

    if (progress >= 100) {
      props.setBlockProps('sslScan', 'completed');
    }

    summary.grade = grade;
    summary.startTime = startTime;
    summary.statusMessage = statusMessage;
    summary.ipAddress = ipAddress;
    summary.progress = progress;
    sslData.summary = summary;
    return sslData;
  };

  const getEndpointData = ipAddress => {
    const host = window.location.host; //         const host = "ziprecipes.net";

    const url = 'https://api.ssllabs.com/api/v3/getEndpointData?host=' + host + '&s=' + ipAddress;
    let data = {};
    data.url = url;
    return _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('ssltest_run', data).then(response => {
      return JSON.parse(response);
    });
  };

  const getSslLabsData = e => {
    let clearCacheUrl = '';

    if (clearCache.current) {
      clearCache.current = false;
      clearCacheUrl = '&startNew=on';
      setSslData(false);
    }

    const host = window.location.host; //         const host = "ziprecipes.net";

    const url = "https://api.ssllabs.com/api/v3/analyze?host=" + host + clearCacheUrl;
    let data = {};
    data.url = url;
    return _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('ssltest_run', data).then(response => {
      return JSON.parse(response);
    });
  };

  const getStyles = () => {
    let progress = 0;

    if (sslData && sslData.summary.progress) {
      progress = sslData.summary.progress;
    } else if (progress == 0 && props.BlockProps['sslScan'] === 'active') {
      progress = 5;
    }

    return Object.assign({}, {
      width: progress + "%"
    });
  };

  const hasHSTS = () => {
    let status = 'processing';

    if (neverScannedYet()) {
      status = 'inactive';
    }

    if (endpointData && endpointData.length > 0) {
      let failedData = endpointData.filter(function (endpoint) {
        return endpoint.details.hstsPolicy.status !== 'present';
      });
      status = failedData.length > 0 ? 'error' : 'success';
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, status === 'inactive' && scoreSnippet("rsssl-test-inactive", "HSTS"), status === 'processing' && scoreSnippet("rsssl-test-processing", "HSTS..."), status === 'error' && scoreSnippet("rsssl-test-error", "No HSTS header"), status === 'success' && scoreSnippet("rsssl-test-success", "HSTS header detected"));
  };

  const cipherStrength = () => {
    //         Start with the score of the strongest cipher.
    //         Add the score of the weakest cipher.
    //         Divide the total by 2.
    let rating = 0;
    let ratingClass = 'rsssl-test-processing';

    if (neverScannedYet()) {
      ratingClass = 'rsssl-test-inactive';
    }

    if (endpointData && endpointData.length > 0) {
      status = 'success';
      let weakest = 256;
      let strongest = 128;
      endpointData.forEach(function (endpoint, i) {
        endpoint.details.suites.forEach(function (suite, j) {
          suite.list.forEach(function (cipher, j) {
            weakest = cipher.cipherStrength < weakest ? cipher.cipherStrength : weakest;
            strongest = cipher.cipherStrength > strongest ? cipher.cipherStrength : strongest;
          });
        });
      });
      rating = (getCypherRating(weakest) + getCypherRating(strongest)) / 2;
      rating = Math.round(rating);
      ratingClass = rating > 70 ? "rsssl-test-success" : "rsssl-test-error";
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, scoreSnippet(ratingClass, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Cipher strength", "really-simple-ssl") + ' ' + rating + '%'));
  };
  /*
  * https://github.com/ssllabs/research/wiki/SSL-Server-Rating-Guide#Certificate-strength
  */


  const getCypherRating = strength => {
    let score = 0;

    if (strength == 0) {
      score = 0;
    } else if (strength < 128) {
      score = 20;
    } else if (strength < 256) {
      score = 80;
    } else {
      score = 100;
    }

    return score;
  };

  const certificateStatus = () => {
    let status = 'processing';

    if (neverScannedYet()) {
      status = 'inactive';
    }

    if (endpointData && endpointData.length > 0) {
      let failedData = endpointData.filter(function (endpoint) {
        return endpoint.grade.indexOf('A') === -1;
      });
      status = failedData.length > 0 ? 'error' : 'success';
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, status === 'inactive' && scoreSnippet("rsssl-test-inactive", "Certificate"), status === 'processing' && scoreSnippet("rsssl-test-processing", "Certificate..."), status === 'error' && !hasErrors && scoreSnippet("rsssl-test-error", "Certificate issue"), status === 'success' && scoreSnippet("rsssl-test-success", "Valid certificate"));
  };

  const scoreSnippet = (className, content) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-score-container"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-score-snippet " + className
    }, content));
  };

  const supportsTlS11 = () => {
    let status = 'processing';

    if (neverScannedYet()) {
      status = 'inactive';
    }

    if (endpointData && endpointData.length > 0) {
      status = 'success';
      endpointData.forEach(function (endpoint, i) {
        endpoint.details.protocols.forEach(function (protocol, j) {
          if (protocol.version === '1.1') status = 'error';
        });
      });
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, status === 'inactive' && scoreSnippet("rsssl-test-inactive", "Protocol support"), status === 'processing' && scoreSnippet("rsssl-test-processing", "Protocol support..."), status === 'error' && scoreSnippet("rsssl-test-error", "Supports TLS 1.1"), status === 'success' && scoreSnippet("rsssl-test-success", "No TLS 1.1"));
  };

  let sslClass = 'rsssl-inactive';
  let progress = sslData ? sslData.summary.progress : 0;
  let startTime = sslData ? sslData.summary.startTime : false;
  let startTimeNice = '';

  if (startTime) {
    let newDate = new Date();
    newDate.setTime(startTime);
    startTimeNice = newDate.toLocaleString();
  } else {
    startTimeNice = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("No test started yet", "really-simple-ssl");
  }

  let statusMessage = sslData ? sslData.summary.statusMessage : false;
  let grade = sslData ? sslData.summary.grade : '?';
  let ipAddress = sslData ? sslData.summary.ipAddress : '';

  if (sslData && sslData.status === 'READY') {
    if (grade.indexOf('A') !== -1) {
      sslClass = "rsssl-success";
    } else {
      sslClass = "rsssl-error";
    }
  }

  if (neverScannedYet()) {
    sslClass = "rsssl-inactive";
  }

  let gradeClass = neverScannedYet() ? 'inactive' : grade;
  let host = window.location.protocol + "//" + window.location.host;
  let url = 'https://www.ssllabs.com/analyze.html?d=' + encodeURIComponent(host);
  let hasErrors = false;
  let errorMessage = '';
  let sslStatusColor = 'black';

  if (isLocalHost()) {
    hasErrors = true;
    sslStatusColor = 'red';
    errorMessage = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Not available on localhost", "really-simple-ssl");
  } else if (sslData && (sslData.errors || sslData.status === 'ERROR')) {
    hasErrors = true;
    sslStatusColor = 'red';
    errorMessage = statusMessage;
  } else if (sslData && progress < 100) {
    hasErrors = true;
    sslStatusColor = 'orange';
    errorMessage = statusMessage;
  }

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: sslClass
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-gridblock-progress-container " + sslClass
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-gridblock-progress",
    style: getStyles()
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-ssl-test-container " + sslClass
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-ssl-test "
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-ssl-test-information"
  }, supportsTlS11(), hasHSTS(), certificateStatus(), cipherStrength()), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-ssl-test-grade rsssl-grade-" + gradeClass
  }, !neverScannedYet() && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, grade), neverScannedYet() && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null)))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-details"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail-icon"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: "info",
    color: sslStatusColor
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail rsssl-status-" + sslStatusColor
  }, hasErrors && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, errorMessage), !hasErrors && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("What does my score mean?", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://really-simple-ssl.com/instructions/about-ssl-labs/",
    target: "_blank"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Read more", "really-simple-ssl"))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-details"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail-icon"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: "list",
    color: "black"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Last check:", "really-simple-ssl"), "\xA0", startTimeNice)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-details"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail-icon"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: "external-link",
    color: "black"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: url,
    target: "_blank"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("View detailed report on Qualys SSL Labs", "really-simple-ssl")))));
};

/* harmony default export */ __webpack_exports__["default"] = (SslLabs);

/***/ }),

/***/ "./src/DashBoard/SslLabsFooter.js":
/*!****************************************!*\
  !*** ./src/DashBoard/SslLabsFooter.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);




const SslLabsFooter = props => {
  const startScan = () => {
    props.setBlockProps('sslScan', 'active');
  };

  let status = props.BlockProps && props.BlockProps.hasOwnProperty('sslScan') ? props.BlockProps['sslScan'] : false;
  let disabled = status === 'active' || window.location.host.indexOf('localhost') !== -1;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    disabled: disabled,
    onClick: e => startScan(e),
    className: "button button-default"
  }, status === 'paused' && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Continue SSL Health check", "really-simple-ssl"), status !== 'paused' && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Check SSL Health", "really-simple-ssl")));
};

/* harmony default export */ __webpack_exports__["default"] = (SslLabsFooter);

/***/ }),

/***/ "./src/DashBoard/TaskElement.js":
/*!**************************************!*\
  !*** ./src/DashBoard/TaskElement.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _Settings_Notices__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Settings/Notices */ "./src/Settings/Notices.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");









class TaskElement extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  handleClick() {
    this.props.highLightField(this.props.notice.output.highlight_field_id);
  }

  handleClearCache(cache_id) {
    let data = {};
    data.cache_id = cache_id;
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.doAction('clear_cache', data).then(response => {
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Re-started test', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_clear_cache',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_6__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_clear_cache');
      });
      this.props.getFields();
    });
  }

  componentDidMount() {
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    let notice = this.props.notice;
    let premium = notice.output.icon === 'premium'; //treat links to rsssl.com and internal links different.

    let urlIsExternal = notice.output.url && notice.output.url.indexOf('really-simple-ssl.com') !== -1;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-task-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: 'rsssl-task-status rsssl-' + notice.output.icon
    }, notice.output.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "rsssl-task-message",
      dangerouslySetInnerHTML: {
        __html: notice.output.msg
      }
    }), urlIsExternal && notice.output.url && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      target: "_blank",
      href: notice.output.url
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("More info", "really-simple-ssl")), notice.output.clear_cache_id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-enable button button-secondary",
      onClick: () => this.handleClearCache(notice.output.clear_cache_id)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Re-check", "really-simple-ssl")), !premium && !urlIsExternal && notice.output.url && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-task-enable button button-secondary",
      href: notice.output.url
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Fix", "really-simple-ssl")), !premium && notice.output.highlight_field_id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-enable button button-secondary",
      onClick: this.handleClick
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Fix", "really-simple-ssl")), notice.output.plusone && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-plusone"
    }, "1"), notice.output.dismissible && notice.output.status !== 'completed' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-task-dismiss"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      "data-id": notice.id,
      onClick: this.props.onCloseTaskHandler
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_2__["default"], {
      name: "times"
    }))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (TaskElement);

/***/ }),

/***/ "./src/Header.js":
/*!***********************!*\
  !*** ./src/Header.js ***!
  \***********************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_getAnchor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/getAnchor */ "./src/utils/getAnchor.js");
/* harmony import */ var _Settings_Notices__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Settings/Notices */ "./src/Settings/Notices.js");






class Header extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  handleClick(menuId) {
    this.props.selectMainMenu(menuId);
  }

  componentDidMount() {
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    let plugin_url = rsssl_settings.plugin_url;
    let active_menu_item = this.props.selectedMainMenuItem;
    var menu = Object.values(this.props.superMenu);
    menu = menu.filter(item => item !== null); //filter out hidden menus if not in the anchor

    let anchor = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_2__["default"])('main');
    menu = menu.filter(item => !item.default_hidden || anchor === item.id);
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header-container"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
      className: "rsssl-logo",
      src: plugin_url + "assets/img/really-simple-ssl-logo.svg",
      alt: "Really Simple SSL logo"
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header-left"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("nav", {
      className: "rsssl-header-menu"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, menu.map((menu_item, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
      key: i
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: active_menu_item === menu_item.id ? 'active' : '',
      onClick: () => this.handleClick(menu_item.id),
      href: "#" + menu_item.id.toString()
    }, menu_item.title)))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header-right"
    }, !rsssl_settings.le_generated_by_rsssl && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-knowledge-base-link",
      href: "https://really-simple-ssl.com/knowledge-base",
      target: "_blank"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Documentation", "really-simple-ssl")), rsssl_settings.le_generated_by_rsssl && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: rsssl_settings.letsencrypt_url
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Let's Encrypt", "really-simple-ssl")), rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "https://wordpress.org/support/plugin/really-simple-ssl/",
      className: "button button-black",
      target: "_blank"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Support", "really-simple-ssl")), !rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: rsssl_settings.upgrade_link,
      className: "button button-black",
      target: "_blank"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Go Pro", "really-simple-ssl")))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Settings_Notices__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "rsssl-wizard-notices"
    }));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Header);

/***/ }),

/***/ "./src/LetsEncrypt/Activate.js":
/*!*************************************!*\
  !*** ./src/LetsEncrypt/Activate.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _Onboarding_Onboarding__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Onboarding/Onboarding */ "./src/Onboarding/Onboarding.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);










const Activate = props => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-lets-encrypt-tests"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Onboarding_Onboarding__WEBPACK_IMPORTED_MODULE_5__["default"], {
    selectMainMenu: props.selectMainMenu
  }));
};

/* harmony default export */ __webpack_exports__["default"] = (Activate);

/***/ }),

/***/ "./src/LetsEncrypt/Directories.js":
/*!****************************************!*\
  !*** ./src/LetsEncrypt/Directories.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _Settings_Notices__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Settings/Notices */ "./src/Settings/Notices.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__);












const Directories = props => {
  const action = props.action;
  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    if (action.action === 'challenge_directory_reachable' && action.status === 'error') {
      props.addHelp(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The challenge directory is used to verify the domain ownership.", "really-simple-ssl"));
    }

    if (action.action === 'check_key_directory' && action.status === 'error') {
      props.addHelp(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The key directory is needed to store the generated keys.", "really-simple-ssl") + ' ' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("By placing it outside the root folder, it is not publicly accessible.", "really-simple-ssl"));
    }

    if (action.action === 'check_certs_directory' && action.status === 'error') {
      props.addHelp(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The certificate will get stored in this directory.", "really-simple-ssl") + ' ' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("By placing it outside the root folder, it is not publicly accessible.", "really-simple-ssl"));
    }
  });

  if (!action) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }

  const handleSwitchToDNS = () => {
    props.updateField('verification_type', 'dns');
    return _utils_api__WEBPACK_IMPORTED_MODULE_2__.runLetsEncryptTest('update_verification_type', 'dns').then(response => {
      props.selectMenu('le-dns-verification');
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switched to DNS', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_switched_to_dns',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_6__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_switched_to_dns');
      });
    });
  };

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, action.status === 'error' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Next step", "really-simple-ssl")), action.status === 'error' && action.action === 'challenge_directory_reachable' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If the challenge directory cannot be created, or is not reachable, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to DNS verification', 'really-simple-ssl'))), action.status !== 'error' && rsssl_settings.hosting_dashboard === 'cpanel' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__["default"], {
    target: "_blank",
    text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If you also want to secure subdomains like mail.domain.com, cpanel.domain.com, you have to use the %sDNS%s challenge.", "really-simple-ssl"),
    url: "https://really-simple-ssl.com/lets-encrypt-authorization-with-dns"
  }), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Please note that auto-renewal with a DNS challenge might not be possible.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to DNS verification', 'really-simple-ssl'))), action.status === 'error' && action.action === 'check_challenge_directory' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Create a challenge directory", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Navigate in FTP or File Manager to the root of your WordPress installation:", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Create a folder called “.well-known”', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Inside the folder called “.well-known” create a new folder called “acme-challenge”, with 644 writing permissions.', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Click the refresh button.', 'really-simple-ssl'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Or you can switch to DNS verification", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If the challenge directory cannot be created, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to DNS verification', 'really-simple-ssl'))), action.status === 'error' && action.action === 'check_key_directory' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Create a key directory", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Create a folder called “ssl”', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Inside the folder called “ssl” create a new folder called “keys”, with 644 writing permissions.', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Click the refresh button.', 'really-simple-ssl')))), action.status === 'error' && action.action === 'check_certs_directory' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Create a certs directory", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Create a folder called “ssl”', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Inside the folder called “ssl” create a new folder called “certs”, with 644 writing permissions.', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Click the refresh button.', 'really-simple-ssl')))));
};

/* harmony default export */ __webpack_exports__["default"] = (Directories);

/***/ }),

/***/ "./src/LetsEncrypt/DnsVerification.js":
/*!********************************************!*\
  !*** ./src/LetsEncrypt/DnsVerification.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _Settings_Notices__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Settings/Notices */ "./src/Settings/Notices.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__);












const DnsVerification = props => {
  const action = props.action;
  const [tokens, setTokens] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    if (action && action.action === 'challenge_directory_reachable' && action.status === 'error') {
      props.addHelp(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The challenge directory is used to verify the domain ownership.", "really-simple-ssl"));
    }

    let newTokens = action ? action.output : false;

    if (typeof newTokens === "undefined" || newTokens.length === 0) {
      newTokens = false;
    }

    if (newTokens) {
      setTokens(newTokens);
    }
  });

  const handleSwitchToDir = () => {
    props.updateField('verification_type', 'dir');
    return _utils_api__WEBPACK_IMPORTED_MODULE_2__.runLetsEncryptTest('update_verification_type', 'dir').then(response => {
      props.selectMenu('le-directories');
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switched to directory', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_switched_to_dns',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_7__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_switched_to_dns');
      });
    });
  };

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, tokens && tokens.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Next step", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Add the following token as text record to your DNS records. We recommend to use a short TTL during installation, in case you need to change it.", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_6__["default"], {
    target: "_blank",
    text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Read more", "really-simple-ssl"),
    url: "https://really-simple-ssl.com/how-to-add-a-txt-record-to-dns"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-text-records"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: 0
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-domain"
  }, "@/", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("domain", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-field"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Value", "really-simple-ssl"))), tokens.map((tokenData, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: i + 1
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-"
  }, "_acme-challenge.", tokenData.domain), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-field rsssl-selectable"
  }, tokenData.token))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("DNS verification active. You can switch back to directory verification here.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDir()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to directory verification', 'really-simple-ssl'))));
};

/* harmony default export */ __webpack_exports__["default"] = (DnsVerification);

/***/ }),

/***/ "./src/LetsEncrypt/Generation.js":
/*!***************************************!*\
  !*** ./src/LetsEncrypt/Generation.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _Settings_Notices__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Settings/Notices */ "./src/Settings/Notices.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__);












const Generation = props => {
  const action = props.action;

  if (!action) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }

  const handleSkipDNS = () => {
    return _utils_api__WEBPACK_IMPORTED_MODULE_2__.runLetsEncryptTest('skip_dns_check').then(response => {
      props.restartTests();
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Skip DNS verification ', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_skip_dns',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_6__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_skip_dns');
      });
    });
  };

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, action.status === 'error' && action.action === 'verify_dns' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("We could not check the DNS records. If you just added the record, please check in a few minutes.", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__["default"], {
    target: "_blank",
    text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("You can manually check the DNS records in an %sonline tool%s.", "really-simple-ssl"),
    url: "https://mxtoolbox.com/SuperTool.aspx"
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If you're sure it's set correctly, you can click the button to skip the DNS check.", "really-simple-ssl"), "\xA0"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__.Button, {
    variant: "secondary",
    onClick: () => handleSkipDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Skip DNS check', 'really-simple-ssl'))));
};

/* harmony default export */ __webpack_exports__["default"] = (Generation);

/***/ }),

/***/ "./src/LetsEncrypt/Installation.js":
/*!*****************************************!*\
  !*** ./src/LetsEncrypt/Installation.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _Settings_Notices__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Settings/Notices */ "./src/Settings/Notices.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_8__);












const Installation = props => {
  const action = props.action;
  const [installationData, setInstallationData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    if (action && action.status === 'warning' && installationData && installationData.generated_by_rsssl) {
      props.addHelp(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("This is the certificate, which you need to install in your hosting dashboard.", "really-simple-ssl"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate (CRT)", "really-simple-ssl"));
      props.addHelp(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The private key can be uploaded or pasted in the appropriate field on your hosting dashboard.", "really-simple-ssl"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Private Key (KEY)", "really-simple-ssl"));
      props.addHelp(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The CA Bundle will sometimes be automatically detected. If not, you can use this file.", "really-simple-ssl"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate Authority Bundle (CABUNDLE)", "really-simple-ssl"));
    }

    if (action && (action.status === 'error' || action.status === 'warning')) {
      _utils_api__WEBPACK_IMPORTED_MODULE_2__.runLetsEncryptTest('installation_data').then(response => {
        if (response) {
          setInstallationData(response.output);
        }
      });
    }
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {});

  const handleCopyAction = type => {
    let success;
    let data = document.querySelector('.rsssl-' + type).innerText;
    const el = document.createElement('textarea');
    el.value = data; //str is your string to copy

    document.body.appendChild(el);
    el.select();

    try {
      success = document.execCommand("copy");
    } catch (e) {
      success = false;
    }

    document.body.removeChild(el);
    const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Copied!', 'really-simple-ssl'), {
      __unstableHTML: true,
      id: 'rsssl_copied_data',
      type: 'snackbar',
      isDismissible: true
    }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_6__["default"])(3000)).then(response => {
      (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_copied_data');
    });
  };

  if (!action) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }

  if (!installationData) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, !installationData.generated_by_rsssl && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The certificate is not generated by Really Simple SSL, so there are no installation files here", "really-simple-ssl")), installationData.generated_by_rsssl && action.status === 'warning' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Next step", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-template-intro"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Install your certificate.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate (CRT)", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-certificate-data rsssl-certificate",
    id: "rsssl-certificate"
  }, installationData.certificate_content), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: installationData.download_url + "&type=certificate",
    className: "button button-secondary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Download", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    onClick: e => handleCopyAction('certificate'),
    className: "button button-primary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Copy content", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Private Key (KEY)", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-certificate-data rsssl-key",
    id: "rsssl-key"
  }, installationData.key_content), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: installationData.download_url + "&type=private_key",
    className: "button button-secondary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Download", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button-primary",
    onClick: e => handleCopyAction('key')
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Copy content", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate Authority Bundle (CABUNDLE)", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-certificate-data rsssl-cabundle",
    id: "rsssl-cabundle"
  }, installationData.ca_bundle_content), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: installationData.download_url + "&type=intermediate",
    className: "button button-secondary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Download", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button-primary",
    onClick: e => handleCopyAction('cabundle')
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Copy content", "really-simple-ssl"))));
};

/* harmony default export */ __webpack_exports__["default"] = (Installation);

/***/ }),

/***/ "./src/LetsEncrypt/LetsEncrypt.js":
/*!****************************************!*\
  !*** ./src/LetsEncrypt/LetsEncrypt.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _Directories__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Directories */ "./src/LetsEncrypt/Directories.js");
/* harmony import */ var _DnsVerification__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./DnsVerification */ "./src/LetsEncrypt/DnsVerification.js");
/* harmony import */ var _Generation__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Generation */ "./src/LetsEncrypt/Generation.js");
/* harmony import */ var _Activate__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Activate */ "./src/LetsEncrypt/Activate.js");
/* harmony import */ var _Installation__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Installation */ "./src/LetsEncrypt/Installation.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");













const LetsEncrypt = props => {
  const [id, setId] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(props.field.id);
  const [actionUpdated, setActionUpdated] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [progress, setProgress] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const actionIndex = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(0);
  const sleep = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(1500);
  const maxAttempts = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(1);
  const intervalId = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const lastActionStatus = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(''); // const previousProgress = useRef(0);

  const previousActionIndex = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(-1);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    props.handleNextButtonDisabled(true);
    runTest(0);
    intervalId.current = setInterval(() => setProgress(progress => progress + 0.2), 100);
  }, []);

  const restartTests = () => {
    //clear statuses to ensure the bullets are grey
    let actions = props.field.actions;

    for (const action of actions) {
      action.status = 'inactive';
    }

    props.field.actions = actions;
    actionIndex.current = 0;
    previousActionIndex.current = -1;
    lastActionStatus.current = '';
    setProgress(0);
    runTest(0);
  };

  const getAction = () => {
    let newActions = props.field.actions;
    return newActions[actionIndex.current];
  };

  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    let maxIndex = props.field.actions.length - 1;

    if (actionIndex.current > previousActionIndex.current) {
      previousActionIndex.current = actionIndex.current;
      setProgress(100 / maxIndex * actionIndex.current);
    } //ensure that progress does not get to 100 when retries are still running


    let currentAction = getAction();

    if (currentAction && currentAction.do === 'retry' && currentAction.attemptCount > 1) {
      setProgress(90);
    }

    if (props.refreshTests) {
      props.resetRefreshTests();
      restartTests();
    }
  });

  const adjustActionsForDNS = actions => {
    //find verification_type
    let verification_type = props.getFieldValue('verification_type');
    if (!verification_type) verification_type = 'dir';

    if (verification_type === 'dns') {
      //check if dns verification already is added
      let dnsVerificationAdded = false;
      actions.forEach(function (action, i) {
        if (action.action === "verify_dns") {
          dnsVerificationAdded = true;
        }
      }); //find bundle index

      let create_bundle_index = -1;
      actions.forEach(function (action, i) {
        if (action.action === "create_bundle_or_renew") {
          create_bundle_index = i;
        }
      });

      if (!dnsVerificationAdded && create_bundle_index > 0) {
        //store create bundle action
        let createBundleAction = actions[create_bundle_index]; //overwrite create bundle action

        let newAction = {};
        newAction.action = 'verify_dns';
        newAction.description = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__.__)("Verifying DNS records...", "really-simple-ssl");
        newAction.attempts = 2;
        actions[create_bundle_index] = newAction;
        actions.push(createBundleAction);
      }
    }

    return actions;
  };

  const processTestResult = action => {
    lastActionStatus.current = action.status;
    let maxIndex = props.field.actions.length - 1;

    if (action.status === 'success') {
      action.attemptCount = 0;
    } else {
      if (!Number.isInteger(action.attemptCount)) {
        action.attemptCount = 0;
      }

      action.attemptCount += 1;
    }

    setActionUpdated(true); //used for dns verification actions

    var event = new CustomEvent('rsssl_le_response', {
      detail: action
    });
    document.dispatchEvent(event); //if all tests are finished with success
    //finalize happens when halfway through our tests it's finished. We can skip all others.

    if (action.do === 'finalize') {
      clearInterval(intervalId.current);
      props.field.actions.forEach(function (action, i) {
        if (i > actionIndex.current) {
          action.hide = true;
        }
      });
      actionIndex.current = maxIndex;
      props.handleNextButtonDisabled(false);
    } else if (action.do === 'continue' || action.do === 'skip') {
      //new action, so reset the attempts count
      action.attemptCount = 1; //skip:  drop previous completely, skip to next.

      if (action.do === 'skip') {
        action.hide = true;
      } //move to next action, but not if we're already on the max


      if (maxIndex > actionIndex.current) {
        actionIndex.current = actionIndex.current + 1;
        runTest(actionIndex.current);
      } else {
        actionIndex.current = maxIndex;
        props.handleNextButtonDisabled(false);
        clearInterval(intervalId.current);
      }
    } else if (action.do === 'retry') {
      if (action.attemptCount >= maxAttempts.current) {
        actionIndex.current = maxIndex;
        clearInterval(intervalId.current);
      } else {
        // clearInterval(intervalId.current);
        runTest(actionIndex.current);
      }
    } else if (action.do === 'stop') {
      clearInterval(intervalId.current);
    }
  };

  const runTest = () => {
    setActionUpdated(false);

    if (props.field.id === 'generation') {
      props.field.actions = adjustActionsForDNS(props.field.actions);
    }

    let action = getAction();
    let test = action.action;
    const startTime = new Date();
    maxAttempts.current = action.attempts;
    _utils_api__WEBPACK_IMPORTED_MODULE_1__.runLetsEncryptTest(test, props.field.id).then(response => {
      const endTime = new Date();
      let timeDiff = endTime - startTime; //in ms

      const elapsedTime = Math.round(timeDiff);
      let action = getAction();
      action.status = response.status ? response.status : 'inactive';
      action.hide = false;
      action.description = response.message;
      action.do = response.action;
      action.output = response.output ? response.output : false;
      sleep.current = 500;

      if (elapsedTime < 1500) {
        sleep.current = 1500 - elapsedTime;
      }
    }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_2__["default"])(sleep.current)).then(() => {
      processTestResult(action);
    });
  };

  const getStyles = () => {
    return Object.assign({}, {
      width: progress + "%"
    });
  };

  const getStatusIcon = action => {
    if (!statuses.hasOwnProperty(action.status)) {
      return statuses['inactive'].icon;
    }

    return statuses[action.status].icon;
  };

  const getStatusColor = action => {
    if (!statuses.hasOwnProperty(action.status)) {
      return statuses['inactive'].color;
    }

    return statuses[action.status].color;
  };

  let progressBarColor = lastActionStatus.current === 'error' ? 'rsssl-orange' : '';

  if (!props.field.actions) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  } // keep current action, before it is filtered. The actionindex doesn't match anymore after filtering


  let currentAction = props.field.actions[actionIndex.current]; //filter out skipped actions

  let actions = props.field.actions.filter(action => action.hide !== true);
  const statuses = {
    'inactive': {
      'icon': 'circle-times',
      'color': 'grey'
    },
    'warning': {
      'icon': 'circle-times',
      'color': 'orange'
    },
    'error': {
      'icon': 'circle-times',
      'color': 'red'
    },
    'success': {
      'icon': 'circle-check',
      'color': 'green'
    }
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-lets-encrypt-tests"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-progress-bar"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-progress"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'rsssl-bar ' + progressBarColor,
    style: getStyles()
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl_letsencrypt_container rsssl-progress-container field-group"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, actions.map((action, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: i
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_10__["default"], {
    name: getStatusIcon(action),
    color: getStatusColor(action)
  }), action.do === 'retry' && action.attemptCount >= 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__.__)("Attempt %s.", "really-simple-ssl").replace('%s', action.attemptCount), " "), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    dangerouslySetInnerHTML: {
      __html: action.description
    }
  }))))), props.field.id === 'directories' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Directories__WEBPACK_IMPORTED_MODULE_3__["default"], {
    save: props.save,
    selectMenu: props.selectMenu,
    field: props.field,
    updateField: props.updateField,
    addHelp: props.addHelp,
    progress: progress,
    action: currentAction
  }), props.field.id === 'dns-verification' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DnsVerification__WEBPACK_IMPORTED_MODULE_4__["default"], {
    save: props.save,
    selectMenu: props.selectMenu,
    field: props.field,
    updateField: props.updateField,
    addHelp: props.addHelp,
    progress: progress,
    action: currentAction
  }), props.field.id === 'generation' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Generation__WEBPACK_IMPORTED_MODULE_5__["default"], {
    restartTests: restartTests,
    save: props.save,
    selectMenu: props.selectMenu,
    field: props.field,
    updateField: props.updateField,
    addHelp: props.addHelp,
    progress: progress,
    action: currentAction
  }), props.field.id === 'installation' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Installation__WEBPACK_IMPORTED_MODULE_7__["default"], {
    restartTests: restartTests,
    save: props.save,
    selectMenu: props.selectMenu,
    field: props.field,
    updateField: props.updateField,
    addHelp: props.addHelp,
    progress: progress,
    action: currentAction
  }), props.field.id === 'activate' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Activate__WEBPACK_IMPORTED_MODULE_6__["default"], {
    restartTests: restartTests,
    save: props.save,
    selectMainMenu: props.selectMainMenu,
    selectMenu: props.selectMenu,
    field: props.field,
    updateField: props.updateField,
    addHelp: props.addHelp,
    progress: progress,
    action: currentAction
  })));
};

/* harmony default export */ __webpack_exports__["default"] = (LetsEncrypt);

/***/ }),

/***/ "./src/Menu/Menu.js":
/*!**************************!*\
  !*** ./src/Menu/Menu.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _MenuItem__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./MenuItem */ "./src/Menu/MenuItem.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);





/**
 * Menu block, rendering th entire menu
 */

class Menu extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let hasPremiumItems = this.props.menu.menu_items.filter(item => {
      return item.premium === true;
    }).length > 0;

    if (!this.props.isAPILoaded) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_1__["default"], null);
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-menu rsssl-grid-item"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
      className: "rsssl-h4"
    }, this.props.menu.title)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-menu-items"
    }, this.props.menu.menu_items.map((menuItem, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_MenuItem__WEBPACK_IMPORTED_MODULE_2__["default"], {
      key: i,
      isAPILoaded: this.props.isAPILoaded,
      menuItem: menuItem,
      selectMenu: this.props.selectMenu,
      selectStep: this.props.selectStep,
      selectedMenuItem: this.props.selectedMenuItem,
      selectedMainMenuItem: this.props.selectedMainMenuItem,
      getPreviousAndNextMenuItems: this.props.getPreviousAndNextMenuItems
    })), hasPremiumItems && !rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-premium-menu-item"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      target: "_blank",
      href: rsssl_settings.upgrade_link,
      className: "button button-black"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Go Pro', 'really-simple-ssl')))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-footer"
    }));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Menu);

/***/ }),

/***/ "./src/Menu/MenuItem.js":
/*!******************************!*\
  !*** ./src/Menu/MenuItem.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);




class MenuItem extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  handleClick() {
    this.props.selectMenu(this.props.menuItem.id);
  }

  componentDidMount() {
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    /*
     * Menu is selected if the item is the same, or if it is a child.
     */
    let menuIsSelected = this.props.selectedMenuItem === this.props.menuItem.id;

    if (this.props.menuItem.menu_items) {
      for (const item of this.props.menuItem.menu_items) {
        if (item.id === this.props.selectedMenuItem) {
          menuIsSelected = true;
        }
      }
    }

    let menuClass = menuIsSelected ? ' rsssl-active' : '';
    menuClass += this.props.menuItem.featured ? ' rsssl-featured' : '';
    menuClass += this.props.menuItem.premium && !rsssl_settings.pro_plugin_active ? ' rsssl-premium' : '';
    let href = '#' + this.props.selectedMainMenuItem + '/' + this.props.menuItem.id;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, this.props.menuItem.visible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-menu-item" + menuClass
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: href,
      onClick: () => this.handleClick()
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, this.props.menuItem.title), this.props.menuItem.featured && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-menu-item-featured-pill"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('New', 'really-simple-ssl')))), this.props.menuItem.menu_items && menuIsSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-submenu-item"
    }, this.props.menuItem.menu_items.map((subMenuItem, i) => subMenuItem.visible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(MenuItem, {
      key: i,
      menuItem: subMenuItem,
      selectMenu: this.props.selectMenu,
      selectedMenuItem: this.props.selectedMenuItem,
      selectedMainMenuItem: this.props.selectedMainMenuItem
    })))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (MenuItem);

/***/ }),

/***/ "./src/Modal/Modal.js":
/*!****************************!*\
  !*** ./src/Modal/Modal.js ***!
  \****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");






class Modal extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      data: [],
      buttonsDisabled: false
    };
  }

  dismissModal(dropItem) {
    this.props.handleModal(false, null, dropItem);
  }

  componentDidMount() {
    this.setState({
      data: this.props.data,
      buttonsDisabled: false
    });
  }

  handleFix(e) {
    //set to disabled
    let action = this.props.data.action;
    this.setState({
      buttonsDisabled: true
    });
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest(action, 'refresh', this.props.data).then(response => {
      let {
        data
      } = this.state;
      data.description = response.msg;
      data.subtitle = '';
      this.setState({
        data: data
      });
      let item = this.props.data;

      if (response.success) {
        this.dismissModal(this.props.data);
      }
    });
  }

  render() {
    const {
      data,
      buttonsDisabled
    } = this.state;
    let disabled = buttonsDisabled ? 'disabled' : '';
    let description = data.description;

    if (!Array.isArray(description)) {
      description = [description];
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-backdrop",
      onClick: e => this.dismissModal(e)
    }, "\xA0"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal",
      id: "{id}"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
      className: "modal-title"
    }, data.title), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "rsssl-modal-close",
      "data-dismiss": "modal",
      "aria-label": "Close",
      onClick: e => this.dismissModal(e)
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: "times"
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-content"
    }, data.subtitle && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-subtitle"
    }, data.subtitle), Array.isArray(description) && description.map((s, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: i,
      className: "rsssl-modal-description"
    }, s))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-footer"
    }, data.edit && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: data.edit,
      target: "_blank",
      className: "button button-secondary"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Edit", "really-simple-ssl")), data.help && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: data.help,
      target: "_blank",
      className: "button rsssl-button-help"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Help", "really-simple-ssl")), !data.ignored && data.action === 'ignore_url' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      disabled: disabled,
      className: "button button-primary",
      onClick: e => this.handleFix(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Ignore", "really-simple-ssl")), data.action !== 'ignore_url' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      disabled: disabled,
      className: "button button-primary",
      onClick: e => this.handleFix(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Fix", "really-simple-ssl")))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Modal);

/***/ }),

/***/ "./src/Modal/ModalControl.js":
/*!***********************************!*\
  !*** ./src/Modal/ModalControl.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);



class ModalControl extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  componentDidMount() {
    this.onClickHandler = this.onClickHandler.bind(this);
  }

  onClickHandler() {
    this.props.handleModal(true, this.props.modalData);
  }

  render() {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button button-" + this.props.btnStyle,
      onClick: e => this.onClickHandler(e)
    }, this.props.btnText);
  }

}

/* harmony default export */ __webpack_exports__["default"] = (ModalControl);

/***/ }),

/***/ "./src/Onboarding/Onboarding.js":
/*!**************************************!*\
  !*** ./src/Onboarding/Onboarding.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");










const Onboarding = props => {
  const [steps, setSteps] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [overrideSSL, setOverrideSSL] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [certificateValid, setCertificateValid] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [sslActivated, setsslActivated] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [activateSSLDisabled, setActivateSSLDisabled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const [stepsChanged, setStepsChanged] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [networkwide, setNetworkwide] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [networkActivationStatus, setNetworkActivationStatus] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [networkProgress, setNetworkProgress] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    if (networkProgress < 100 && networkwide && networkActivationStatus === 'main_site_activated') {
      _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest('activate_ssl_networkwide').then(response => {
        if (response.success) {
          setNetworkProgress(response.progress);

          if (response.progress >= 100) {
            updateActionForItem('ssl_enabled', '', 'success');
          }
        }
      });
    }
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    updateOnBoardingData(false);
  }, []);

  const updateOnBoardingData = forceRefresh => {
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.getOnboarding(forceRefresh).then(response => {
      let steps = response.steps;
      setNetworkwide(response.networkwide);
      setOverrideSSL(response.ssl_detection_overridden);
      setActivateSSLDisabled(!response.ssl_detection_overridden);
      setCertificateValid(response.certificate_valid);
      setsslActivated(response.ssl_enabled);
      steps[0].visible = true; //if ssl is already enabled, the server will send only one step. In that case we can skip the below.
      //it's only needed when SSL is activated just now, client side.

      if (response.ssl_enabled && steps.length > 1) {
        steps[0].visible = false;
        steps[1].visible = true;
      }

      setNetworkActivationStatus(response.network_activation_status);

      if (response.network_activation_status === 'completed') {
        setNetworkProgress(100);
      }

      setSteps(steps);
      setStepsChanged('initial');
    });
  };

  const refreshSSLStatus = e => {
    e.preventDefault();
    steps.forEach(function (step, i) {
      if (step.id === 'activate_ssl') {
        step.items.forEach(function (item, j) {
          if (item.status === 'error') {
            steps[i].items[j].status = 'processing';
            steps[i].items[j].title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Re-checking SSL certificate, please wait...", "really-simple-ssl");
          }
        });
      }
    });
    setSteps(steps);
    setStepsChanged(true);
    setTimeout(function () {
      updateOnBoardingData(true);
    }, 1000); //add a delay, otherwise it's so fast the user may not trust it.
  };

  const activateSSL = () => {
    setStepsChanged(false);
    let sslUrl = window.location.href.replace("http://", "https://");
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest('activate_ssl').then(response => {
      steps[0].visible = false;
      steps[1].visible = true; //change url to https, after final check

      if (response.success) {
        setSteps(steps);
        setStepsChanged(true);
        setsslActivated(response.success);
        props.updateField('ssl_enabled', true);

        if (response.site_url_changed) {
          window.location.reload();
        } else {
          props.getFields();

          if (networkwide) {
            setNetworkActivationStatus('main_site_activated');
          }
        }
      }
    });
  };

  const updateActionForItem = (findItem, newAction, newStatus) => {
    let stepsCopy = steps;
    stepsCopy.forEach(function (step, i) {
      stepsCopy[i].items.forEach(function (item, j) {
        if (item.id === findItem) {
          let itemCopy = stepsCopy[i].items[j];
          itemCopy.current_action = newAction;

          if (newStatus) {
            itemCopy.status = newStatus;
          }

          stepsCopy[i].items[j] = itemCopy;
        }
      });
    });
    setSteps(stepsCopy);
    setStepsChanged(findItem + newAction + newStatus);
  };

  const itemButtonHandler = (id, action) => {
    let data = {};
    data.id = id;
    updateActionForItem(id, action, false);
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.doAction(action, data).then(response => {
      if (response.success) {
        if (action === 'activate_setting') {
          //ensure all fields are updated, and progress is retrieved again
          props.getFields();
        }

        let nextAction = response.next_action;

        if (nextAction !== 'none' && nextAction !== 'completed') {
          updateActionForItem(id, nextAction, false);
          _utils_api__WEBPACK_IMPORTED_MODULE_2__.doAction(nextAction, data).then(response => {
            if (response.success) {
              updateActionForItem(id, 'completed', 'success');
            } else {
              updateActionForItem(id, 'failed', 'error');
            }
          }).catch(error => {
            updateActionForItem(id, 'failed', 'error');
          });
        } else {
          updateActionForItem(id, 'completed', 'success');
        }
      } else {
        updateActionForItem(id, 'failed', 'error');
      }
    }).catch(error => {
      updateActionForItem(id, 'failed', 'error');
    });
  };

  const parseStepItems = items => {
    return items.map((item, index) => {
      let {
        title,
        current_action,
        action,
        status,
        button,
        id
      } = item;

      if (id === 'ssl_enabled' && networkwide) {
        if (networkProgress >= 100) {
          status = 'success';
          title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("SSL has been activated network wide", "really-simple-ssl");
        } else {
          status = 'processing';
          title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Processing activation of subsites networkwide", "really-simple-ssl");
        }
      }

      const statuses = {
        'inactive': {
          'icon': 'info',
          'color': 'grey'
        },
        'warning': {
          'icon': 'circle-times',
          'color': 'orange'
        },
        'error': {
          'icon': 'circle-times',
          'color': 'red'
        },
        'success': {
          'icon': 'circle-check',
          'color': 'green'
        },
        'processing': {
          'icon': 'file-download',
          'color': 'red'
        }
      };
      const statusIcon = statuses[status].icon;
      const statusColor = statuses[status].color;
      const currentActions = {
        'activate_setting': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Activating...', "really-simple-ssl"),
        'activate': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Activating...', "really-simple-ssl"),
        'install_plugin': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Installing...', "really-simple-ssl"),
        'error': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Failed', "really-simple-ssl"),
        'completed': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Finished', "really-simple-ssl")
      };
      let buttonTitle = '';

      if (button) {
        buttonTitle = button;

        if (current_action !== 'none') {
          buttonTitle = currentActions[current_action];

          if (current_action === 'failed') {
            buttonTitle = currentActions['error'];
          }
        }
      }

      let showLink = button && button === buttonTitle;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
        key: index
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_5__["default"], {
        name: statusIcon,
        color: statusColor
      }), title, id === 'ssl_enabled' && networkwide && networkActivationStatus === 'main_site_activated' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0-\xA0", networkProgress < 100 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("working", "really-simple-ssl"), "\xA0", networkProgress, "%"), networkProgress >= 100 && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("completed", "really-simple-ssl")), button && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0-\xA0", showLink && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
        className: "button button-secondary",
        isLink: true,
        onClick: () => itemButtonHandler(id, action)
      }, buttonTitle), !showLink && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, buttonTitle)));
    });
  };

  const goToDashboard = () => {
    if (props.isModal) props.dismissModal();
    props.selectMainMenu('dashboard');
  };

  const goToLetsEncrypt = () => {
    if (props.isModal) props.dismissModal();
    window.location.href = rsssl_settings.letsencrypt_url;
  };

  const controlButtons = () => {
    let ActivateSSLText = networkwide ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Activate SSL networkwide", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Activate SSL", "really-simple-ssl");

    if (steps[0].visible && steps.length > 1) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        disabled: !certificateValid && !overrideSSL,
        className: "button button-primary",
        onClick: () => {
          activateSSL();
        }
      }, ActivateSSLText), certificateValid && !rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
        target: "_blank",
        href: rsssl_settings.upgrade_link,
        className: "button button-default"
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Improve Security with PRO", "really-simple-ssl")), !certificateValid && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        className: "button button-default",
        onClick: () => {
          goToLetsEncrypt();
        }
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Install SSL", "really-simple-ssl")), !certificateValid && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToggleControl, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Override SSL detection", "really-simple-ssl"),
        checked: overrideSSL,
        onChange: value => {
          setOverrideSSL(value);
          let data = {};
          data.overrideSSL = value;
          _utils_api__WEBPACK_IMPORTED_MODULE_2__.doAction('override_ssl_detection', data).then(response => {
            setActivateSSLDisabled(!value);
          });
        }
      }));
    }

    if (steps.length > 1 && steps[1].visible || steps[0].visible) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        className: "button button-primary",
        onClick: () => {
          goToDashboard();
        }
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Go to Dashboard', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        className: "button button-default",
        onClick: () => {
          props.dismissModal();
        }
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Dismiss', 'really-simple-ssl')));
    }
  };

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, !stepsChanged && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_6__["default"], {
    lines: "12"
  }), stepsChanged && steps.map((step, index) => {
    const {
      title,
      subtitle,
      items,
      info_text: infoText,
      visible
    } = step;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-content-step",
      key: index,
      style: {
        display: visible ? 'block' : 'none'
      }
    }, title && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", {
      className: "rsssl-modal-subtitle"
    }, title), subtitle && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-description"
    }, subtitle), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, parseStepItems(items)), certificateValid && infoText && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-description",
      dangerouslySetInnerHTML: {
        __html: infoText
      }
    }), !certificateValid && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-description"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "#",
      onClick: e => refreshSSLStatus(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Refresh SSL status", "really-simple-ssl")), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("The SSL detection method is not 100% accurate.", "really-simple-ssl"), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("If you’re certain an SSL certificate is present, and refresh SSL status does not work, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-content-step-footer"
    }, controlButtons()));
  }));
};

/* harmony default export */ __webpack_exports__["default"] = (Onboarding);

/***/ }),

/***/ "./src/Onboarding/OnboardingModal.js":
/*!*******************************************!*\
  !*** ./src/Onboarding/OnboardingModal.js ***!
  \*******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _Onboarding__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Onboarding */ "./src/Onboarding/Onboarding.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");










const OnboardingModal = props => {
  const [modalLoaded, setModalLoaded] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!modalLoaded) {
      _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('get_modal_status').then(response => {
        setModalLoaded(true);
        props.setShowOnBoardingModal(!response.dismissed);
      });
    }
  });
  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    if (props.showOnBoardingModal === true) {
      let data = {};
      data.dismiss = false;
      _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('dismiss_modal', 'refresh', data).then(response => {});
    }
  });

  const dismissModal = () => {
    let data = {};
    data.dismiss = true;
    props.setShowOnBoardingModal(false);
    _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('dismiss_modal', 'refresh', data).then(response => {});
  };

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, props.showOnBoardingModal && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-backdrop"
  }, "\xA0"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal rsssl-onboarding"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-header"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    className: "rsssl-logo",
    src: rsssl_settings.plugin_url + 'assets/img/really-simple-ssl-logo.svg',
    alt: "Really Simple SSL logo"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "rsssl-modal-close",
    "data-dismiss": "modal",
    "aria-label": "Close",
    onClick: dismissModal
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    role: "img",
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 320 512",
    height: "24"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    fill: "#000000",
    d: "M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"
  })))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-content"
  }, !props.isAPILoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_6__["default"], {
    name: "file-download",
    color: "orange"
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("Please wait while we detect your setup", "really-simple-ssl"))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_4__["default"], {
    lines: "10"
  })), props.isAPILoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Onboarding__WEBPACK_IMPORTED_MODULE_2__["default"], {
    getFields: props.getFields,
    updateField: props.updateField,
    selectMainMenu: props.selectMainMenu,
    isModal: true,
    dismissModal: dismissModal
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rssl-modal-footer"
  }))));
};

/* harmony default export */ __webpack_exports__["default"] = (OnboardingModal);

/***/ }),

/***/ "./src/Page.js":
/*!*********************!*\
  !*** ./src/Page.js ***!
  \*********************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/api */ "./src/utils/api.js");
/* harmony import */ var _Header__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Header */ "./src/Header.js");
/* harmony import */ var _DashBoard_DashboardPage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./DashBoard/DashboardPage */ "./src/DashBoard/DashboardPage.js");
/* harmony import */ var _Settings_SettingsPage__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Settings/SettingsPage */ "./src/Settings/SettingsPage.js");
/* harmony import */ var _Modal_Modal__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Modal/Modal */ "./src/Modal/Modal.js");
/* harmony import */ var _Placeholder_PagePlaceholder__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Placeholder/PagePlaceholder */ "./src/Placeholder/PagePlaceholder.js");
/* harmony import */ var _Onboarding_OnboardingModal__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Onboarding/OnboardingModal */ "./src/Onboarding/OnboardingModal.js");
/* harmony import */ var _utils_getAnchor__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./utils/getAnchor */ "./src/utils/getAnchor.js");











class Page extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.pageProps = [];
    this.pageProps['licenseStatus'] = rsssl_settings.licenseStatus;
    this.updateFields = this.updateFields.bind(this);
    this.updateProgress = this.updateProgress.bind(this);
    this.getFields = this.getFields.bind(this);
    this.selectMenu = this.selectMenu.bind(this);
    this.getSelectedMenu = this.getSelectedMenu.bind(this);
    this.selectStep = this.selectStep.bind(this);
    this.handleModal = this.handleModal.bind(this);
    this.highLightField = this.highLightField.bind(this);
    this.updateField = this.updateField.bind(this);
    this.getFieldValue = this.getFieldValue.bind(this);
    this.addHelp = this.addHelp.bind(this);
    this.selectMainMenu = this.selectMainMenu.bind(this);
    this.setPageProps = this.setPageProps.bind(this);
    this.getPreviousAndNextMenuItems = this.getPreviousAndNextMenuItems.bind(this);
    this.setShowOnBoardingModal = this.setShowOnBoardingModal.bind(this);
    this.state = {
      selectedMainMenuItem: '',
      showOnBoardingModal: false,
      selectedMenuItem: '',
      selectedStep: 1,
      highLightedField: '',
      fields: '',
      menu: [],
      progress: '',
      isAPILoaded: false,
      pageProps: this.pageProps,
      showModal: false,
      modalData: [],
      dropItemFromModal: false,
      nextMenuItem: '',
      previousMenuItem: ''
    };
    this.getFields();
  }

  setShowOnBoardingModal(status) {
    const {
      showOnBoardingModal
    } = this.state;

    if (status !== showOnBoardingModal) {
      this.setState({
        showOnBoardingModal: status
      });
    }
  }

  updateFields(fields) {
    this.fields = fields;
    this.setState({
      fields: fields
    });
  }

  updateProgress(progress) {
    this.progress = progress;
    this.setState({
      progress: progress
    });
  }

  componentDidMount() {
    window.addEventListener('hashchange', () => {
      let selectedMainMenuItem = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_8__["default"])('main') || 'dashboard';
      this.menu = this.getSelectedMenu(this.superMenu, selectedMainMenuItem);
      this.setState({
        selectedMainMenuItem: selectedMainMenuItem,
        selectedMenuItem: this.getDefaultMenuItem(),
        menu: this.menu
      }, () => {
        this.getPreviousAndNextMenuItems();
      });
    });
  }
  /*
  * filter sidebar menu from complete menu structure
  */


  getSelectedMenu(superMenu, selectedMainMenuItem) {
    for (const key in superMenu) {
      if (superMenu.hasOwnProperty(key)) {
        if (superMenu[key] && superMenu[key].id === selectedMainMenuItem) {
          return superMenu[key];
        }
      }
    }
  }

  getFields() {
    _utils_api__WEBPACK_IMPORTED_MODULE_1__.getFields().then(response => {
      this.superMenu = response.menu;
      let selectedMainMenuItem = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_8__["default"])('main') || 'dashboard';
      this.menu = this.getSelectedMenu(this.superMenu, selectedMainMenuItem);
      this.fields = response.fields;
      this.progress = response.progress;
      this.setState({
        isAPILoaded: true,
        fields: this.fields,
        progress: this.progress,
        menu: this.menu,
        selectedMenuItem: this.getDefaultMenuItem(),
        selectedMainMenuItem: selectedMainMenuItem
      }, () => {
        this.getPreviousAndNextMenuItems();
      });
    });
  }
  /*
   * Allow child blocks to set data on the gridblock
   * @param key
   * @param value
   */


  setPageProps(key, value) {
    const {
      pageProps
    } = this.state; //if (pageProps[key] !== value ) {

    this.pageProps[key] = value;
    this.setState({
      pageProps: this.pageProps
    }); //}
  }
  /*
   * Handle instantiation of a modal window
   * @param showModal
   * @param data
   * @param dropItem
   */


  handleModal(showModal, data, dropItem) {
    this.setState({
      showModal: showModal,
      modalData: data,
      dropItemFromModal: dropItem
    });
  }

  selectMenu(selectedMenuItem) {
    this.setState({
      selectedMenuItem: selectedMenuItem
    });
  }

  selectStep(selectedStep) {
    this.setState({
      selectedStep: selectedStep
    });
  }

  getDefaultMenuItem() {
    let fallBackMenuItem = this.menu && this.menu.menu_items.hasOwnProperty(0) ? this.menu.menu_items[0].id : 'general';
    let anchor = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_8__["default"])('menu');
    let foundAnchorInMenu = false; //check if this anchor actually exists in our current submenu. If not, clear it

    for (const key in this.menu.menu_items) {
      if (this.menu.menu_items.hasOwnProperty(key) && this.menu.menu_items[key].id === anchor) {
        foundAnchorInMenu = true;
      }
    }

    if (!foundAnchorInMenu) anchor = false;
    return anchor ? anchor : fallBackMenuItem;
  }

  selectMainMenu(selectedMainMenuItem) {
    this.menu = this.getSelectedMenu(this.superMenu, selectedMainMenuItem);
    let selectedMenuItem = this.getDefaultMenuItem();
    this.setState({
      menu: this.menu,
      selectedMainMenuItem: selectedMainMenuItem,
      selectedMenuItem: selectedMenuItem
    });
  }
  /*
   * Update a field
   * @param field
   */


  updateField(id, value) {
    let fields = this.fields;

    for (const fieldItem of fields) {
      if (fieldItem.id === id) {
        fieldItem.value = value;
      }
    }

    this.fields = fields;
    this.setState({
      fields: fields
    });
  }
  /*
  * Allow children to check a field value from another page (in a page, only visible fields are know)
  */


  getFieldValue(id) {
    let fields = this.fields;

    for (const fieldItem of fields) {
      if (fieldItem.id === id) {
        return fieldItem.value;
      }
    }

    return false;
  }

  addHelp(id, label, text, title) {
    //create help object
    let help = {};
    help.label = label;
    help.text = text;
    if (title) help.title = title;
    let fields = this.fields; //add to selected field

    for (const fieldItem of fields) {
      if (fieldItem.id === id && !fieldItem.help) {
        fieldItem.help = help;
        this.fields = fields;
        this.setState({
          fields: fields
        });
      }
    }
  }

  highLightField(fieldId) {
    //switch to settings page
    this.selectMainMenu('settings'); //get menu item based on fieldId

    let selectedField = null;
    let fields = this.fields.filter(field => field.id === fieldId);

    if (fields.length) {
      selectedField = fields[0];
      this.selectMenu(selectedField.menu_id);
    }

    this.highLightedField = fieldId;
  }
  /*
   * Get anchor from URL
   * @returns {string|boolean}
   */
  // Parses menu items and nested items in single array


  menuItemParser(parsedMenuItems, menuItems) {
    menuItems.forEach(menuItem => {
      if (menuItem.visible) {
        parsedMenuItems.push(menuItem.id);

        if (menuItem.hasOwnProperty('menu_items')) {
          this.menuItemParser(parsedMenuItems, menuItem.menu_items);
        }
      }
    });
    return parsedMenuItems;
  }

  getPreviousAndNextMenuItems() {
    let previousMenuItem;
    let nextMenuItem;
    const {
      menu_items: menuItems
    } = this.state.menu;
    const parsedMenuItems = [];
    this.menuItemParser(parsedMenuItems, menuItems); // Finds current menu item index

    const currentMenuItemIndex = parsedMenuItems.findIndex(menuItem => menuItem === this.state.selectedMenuItem);

    if (currentMenuItemIndex !== -1) {
      previousMenuItem = parsedMenuItems[currentMenuItemIndex === 0 ? '' : currentMenuItemIndex - 1];
      nextMenuItem = parsedMenuItems[currentMenuItemIndex === parsedMenuItems.length - 1 ? '' : currentMenuItemIndex + 1];
      this.setState({
        previousMenuItem: previousMenuItem ? previousMenuItem : parsedMenuItems[0],
        nextMenuItem: nextMenuItem ? nextMenuItem : parsedMenuItems[parsedMenuItems.length - 1]
      });
    }

    return {
      nextMenuItem,
      previousMenuItem
    };
  }

  render() {
    const {
      pageProps,
      selectedMainMenuItem,
      showOnBoardingModal,
      selectedMenuItem,
      fields,
      menu,
      progress,
      isAPILoaded,
      showModal,
      modalData,
      dropItemFromModal
    } = this.state;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wrapper"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Onboarding_OnboardingModal__WEBPACK_IMPORTED_MODULE_7__["default"], {
      isAPILoaded: isAPILoaded,
      selectMenu: this.selectMenu,
      selectMainMenu: this.selectMainMenu,
      getFields: this.getFields,
      updateField: this.updateField,
      setShowOnBoardingModal: this.setShowOnBoardingModal,
      showOnBoardingModal: showOnBoardingModal,
      pageProps: this.pageProps,
      setPageProps: this.setPageProps
    }), !isAPILoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_PagePlaceholder__WEBPACK_IMPORTED_MODULE_6__["default"], null), showModal && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Modal_Modal__WEBPACK_IMPORTED_MODULE_5__["default"], {
      handleModal: this.handleModal,
      data: modalData
    }), isAPILoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Header__WEBPACK_IMPORTED_MODULE_2__["default"], {
      selectedMainMenuItem: selectedMainMenuItem,
      selectMainMenu: this.selectMainMenu,
      superMenu: this.superMenu,
      fields: fields
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem
    }, selectedMainMenuItem !== 'dashboard' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Settings_SettingsPage__WEBPACK_IMPORTED_MODULE_4__["default"], {
      dropItemFromModal: dropItemFromModal,
      updateFields: this.updateFields,
      updateProgress: this.updateProgress,
      pageProps: this.pageProps,
      handleModal: this.handleModal,
      getDefaultMenuItem: this.getDefaultMenuItem,
      updateField: this.updateField,
      getFieldValue: this.getFieldValue,
      addHelp: this.addHelp,
      setPageProps: this.setPageProps,
      selectMenu: this.selectMenu,
      selectStep: this.selectStep,
      selectedStep: this.state.selectedStep,
      highLightField: this.highLightField,
      highLightedField: this.highLightedField,
      selectedMenuItem: selectedMenuItem,
      selectedMainMenuItem: selectedMainMenuItem,
      selectMainMenu: this.selectMainMenu,
      isAPILoaded: isAPILoaded,
      fields: fields,
      menu: menu,
      progress: progress,
      getPreviousAndNextMenuItems: this.getPreviousAndNextMenuItems,
      nextMenuItem: this.state.nextMenuItem,
      previousMenuItem: this.state.previousMenuItem
    }), selectedMainMenuItem === 'dashboard' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DashBoard_DashboardPage__WEBPACK_IMPORTED_MODULE_3__["default"], {
      setShowOnBoardingModal: this.setShowOnBoardingModal,
      isAPILoaded: isAPILoaded,
      fields: fields,
      selectMainMenu: this.selectMainMenu,
      highLightField: this.highLightField,
      getFields: this.getFields,
      pageProps: pageProps
    }))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Page);

/***/ }),

/***/ "./src/Placeholder/PagePlaceholder.js":
/*!********************************************!*\
  !*** ./src/Placeholder/PagePlaceholder.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);



class PagePlaceholder extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let plugin_url = rsssl_settings.plugin_url;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header-container"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
      className: "rsssl-logo",
      src: plugin_url + 'assets/img/really-simple-ssl-logo.svg',
      alt: "Really Simple SSL logo"
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-content-area rsssl-grid rsssl-dashboard rsssl-page-placeholder"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item  rsssl-column-2 rsssl-row-2 "
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item rsssl-row-2"
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item rsssl-row-2"
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item  rsssl-column-2"
    })));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (PagePlaceholder);

/***/ }),

/***/ "./src/Placeholder/Placeholder.js":
/*!****************************************!*\
  !*** ./src/Placeholder/Placeholder.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);



class Placeholder extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let lines = this.props.lines;
    if (!lines) lines = 4;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-placeholder"
    }, Array.from({
      length: lines
    }).map((item, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-placeholder-line",
      key: i
    })));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Placeholder);

/***/ }),

/***/ "./src/Settings/ChangeStatus.js":
/*!**************************************!*\
  !*** ./src/Settings/ChangeStatus.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);




class ChangeStatus extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let statusClass = this.props.item.status == 1 ? 'button button-primary rsssl-status-allowed' : 'button button-default rsssl-status-revoked';
    let label = this.props.item.status == 1 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Revoke", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Allow", "really-simple-ssl");
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      onClick: () => this.props.onChangeHandlerDataTableStatus(this.props.item.status, this.props.item, 'status'),
      className: statusClass
    }, label);
  }

}

/* harmony default export */ __webpack_exports__["default"] = (ChangeStatus);

/***/ }),

/***/ "./src/Settings/Field.js":
/*!*******************************!*\
  !*** ./src/Settings/Field.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _License__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./License */ "./src/Settings/License.js");
/* harmony import */ var _Password__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Password */ "./src/Settings/Password.js");
/* harmony import */ var _Host__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Host */ "./src/Settings/Host.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _LetsEncrypt_LetsEncrypt__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../LetsEncrypt/LetsEncrypt */ "./src/LetsEncrypt/LetsEncrypt.js");
/* harmony import */ var _LetsEncrypt_Activate__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../LetsEncrypt/Activate */ "./src/LetsEncrypt/Activate.js");
/* harmony import */ var _MixedContentScan__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./MixedContentScan */ "./src/Settings/MixedContentScan.js");
/* harmony import */ var _PermissionsPolicy__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./PermissionsPolicy */ "./src/Settings/PermissionsPolicy.js");
/* harmony import */ var _Support__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./Support */ "./src/Settings/Support.js");
/* harmony import */ var _LearningMode__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./LearningMode */ "./src/Settings/LearningMode.js");
/* harmony import */ var _ChangeStatus__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./ChangeStatus */ "./src/Settings/ChangeStatus.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
















/*
 * https://react-data-table-component.netlify.app
 */



class Field extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.scrollAnchor = false;
    this.onChangeHandlerDataTableStatus = this.onChangeHandlerDataTableStatus.bind(this);
    this.onChangeHandler = this.onChangeHandler.bind(this);
  }

  componentDidMount() {}

  componentDidUpdate() {
    if (this.props.highLightedField === this.props.field.id && this.scrollAnchor.current) {
      this.scrollAnchor.current.scrollIntoView();
    }
  }

  onChangeHandler(fieldValue) {
    let fields = this.props.fields;
    let field = this.props.field;
    fields[this.props.index]['value'] = fieldValue; //we can configure other fields if a field is enabled, or set to a certain value.

    let configureFieldCondition = false;

    if (field.configure_on_activation) {
      if (field.configure_on_activation.hasOwnProperty('condition') && this.props.field.value == field.configure_on_activation.condition) {
        configureFieldCondition = true;
      }

      let configureField = field.configure_on_activation[0];

      for (let fieldId in configureField) {
        if (configureFieldCondition && configureField.hasOwnProperty(fieldId)) {
          this.props.updateField(fieldId, configureField[fieldId]);
        }
      }
    }

    this.props.saveChangedFields(field.id);
  }
  /*
   * Handle data update for a datatable, for the status only (true/false)
   * @param enabled
   * @param clickedItem
   * @param type
   */


  onChangeHandlerDataTableStatus(enabled, clickedItem, type) {
    let field = this.props.field;
    enabled = enabled == 1 ? 0 : 1;

    if (typeof field.value === 'object') {
      field.value = Object.values(field.value);
    } //find this item in the field list


    for (const item of field.value) {
      if (item.id === clickedItem.id) {
        item[type] = enabled;
      }

      delete item.valueControl;
      delete item.statusControl;
      delete item.deleteControl;
    } //the updateItemId allows us to update one specific item in a field set.


    field.updateItemId = clickedItem.id;
    let saveFields = [];
    saveFields.push(field);
    this.props.updateField(field.id, field.value);
    _utils_api__WEBPACK_IMPORTED_MODULE_3__.setFields(saveFields).then(response => {//this.props.showSavedSettingsNotice();
    });
  }

  onCloseTaskHandler() {}

  render() {
    if (this.highLightClass) {
      this.scrollAnchor = React.createRef();
    }

    let field = this.props.field;
    let fieldValue = field.value;
    let fields = this.props.fields;
    let disabled = field.disabled;
    this.highLightClass = this.props.highLightedField === this.props.field.id ? 'rsssl-field-wrap rsssl-highlight' : 'rsssl-field-wrap'; //if an element is highlighted, get that element, and scroll into view

    let options = [];

    if (field.options) {
      for (var key in field.options) {
        if (field.options.hasOwnProperty(key)) {
          let item = {};
          item.label = field.options[key];
          item.value = key;
          options.push(item);
        }
      }
    } //if a feature can only be used on networkwide or single site setups, pass that info here.


    if (!rsssl_settings.networkwide_active && field.networkwide_required) {
      disabled = true;
      field.comment = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("This feature is only available networkwide.", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__["default"], {
        target: "_blank",
        text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Network settings", "really-simple-ssl"),
        url: rsssl_settings.network_link
      }));
    }

    if (field.conditionallyDisabled) {
      disabled = true;
    }

    if (!field.visible) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
    }

    if (field.type === 'checkbox') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToggleControl, {
        disabled: disabled,
        checked: field.value == 1,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue)
      }), field.comment && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-comment",
        dangerouslySetInnerHTML: {
          __html: field.comment
        }
      }));
    }

    if (field.type === 'hidden') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
        type: "hidden",
        value: field.value
      });
    }

    if (field.type === 'radio') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        selected: fieldValue,
        options: options
      }));
    }

    if (field.type === 'text' || field.type === 'email') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
        required: field.required,
        disabled: disabled,
        help: field.comment,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        value: fieldValue
      }));
    }

    if (field.type === 'button') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: 'rsssl-field-button ' + this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, field.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__["default"], {
        className: "button button-default",
        text: field.button_text,
        url: field.url
      }));
    }

    if (field.type === 'password') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Password__WEBPACK_IMPORTED_MODULE_5__["default"], {
        index: this.props.index,
        field: field,
        fields: this.props.fields,
        saveChangedFields: this.props.saveChangedFields
      }));
    }

    if (field.type === 'textarea') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
        label: field.label,
        disabled: disabled,
        help: field.comment,
        value: fieldValue,
        onChange: fieldValue => this.onChangeHandler(fieldValue)
      }));
    }

    if (field.type === 'license') {
      let field = this.props.field;
      let fieldValue = field.value;
      let fields = this.props.fields;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_License__WEBPACK_IMPORTED_MODULE_4__["default"], {
        setPageProps: this.props.setPageProps,
        fieldsUpdateComplete: this.props.fieldsUpdateComplete,
        index: this.props.index,
        fields: fields,
        field: field,
        fieldValue: fieldValue,
        saveChangedFields: this.props.saveChangedFields,
        highLightField: this.props.highLightField,
        highLightedField: this.props.highLightedField
      }));
    }

    if (field.type === 'number') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalNumberControl, {
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        help: field.comment,
        label: field.label,
        value: fieldValue
      }));
    }

    if (field.type === 'email') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
        help: field.comment,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        value: fieldValue
      }));
    }

    if (field.type === 'host') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Host__WEBPACK_IMPORTED_MODULE_6__["default"], {
        index: this.props.index,
        saveChangedFields: this.props.saveChangedFields,
        handleNextButtonDisabled: this.props.handleNextButtonDisabled,
        updateFields: this.props.updateFields,
        fields: this.props.fields,
        field: this.props.field
      }));
    }

    if (field.type === 'select') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl, {
        disabled: disabled,
        help: field.comment,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        value: fieldValue,
        options: options
      }));
    }

    if (field.type === 'support') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Support__WEBPACK_IMPORTED_MODULE_12__["default"], null));
    }

    if (field.type === 'permissionspolicy') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_PermissionsPolicy__WEBPACK_IMPORTED_MODULE_11__["default"], {
        disabled: disabled,
        updateField: this.props.updateField,
        field: this.props.field,
        options: options,
        highLightClass: this.highLightClass,
        fields: fields
      }));
    }

    if (field.type === 'learningmode') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LearningMode__WEBPACK_IMPORTED_MODULE_13__["default"], {
        disabled: disabled,
        onChangeHandlerDataTableStatus: this.onChangeHandlerDataTableStatus,
        updateField: this.props.updateField,
        field: this.props.field,
        options: options,
        highLightClass: this.highLightClass,
        fields: fields
      }));
    }

    if (field.type === 'mixedcontentscan') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: this.highLightClass,
        ref: this.scrollAnchor
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_MixedContentScan__WEBPACK_IMPORTED_MODULE_10__["default"], {
        dropItemFromModal: this.props.dropItemFromModal,
        handleModal: this.props.handleModal,
        field: this.props.field,
        fields: this.props.selectedFields
      }));
    }

    if (field.type === 'letsencrypt') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LetsEncrypt_LetsEncrypt__WEBPACK_IMPORTED_MODULE_8__["default"], {
        key: field.id,
        resetRefreshTests: this.props.resetRefreshTests,
        refreshTests: this.props.refreshTests,
        getFieldValue: this.props.getFieldValue,
        save: this.props.save,
        selectMenu: this.props.selectMenu,
        addHelp: this.props.addHelp,
        updateField: this.props.updateField,
        fields: this.props.fields,
        field: field,
        handleNextButtonDisabled: this.props.handleNextButtonDisabled
      });
    }

    if (field.type === 'activate') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LetsEncrypt_Activate__WEBPACK_IMPORTED_MODULE_9__["default"], {
        key: field.id,
        selectMainMenu: this.props.selectMainMenu,
        resetRefreshTests: this.props.resetRefreshTests,
        refreshTests: this.props.refreshTests,
        getFieldValue: this.props.getFieldValue,
        save: this.props.save,
        selectMenu: this.props.selectMenu,
        addHelp: this.props.addHelp,
        updateField: this.props.updateField,
        fields: this.props.fields,
        field: field,
        handleNextButtonDisabled: this.props.handleNextButtonDisabled
      });
    }

    return 'not found field type ' + field.type;
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Field);

/***/ }),

/***/ "./src/Settings/Help.js":
/*!******************************!*\
  !*** ./src/Settings/Help.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);




/**
 * Render a help notice in the sidebar
 */

class Help extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  render() {
    let notice = this.props.help;

    if (!notice.title) {
      notice.title = notice.text;
      notice.text = false;
    }

    let openStatus = this.props.noticesExpanded ? 'open' : ''; //we can use notice.linked_field to create a visual link to the field.

    let target = notice.url && notice.url.indexOf("really-simple-ssl.com") !== -1 ? "_blank" : '_self';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, notice.title && notice.text && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("details", {
      className: "rsssl-wizard-help-notice rsssl-" + notice.label.toLowerCase(),
      open: openStatus
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("summary", null, notice.title, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_1__["default"], {
      name: "chevron-down"
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      dangerouslySetInnerHTML: {
        __html: notice.text
      }
    }), notice.url && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-help-more-info"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      target: target,
      href: notice.url
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("More info", "really-simple-ssl")))), notice.title && !notice.text && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-help-notice rsssl-" + notice.label.toLowerCase()
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, notice.title)));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Help);

/***/ }),

/***/ "./src/Settings/Host.js":
/*!******************************!*\
  !*** ./src/Settings/Host.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");






class Host extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.disabled = false;
  }

  onChangeHandler(fieldValue) {
    let fields = this.props.fields;
    let field = this.props.field;
    field.value = fieldValue;
    fields[this.props.index]['value'] = fieldValue; //force update, and get new fields.

    this.disabled = true;
    let saveFields = [];
    this.props.handleNextButtonDisabled(true);
    saveFields.push(field);
    _utils_api__WEBPACK_IMPORTED_MODULE_3__.setFields(saveFields).then(response => {
      this.props.updateFields(response.fields);
      this.disabled = false;
      this.props.handleNextButtonDisabled(false);
    });
  }

  render() {
    let fieldValue = this.props.field.value;
    let field = this.props.field;
    let options = [];

    if (field.options) {
      for (var key in field.options) {
        if (field.options.hasOwnProperty(key)) {
          let item = {};
          item.label = field.options[key];
          item.value = key;
          options.push(item);
        }
      }
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl, {
      label: field.label,
      onChange: fieldValue => this.onChangeHandler(fieldValue),
      value: fieldValue,
      options: options,
      disabled: this.disabled
    });
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Host);

/***/ }),

/***/ "./src/Settings/LearningMode.js":
/*!**************************************!*\
  !*** ./src/Settings/LearningMode.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _ChangeStatus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ChangeStatus */ "./src/Settings/ChangeStatus.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");








class Delete extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: " rsssl-learning-mode-delete",
      onClick: () => this.props.onDeleteHandler(this.props.item)
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 320 512",
      height: "16"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: "#000000",
      d: "M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"
    })));
  }

}

class LearningMode extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      enforced_by_thirdparty: 0,
      enforce: 0,
      learning_mode: 0,
      lm_enabled_once: 0,
      learning_mode_completed: 0,
      filterValue: -1
    };
  }

  componentDidMount() {
    this.doFilter = this.doFilter.bind(this);
    this.onDeleteHandler = this.onDeleteHandler.bind(this);
    let field = this.props.fields.filter(field => field.id === this.props.field.control_field)[0];
    let enforced_by_thirdparty = field.value === 'enforced-by-thirdparty';
    let enforce = enforced_by_thirdparty || field.value === 'enforce';
    let learning_mode = field.value === 'learning_mode';
    let learning_mode_completed = field.value === 'completed';
    let lm_enabled_once_field_name = this.props.field.control_field + '_lm_enabled_once';
    let lm_enabled_once_field = this.props.fields.filter(field => field.id === lm_enabled_once_field_name)[0];
    let lm_enabled_once = lm_enabled_once_field.value; //we somehow need this to initialize the field. Otherwise it doesn't work on load. need to figure that out.

    this.props.updateField(field.id, field.value);
    this.setState({
      enforced_by_thirdparty: enforced_by_thirdparty,
      enforce: enforce,
      learning_mode: learning_mode,
      lm_enabled_once: lm_enabled_once,
      learning_mode_completed: learning_mode_completed
    });
  }

  doFilter(e) {
    this.setState({
      filterValue: e.target.value
    });
  }

  toggleEnforce(e, enforce) {
    e.preventDefault();
    let fields = this.props.fields;
    let field = fields.filter(field => field.id === this.props.field.control_field)[0]; //enforce this setting

    field.value = enforce == 1 ? 'enforce' : 'disabled';
    this.setState({
      enforce: enforce,
      learning_mode_completed: 0
    });
    let saveFields = [];
    saveFields.push(field);
    _utils_api__WEBPACK_IMPORTED_MODULE_4__.setFields(saveFields).then(response => {});
  }

  toggleLearningMode(e) {
    e.preventDefault();
    let fields = this.props.fields;
    let field = fields.filter(field => field.id === this.props.field.control_field)[0];
    let lm_enabled_once_field_name = this.props.field.control_field + '_lm_enabled_once';
    let lm_enabled_once_field = fields.filter(field => field.id === lm_enabled_once_field_name)[0];
    let learning_mode = field.value === 'learning_mode' ? 1 : 0;
    let learning_mode_completed = field.value === 'completed' ? 1 : 0;

    if (learning_mode) {
      lm_enabled_once_field.value = 1;
    }

    field.value = learning_mode || learning_mode_completed ? 'disabled' : 'learning_mode';

    if (learning_mode || learning_mode_completed) {
      learning_mode = 0;
    } else {
      learning_mode = 1;
    }

    learning_mode_completed = 0;
    this.setState({
      learning_mode: learning_mode,
      lm_enabled_once: lm_enabled_once_field.value,
      learning_mode_completed: learning_mode_completed
    });
    let saveFields = [];
    saveFields.push(field);
    saveFields.push(lm_enabled_once_field);
    _utils_api__WEBPACK_IMPORTED_MODULE_4__.setFields(saveFields).then(response => {});
  }
  /*
   * Handle data delete
   * @param enabled
   * @param clickedItem
   * @param type
   */


  onDeleteHandler(clickedItem) {
    let field = this.props.field;

    if (typeof field.value === 'object') {
      field.value = Object.values(field.value);
    } //find this item in the field list and remove it.


    field.value.forEach(function (item, i) {
      if (item.id === clickedItem.id) {
        field.value.splice(i, 1);
      }
    }); //remove objects from values

    for (const item of field.value) {
      delete item.valueControl;
      delete item.statusControl;
      delete item.deleteControl;
    } //the updateItemId allows us to update one specific item in a field set.


    field.updateItemId = clickedItem.id;
    field.action = 'delete';
    let saveFields = [];
    saveFields.push(field);
    this.props.updateField(field.id, field.value);
    _utils_api__WEBPACK_IMPORTED_MODULE_4__.setFields(saveFields).then(response => {});
  }

  render() {
    let field = this.props.field;
    let fieldValue = field.value;
    let options = this.props.options;

    let configuringString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)(" The %s is now in report-only mode and will collect directives. This might take a while. Afterwards you can Exit, Edit and Enforce these Directives.", "really-simple-ssl").replace('%s', field.label);

    let disabledString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("%s has been disabled.", "really-simple-ssl").replace('%s', field.label);

    let enforcedString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("%s is enforced.", "really-simple-ssl").replace('%s', field.label);

    const {
      filterValue,
      enforced_by_thirdparty,
      enforce,
      learning_mode,
      lm_enabled_once,
      learning_mode_completed
    } = this.state;
    let enforceDisabled = !lm_enabled_once;
    if (enforced_by_thirdparty) disabledString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("%s is already set outside Really Simple SSL.", "really-simple-ssl").replace('%s', field.label);

    const Filter = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
      onChange: e => this.doFilter(e),
      value: filterValue
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "-1"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("All", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "1"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Allowed", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "0"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Blocked", "really-simple-ssl")))); //build our header


    columns = [];
    field.columns.forEach(function (item, i) {
      let newItem = {
        name: item.name,
        sortable: item.sortable,
        width: item.width,
        selector: row => row[item.column]
      };
      columns.push(newItem);
    });
    let data = field.value;

    if (typeof data === 'object') {
      data = Object.values(data);
    }

    if (!Array.isArray(data)) {
      data = [];
    }

    data = data.filter(item => item.status < 2);

    if (filterValue != -1) {
      data = data.filter(item => item.status == filterValue);
    }

    for (const item of data) {
      if (item.login_status) item.login_statusControl = item.login_status == 1 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("success", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("failed", "really-simple-ssl");
      item.statusControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ChangeStatus__WEBPACK_IMPORTED_MODULE_2__["default"], {
        item: item,
        onChangeHandlerDataTableStatus: this.props.onChangeHandlerDataTableStatus
      });
      item.deleteControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Delete, {
        item: item,
        onDeleteHandler: this.onDeleteHandler
      });
    }

    const conditionalRowStyles = [{
      when: row => row.status == 0,
      classNames: ['rsssl-datatables-revoked']
    }];
    const customStyles = {
      headCells: {
        style: {
          paddingLeft: '0',
          // override the cell padding for head cells
          paddingRight: '0'
        }
      },
      cells: {
        style: {
          paddingLeft: '0',
          // override the cell padding for data cells
          paddingRight: '0'
        }
      }
    };
    Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())('really-simple-plugins', {
      divider: {
        default: 'transparent'
      }
    }, 'light');
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: this.highLightClass
    }, data.length == 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-learningmode-placeholder"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null))), data.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()), {
      columns: columns,
      data: data,
      dense: true,
      pagination: true,
      noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("No results", "really-simple-ssl"),
      persistTableHead: true,
      theme: "really-simple-plugins",
      customStyles: customStyles,
      conditionalRowStyles: conditionalRowStyles
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-learning-mode-footer"
    }, enforce != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      disabled: enforceDisabled,
      className: "button button-primary",
      onClick: e => this.toggleEnforce(e, true)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enforce", "really-simple-ssl")), !enforced_by_thirdparty && enforce == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      onClick: e => this.toggleEnforce(e, false)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Disable", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "checkbox",
      disabled: enforce,
      checked: learning_mode == 1,
      value: learning_mode,
      onChange: e => this.toggleLearningMode(e)
    }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enable Learning Mode to configure automatically", "really-simple-ssl")), enforce == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-shield-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_5__["default"], {
      name: "shield",
      size: "80px"
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-learning-mode-enforced"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enforced", "really-simple-ssl")), enforcedString, "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-learning-mode-link",
      href: "#",
      onClick: e => this.toggleEnforce(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Disable to configure", "really-simple-ssl")))), learning_mode == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-learning-mode"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Learning Mode", "really-simple-ssl")), configuringString, "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-learning-mode-link",
      href: "#",
      onClick: e => this.toggleLearningMode(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Exit", "really-simple-ssl")))), learning_mode_completed == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-learning-mode-completed"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Learning Mode", "really-simple-ssl")), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("We finished the configuration.", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-learning-mode-link",
      href: "#",
      onClick: e => this.toggleLearningMode(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Review the settings and enforce the policy", "really-simple-ssl")))), rsssl_settings.pro_plugin_active && this.props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, !enforced_by_thirdparty && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-disabled"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Disabled ", "really-simple-ssl")), enforced_by_thirdparty && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-learning-mode-enforced"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enforced", "really-simple-ssl")), disabledString)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Filter, null))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (LearningMode);

/***/ }),

/***/ "./src/Settings/License.js":
/*!*********************************!*\
  !*** ./src/Settings/License.js ***!
  \*********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _DashBoard_TaskElement__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../DashBoard/TaskElement */ "./src/DashBoard/TaskElement.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);







class License extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.noticesLoaded = false;
    this.fieldsUpdateComplete = false;
    this.licenseStatus = 'invalid';
    this.getLicenseNotices = this.getLicenseNotices.bind(this);
    this.state = {
      licenseStatus: 'invalid',
      noticesLoaded: false,
      notices: []
    };
  }

  getLicenseNotices() {
    return _utils_api__WEBPACK_IMPORTED_MODULE_3__.runTest('licenseNotices', 'refresh').then(response => {
      return response;
    });
  }

  componentDidMount() {
    this.props.highLightField('');
    this.setState({
      noticesLoaded: this.noticesLoaded,
      licenseStatus: this.licenseStatus,
      notices: this.notices
    });
  }

  componentDidUpdate(prevProps) {
    if (!this.fieldsUpdateComplete && this.props.fieldsUpdateComplete) {
      this.getLicenseNotices().then(response => {
        this.fieldsUpdateComplete = this.props.fieldsUpdateComplete;
        this.props.setPageProps('licenseStatus', response.licenseStatus);
        this.notices = response.notices;
        this.licenseStatus = response.licenseStatus;
        this.noticesLoaded = true;
        this.setState({
          noticesLoaded: this.noticesLoaded,
          licenseStatus: this.licenseStatus,
          notices: this.notices
        });
      });
    }
  }

  onChangeHandler(fieldValue) {
    this.fieldsUpdateComplete = false;
    let fields = this.props.fields;
    let field = this.props.field;
    fields[this.props.index]['value'] = fieldValue;
    this.props.saveChangedFields(field.id);
    this.setState({
      fields: fields
    });
  }

  onCloseTaskHandler() {}

  toggleActivation() {
    this.setState({
      noticesLoaded: false
    });
    const {
      licenseStatus
    } = this.state;

    if (licenseStatus === 'valid') {
      _utils_api__WEBPACK_IMPORTED_MODULE_3__.runTest('deactivate_license').then(response => {
        this.props.setPageProps('licenseStatus', response.licenseStatus);
        this.notices = response.notices;
        this.licenseStatus = response.licenseStatus;
        this.noticesLoaded = true;
        this.setState({
          noticesLoaded: this.noticesLoaded,
          licenseStatus: this.licenseStatus,
          notices: this.notices
        });
      });
    } else {
      let data = {};
      data.license = this.props.field.value;
      _utils_api__WEBPACK_IMPORTED_MODULE_3__.doAction('activate_license', data).then(response => {
        this.props.setPageProps('licenseStatus', response.licenseStatus);
        this.notices = response.notices;
        this.licenseStatus = response.licenseStatus;
        this.noticesLoaded = true;
        this.setState({
          noticesLoaded: this.noticesLoaded,
          licenseStatus: this.licenseStatus,
          notices: this.notices
        });
      });
    }
  }

  render() {
    const {
      noticesLoaded,
      notices,
      licenseStatus
    } = this.state;
    let field = this.props.field;
    let fieldValue = field.value;
    let fields = this.props.fields;
    /**
     * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
     */

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "components-base-control"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "components-base-control__field"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      className: "components-base-control__label",
      htmlFor: field.id
    }, field.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-license-field"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      className: "components-text-control__input",
      type: "password",
      id: field.id,
      value: fieldValue,
      onChange: e => this.onChangeHandler(e.target.value)
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button button-default",
      onClick: () => this.toggleActivation()
    }, licenseStatus === 'valid' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Deactivate", "really-simple-ssl")), licenseStatus !== 'valid' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Activate", "really-simple-ssl"))))), !noticesLoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_2__["default"], null), noticesLoaded && notices.map((notice, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DashBoard_TaskElement__WEBPACK_IMPORTED_MODULE_1__["default"], {
      key: i,
      index: i,
      notice: notice,
      onCloseTaskHandler: this.onCloseTaskHandler,
      highLightField: ""
    })));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (License);

/***/ }),

/***/ "./src/Settings/MixedContentScan.js":
/*!******************************************!*\
  !*** ./src/Settings/MixedContentScan.js ***!
  \******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _Modal_ModalControl__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Modal/ModalControl */ "./src/Modal/ModalControl.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");










class subHeaderComponentMemo extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("All results", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Show", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("All results", "really-simple-ssl")));
  }

}

class MixedContentScan extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.nonce = '';
    this.state = {
      data: [],
      progress: 0,
      action: '',
      state: 'stop',
      paused: false,
      showIgnoredUrls: false,
      resetPaginationToggle: false
    };
  }

  getScanStatus() {
    return _utils_api__WEBPACK_IMPORTED_MODULE_4__.runTest('scan_status', 'refresh').then(response => {
      return response;
    });
  }

  componentDidMount() {
    let data = [];
    let progress = 0;
    let action = '';
    let state = 'stop';
    let completedStatus = 'never';

    if (this.props.field.value.data) {
      data = this.props.field.value.data;
    }

    if (this.props.field.value.progress) {
      progress = this.props.field.value.progress;
    }

    if (this.props.field.value.action) {
      action = this.props.field.value.action;
    }

    if (this.props.field.value.state) {
      state = this.props.field.value.state;
    }

    if (this.props.field.value.completed_status) {
      completedStatus = this.props.field.value.completed_status;
    }

    if (this.props.field.value.nonce) {
      this.nonce = this.props.field.value.nonce;
    }

    this.setState({
      completedStatus: completedStatus,
      data: data,
      progress: progress,
      action: action,
      state: state
    });
  }

  start(e) {
    //add start_full option
    let state = 'start';

    if (this.state.paused) {
      state = 'running';
    }

    this.setState({
      state: 'running',
      paused: false
    });
    _utils_api__WEBPACK_IMPORTED_MODULE_4__.runTest('mixed_content_scan', state).then(response => {
      this.setState({
        data: response.data,
        progress: response.progress,
        action: response.action,
        state: response.state
      });

      if (response.state === 'running') {
        this.run();
      }
    });
  }

  run(e) {
    if (this.state.paused) {
      return;
    }

    _utils_api__WEBPACK_IMPORTED_MODULE_4__.runTest('mixed_content_scan', 'running').then(response => {
      this.setState({
        completedStatus: response.completed_status,
        data: response.data,
        progress: response.progress,
        action: response.action,
        state: response.state
      }); //if scan was stopped while running, set it to stopped now.

      if (this.state.paused) {
        this.stop();
      } else if (response.state === 'running') {
        this.run();
      }
    });
  }

  toggleIgnoredUrls(e) {
    let {
      showIgnoredUrls
    } = this.state;
    this.setState({
      showIgnoredUrls: !showIgnoredUrls
    });
  }

  stop(e) {
    this.setState({
      state: 'stop',
      paused: true
    });
    _utils_api__WEBPACK_IMPORTED_MODULE_4__.runTest('mixed_content_scan', 'stop').then(response => {
      this.setState({
        completedStatus: response.completed_status,
        data: response.data,
        progress: response.progress,
        action: response.action
      });
    });
  }
  /**
   * After an update, remove an item from the data array
   * @param removeItem
   */


  removeDataItem(removeItem) {
    const updatedData = this.state.data.filter(item => item.id === removeItem.id);
    this.setState({
      data: updatedData
    });
  }

  render() {
    let {
      completedStatus,
      data,
      action,
      progress,
      state,
      showIgnoredUrls,
      resetPaginationToggle
    } = this.state;
    let field = this.props.field;
    let fieldValue = field.value;
    let fields = this.props.fields;
    if (!rsssl_settings.pro_plugin_active) progress = 80;
    columns = [];
    field.columns.forEach(function (item, i) {
      let newItem = {
        name: item.name,
        sortable: item.sortable,
        grow: item.grow,
        selector: row => row[item.column],
        right: !!item.right
      };
      columns.push(newItem);
    });

    if (typeof data === 'object') {
      data = Object.values(data);
    }

    if (!Array.isArray(data)) {
      data = [];
    }

    completedStatus = completedStatus ? completedStatus.toLowerCase() : 'never';
    let dropItem = this.props.dropItemFromModal;

    for (const item of data) {
      item.warningControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
        className: "rsssl-task-status rsssl-warning"
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Warning", "really-simple-ssl")); //@todo check action for correct filter or drop action.

      if (dropItem && dropItem.url === item.blocked_url) {
        if (dropItem.action === 'ignore_url') {
          item.ignored = true;
        } else {
          item.fixed = true;
        }
      } //give fix and details the url as prop


      if (item.fix) {
        item.fix.url = item.blocked_url;
        item.fix.nonce = this.nonce;
      }

      if (item.details) {
        item.details.url = item.blocked_url;
        item.details.nonce = this.nonce;
        item.details.ignored = item.ignored;
      }

      if (item.location.length > 0) {
        if (item.location.indexOf('http://') !== -1 || item.location.indexOf('https://') !== -1) {
          item.locationControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
            href: item.location,
            target: "_blank"
          }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("View", "really-simple-ssl"));
        } else {
          item.locationControl = item.location;
        }
      }

      item.detailsControl = item.details && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Modal_ModalControl__WEBPACK_IMPORTED_MODULE_5__["default"], {
        removeDataItem: this.removeDataItem,
        handleModal: this.props.handleModal,
        item: item,
        btnText: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Details", "really-simple-ssl"),
        btnStyle: "secondary",
        modalData: item.details
      });
      item.fixControl = item.fix && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Modal_ModalControl__WEBPACK_IMPORTED_MODULE_5__["default"], {
        className: "button button-primary",
        removeDataItem: this.removeDataItem,
        handleModal: this.props.handleModal,
        item: item,
        btnText: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Fix", "really-simple-ssl"),
        btnStyle: "primary",
        modalData: item.fix
      });
    }

    if (!showIgnoredUrls) {
      data = data.filter(item => !item.ignored);
    } //filter also recently fixed items


    data = data.filter(item => !item.fixed);
    progress += '%';
    let startDisabled = state === 'running';
    let stopDisabled = state !== 'running';

    let label = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Show ignored URLs", 'burst-statistics');

    const customStyles = {
      headCells: {
        style: {
          paddingLeft: '0',
          // override the cell padding for head cells
          paddingRight: '0'
        }
      },
      cells: {
        style: {
          paddingLeft: '0',
          // override the cell padding for data cells
          paddingRight: '0'
        }
      }
    };
    Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())('really-simple-plugins', {
      divider: {
        default: 'transparent'
      }
    }, 'light');
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress-container"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress-bar",
      style: {
        width: progress
      }
    })), state === 'running' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-current-scan-action"
    }, action), data.length == 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-mixed-content-description"
    }, state !== 'running' && completedStatus === 'never' && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("No results. Start your first scan", "really-simple-ssl"), state !== 'running' && completedStatus === 'completed' && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Everything is now served over SSL", "really-simple-ssl")), (state === 'running' || completedStatus !== 'completed') && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-mixed-content-placeholder"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null)), state !== 'running' && completedStatus === 'completed' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-shield-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_7__["default"], {
      name: "shield",
      size: "80px"
    }))), data.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: 'rsssl-mixed-content-datatable'
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()), {
      columns: columns,
      data: data,
      dense: true,
      pagination: true,
      paginationResetDefaultPage: resetPaginationToggle // optionally, a hook to reset pagination to page 1
      ,
      noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("No results", "really-simple-ssl") //or your component
      ,
      theme: "really-simple-plugins",
      customStyles: customStyles // subHeader
      // subHeaderComponent=<subHeaderComponentMemo/>

    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content-footer"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      disabled: startDisabled,
      onClick: e => this.start(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Start scan", "really-simple-ssl-pro")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      disabled: stopDisabled,
      onClick: e => this.stop(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Stop", "really-simple-ssl-pro")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
      checked: showIgnoredUrls == 1,
      onChange: e => this.toggleIgnoredUrls(e)
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Show ignored URLs', 'burst-statistics'))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (MixedContentScan);

/***/ }),

/***/ "./src/Settings/Notices.js":
/*!*********************************!*\
  !*** ./src/Settings/Notices.js ***!
  \*********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/notices */ "@wordpress/notices");
/* harmony import */ var _wordpress_notices__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_notices__WEBPACK_IMPORTED_MODULE_3__);


/**
 * Notice after saving was successfull
 */




const Notices = () => {
  const notices = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useSelect)(select => select(_wordpress_notices__WEBPACK_IMPORTED_MODULE_3__.store).getNotices().filter(notice => notice.type === 'snackbar'), []);

  if (typeof notices === 'undefined') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }

  const {
    removeNotice
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useDispatch)(_wordpress_notices__WEBPACK_IMPORTED_MODULE_3__.store);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SnackbarList, {
    className: "edit-site-notices",
    notices: notices,
    onRemove: removeNotice
  });
};

/* harmony default export */ __webpack_exports__["default"] = (Notices);

/***/ }),

/***/ "./src/Settings/Password.js":
/*!**********************************!*\
  !*** ./src/Settings/Password.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);



class Password extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  onChangeHandler(fieldValue) {
    let fields = this.props.fields;
    let field = this.props.field;
    fields[this.props.index]['value'] = fieldValue;
    this.props.saveChangedFields(field.id);
    this.setState({
      fields: fields
    });
  }

  render() {
    let field = this.props.field;
    let fieldValue = field.value;
    let fields = this.props.fields;
    /**
     * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
     */

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "components-base-control"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "components-base-control__field"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      className: "components-base-control__label",
      htmlFor: field.id
    }, field.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      className: "components-text-control__input",
      type: "password",
      id: field.id,
      value: fieldValue,
      onChange: e => this.onChangeHandler(e.target.value)
    })));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Password);

/***/ }),

/***/ "./src/Settings/PermissionsPolicy.js":
/*!*******************************************!*\
  !*** ./src/Settings/PermissionsPolicy.js ***!
  \*******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _ChangeStatus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./ChangeStatus */ "./src/Settings/ChangeStatus.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");









class PermissionsPolicy extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      enable_permissions_policy: 0
    };
  }

  componentDidMount() {
    this.togglePermissionsPolicyStatus = this.togglePermissionsPolicyStatus.bind(this);
    this.onChangeHandler = this.onChangeHandler.bind(this);
    let field = this.props.fields.filter(field => field.id === 'enable_permissions_policy')[0];
    this.setState({
      enable_permissions_policy: field.value
    });
  }

  onChangeHandler(value, clickedItem) {
    let field = this.props.field;

    if (typeof field.value === 'object') {
      field.value = Object.values(field.value);
    } //find this item in the field list


    for (const item of field.value) {
      if (item.id === clickedItem.id) {
        item['value'] = value;
      }

      delete item.valueControl;
      delete item.statusControl;
      delete item.deleteControl;
    } //the updateItemId allows us to update one specific item in a field set.


    field.updateItemId = clickedItem.id;
    let saveFields = [];
    saveFields.push(field);
    this.props.updateField(field.id, field.value);
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.setFields(saveFields).then(response => {//this.props.showSavedSettingsNotice();
    });
  }

  togglePermissionsPolicyStatus(e, enforce) {
    e.preventDefault();
    let fields = this.props.fields; //look up permissions policy enable field //enable_permissions_policy

    let field = fields.filter(field => field.id === 'enable_permissions_policy')[0]; //enforce this setting

    field.value = enforce;
    this.setState({
      enable_permissions_policy: enforce
    });
    let saveFields = [];
    saveFields.push(field);
    this.props.updateField(field.id, field.value);
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.setFields(saveFields).then(response => {//this.props.showSavedSettingsNotice();
    });
  }

  render() {
    let field = this.props.field;
    let fieldValue = field.value;
    let options = this.props.options;
    const {
      enable_permissions_policy
    } = this.state;
    columns = [];
    field.columns.forEach(function (item, i) {
      let newItem = {
        name: item.name,
        sortable: item.sortable,
        width: item.width,
        selector: row => row[item.column]
      };
      columns.push(newItem);
    });
    let data = field.value;

    if (typeof data === 'object') {
      data = Object.values(data);
    }

    if (!Array.isArray(data)) {
      data = [];
    }

    let disabled = false;

    for (const item of data) {
      item.valueControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl, {
        help: "",
        value: item.value,
        disabled: disabled,
        options: options,
        label: "",
        onChange: fieldValue => this.onChangeHandler(fieldValue, item, 'value')
      });
    }

    const customStyles = {
      headCells: {
        style: {
          paddingLeft: '0',
          // override the cell padding for head cells
          paddingRight: '0'
        }
      },
      cells: {
        style: {
          paddingLeft: '0',
          // override the cell padding for data cells
          paddingRight: '0'
        }
      }
    };
    Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())('really-simple-plugins', {
      divider: {
        default: 'transparent'
      }
    }, 'light');
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: this.props.highLightClass
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()), {
      columns: columns,
      data: data,
      dense: true,
      pagination: false,
      customStyles: customStyles,
      theme: "really-simple-plugins"
    }), enable_permissions_policy != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button button-primary",
      onClick: e => this.togglePermissionsPolicyStatus(e, true)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Enforce", "really-simple-ssl")), enable_permissions_policy == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-shield-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_6__["default"], {
      name: "shield",
      size: "80px"
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-learning-mode-enforced"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Enforced", "really-simple-ssl")), this.props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Permissions Policy is set outside Really Simple SSL.", "really-simple-ssl"), "\xA0"), !this.props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Permissions Policy is enforced.", "really-simple-ssl"), "\xA0"), !this.props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-learning-mode-link",
      href: "#",
      onClick: e => this.togglePermissionsPolicyStatus(e, false)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Disable", "really-simple-ssl")))), this.props.disabled && enable_permissions_policy != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-disabled"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Disabled ", "really-simple-ssl")), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("The Permissions Policy has been disabled.", "really-simple-ssl"))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (PermissionsPolicy);

/***/ }),

/***/ "./src/Settings/Settings.js":
/*!**********************************!*\
  !*** ./src/Settings/Settings.js ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _utils_lib__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/lib */ "./src/utils/lib.js");
/* harmony import */ var _SettingsGroup__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SettingsGroup */ "./src/Settings/SettingsGroup.js");
/* harmony import */ var _Help__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Help */ "./src/Settings/Help.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);








/**
 * Renders the selected settings
 *
 */

class Settings extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      noticesExpanded: true
    };
    this.toggleNotices = this.toggleNotices.bind(this);
    this.saveAndContinue = this.saveAndContinue.bind(this);
    this.save = this.save.bind(this);
  }

  componentDidMount() {}

  toggleNotices() {
    const {
      noticesExpanded
    } = this.state;
    this.setState({
      noticesExpanded: !noticesExpanded
    });
  }

  save() {
    this.props.save();
  }

  saveAndContinue() {
    if (!this.props.nextButtonDisabled) {
      this.props.saveAndContinue();
    }
  }

  render() {
    let isAPILoaded = this.props.isAPILoaded;
    let progress = this.props.progress;
    let selectedMenuItem = this.props.selectedMenuItem;
    let fields = this.props.fields;
    let selectedStep = this.props.selectedStep;
    let menu = this.props.menu;
    const {
      menu_items: menuItems
    } = menu;
    const {
      noticesExpanded
    } = this.state;

    if (!isAPILoaded) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_1__["default"], null);
    }

    let selectedFields = fields.filter(field => field.menu_id === selectedMenuItem);
    let groups = [];

    for (const selectedField of selectedFields) {
      if (!(0,_utils_lib__WEBPACK_IMPORTED_MODULE_2__.in_array)(selectedField.group_id, groups)) {
        groups.push(selectedField.group_id);
      }
    }

    let btnSaveText = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Save', 'really-simple-ssl');

    for (const menuItem of menuItems) {
      if (menuItem.id === selectedMenuItem && menuItem.tests_only) {
        btnSaveText = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Refresh', 'really-simple-ssl');
      }
    } //convert progress notices to an array useful for the help blocks


    let notices = [];

    for (const notice of progress.notices) {
      let noticeIsLinkedToField = false; //notices that are linked to a field. Only in case of warnings.

      if (notice.show_with_options && notice.output.icon === 'warning') {
        let noticeFields = selectedFields.filter(field => notice.show_with_options.includes(field.id));
        noticeIsLinkedToField = noticeFields.length > 0;
      } //notices that are linked to a menu id.


      if (noticeIsLinkedToField || notice.menu_id === selectedMenuItem) {
        let help = {};
        help.title = notice.output.title ? notice.output.title : false;
        help.label = notice.output.label;
        help.id = notice.id;
        help.text = notice.output.msg;
        help.url = notice.output.url;
        help.linked_field = notice.show_with_option;
        notices.push(help);
      }
    }

    for (const notice of selectedFields.filter(field => field.help)) {
      let help = notice.help;
      help.id = notice.id;
      notices.push(notice.help);
    }

    notices = notices.filter(notice => notice.label.toLowerCase() !== 'completed');
    let continueLink = this.props.nextButtonDisabled ? `#${this.props.selectedMainMenuItem}/${this.props.selectedMenuItem}` : `#${this.props.selectedMainMenuItem}/${this.props.nextMenuItem}`;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-settings"
    }, groups.map((group, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SettingsGroup__WEBPACK_IMPORTED_MODULE_3__["default"], {
      updateFields: this.props.updateFields,
      dropItemFromModal: this.props.dropItemFromModal,
      selectMenu: this.props.selectMenu,
      selectMainMenu: this.props.selectMainMenu,
      handleNextButtonDisabled: this.props.handleNextButtonDisabled,
      menu: this.props.menu,
      handleModal: this.props.handleModal,
      showSavedSettingsNotice: this.props.showSavedSettingsNotice,
      updateField: this.props.updateField,
      getFieldValue: this.props.getFieldValue,
      refreshTests: this.props.refreshTests,
      resetRefreshTests: this.props.resetRefreshTests,
      addHelp: this.props.addHelp,
      pageProps: this.props.pageProps,
      setPageProps: this.props.setPageProps,
      fieldsUpdateComplete: this.props.fieldsUpdateComplete,
      key: i,
      index: i,
      highLightField: this.props.highLightField,
      highLightedField: this.props.highLightedField,
      selectedMenuItem: selectedMenuItem,
      saveChangedFields: this.props.saveChangedFields,
      group: group,
      fields: selectedFields
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-footer"
    }, this.props.selectedMenuItem !== menuItems[0].id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "button button-secondary",
      href: `#${this.props.selectedMainMenuItem}/${this.props.previousMenuItem}`,
      onClick: () => this.props.previousStep(true)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Previous', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button button-primary",
      onClick: this.save
    }, btnSaveText), this.props.selectedMenuItem !== menuItems[menuItems.length - 1].id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      disabled: this.props.nextButtonDisabled,
      className: "button button-primary",
      href: continueLink,
      onClick: this.saveAndContinue
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Save and Continue', 'really-simple-ssl'))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-help"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-help-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-help-title rsssl-h4"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("Notifications", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-help-control",
      onClick: () => this.toggleNotices()
    }, !noticesExpanded && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("Expand all", "really-simple-ssl"), noticesExpanded && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("Collapse all", "really-simple-ssl"))), notices.map((field, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Help__WEBPACK_IMPORTED_MODULE_4__["default"], {
      key: i,
      noticesExpanded: noticesExpanded,
      index: i,
      help: field,
      fieldId: field.id
    }))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Settings);

/***/ }),

/***/ "./src/Settings/SettingsGroup.js":
/*!***************************************!*\
  !*** ./src/Settings/SettingsGroup.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Field__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Field */ "./src/Settings/Field.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _utils_getAnchor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/getAnchor */ "./src/utils/getAnchor.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");







/**
 * Render a grouped block of settings
 */

class SettingsGroup extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      fields: this.props.fields,
      isAPILoaded: this.props.isAPILoaded
    };
    this.upgrade = 'https://really-simple-ssl.com/pro/';
    this.fields = this.props.fields;
  }

  componentDidMount() {
    this.getLicenseStatus = this.getLicenseStatus.bind(this);
    this.handleLetsEncryptReset = this.handleLetsEncryptReset.bind(this);
  }

  getLicenseStatus() {
    if (this.props.pageProps.hasOwnProperty('licenseStatus')) {
      return this.props.pageProps['licenseStatus'];
    }

    return 'invalid';
  }
  /*
  * On reset of LE, send this info to the back-end, and redirect to the first step.
  * reload to ensure that.
  */


  handleLetsEncryptReset(e) {
    e.preventDefault();
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.runLetsEncryptTest('reset').then(response => {
      let url = window.location.href.replace(/#letsencrypt.*/, '&r=' + +new Date() + '#letsencrypt/le-system-status');
      window.location.href = url;
    });
  }

  render() {
    let selectedMenuItem = this.props.selectedMenuItem;
    let selectedFields = []; //get all fields with group_id this.props.group_id

    for (const selectedField of this.props.fields) {
      if (selectedField.group_id === this.props.group) {
        selectedFields.push(selectedField);
      }
    }

    let activeGroup; //first, set the selected menu item as activate group, so we have a default in case there are no groups

    for (const item of this.props.menu.menu_items) {
      if (item.id === selectedMenuItem) {
        activeGroup = item;
      } else if (item.menu_items) {
        activeGroup = item.menu_items.filter(menuItem => menuItem.id === selectedMenuItem)[0];
      }

      if (activeGroup) {
        break;
      }
    } //now check if we have actual groups


    for (const item of this.props.menu.menu_items) {
      if (item.id === selectedMenuItem && item.hasOwnProperty('groups')) {
        let currentGroup = item.groups.filter(group => group.id === this.props.group);

        if (currentGroup.length > 0) {
          activeGroup = currentGroup[0];
        }
      }
    }

    let status = 'invalid';
    let msg = activeGroup.premium_text ? activeGroup.premium_text : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Learn more about %sPremium%s", "really-simple-ssl");

    if (rsssl_settings.pro_plugin_active) {
      status = this.getLicenseStatus();

      if (status === 'empty' || status === 'deactivated') {
        msg = rsssl_settings.messageInactive;
      } else {
        msg = rsssl_settings.messageInvalid;
      }
    }

    let disabled = status !== 'valid' && activeGroup.premium; //if a feature can only be used on networkwide or single site setups, pass that info here.

    let networkwide_error = !rsssl_settings.networkwide_active && activeGroup.networkwide_required;
    this.upgrade = activeGroup.upgrade ? activeGroup.upgrade : this.upgrade;
    let helplinkText = activeGroup.helpLink_text ? activeGroup.helpLink_text : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Instructions", "really-simple-ssl");
    let anchor = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_3__["default"])('main');
    let disabledClass = disabled || networkwide_error ? 'rsssl-disabled' : '';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item rsssl-" + activeGroup.id + ' ' + disabledClass
    }, activeGroup.title && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
      className: "rsssl-h4"
    }, activeGroup.title), activeGroup.helpLink && anchor !== 'letsencrypt' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-controls"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
      target: "_blank",
      className: "rsssl-helplink",
      text: helplinkText,
      url: activeGroup.helpLink
    })), anchor === 'letsencrypt' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-controls"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "#",
      className: "rsssl-helplink",
      onClick: e => this.handleLetsEncryptReset(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Reset Let's Encrypt", "really-simple-ssl")))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content"
    }, activeGroup.intro && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-settings-block-intro"
    }, activeGroup.intro), selectedFields.map((field, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Field__WEBPACK_IMPORTED_MODULE_1__["default"], {
      key: i,
      index: i,
      updateFields: this.props.updateFields,
      selectMenu: this.props.selectMenu,
      selectMainMenu: this.props.selectMainMenu,
      dropItemFromModal: this.props.dropItemFromModal,
      handleNextButtonDisabled: this.props.handleNextButtonDisabled,
      handleModal: this.props.handleModal,
      showSavedSettingsNotice: this.props.showSavedSettingsNotice,
      updateField: this.props.updateField,
      getFieldValue: this.props.getFieldValue,
      refreshTests: this.props.refreshTests,
      resetRefreshTests: this.props.resetRefreshTests,
      addHelp: this.props.addHelp,
      setPageProps: this.props.setPageProps,
      fieldsUpdateComplete: this.props.fieldsUpdateComplete,
      highLightField: this.props.highLightField,
      highLightedField: this.props.highLightedField,
      saveChangedFields: this.props.saveChangedFields,
      field: field,
      fields: selectedFields
    }))), disabled && !networkwide_error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-status rsssl-premium"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Upgrade", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, msg, "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-locked-link",
      href: "#settings/license"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Check license", "really-simple-ssl"))), !rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
      target: "_blank",
      text: msg,
      url: this.upgrade
    })))), networkwide_error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-status rsssl-warning"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Network feature", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("This feature is only available networkwide.", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Network settings", "really-simple-ssl"),
      url: rsssl_settings.network_link
    })))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (SettingsGroup);

/***/ }),

/***/ "./src/Settings/SettingsPage.js":
/*!**************************************!*\
  !*** ./src/Settings/SettingsPage.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_lib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/lib */ "./src/utils/lib.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _Menu_Menu__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Menu/Menu */ "./src/Menu/Menu.js");
/* harmony import */ var _Notices__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Notices */ "./src/Settings/Notices.js");
/* harmony import */ var _Settings__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Settings */ "./src/Settings/Settings.js");
/* harmony import */ var _utils_sleeper_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/sleeper.js */ "./src/utils/sleeper.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__);











/*
 * Renders the settings page with Menu and currently selected settings
 *
 */

class SettingsPage extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.save = this.save.bind(this);
    this.saveAndContinue = this.saveAndContinue.bind(this);
    this.wizardNextPrevious = this.wizardNextPrevious.bind(this);
    this.saveChangedFields = this.saveChangedFields.bind(this);
    this.addVisibleToMenuItems = this.addVisibleToMenuItems.bind(this);
    this.updateFieldsListWithConditions = this.updateFieldsListWithConditions.bind(this);
    this.filterMenuItems = this.filterMenuItems.bind(this);
    this.showSavedSettingsNotice = this.showSavedSettingsNotice.bind(this);
    this.resetRefreshTests = this.resetRefreshTests.bind(this);
    this.handleNextButtonDisabled = this.handleNextButtonDisabled.bind(this);
    this.checkRequiredFields = this.checkRequiredFields.bind(this);
    this.state = {
      refreshTests: false,
      fields: '',
      isAPILoaded: false,
      changedFields: '',
      nextButtonDisabled: false
    };
  }

  componentDidMount() {
    //if count >1, it's a wizard
    this.selectedMenuItem = this.props.selectedMenuItem;
    this.changedFields = [];
    this.setState({
      isAPILoaded: true,
      fields: this.props.fields,
      changedFields: this.changedFields,
      selectedMainMenuItem: this.props.selectedMainMenuItem
    });
    this.props.menu.menu_items = this.addVisibleToMenuItems(this.props.menu.menu_items);
    this.checkRequiredFields();
    this.updateFieldsListWithConditions();
  } //if the main menu is switched, only this event fires, not the didmount event.


  componentDidUpdate() {
    this.props.menu.menu_items = this.addVisibleToMenuItems(this.props.menu.menu_items);
    this.updateFieldsListWithConditions();
  }

  addVisibleToMenuItems(menuItems) {
    const newMenuItems = menuItems;

    for (const [index, menuItem] of menuItems.entries()) {
      menuItem.visible = true;

      if (menuItem.hasOwnProperty('menu_items')) {
        menuItem.menu_items = this.addVisibleToMenuItems(menuItem.menu_items);
      }

      newMenuItems[index] = menuItem;
    }

    return newMenuItems;
  }
  /*
  * Set next button to disabled from the fields
  */


  handleNextButtonDisabled(disable) {
    const {
      nextButtonDisabled
    } = this.state;

    if (nextButtonDisabled !== disable) {
      this.setState({
        nextButtonDisabled: disable
      });
    }
  } //check if all required fields have been enabled. If so, enable save/continue button


  checkRequiredFields() {
    let fieldsOnPage = []; //get all fields with group_id this.props.group_id

    for (const field of this.props.fields) {
      if (field.menu_id === this.props.selectedMenuItem) {
        fieldsOnPage.push(field);
      }
    } //if the only field on this page has actions, this is a tests page, the nextButtonDisabled should be handled by the LE componenent


    let isTestPage = fieldsOnPage.length == 1 && fieldsOnPage[0].actions && fieldsOnPage[0].actions.length > 0;

    if (!isTestPage) {
      let requiredFields = fieldsOnPage.filter(field => field.required && (field.value.length == 0 || !field.value));

      if (requiredFields.length > 0) {
        this.handleNextButtonDisabled(true);
      } else {
        this.handleNextButtonDisabled(false);
      }
    }
  }

  filterMenuItems(menuItems) {
    const newMenuItems = menuItems;

    for (const [index, menuItem] of menuItems.entries()) {
      const menuItemFields = this.props.fields.filter(field => {
        return field.menu_id === menuItem.id && field.visible && !field.conditionallyDisabled;
      });

      if (menuItemFields.length === 0 && !menuItem.hasOwnProperty('menu_items')) {
        newMenuItems[index].visible = false;
      } else {
        newMenuItems[index].visible = true;

        if (menuItem.hasOwnProperty('menu_items')) {
          newMenuItems[index].menu_items = this.filterMenuItems(menuItem.menu_items);
        }
      } //if the current selected menu item has no fields, but it has a submenu, select the submenu.


      if (menuItem.id === this.props.selectedMenuItem && menuItemFields.length === 0 && menuItem.hasOwnProperty('menu_items')) {
        //get first item of submenu's
        const firstSubMenuItem = newMenuItems[index].menu_items[0].id;
        this.props.selectMenu(firstSubMenuItem);
      }
    }

    return newMenuItems;
  }

  updateFieldsListWithConditions() {
    for (const field of this.props.fields) {
      let enabled = !(field.hasOwnProperty('react_conditions') && !this.validateConditions(field.react_conditions, this.props.fields)); //we want to update the changed fields if this field has just become visible. Otherwise the new field won't get saved.

      let previouslyDisabled = this.props.fields[this.props.fields.indexOf(field)].conditionallyDisabled;
      this.props.fields[this.props.fields.indexOf(field)].conditionallyDisabled = !enabled;

      if (previouslyDisabled && enabled) {
        //if this is a learning mode field, do not add it to the changed fields list
        let changedFields = this.changedFields;

        if (field.type !== 'learningmode' && !(0,_utils_lib__WEBPACK_IMPORTED_MODULE_1__.in_array)(field.id, changedFields)) {
          changedFields.push(field.id);
        }

        this.changedFields = changedFields;
        this.setState({
          changedFields: changedFields
        });
      }

      if (!enabled && (field.type === 'letsencrypt' || field.condition_action === 'hide')) {
        this.props.fields[this.props.fields.indexOf(field)].visible = false;
      } else {
        this.props.fields[this.props.fields.indexOf(field)].visible = true;
      }
    }

    this.props.menu.menu_items = this.filterMenuItems(this.props.menu.menu_items);
  }

  saveChangedFields(changedField) {
    this.updateFieldsListWithConditions();
    let changedFields = this.changedFields;

    if (!(0,_utils_lib__WEBPACK_IMPORTED_MODULE_1__.in_array)(changedField, changedFields)) {
      changedFields.push(changedField);
    }

    this.changedFields = changedFields;
    this.setState({
      changedFields: changedFields
    });
  }

  showSavedSettingsNotice() {
    const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_8__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Settings Saved', 'really-simple-ssl'), {
      __unstableHTML: true,
      id: 'rsssl_settings_saved',
      type: 'snackbar',
      isDismissible: true
    }).then((0,_utils_sleeper_js__WEBPACK_IMPORTED_MODULE_7__["default"])(2000)).then(response => {
      (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_8__.dispatch)('core/notices').removeNotice('rsssl_settings_saved');
    });
  }

  save(skipRefreshTests) {
    //skipRefreshTests is default false, but when called from next/previous, it is true
    //this prevents the LE test from restarting on next/previous.
    const {
      fields
    } = this.state;
    let saveFields = [];

    for (const field of fields) {
      if ((0,_utils_lib__WEBPACK_IMPORTED_MODULE_1__.in_array)(field.id, this.changedFields)) {
        saveFields.push(field);
      }
    }

    _utils_api__WEBPACK_IMPORTED_MODULE_2__.setFields(saveFields).then(response => {
      this.changedFields = [];
      this.props.updateProgress(response.progress);
      this.setState({
        changedFields: []
      });

      if (!skipRefreshTests) {
        this.setState({
          refreshTests: true
        });
      }

      this.showSavedSettingsNotice();
    });
  }

  resetRefreshTests() {
    this.setState({
      refreshTests: false
    });
  }

  wizardNextPrevious(isPrevious) {
    const {
      nextMenuItem,
      previousMenuItem
    } = this.props.getPreviousAndNextMenuItems();
    this.props.selectMenu(isPrevious ? previousMenuItem : nextMenuItem);
  }

  saveAndContinue() {
    this.wizardNextPrevious(false);
    this.save(true);
  }

  validateConditions(conditions, fields) {
    let relation = conditions.relation === 'OR' ? 'OR' : 'AND';
    let conditionApplies = relation === 'AND' ? true : false;

    for (const key in conditions) {
      if (conditions.hasOwnProperty(key)) {
        let thisConditionApplies = relation === 'AND' ? true : false;
        let subConditionsArray = conditions[key];

        if (subConditionsArray.hasOwnProperty('relation')) {
          thisConditionApplies = this.validateConditions(subConditionsArray, fields);
        } else {
          for (let conditionField in subConditionsArray) {
            let invert = conditionField.indexOf('!') === 0;

            if (subConditionsArray.hasOwnProperty(conditionField)) {
              let conditionValue = subConditionsArray[conditionField];
              conditionField = conditionField.replace('!', '');
              let conditionFields = fields.filter(field => field.id === conditionField);

              if (conditionFields.hasOwnProperty(0)) {
                if (conditionFields[0].type === 'checkbox') {
                  let actualValue = +conditionFields[0].value;
                  conditionValue = +conditionValue;
                  thisConditionApplies = actualValue === conditionValue;
                } else {
                  if (conditionValue.indexOf('EMPTY') !== -1) {
                    thisConditionApplies = conditionFields[0].value.length === 0;
                  } else {
                    thisConditionApplies = conditionFields[0].value.toLowerCase() === conditionValue.toLowerCase();
                  }
                }
              }

              if (invert) {
                thisConditionApplies = !thisConditionApplies;
              }

              if (relation === 'AND') {
                conditionApplies = conditionApplies && thisConditionApplies;
              } else {
                conditionApplies = conditionApplies || thisConditionApplies;
              }
            }
          }
        }
      }
    }

    return conditionApplies ? 1 : 0;
  }

  render() {
    const {
      selectedStep,
      isAPILoaded,
      refreshTests,
      changedFields,
      nextButtonDisabled
    } = this.state;

    if (!isAPILoaded) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], null);
    }

    let fieldsUpdateComplete = changedFields.length === 0;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Menu_Menu__WEBPACK_IMPORTED_MODULE_4__["default"], {
      isAPILoaded: isAPILoaded,
      menu: this.props.menu,
      selectMenu: this.props.selectMenu,
      selectStep: this.props.selectStep,
      selectedStep: this.props.selectedStep,
      selectedMenuItem: this.props.selectedMenuItem,
      selectedMainMenuItem: this.props.selectedMainMenuItem,
      getPreviousAndNextMenuItems: this.props.getPreviousAndNextMenuItems
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Settings__WEBPACK_IMPORTED_MODULE_6__["default"], {
      updateFields: this.props.updateFields,
      dropItemFromModal: this.props.dropItemFromModal,
      selectMenu: this.props.selectMenu,
      selectMainMenu: this.props.selectMainMenu,
      nextButtonDisabled: nextButtonDisabled,
      handleNextButtonDisabled: this.handleNextButtonDisabled,
      getDefaultMenuItem: this.props.getDefaultMenuItem,
      handleModal: this.props.handleModal,
      showSavedSettingsNotice: this.showSavedSettingsNotice,
      updateField: this.props.updateField,
      getFieldValue: this.props.getFieldValue,
      resetRefreshTests: this.resetRefreshTests,
      refreshTests: refreshTests,
      addHelp: this.props.addHelp,
      pageProps: this.props.pageProps,
      setPageProps: this.props.setPageProps,
      fieldsUpdateComplete: fieldsUpdateComplete,
      highLightField: this.props.highLightField,
      highLightedField: this.props.highLightedField,
      isAPILoaded: isAPILoaded,
      fields: this.props.fields,
      progress: this.props.progress,
      saveChangedFields: this.saveChangedFields,
      menu: this.props.menu,
      save: this.save,
      saveAndContinue: this.saveAndContinue,
      selectedMenuItem: this.props.selectedMenuItem,
      selectedMainMenuItem: this.props.selectedMainMenuItem,
      selectedStep: this.props.selectedStep,
      previousStep: this.wizardNextPrevious,
      nextMenuItem: this.props.nextMenuItem,
      previousMenuItem: this.props.previousMenuItem
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Notices__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: "rsssl-wizard-notices"
    }));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (SettingsPage);

/***/ }),

/***/ "./src/Settings/Support.js":
/*!*********************************!*\
  !*** ./src/Settings/Support.js ***!
  \*********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");







class Support extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      message: '',
      sending: false
    };
  }

  componentDidMount() {
    this.onChangeHandler = this.onChangeHandler.bind(this);
    this.onClickHandler = this.onClickHandler.bind(this);
  }

  onChangeHandler(message) {
    this.setState({
      message: message
    });
  }

  onClickHandler(event) {
    this.setState({
      sending: true
    });
    return _utils_api__WEBPACK_IMPORTED_MODULE_4__.runTest('supportData', 'refresh').then(response => {
      const {
        message
      } = this.state;
      let encodedMessage = message.replace(/(?:\r\n|\r|\n)/g, '--br--');
      let url = 'https://really-simple-ssl.com/support' + '?customername=' + encodeURIComponent(response.customer_name) + '&email=' + response.email + '&domain=' + response.domain + '&scanresults=' + encodeURIComponent(response.scan_results) + '&licensekey=' + encodeURIComponent(response.license_key) + '&supportrequest=' + encodeURIComponent(encodedMessage) + '&htaccesscontents=' + response.htaccess_contents + '&debuglog=' + response.system_status;
      window.location.assign(url);
    });
  }

  render() {
    const {
      message,
      sending
    } = this.state;
    let disabled = sending || message.length == 0;
    let textAreaDisabled = sending;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
      disabled: textAreaDisabled,
      placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Type your question here", "really-simple-ssl"),
      onChange: message => this.onChangeHandler(message)
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      disabled: disabled,
      variant: "secondary",
      onClick: e => this.onClickHandler(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Send', 'really-simple-ssl')));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Support);

/***/ }),

/***/ "./src/utils/Hyperlink.js":
/*!********************************!*\
  !*** ./src/utils/Hyperlink.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);



class Hyperlink extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let label_pre = '';
    let label_post = '';
    let link_text = '';

    if (this.props.text.indexOf('%s') !== -1) {
      let parts = this.props.text.split(/%s/);
      label_pre = parts[0];
      link_text = parts[1];
      label_post = parts[2];
    } else {
      link_text = this.props.text;
    }

    let className = this.props.className ? this.props.className : 'rsssl-link';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, label_pre, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: className,
      target: this.props.target,
      href: this.props.url
    }, link_text), label_post);
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Hyperlink);

/***/ }),

/***/ "./src/utils/Icon.js":
/*!***************************!*\
  !*** ./src/utils/Icon.js ***!
  \***************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


const Icon = props => {
  const {
    name,
    color,
    size
  } = props; // set defaults if not se

  const iconName = name || 'bullet';
  const iconColor = color || 'black';
  const iconSize = size || 15;
  const iconColors = {
    'black': 'var(--rsp-black)',
    'green': 'var(--rsp-green)',
    'yellow': 'var(--rsp-yellow)',
    'orange': 'var(--rsp-yellow)',
    'red': 'var(--rsp-red)',
    'grey': 'var(--rsp-grey-400)'
  };
  let renderedIcon = '';

  if (iconName === 'bullet') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256z"
      }))
    };
  }

  if (iconName === 'circle') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"
      }))
    };
  }

  if (iconName === 'check') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"
      }))
    };
  }

  if (iconName === 'warning') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M506.3 417l-213.3-364c-16.33-28-57.54-28-73.98 0l-213.2 364C-10.59 444.9 9.849 480 42.74 480h426.6C502.1 480 522.6 445 506.3 417zM232 168c0-13.25 10.75-24 24-24S280 154.8 280 168v128c0 13.25-10.75 24-23.1 24S232 309.3 232 296V168zM256 416c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 401.9 273.4 416 256 416z"
      }))
    };
  }

  if (iconName === 'error') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM232 152C232 138.8 242.8 128 256 128s24 10.75 24 24v128c0 13.25-10.75 24-24 24S232 293.3 232 280V152zM256 400c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 385.9 273.4 400 256 400z"
      }))
    };
  }

  if (iconName === 'times') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 320 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"
      }))
    };
  }

  if (iconName === 'circle-check') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM371.8 211.8C382.7 200.9 382.7 183.1 371.8 172.2C360.9 161.3 343.1 161.3 332.2 172.2L224 280.4L179.8 236.2C168.9 225.3 151.1 225.3 140.2 236.2C129.3 247.1 129.3 264.9 140.2 275.8L204.2 339.8C215.1 350.7 232.9 350.7 243.8 339.8L371.8 211.8z"
      }))
    };
  }

  if (iconName === 'circle-times') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM175 208.1L222.1 255.1L175 303C165.7 312.4 165.7 327.6 175 336.1C184.4 346.3 199.6 346.3 208.1 336.1L255.1 289.9L303 336.1C312.4 346.3 327.6 346.3 336.1 336.1C346.3 327.6 346.3 312.4 336.1 303L289.9 255.1L336.1 208.1C346.3 199.6 346.3 184.4 336.1 175C327.6 165.7 312.4 165.7 303 175L255.1 222.1L208.1 175C199.6 165.7 184.4 165.7 175 175C165.7 184.4 165.7 199.6 175 208.1V208.1z"
      }))
    };
  }

  if (iconName === 'chevron-up') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 448 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M416 352c-8.188 0-16.38-3.125-22.62-9.375L224 173.3l-169.4 169.4c-12.5 12.5-32.75 12.5-45.25 0s-12.5-32.75 0-45.25l192-192c12.5-12.5 32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25C432.4 348.9 424.2 352 416 352z"
      }))
    };
  }

  if (iconName === 'chevron-down') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 448 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"
      }))
    };
  }

  if (iconName === 'chevron-right') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 320 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"
      }))
    };
  }

  if (iconName === 'chevron-left') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 320 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M224 480c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25l192-192c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25L77.25 256l169.4 169.4c12.5 12.5 12.5 32.75 0 45.25C240.4 476.9 232.2 480 224 480z"
      }))
    };
  }

  if (iconName === 'plus') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M432 256c0 17.69-14.33 32.01-32 32.01H256v144c0 17.69-14.33 31.99-32 31.99s-32-14.3-32-31.99v-144H48c-17.67 0-32-14.32-32-32.01s14.33-31.99 32-31.99H192v-144c0-17.69 14.33-32.01 32-32.01s32 14.32 32 32.01v144h144C417.7 224 432 238.3 432 256z"
      }))
    };
  }

  if (iconName === 'minus') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M400 288h-352c-17.69 0-32-14.32-32-32.01s14.31-31.99 32-31.99h352c17.69 0 32 14.3 32 31.99S417.7 288 400 288z"
      }))
    };
  }

  if (iconName === 'sync') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M483.515 28.485L431.35 80.65C386.475 35.767 324.485 8 256 8 123.228 8 14.824 112.338 8.31 243.493 7.971 250.311 13.475 256 20.301 256h28.045c6.353 0 11.613-4.952 11.973-11.294C66.161 141.649 151.453 60 256 60c54.163 0 103.157 21.923 138.614 57.386l-54.128 54.129c-7.56 7.56-2.206 20.485 8.485 20.485H492c6.627 0 12-5.373 12-12V36.971c0-10.691-12.926-16.045-20.485-8.486zM491.699 256h-28.045c-6.353 0-11.613 4.952-11.973 11.294C445.839 370.351 360.547 452 256 452c-54.163 0-103.157-21.923-138.614-57.386l54.128-54.129c7.56-7.56 2.206-20.485-8.485-20.485H20c-6.627 0-12 5.373-12 12v143.029c0 10.691 12.926 16.045 20.485 8.485L80.65 431.35C125.525 476.233 187.516 504 256 504c132.773 0 241.176-104.338 247.69-235.493.339-6.818-5.165-12.507-11.991-12.507z"
      }))
    };
  }

  if (iconName === 'sync-error') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M256 79.1C178.5 79.1 112.7 130.1 89.2 199.7C84.96 212.2 71.34 218.1 58.79 214.7C46.23 210.5 39.48 196.9 43.72 184.3C73.6 95.8 157.3 32 256 32C337.5 32 408.8 75.53 448 140.6V104C448 90.75 458.7 80 472 80C485.3 80 496 90.75 496 104V200C496 213.3 485.3 224 472 224H376C362.7 224 352 213.3 352 200C352 186.7 362.7 176 376 176H412.8C383.7 118.1 324.4 80 256 80V79.1zM280 263.1C280 277.3 269.3 287.1 256 287.1C242.7 287.1 232 277.3 232 263.1V151.1C232 138.7 242.7 127.1 256 127.1C269.3 127.1 280 138.7 280 151.1V263.1zM224 352C224 334.3 238.3 319.1 256 319.1C273.7 319.1 288 334.3 288 352C288 369.7 273.7 384 256 384C238.3 384 224 369.7 224 352zM40 432C26.75 432 16 421.3 16 408V311.1C16 298.7 26.75 287.1 40 287.1H136C149.3 287.1 160 298.7 160 311.1C160 325.3 149.3 336 136 336H99.19C128.3 393 187.6 432 256 432C333.5 432 399.3 381.9 422.8 312.3C427 299.8 440.7 293 453.2 297.3C465.8 301.5 472.5 315.1 468.3 327.7C438.4 416.2 354.7 480 256 480C174.5 480 103.2 436.5 64 371.4V408C64 421.3 53.25 432 40 432V432z"
      }))
    };
  }

  if (iconName === 'shortcode') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 448 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M128 32H32C14.4 32 0 46.4 0 64v384c0 17.6 14.4 32 32 32h96C145.7 480 160 465.7 160 448S145.7 416 128 416H64V96h64C145.7 96 160 81.67 160 64S145.7 32 128 32zM416 32h-96C302.3 32 288 46.33 288 63.1S302.3 96 319.1 96H384v320h-64C302.3 416 288 430.3 288 447.1S302.3 480 319.1 480H416c17.6 0 32-14.4 32-32V64C448 46.4 433.6 32 416 32z"
      }))
    };
  }

  if (iconName === 'file') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 384 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M0 64C0 28.65 28.65 0 64 0H229.5C246.5 0 262.7 6.743 274.7 18.75L365.3 109.3C377.3 121.3 384 137.5 384 154.5V448C384 483.3 355.3 512 320 512H64C28.65 512 0 483.3 0 448V64zM336 448V160H256C238.3 160 224 145.7 224 128V48H64C55.16 48 48 55.16 48 64V448C48 456.8 55.16 464 64 464H320C328.8 464 336 456.8 336 448z"
      }))
    };
  }

  if (iconName === 'file-disabled') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 640 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M639.1 487.1c0-7.119-3.153-14.16-9.191-18.89l-118.8-93.12l.0013-237.3c0-16.97-6.742-33.26-18.74-45.26l-74.63-74.64C406.6 6.742 390.3 0 373.4 0H192C156.7 0 128 28.65 128 64L128 75.01L38.82 5.11C34.41 1.672 29.19 0 24.04 0C10.19 0-.0002 11.3-.0002 23.1c0 7.12 3.153 14.16 9.192 18.89l591.1 463.1C605.6 510.3 610.8 512 615.1 512C629.8 512 639.1 500.6 639.1 487.1zM464 338.4l-287.1-225.7l-.002-48.51c0-8.836 7.164-16 15.1-16h160l-.0065 79.87c0 17.67 14.33 31.1 31.1 31.1L464 159.1V338.4zM448 463.1H192c-8.834 0-15.1-7.164-15.1-16L176 234.6L128 197L128 447.1c0 35.34 28.65 64 63.1 64H448c20.4 0 38.45-9.851 50.19-24.84l-37.72-29.56C457.5 461.4 453.2 463.1 448 463.1z"
      }))
    };
  }

  if (iconName === 'file-download') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 384 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M216 342.1V240c0-13.25-10.75-24-24-24S168 226.8 168 240v102.1L128.1 303C124.3 298.3 118.2 296 112 296S99.72 298.3 95.03 303c-9.375 9.375-9.375 24.56 0 33.94l80 80c9.375 9.375 24.56 9.375 33.94 0l80-80c9.375-9.375 9.375-24.56 0-33.94s-24.56-9.375-33.94 0L216 342.1zM365.3 93.38l-74.63-74.64C278.6 6.742 262.3 0 245.4 0H64C28.65 0 0 28.65 0 64l.0065 384c0 35.34 28.65 64 64 64H320c35.2 0 64-28.8 64-64V138.6C384 121.7 377.3 105.4 365.3 93.38zM336 448c0 8.836-7.164 16-16 16H64.02c-8.838 0-16-7.164-16-16L48 64.13c0-8.836 7.164-16 16-16h160L224 128c0 17.67 14.33 32 32 32h79.1V448z"
      }))
    };
  }

  if (iconName === 'calendar') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 448 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M152 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V448C448 483.3 419.3 512 384 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24V64zM48 448C48 456.8 55.16 464 64 464H384C392.8 464 400 456.8 400 448V192H48V448z"
      }))
    };
  }

  if (iconName === 'calendar-error') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 576 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M151.1 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V192H47.1V448C47.1 456.8 55.16 464 63.1 464H284.5C296.7 482.8 312.5 499.1 330.8 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24L151.1 64zM576 368C576 447.5 511.5 512 432 512C352.5 512 287.1 447.5 287.1 368C287.1 288.5 352.5 224 432 224C511.5 224 576 288.5 576 368zM432 416C418.7 416 408 426.7 408 440C408 453.3 418.7 464 432 464C445.3 464 456 453.3 456 440C456 426.7 445.3 416 432 416zM447.1 288C447.1 279.2 440.8 272 431.1 272C423.2 272 415.1 279.2 415.1 288V368C415.1 376.8 423.2 384 431.1 384C440.8 384 447.1 376.8 447.1 368V288z"
      }))
    };
  }

  if (iconName === 'help') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM256 400c-18 0-32-14-32-32s13.1-32 32-32c17.1 0 32 14 32 32S273.1 400 256 400zM325.1 258L280 286V288c0 13-11 24-24 24S232 301 232 288V272c0-8 4-16 12-21l57-34C308 213 312 206 312 198C312 186 301.1 176 289.1 176h-51.1C225.1 176 216 186 216 198c0 13-11 24-24 24s-24-11-24-24C168 159 199 128 237.1 128h51.1C329 128 360 159 360 198C360 222 347 245 325.1 258z"
      }))
    };
  }

  if (iconName === 'copy') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M502.6 70.63l-61.25-61.25C435.4 3.371 427.2 0 418.7 0H255.1c-35.35 0-64 28.66-64 64l.0195 256C192 355.4 220.7 384 256 384h192c35.2 0 64-28.8 64-64V93.25C512 84.77 508.6 76.63 502.6 70.63zM464 320c0 8.836-7.164 16-16 16H255.1c-8.838 0-16-7.164-16-16L239.1 64.13c0-8.836 7.164-16 16-16h128L384 96c0 17.67 14.33 32 32 32h47.1V320zM272 448c0 8.836-7.164 16-16 16H63.1c-8.838 0-16-7.164-16-16L47.98 192.1c0-8.836 7.164-16 16-16H160V128H63.99c-35.35 0-64 28.65-64 64l.0098 256C.002 483.3 28.66 512 64 512h192c35.2 0 64-28.8 64-64v-32h-47.1L272 448z"
      }))
    };
  }

  if (iconName === 'info') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-144c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32z"
      }))
    };
  }

  if (iconName === 'list') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M184.1 38.2c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L39 113c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zm0 160c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L39 273c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zM256 96c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H288c-17.7 0-32-14.3-32-32zm0 160c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H288c-17.7 0-32-14.3-32-32zM192 416c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H224c-17.7 0-32-14.3-32-32zM80 464c-26.5 0-48-21.5-48-48s21.5-48 48-48s48 21.5 48 48s-21.5 48-48 48z"
      }))
    };
  }

  if (iconName === 'external-link') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 512 512",
        height: iconSize
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: iconColors[iconColor],
        d: "M384 32c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96C0 60.7 28.7 32 64 32H384zM160 144c-13.3 0-24 10.7-24 24s10.7 24 24 24h94.1L119 327c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l135-135V328c0 13.3 10.7 24 24 24s24-10.7 24-24V168c0-13.3-10.7-24-24-24H160z"
      }))
    };
  }

  if (iconName === 'shield') {
    renderedIcon = {
      html: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
        height: iconSize,
        "aria-hidden": "true",
        focusable: "false",
        role: "img",
        id: "uuid-026a4e87-44db-4336-a398-3c29d25b7317",
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 280.8 363.67"
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: "#f9c23e",
        d: "M280.8,62.4L140.5,0,0,62.2V213.3c0,10.7,1.6,21.3,4.9,31.5,9.5,29.9,28.2,52.8,54.4,69.5,26,16.6,52.4,32.4,78.6,48.6,2,1.2,3.4,.9,5.1-.2,19.9-12.3,39.8-24.5,59.6-36.8,12.6-7.8,25.5-15.1,36.5-25.1,26.4-24.2,41.4-53.6,41.5-89.9V62.4h.2Z"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
        className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
        x: "155",
        y: "266.8",
        width: "77.6",
        height: "6"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: "#1d2327",
        d: "M224.4,204.5h-1.8v-10.1c0-15.9-12.9-28.8-28.8-28.8s-28.8,12.9-28.8,28.8v10.1h-1.8c-4.6,0-8.3,3.7-8.3,8.3v51.3h77.6v-51.3c0-4.6-3.7-8.3-8.3-8.3h.2Zm-45.3-10.1c0-8.1,6.6-14.7,14.7-14.7s14.7,6.6,14.7,14.7v10.1h-29.5v-10.1h.1Zm36.6,32.9l-20.7,20.2c-.2,.2-.3,.4-.5,.6l-2,2c-.2,.2-.4,.4-.6,.5l-3.8,3.8-4.5-4.3-2-2c-.2-.2-.4-.4-.5-.6l-9.1-9.1c-2.4-2.4-2.4-6.4,0-8.8l2-2c2.4-2.4,6.4-2.4,8.8,0l5.3,5.3,16.9-16.4c2.4-2.4,6.4-2.4,8.8,0l2,2c2.4,2.4,2.4,6.4,0,8.8h-.1Z"
      })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        fill: "#1d2327",
        d: "M125.2,192.3c-.5-2.9-.5-5.8-1-8.6-.5-2.4-2.6-4-4.8-3.9-2.3,0-4.2,1.9-4.7,4.3-.2,1,0,1.9,0,2.9,.8,14.6,7.2,26.3,18.2,35.7,2.2,1.9,4.5,3.5,6.9,4.8v-11.8c-7.4-5.8-12.9-14.1-14.6-23.3v-.1Z"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
        d: "M96.4,236.1c-13-15-20-32.3-19.5-52.3,.3-13.1,6.1-23.6,16.6-31.2,11.5-8.5,24.5-10.9,38.3-7.1,12.7,3.5,22,10.7,27.4,22,2.1-2.7,4.5-5.2,7.2-7.4-4-7-9.7-12.9-17-17.4-17-10.4-34.9-11.7-52.9-3.1-19,9.1-28.7,24.7-29.3,45.8,0,5.2,.5,10.2,1.4,15.2,3.4,19.4,13.4,35.2,27.2,48.9,1.1,1.1,2.5,1.6,4.1,1.4,1.8-.2,3.2-1.3,3.8-3,.6-1.8,.4-3.6-1-5.1-2.1-2.2-4.2-4.4-6.2-6.7h-.1Z"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        class: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
        d: "M68.1,89.4c1.1-.4,2.1-1,3.1-1.5,17.9-9.1,36.8-12.7,56.8-11.3,12.2,.8,23.9,3.8,35.1,8.7,3,1.3,5.9,2.8,8.9,4.1,2.7,1.1,5.3,0,6.4-2.4,1.1-2.3,0-5-2.3-6.3-11-5.7-22.4-10-34.6-12.3-4.2-.8-8.5-1.1-12.8-1.7h-17.1c-.3,0-.6,.2-.9,.2-11.2,.8-22,3.2-32.5,7.2-4.9,1.9-9.7,4.1-14.3,6.6-2.5,1.3-3.4,4.2-2.2,6.5,1.1,2.2,4,3.2,6.4,2.1v.1Z"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        class: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
        d: "M61.1,153.5c13.6-21.6,33.6-31.5,58.7-32.1h6c.8,0,1.6,.2,2.3,.3,13.4,1.7,25.5,6.6,35.9,15.4,5.8,4.9,10.5,10.3,14.1,16.2,3.1-1.2,6.4-2,9.8-2.5-4.7-8.7-11.3-16.3-19.6-22.7-19-14.6-40.5-19.5-64.1-15.1-14.3,2.7-26.9,9-37.7,18.8-10.4,9.5-17.8,20.9-21.2,34.6-2.8,11.3-2.6,22.7-.9,34.1,1.1,7,2.9,13.9,5.4,20.5,.9,2.3,3,3.7,5.2,3.5,2.1-.2,3.9-2,4.3-4.3,.2-1.1-.2-2.2-.6-3.2-4.3-11.9-6.3-24.1-5.6-36.7,.5-9.6,2.8-18.7,8-26.8h0Z"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        class: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
        d: "M139.8,240.6c-20.9-8.4-34.1-23.7-38.4-46.7-.8-4.3-1.4-8.7-.4-13,1.8-7.1,6.4-11.4,13.4-13.5,11.8-3.4,24.7,5.3,24.5,17.6,0,4.8,1.4,9.3,4,13.4,.3,.5,.6,.9,.9,1.3,1.6-2.4,3.7-4.6,6.1-6.2,0-.9,0-1.9,.2-2.8-.7-1.7-1.1-3.5-1.2-5.3-.3-6.1-1.6-11.9-5.5-16.8-6.8-8.8-15.9-12.4-27-11.5-11.3,.9-21.6,9.6-24.5,20.6-1.8,6.6-.9,13.3,.4,19.8,2.4,12.9,8.2,24,17.1,33.7,8.6,9.4,18.8,15.8,30.6,19.8v-10.4h-.2Z"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
        class: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
        d: "M47.5,133.2c6.8-8.8,15-16,24.6-21.6,20.8-12,43.2-15.2,66.6-11,14.8,2.7,28.2,8.7,39.9,18.2,6.3,5,11.6,11,16.4,17.4,1.9,2.5,4.8,2.8,7,1.1,2.1-1.7,2.4-4.5,.6-7-5.9-8.2-12.8-15.3-20.9-21.3-18.3-13.6-39.1-19.6-61.7-20-6.3,0-12.5,.6-18.6,1.6-15.7,2.8-30.1,8.6-42.9,18.1-8.3,6.2-15.5,13.5-21.5,22-1.6,2.3-1.3,5.1,.7,6.7,2.1,1.7,4.9,1.5,6.8-.7,1-1.2,1.9-2.5,2.9-3.7l.1,.2Z"
      }))))
    };
  }

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'rsssl-icon rsssl-icon-' + iconName
  }, renderedIcon.html);
};

/* harmony default export */ __webpack_exports__["default"] = (Icon);

/***/ }),

/***/ "./src/utils/api.js":
/*!**************************!*\
  !*** ./src/utils/api.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "doAction": function() { return /* binding */ doAction; },
/* harmony export */   "getBlock": function() { return /* binding */ getBlock; },
/* harmony export */   "getFields": function() { return /* binding */ getFields; },
/* harmony export */   "getNonce": function() { return /* binding */ getNonce; },
/* harmony export */   "getOnboarding": function() { return /* binding */ getOnboarding; },
/* harmony export */   "runLetsEncryptTest": function() { return /* binding */ runLetsEncryptTest; },
/* harmony export */   "runTest": function() { return /* binding */ runTest; },
/* harmony export */   "setFields": function() { return /* binding */ setFields; }
/* harmony export */ });
/* harmony import */ var _getAnchor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getAnchor */ "./src/utils/getAnchor.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! axios */ "../../../../../../../node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);



/*
 * Makes a get request to the fields list
 *
 * @param {string|boolean} restBase - rest base for the query.
 * @param {object} args
 * @returns {AxiosPromise<any>}
 */

const getNonce = () => {
  return '&nonce=' + rsssl_settings.rsssl_nonce + '&token=' + Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
};

const usesPlainPermalinks = () => {
  return rsssl_settings.site_url.indexOf('?') !== -1;
};

const apiGet = path => {
  if (usesPlainPermalinks()) {
    let config = {
      headers: {
        'X-WP-Nonce': rsssl_settings.nonce
      }
    };
    return axios__WEBPACK_IMPORTED_MODULE_1___default().get(rsssl_settings.site_url + path, config).then(response => {
      return response.data;
    });
  } else {
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: path
    });
  }
};

const apiPost = (path, data) => {
  if (usesPlainPermalinks()) {
    let config = {
      headers: {
        'X-WP-Nonce': rsssl_settings.nonce
      }
    };
    return axios__WEBPACK_IMPORTED_MODULE_1___default().post(rsssl_settings.site_url + path, data, config).then(response => {
      return response.data;
    });
  } else {
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: path,
      method: 'POST',
      data: data
    });
  }
};

const glue = () => {
  return rsssl_settings.site_url.indexOf('?') !== -1 ? '&' : '?';
};

const getFields = () => {
  //we pass the anchor, so we know when LE is loaded
  let anchor = (0,_getAnchor__WEBPACK_IMPORTED_MODULE_0__["default"])('main');
  return apiGet('reallysimplessl/v1/fields/get' + glue() + anchor + getNonce(), 'GET');
};
/*
 * Post our data to the back-end
 * @param data
 * @returns {Promise<AxiosResponse<any>>}
 */

const setFields = data => {
  //we pass the anchor, so we know when LE is loaded
  let anchor = (0,_getAnchor__WEBPACK_IMPORTED_MODULE_0__["default"])('main');
  let nonce = {
    'nonce': rsssl_settings.rsssl_nonce
  };
  data.push(nonce);
  return apiPost('reallysimplessl/v1/fields/set' + glue() + anchor, data);
};
const getBlock = block => {
  return apiGet('reallysimplessl/v1/block/' + block + glue() + getNonce());
};
const runTest = (test, state, data) => {
  if (data) {
    data = encodeURIComponent(JSON.stringify(data));
  }

  return apiGet('reallysimplessl/v1/tests/' + test + glue() + 'state=' + state + getNonce() + '&data=' + data);
};
const runLetsEncryptTest = (test, id) => {
  return apiGet('reallysimplessl/v1/tests/' + test + glue() + 'letsencrypt=1&id=' + id + getNonce());
};
const doAction = (action, data) => {
  if (typeof data === 'undefined') data = {};
  data.nonce = rsssl_settings.rsssl_nonce;
  return apiPost('reallysimplessl/v1/do_action/' + action, data);
};
const getOnboarding = forceRefresh => {
  return apiGet('reallysimplessl/v1/onboarding' + glue() + 'forceRefresh=' + forceRefresh + getNonce());
};

/***/ }),

/***/ "./src/utils/getAnchor.js":
/*!********************************!*\
  !*** ./src/utils/getAnchor.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/*
 * helper function to delay after a promise
 * @param ms
 * @returns {function(*): Promise<unknown>}
 */
const getAnchor = level => {
  let url = window.location.href;

  if (url.indexOf('#') === -1) {
    return false;
  }

  let queryString = url.split('#');

  if (queryString.length === 1) {
    return false;
  }

  let urlPart = queryString[1]; //for submenu, we have to get the string after the slash.

  if (level === 'menu') {
    //if there is no slash, there is no menu level
    if (urlPart.indexOf('/') === -1) {
      return false;
    } else {
      let urlParts = urlPart.split('/');

      if (urlParts.length <= 1) {
        return false;
      } else {
        return urlParts[1];
      }
    }
  } else {
    //main, just get the first.
    if (urlPart.indexOf('/') === -1) {
      return urlPart;
    } else {
      let urlParts = urlPart.split('/');
      return urlParts[0];
    }
  }

  return false;
};

/* harmony default export */ __webpack_exports__["default"] = (getAnchor);

/***/ }),

/***/ "./src/utils/lib.js":
/*!**************************!*\
  !*** ./src/utils/lib.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "in_array": function() { return /* binding */ in_array; }
/* harmony export */ });
const in_array = (needle, haystack) => {
  let length = haystack.length;

  for (let i = 0; i < length; i++) {
    if (haystack[i] == needle) return true;
  }

  return false;
};

/***/ }),

/***/ "./src/utils/sleeper.js":
/*!******************************!*\
  !*** ./src/utils/sleeper.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/*
 * helper function to delay after a promise
 * @param ms
 * @returns {function(*): Promise<unknown>}
 */
const sleeper = ms => {
  return function (x) {
    return new Promise(resolve => setTimeout(() => resolve(x), ms));
  };
};

/* harmony default export */ __webpack_exports__["default"] = (sleeper);

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "@wordpress/notices":
/*!*********************************!*\
  !*** external ["wp","notices"] ***!
  \*********************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["notices"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
!function() {
"use strict";
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Page__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Page */ "./src/Page.js");



/**
 * Initialize
 */

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('really-simple-ssl');

  if (container) {
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Page__WEBPACK_IMPORTED_MODULE_1__["default"], null)), container);
  }
});
/*
* Some oldschool stuff
*/

document.addEventListener('click', e => {
  if (e.target.closest('#ssl-labs-check-button')) {
    document.querySelector('.rsssl-ssllabs').classList.add('rsssl-block-highlight');
  }
});
}();
/******/ })()
;
//# sourceMappingURL=index.js.map