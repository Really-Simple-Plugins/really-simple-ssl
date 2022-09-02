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
      isApiLoaded: this.props.isAPILoaded,
      fields: this.props.fields,
      highLightField: this.props.highLightField,
      selectMainMenu: this.props.selectMainMenu
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
/* harmony import */ var _SecurityFeaturesBlock_SecurityFeaturesBlock__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./SecurityFeaturesBlock/SecurityFeaturesBlock */ "./src/DashBoard/SecurityFeaturesBlock/SecurityFeaturesBlock.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");








/**
 * using the gridbutton generates a button which will refresh the gridblock when clicked
 * The onclick action triggers the getBlockData method
 *
 */

class GridButton extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    let disabled = this.props.disabled ? 'disabled' : '';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button-primary",
      disabled: disabled,
      onClick: this.props.onClick
    }, this.props.text);
  }

}
/**
 * Mapping of components, for use in the config array
 * @type {{SslLabs: JSX.Element}}
 */


var dynamicComponents = {
  "SecurityFeaturesBlock": _SecurityFeaturesBlock_SecurityFeaturesBlock__WEBPACK_IMPORTED_MODULE_5__["default"],
  "ProgressBlock": _ProgressBlock__WEBPACK_IMPORTED_MODULE_3__["default"],
  "ProgressHeader": _ProgressBlockHeader__WEBPACK_IMPORTED_MODULE_4__["default"]
};

class GridBlock extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.footerHtml = this.props.block.footer.data;
    this.BlockProps = [];
    this.state = {
      isAPILoaded: false,
      content: '',
      testDisabled: false,
      footerHtml: this.props.block.footer.html,
      progress: 0,
      testRunning: false,
      BlockProps: null
    };
    this.dynamicComponents = {
      "getBlockData": this.getBlockData
    };

    if (this.props.block.content.type === 'test') {
      this.getBlockData('initial');
    } else {
      this.content = this.props.block.content.data;
    }
  }
  /**
   * Get block data for this grid block, in object format, as defined in settings/config/config.php
   * @param state
   * @returns {Promise<AxiosResponse<any>>}
   */


  getBlockData(state) {
    let setState = 'clearcache';

    if (state === 'initial' || state === 'refresh') {
      setState = state;
    }

    let test = this.props.block.content.data;
    return _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest(test, setState).then(response => {
      this.content = response.data.html;
      this.testDisabled = response.data.disabled;
      this.progress = response.data.progress;
      this.testRunning = this.progress < 100;
      this.footerHtml = response.data.footerHtml;
      this.setState({
        testRunning: this.testRunning,
        content: this.content,
        testDisabled: this.testDisabled,
        footerHtml: this.footerHtml,
        progress: this.progress,
        isAPILoaded: true
      });
    });
  }

  componentDidMount() {
    this.getBlockData = this.getBlockData.bind(this);
    this.highLightField = this.highLightField.bind(this);
    this.setBlockProps = this.setBlockProps.bind(this);

    if (this.props.block.content.type === 'html' || this.props.block.content.type === 'react') {
      let content = this.props.block.content.data;
      this.content = content;
      this.setState({
        isAPILoaded: true,
        content: content,
        progress: 100
      });
    }
  }
  /**
   * Allow child blocks to set data on the gridblock
   * @param key
   * @param value
   */


  setBlockProps(key, value) {
    this.BlockProps[key] = value;
    this.setState({
      BlockProps: this.BlockProps
    });
  }

  highLightField(fieldId) {
    this.props.highLightField(fieldId);
  }

  render() {
    let {
      isAPILoaded,
      content
    } = this.state;
    let blockData = this.props.block;
    let className = "rsssl-grid-item " + blockData.class + " rsssl-" + blockData.id;

    if (this.props.block.content.type === 'react') {
      content = this.props.block.content.data;
    }

    if (this.testRunning) {
      const timer = setTimeout(() => {
        this.getBlockData('refresh');
      }, blockData.content.interval);
    } // console.log(blockData);


    let DynamicBlockProps = {
      saveChangedFields: this.props.saveChangedFields,
      setBlockProps: this.setBlockProps,
      BlockProps: this.BlockProps,
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
    }), blockData.controls && blockData.controls.type === 'react' && wp.element.createElement(dynamicComponents[blockData.controls.data], DynamicBlockProps))), !isAPILoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_6__["default"], {
      lines: "4"
    }), blockData.content.type !== 'react' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content",
      dangerouslySetInnerHTML: {
        __html: content
      }
    }), blockData.content.type === 'react' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content"
    }, wp.element.createElement(dynamicComponents[content], DynamicBlockProps)), blockData.footer.hasOwnProperty('button') && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-footer"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(GridButton, {
      text: blockData.footer.button.text,
      onClick: this.getBlockData,
      disabled: this.testDisabled
    })), blockData.footer.type === 'html' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-footer",
      dangerouslySetInnerHTML: {
        __html: this.footerHtml
      }
    }));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (GridBlock);

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
    this.state = {
      progressText: '',
      filter: 'all',
      notices: null,
      percentageCompleted: 0,
      progressLoaded: false
    };
    this.getProgressData().then(response => {
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

  componentDidMount() {
    this.getProgressData = this.getProgressData.bind(this);
    this.onCloseTaskHandler = this.onCloseTaskHandler.bind(this);
  }

  getStyles() {
    return Object.assign({}, {
      width: this.percentageCompleted + "%"
    });
  }

  getProgressData() {
    return _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('progressData', 'refresh').then(response => {
      return response.data;
    });
  }

  onCloseTaskHandler(e) {
    let button = e.target.closest('button');
    let type = button.getAttribute('data-id');
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
      return notice.id !== type;
    });
    this.props.setBlockProps('notices', notices);
    return _utils_api__WEBPACK_IMPORTED_MODULE_1__.runTest('dismiss_task', type).then(response => {
      this.percentageCompleted = response.data.percentage;
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
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-percentage"
    }, this.percentageCompleted, "%"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-text-span"
    }, this.progressText)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-scroll-container"
    }, notices.map((notice, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_TaskElement__WEBPACK_IMPORTED_MODULE_2__["default"], {
      key: i,
      index: i,
      notice: notice,
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
        return notice.output.status === 'open';
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





class SecurityFeatureBullet extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  componentDidMount() {}

  render() {
    let field = this.props.field;
    let bulletClassName = field.value == 1 ? 'rsssl-bullet rsssl-bullet-success' : 'rsssl-bullet rsssl-bullet-error';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-new-feature"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: bulletClassName
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rssl-new-feature-label"
    }, field.value == 1 && field.new_features_block.active, field.value != 1 && field.new_features_block.inactive, field.value != 1 && field.new_features_block.readmore.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, "\xA0-\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_1__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("read more", "really-simple-ssl"),
      url: field.new_features_block.readmore
    }))));
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

  redirectToSettingsMenu() {
    this.props.selectMainMenu('settings');
  }

  render() {
    if (this.props.fields && this.props.fields.length == 0) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], null);
    }

    let fields = this.props.fields;
    fields = fields.filter(field => field.new_features_block);
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, fields.map((field, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SecurityFeatureBullet__WEBPACK_IMPORTED_MODULE_2__["default"], {
      key: i,
      index: i,
      field: field,
      fields: fields
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-new-feature-desc"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("Upgrade your security in a few clicks!", "realy-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("Check out the", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_4__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("Documentation", "really-simple-ssl"),
      url: "https://really-simple-ssl.com/hardening"
    }), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("or the", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_4__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("WordPress forum", "really-simple-ssl"),
      url: "https://wordpress.org/plugins/really-simple-ssl"
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      variant: "secondary",
      onClick: e => this.redirectToSettingsMenu(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Settings', 'really-simple-ssl')));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (SecurityFeaturesBlock);

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




class TaskElement extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  handleClick() {
    this.props.highLightField(this.props.notice.output.highlight_field_id);
  }

  componentDidMount() {
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    let notice = this.props.notice;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-task-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: 'rsssl-task-status rsssl-' + notice.output.icon
    }, notice.output.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "rsssl-task-message",
      dangerouslySetInnerHTML: {
        __html: notice.output.msg
      }
    }), notice.output.url && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      target: "_blank",
      href: notice.output.url
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("More info", "really-simple-ssl")), notice.output.highlight_field_id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-enable",
      onClick: this.handleClick
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Fix", "really-simple-ssl")), notice.output.plusone && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-plusone"
    }, "1"), notice.output.dismissible && notice.output.status !== 'completed' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-task-dismiss"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      "data-id": notice.id,
      onClick: this.props.onCloseTaskHandler
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-close-warning-x"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      width: "20",
      height: "20",
      viewBox: "0, 0, 400,400"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      id: "path0",
      d: "M55.692 37.024 C 43.555 40.991,36.316 50.669,36.344 62.891 C 36.369 73.778,33.418 70.354,101.822 138.867 L 162.858 200.000 101.822 261.133 C 33.434 329.630,36.445 326.135,36.370 337.109 C 36.270 351.953,47.790 363.672,62.483 363.672 C 73.957 363.672,68.975 367.937,138.084 298.940 L 199.995 237.127 261.912 298.936 C 331.022 367.926,326.053 363.672,337.517 363.672 C 351.804 363.672,363.610 352.027,363.655 337.891 C 363.689 326.943,367.629 331.524,299.116 262.841 C 265.227 228.868,237.500 200.586,237.500 199.991 C 237.500 199.395,265.228 171.117,299.117 137.150 C 367.625 68.484,363.672 73.081,363.672 62.092 C 363.672 48.021,351.832 36.371,337.500 36.341 C 326.067 36.316,331.025 32.070,261.909 101.066 L 199.990 162.877 138.472 101.388 C 87.108 50.048,76.310 39.616,73.059 38.191 C 68.251 36.083,60.222 35.543,55.692 37.024 ",
      stroke: "none",
      fill: "#000000"
    }))))));
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
    let menu = rsssl_settings.menu;
    let plugin_url = rsssl_settings.plugin_url;
    let active_menu_item = this.props.selectedMainMenuItem;
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
    }, menu_item.label)))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header-right"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-knowledge-base-link",
      href: "https://really-simple-ssl.com/knowledge-base",
      target: "_blank"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Documentation", "really-simple-ssl")), rsssl_settings.pro_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "https://wordpress.org/support/plugin/really-simple-ssl/",
      className: "button button-black",
      target: "_blank"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Support", "really-simple-ssl")), !rsssl_settings.pro_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: "https://really-simple-ssl.com/pro",
      className: "button button-black",
      target: "_blank"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Support", "really-simple-ssl")))));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Header);

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




/**
 * Menu block, rendering th entire menu
 */

class Menu extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      fields: this.props.fields,
      menu: this.props.menu,
      menuItems: this.props.menuItems,
      isAPILoaded: this.props.isAPILoaded
    };
  }

  render() {
    const {
      fields,
      menu,
      menuItems,
      isAPILoaded
    } = this.state;

    if (!isAPILoaded) {
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
    }, menuItems.map((menuItem, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_MenuItem__WEBPACK_IMPORTED_MODULE_2__["default"], {
      key: i,
      isAPILoaded: isAPILoaded,
      menuItem: menuItem,
      selectMenu: this.props.selectMenu,
      selectStep: this.props.selectStep,
      selectedMenuItem: this.props.selectedMenuItem,
      getPreviousAndNextMenuItems: this.props.getPreviousAndNextMenuItems
    })))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
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



class MenuItem extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.menuItem = this.props.menuItem;
    this.state = {
      menuItem: this.props.menuItem,
      isAPILoaded: this.props.isAPILoaded
    };
  }

  handleClick() {
    this.props.selectMenu(this.props.menuItem.id);
  }

  componentDidMount() {
    this.handleClick = this.handleClick.bind(this);
  }

  render() {
    const {
      menuItem,
      isAPILoaded
    } = this.state;
    /**
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

    let activeClass = menuIsSelected ? ' rsssl-active' : '';
    let featuredClass = this.props.menuItem.featured ? ' rsssl-featured' : '';
    let href = '#settings/' + this.props.menuItem.id;
    return this.props.menuItem.visible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-menu-item" + activeClass + featuredClass
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: href,
      onClick: () => this.handleClick()
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, this.props.menuItem.title), this.props.menuItem.featured && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "rsssl-menu-item-featured"
    }, this.props.menuItem.featured)), this.props.menuItem.menu_items && menuIsSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-submenu-item"
    }, this.props.menuItem.menu_items.map((subMenuItem, i) => subMenuItem.visible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(MenuItem, {
      key: i,
      menuItem: subMenuItem,
      selectMenu: this.props.selectMenu,
      selectedMenuItem: this.props.selectedMenuItem
    }))));
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
      this.props.data;
      let {
        data
      } = this.state;
      data.description = response.data.msg;
      data.subtitle = '';
      this.setState({
        data: data
      });
      let item = this.props.data;

      if (response.data.success) {
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
    }, data.subtitle && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-modal-subtitle"
    }, data.subtitle), Array.isArray(description) && description.map(s => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
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
      onClick: e => this.onClickHandler(e)
    }, this.props.btnText);
  }

}

/* harmony default export */ __webpack_exports__["default"] = (ModalControl);

/***/ }),

/***/ "./src/OnboardingModal.js":
/*!********************************!*\
  !*** ./src/OnboardingModal.js ***!
  \********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'immutability-helper'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());








const OnboardingModal = props => {
  const [show, setShow] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [steps, setSteps] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [overrideSSL, setOverrideSSL] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [sslActivated, setsslActivated] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [activateSSLDisabled, setActivateSSLDisabled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const [stepsChanged, setStepsChanged] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [networkwide, setNetworkwide] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [networkActivationStatus, setNetworkActivationStatus] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [networkProgress, setNetworkProgress] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-use'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(() => {
    // do componentDidUpdate logic
    if (networkProgress < 100 && networkwide && networkActivationStatus === 'main_site_activated') {
      _utils_api__WEBPACK_IMPORTED_MODULE_2__.activateSSLNetworkwide().then(response => {
        if (response.data.success) {
          setNetworkProgress(response.data.progress);

          if (response.data.progress >= 100) {
            updateActionForItem('ssl_enabled', '', 'success');
          }
        }
      });
    }
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.getOnboarding().then(response => {
      let steps = response.data.steps;
      setNetworkwide(response.data.networkwide);
      steps[0].visible = true;
      setsslActivated(response.data.ssl_enabled);
      setNetworkActivationStatus(response.data.network_activation_status);
      setSteps(steps);
      setStepsChanged('initial');
      setShow(!response.data.dismissed);
    });
  }, []);

  const dismissModal = () => {
    let data = {};
    data.id = 'dismiss_onboarding_modal';
    data.action = 'dismiss';
    data.type = '';
    setShow(false);
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.onboardingActions(data).then(response => {});
  };

  const activateSSL = () => {
    let sslUrl = window.location.href.replace("http://", "https://");
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.activateSSL().then(response => {
      steps[0].visible = false;
      steps[1].visible = true; //change url to https, after final check

      if (response.data.success) {
        setSteps(steps);
        setsslActivated(response.data.success);

        if (response.data.site_url_changed) {
          window.location.reload();
        } else if (networkwide) {
          setNetworkActivationStatus('main_site_activated');
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

  const itemButtonHandler = (id, type, action) => {
    let data = {};
    data.action = action;
    data.id = id;
    data.type = type;
    updateActionForItem(id, action, false);
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.onboardingActions(data).then(response => {
      if (response.data.success) {
        let nextAction = response.data.next_action;

        if (nextAction !== 'none') {
          data.action = nextAction;
          updateActionForItem(id, nextAction, false);
          _utils_api__WEBPACK_IMPORTED_MODULE_2__.onboardingActions(data).then(response => {
            if (response.data.success) {
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
        help,
        button,
        id,
        type,
        percentage
      } = item;
      const statuses = {
        'inactive': 'rsssl-inactive',
        'warning': 'rsssl-warning',
        'error': 'rsssl-error',
        'success': 'rsssl-success',
        'processing': 'rsssl-processing'
      };
      const currentActions = {
        'activate': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Activating...', "really-simple-ssl"),
        'install_plugin': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Installing...', "really-simple-ssl"),
        'error': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Failed', "really-simple-ssl"),
        'completed': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Finished', "really-simple-ssl")
      };
      let buttonTitle = '';

      if (button) {
        buttonTitle = button.title;

        if (current_action !== 'none') {
          buttonTitle = currentActions[current_action];

          if (current_action === 'failed') {
            buttonTitle = currentActions['error'];
          }
        }
      }

      let isLink = button && button.title === buttonTitle;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
        key: index,
        className: statuses[status]
      }, title, percentage && networkActivationStatus === 'main_site_activated' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0-\xA0", networkProgress < 100 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("working", "really-simple-ssl"), "\xA0", networkProgress, "%"), networkProgress >= 100 && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("completed", "really-simple-ssl")), button && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0-\xA0", isLink && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
        isLink: true,
        onClick: () => itemButtonHandler(id, type, action)
      }, buttonTitle), !isLink && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, buttonTitle), current_action === 'activate' || current_action === 'install_plugin' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-loader"
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rect1",
        key: "1"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rect2",
        key: "2"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rect3",
        key: "3"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rect4",
        key: "4"
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rect5",
        key: "5"
      }))));
    });
  };

  const parseStepButtons = buttons => {
    return buttons.map(button => {
      const {
        title,
        variant,
        disabled,
        type,
        href,
        target,
        action
      } = button;
      const buttonTypes = {
        'button': (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
          variant: variant,
          disabled: disabled && activateSSLDisabled,
          onClick: () => {
            if (action === "dismiss") {
              dismissModal();
            }

            if (action === "activate_ssl") {
              activateSSL();
            }
          }
        }, title),
        'link': (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
          variant: variant,
          href: href,
          disabled: disabled,
          isLink: true,
          target: target
        }, title),
        'checkbox': (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToggleControl, {
          label: title,
          disabled: disabled,
          checked: overrideSSL,
          onChange: value => {
            setOverrideSSL(value);
            _utils_api__WEBPACK_IMPORTED_MODULE_2__.overrideSSLDetection(value).then(response => {
              setActivateSSLDisabled(!value);
            });
          }
        })
      };
      return buttonTypes[type];
    });
  };

  const renderSteps = () => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, stepsChanged && steps.map((step, index) => {
      const {
        title,
        subtitle,
        items,
        info_text: infoText,
        buttons,
        visible
      } = step;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-modal-content-step",
        key: index,
        style: {
          display: visible ? 'block' : 'none'
        }
      }, title && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-modal-subtitle"
      }, title), subtitle && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-modal-description"
      }, subtitle), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, parseStepItems(items)), infoText && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-modal-description",
        dangerouslySetInnerHTML: {
          __html: infoText
        }
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-modal-content-step-footer"
      }, parseStepButtons(buttons)));
    }));
  };

  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, show && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
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
    className: "rsssl-modal-content",
    id: "rsssl-message"
  }, renderSteps()), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
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
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "../../../../../../../node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/api */ "./src/utils/api.js");
/* harmony import */ var _Header__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Header */ "./src/Header.js");
/* harmony import */ var _DashBoard_DashboardPage__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./DashBoard/DashboardPage */ "./src/DashBoard/DashboardPage.js");
/* harmony import */ var _Settings_SettingsPage__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Settings/SettingsPage */ "./src/Settings/SettingsPage.js");
/* harmony import */ var _Modal_Modal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Modal/Modal */ "./src/Modal/Modal.js");
/* harmony import */ var _Placeholder_PagePlaceholder__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Placeholder/PagePlaceholder */ "./src/Placeholder/PagePlaceholder.js");
/* harmony import */ var _OnboardingModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./OnboardingModal */ "./src/OnboardingModal.js");











class Page extends _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Component {
  constructor() {
    super(...arguments);

    (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "get_anchor", level => {
      let url = window.location.href;

      if (url.indexOf('#') === -1) {
        return false;
      }

      let queryString = url.split('#');

      if (queryString.length === 1) {
        return false;
      }

      let url_variables = queryString[1].split('#');

      if (url_variables.length > 0) {
        let anchor = url_variables[0];

        if (url.indexOf('/') === -1) {
          return anchor;
        } else {
          let anchor_variables = anchor.split('/');

          if (anchor_variables.length > 0) {
            if (level === 'main') {
              return anchor_variables[0];
            } else if (anchor_variables.hasOwnProperty(1)) {
              return anchor_variables[1];
            } else {
              return false;
            }
          }
        }
      }

      return false;
    });

    this.pageProps = [];
    this.pageProps['licenseStatus'] = rsssl_settings.licenseStatus;
    this.state = {
      selectedMainMenuItem: this.get_anchor('main') || 'dashboard',
      selectedMenuItem: this.get_anchor('menu') || 'general',
      selectedStep: 1,
      highLightedField: '',
      fields: '',
      menu: '',
      progress: '',
      isAPILoaded: false,
      pageProps: this.pageProps,
      showModal: false,
      modalData: [],
      dropItemFromModal: false,
      nextMenuItem: '',
      previousMenuItem: ''
    };
    this.getFields().then(response => {
      let fields = response.fields;
      let menu = response.menu;
      let progress = response.progress;
      this.menu = menu;
      this.progress = progress;
      this.fields = fields;
      this.setState({
        isAPILoaded: true,
        fields: fields,
        menu: menu,
        progress: progress
      }, () => {
        this.getPreviousAndNextMenuItems();
      });
    });
    this.selectMenu = this.selectMenu.bind(this);
    this.selectStep = this.selectStep.bind(this);
    this.handleModal = this.handleModal.bind(this);
    this.highLightField = this.highLightField.bind(this);
    this.updateField = this.updateField.bind(this);
    this.selectMainMenu = this.selectMainMenu.bind(this);
    this.setPageProps = this.setPageProps.bind(this);
    this.getPreviousAndNextMenuItems = this.getPreviousAndNextMenuItems.bind(this);
  }

  getFields() {
    return _utils_api__WEBPACK_IMPORTED_MODULE_2__.getFields().then(response => {
      return response.data;
    });
  }
  /*
   * Allow child blocks to set data on the gridblock
   * @param key
   * @param value
   */


  setPageProps(key, value) {
    this.pageProps[key] = value;
    this.setState({
      pageProps: this.pageProps
    });
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

  selectMainMenu(selectedMainMenuItem) {
    this.setState({
      selectedMainMenuItem: selectedMainMenuItem
    });
  }
  /*
   * Update a field
   * @param field
   */


  updateField(field) {
    let fields = this.fields;

    for (const fieldItem of fields) {
      if (fieldItem.id === field.id) {
        fieldItem.value = field.value;
      }
    }

    this.fields = fields;
    this.setState({
      fields: fields
    });
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
      selectedMainMenuItem,
      selectedMenuItem,
      fields,
      menu,
      progress,
      isAPILoaded,
      showModal,
      modalData,
      dropItemFromModal
    } = this.state;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
      className: "rsssl-wrapper"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_OnboardingModal__WEBPACK_IMPORTED_MODULE_8__["default"], {
      setPageProps: this.setPageProps
    }), !isAPILoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_Placeholder_PagePlaceholder__WEBPACK_IMPORTED_MODULE_7__["default"], null), showModal && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_Modal_Modal__WEBPACK_IMPORTED_MODULE_6__["default"], {
      handleModal: this.handleModal,
      data: modalData
    }), isAPILoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_Header__WEBPACK_IMPORTED_MODULE_3__["default"], {
      selectedMainMenuItem: selectedMainMenuItem,
      selectMainMenu: this.selectMainMenu,
      fields: fields
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
      className: "rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem
    }, selectedMainMenuItem === 'settings' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_Settings_SettingsPage__WEBPACK_IMPORTED_MODULE_5__["default"], {
      dropItemFromModal: dropItemFromModal,
      pageProps: this.pageProps,
      handleModal: this.handleModal,
      updateField: this.updateField,
      setPageProps: this.setPageProps,
      selectMenu: this.selectMenu,
      selectStep: this.selectStep,
      selectedStep: this.state.selectedStep,
      highLightField: this.highLightField,
      highLightedField: this.highLightedField,
      selectedMenuItem: selectedMenuItem,
      isAPILoaded: isAPILoaded,
      fields: fields,
      menu: menu,
      progress: progress,
      getPreviousAndNextMenuItems: this.getPreviousAndNextMenuItems,
      nextMenuItem: this.state.nextMenuItem,
      previousMenuItem: this.state.previousMenuItem
    }), selectedMainMenuItem === 'dashboard' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_DashBoard_DashboardPage__WEBPACK_IMPORTED_MODULE_4__["default"], {
      isAPILoaded: isAPILoaded,
      fields: fields,
      selectMainMenu: this.selectMainMenu,
      highLightField: this.highLightField,
      pageProps: this.pageProps
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
      onClick: () => this.props.onChangeHandlerDataTable(!this.props.item.status, this.props.item, 'status'),
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
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _MixedContentScan__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./MixedContentScan */ "./src/Settings/MixedContentScan.js");
/* harmony import */ var _PermissionsPolicy__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./PermissionsPolicy */ "./src/Settings/PermissionsPolicy.js");
/* harmony import */ var _Support__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./Support */ "./src/Settings/Support.js");
/* harmony import */ var _LearningMode__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./LearningMode */ "./src/Settings/LearningMode.js");
/* harmony import */ var _ChangeStatus__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./ChangeStatus */ "./src/Settings/ChangeStatus.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());












/*
 * https://react-data-table-component.netlify.app
 */



class Field extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.highLightClass = this.props.highLightedField === this.props.field.id ? 'rsssl-highlight' : '';
  }

  componentDidMount() {
    this.props.highLightField('');
    this.onChangeHandlerDataTable = this.onChangeHandlerDataTable.bind(this);
  }

  onChangeHandler(fieldValue) {
    let fields = this.props.fields;
    let field = this.props.field;
    fields[this.props.index]['value'] = fieldValue;
    this.props.saveChangedFields(field.id);
    this.setState({
      fields
    });
  }
  /*
   * Handle data update for a datatable
   * @param enabled
   * @param clickedItem
   * @param type
   */


  onChangeHandlerDataTable(enabled, clickedItem, type) {
    let field = this.props.field;

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
    this.props.updateField(field);
    _utils_api__WEBPACK_IMPORTED_MODULE_3__.setFields(saveFields).then(response => {//this.props.showSavedSettingsNotice();
    });
  }

  onCloseTaskHandler() {}

  render() {
    let field = this.props.field;
    let fieldValue = field.value;
    let fields = this.props.fields;
    let disabled = field.disabled;
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


    if (!rsssl_settings.networkwide_active && field.networkwide) {
      disabled = true;
      field.comment = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("This feature is only available networkwide.", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_5__["default"], {
        target: "_blank",
        text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Network settings", "really-simple-ssl"),
        url: rsssl_settings.network_link
      }));
    }

    if (field.conditionallyDisabled) {
      disabled = true;
    }

    if (!field.visible || field.type === 'database') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
    }

    if (field.type === 'checkbox') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelRow, {
        className: this.highLightClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToggleControl, {
        disabled: disabled,
        checked: field.value == 1,
        help: field.comment,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue)
      }));
    }

    if (field.type === 'hidden') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
        type: "hidden",
        value: field.value
      }));
    }

    if (field.type === 'radio') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelRow, {
        className: this.highLightClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        selected: fieldValue,
        options: options
      }));
    }

    if (field.type === 'text') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
        className: this.highLightClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
        help: field.comment,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        value: fieldValue
      }));
    }

    if (field.type === 'textarea') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
        className: this.highLightClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
        label: field.label,
        help: field.comment,
        value: fieldValue,
        onChange: fieldValue => this.onChangeHandler(fieldValue)
      }));
    }

    if (field.type === 'license') {
      /*
       * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
       */
      let field = this.props.field;
      let fieldValue = field.value;
      let fields = this.props.fields;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_License__WEBPACK_IMPORTED_MODULE_4__["default"], {
        setPageProps: this.props.setPageProps,
        fieldsUpdateComplete: this.props.fieldsUpdateComplete,
        index: this.props.index,
        fields: fields,
        field: field,
        fieldValue: fieldValue,
        saveChangedFields: this.props.saveChangedFields,
        highLightField: this.props.highLightField,
        highLightedField: this.props.highLightedField
      });
    }

    if (field.type === 'number') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
        className: this.highLightClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalNumberControl, {
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        help: field.comment,
        label: field.label,
        value: fieldValue
      }));
    }

    if (field.type === 'email') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
        className: this.highLightClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
        help: field.comment,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        value: fieldValue
      }));
    }

    if (field.type === 'select') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
        className: this.highLightClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl // multiple
      , {
        help: field.comment,
        label: field.label,
        onChange: fieldValue => this.onChangeHandler(fieldValue),
        value: fieldValue,
        options: options
      }));
    }

    if (field.type === 'support') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Support__WEBPACK_IMPORTED_MODULE_8__["default"], null);
    }

    if (field.type === 'permissionspolicy') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_PermissionsPolicy__WEBPACK_IMPORTED_MODULE_7__["default"], {
        onChangeHandlerDataTable: this.onChangeHandlerDataTable,
        updateField: this.props.updateField,
        field: this.props.field,
        options: options,
        highLightClass: this.highLightClass,
        fields: fields
      });
    }

    if (field.type === 'learningmode') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LearningMode__WEBPACK_IMPORTED_MODULE_9__["default"], {
        onChangeHandlerDataTable: this.onChangeHandlerDataTable,
        updateField: this.props.updateField,
        field: this.props.field,
        options: options,
        highLightClass: this.highLightClass,
        fields: fields
      });
    }

    if (field.type === 'mixedcontentscan') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_MixedContentScan__WEBPACK_IMPORTED_MODULE_6__["default"], {
        dropItemFromModal: this.props.dropItemFromModal,
        handleModal: this.props.handleModal,
        field: this.props.field,
        fields: this.props.selectedFields
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


/**
 * Render a help notice in the sidebar
 */

class Help extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  render() {
    let notice = this.props.help;

    if (!notice.title) {
      notice.title = notice.text;
      notice.text = false;
    } //we can use notice.linked_field to create a visual link to the field.


    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, notice.title && notice.text && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("details", {
      className: "rsssl-wizard-help-notice rsssl-" + notice.label.toLowerCase()
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("summary", null, notice.title), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      dangerouslySetInnerHTML: {
        __html: notice.text
      }
    })), notice.title && !notice.text && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-help-notice  rsssl-" + notice.label.toLowerCase()
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, notice.title)));
  }

}

/* harmony default export */ __webpack_exports__["default"] = (Help);

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
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _ChangeStatus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./ChangeStatus */ "./src/Settings/ChangeStatus.js");
Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");








class Delete extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
  }

  render() {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      type: "button",
      className: "rsssl-learning-mode-delete",
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
      enforce: 0,
      learning_mode: 0,
      learning_mode_completed: 0,
      filterValue: 0
    };
  }

  componentDidMount() {
    this.doFilter = this.doFilter.bind(this);
    this.onDeleteHandler = this.onDeleteHandler.bind(this);
    let field = this.props.fields.filter(field => field.id === this.props.field.control_field)[0];
    let enforce = field.value === 'enforce';
    let learning_mode = field.value === 'learning_mode';
    let learning_mode_completed = field.value === 'completed';
    this.setState({
      enforce: enforce,
      learning_mode: learning_mode,
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
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.setFields(saveFields).then(response => {});
  }

  toggleLearningMode(e) {
    e.preventDefault();
    let fields = this.props.fields;
    let field = fields.filter(field => field.id === this.props.field.control_field)[0];
    let learning_mode = field.value === 'learning_mode' ? 1 : 0;
    let learning_mode_completed = field.value === 'completed' ? 1 : 0;
    field.value = learning_mode || learning_mode_completed ? 'disabled' : 'learning_mode';

    if (learning_mode || learning_mode_completed) {
      learning_mode = 0;
    } else {
      learning_mode = 1;
    }

    learning_mode_completed = 0;
    this.setState({
      learning_mode: learning_mode,
      learning_mode_completed: learning_mode_completed
    });
    let saveFields = [];
    saveFields.push(field);
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.setFields(saveFields).then(response => {});
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
    } //find this item in the field list


    field.value.forEach(function (item, i) {
      delete item.valueControl;
      delete item.statusControl;
      delete item.deleteControl;

      if (item.id === clickedItem.id) {
        field.value.splice(i, 1);
      }
    }); //the updateItemId allows us to update one specific item in a field set.

    field.updateItemId = clickedItem.id;
    field.action = 'delete';
    let saveFields = [];
    saveFields.push(field);
    console.log("update field");
    console.log(field);
    this.props.updateField(field);
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.setFields(saveFields).then(response => {});
  }

  render() {
    let field = this.props.field;
    let fieldValue = field.value;
    let options = this.props.options;

    let configuringString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("We're configuring your %s", "really-simple-ssl").replace('%s', field.label);

    let disabledString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("%s has been disabled.", "really-simple-ssl").replace('%s', field.label);

    const {
      filterValue,
      enforce,
      learning_mode,
      learning_mode_completed
    } = this.state;

    const Filter = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
      onChange: e => this.doFilter(e)
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "-1",
      selected: filterValue == -1
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("All", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "1",
      selected: filterValue == 1
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Allowed", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "0",
      selected: filterValue == 0
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Blocked", "really-simple-ssl")))); //build our header


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

    if (filterValue != -1) {
      data = data.filter(item => item.status == filterValue);
    }

    for (const item of data) {
      item.login_statusControl = item.login_status == 1 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("success", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("failed", "really-simple-ssl");
      item.statusControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ChangeStatus__WEBPACK_IMPORTED_MODULE_3__["default"], {
        item: item,
        onChangeHandlerDataTable: this.props.onChangeHandlerDataTable
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
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
      className: this.highLightClass
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()), {
      columns: columns,
      data: data,
      dense: true,
      pagination: true,
      noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("No results", "really-simple-ssl"),
      persistTableHead: true,
      subHeader: true,
      subHeaderComponent: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Filter, null),
      conditionalRowStyles: conditionalRowStyles
    }), enforce != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      onClick: e => this.toggleEnforce(e, true)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Enforce", "really-simple-ssl")), enforce == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      onClick: e => this.toggleEnforce(e, false)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Disable", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "checkbox",
      disabled: enforce,
      checked: learning_mode == 1,
      value: learning_mode,
      onChange: e => this.toggleLearningMode(e)
    }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Enable Learning Mode", "really-simple-ssl"))), learning_mode == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-learning-mode"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Learning Mode", "really-simple-ssl")), configuringString, "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-learning-mode-link",
      href: "#",
      onClick: e => this.toggleLearningMode(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Disable learning mode and configure manually", "really-simple-ssl")))), learning_mode_completed == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-learning-mode-completed"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Learning Mode", "really-simple-ssl")), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("We finished the configuration.", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-learning-mode-link",
      href: "#",
      onClick: e => this.toggleLearningMode(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Review the settings and enforce the policy", "really-simple-ssl")))), rsssl_settings.pro_plugin_active && field.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-disabled"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("disabled ", "really-simple-ssl")), disabledString)));
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
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _DashBoard_TaskElement__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../DashBoard/TaskElement */ "./src/DashBoard/TaskElement.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");







class License extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.noticesLoaded = false;
    this.fieldsUpdateComplete = false;
    this.licenseStatus = 'invalid';
    this.state = {
      licenseStatus: 'invalid',
      noticesLoaded: false,
      notices: []
    };
    this.highLightClass = this.props.highLightedField === this.props.field.id ? 'rsssl-highlight' : '';
  }

  getLicenseNotices() {
    return _utils_api__WEBPACK_IMPORTED_MODULE_4__.runTest('licenseNotices', 'refresh').then(response => {
      return response.data;
    });
  }

  componentDidMount() {
    this.props.highLightField('');
    this.getLicenseNotices = this.getLicenseNotices.bind(this);
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

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
      className: this.highLightClass
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
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
    })), !noticesLoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], null), noticesLoaded && notices.map((notice, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DashBoard_TaskElement__WEBPACK_IMPORTED_MODULE_2__["default"], {
      key: i,
      index: i,
      notice: notice,
      onCloseTaskHandler: this.onCloseTaskHandler,
      highLightField: ""
    }))));
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

  componentDidMount() {
    let data = [];
    let progress = 0;
    let action = '';
    let state = 'stop';

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

    if (this.props.field.value.nonce) {
      this.nonce = this.props.field.value.nonce;
    }

    this.setState({
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
        data: response.data.data,
        progress: response.data.progress,
        action: response.data.action,
        state: response.data.state
      });

      if (response.data.state === 'running') {
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
        data: response.data.data,
        progress: response.data.progress,
        action: response.data.action,
        state: response.data.state
      }); //if scan was stopped while running, set it to stopped now.

      if (this.state.paused) {
        this.stop();
      } else if (response.data.state === 'running') {
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
        data: response.data.data,
        progress: response.data.progress,
        action: response.data.action
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
    columns = [];
    field.columns.forEach(function (item, i) {
      let newItem = {
        name: item.name,
        width: item.width,
        sortable: item.sortable,
        selector: row => row[item.column]
      };
      columns.push(newItem);
    });

    if (typeof data === 'object') {
      data = Object.values(data);
    }

    if (!Array.isArray(data)) {
      data = [];
    }

    let dropItem = this.props.dropItemFromModal;

    for (const item of data) {
      item.warningControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
        className: "rsssl-warning"
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
        modalData: item.details
      });
      item.fixControl = item.fix && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Modal_ModalControl__WEBPACK_IMPORTED_MODULE_5__["default"], {
        removeDataItem: this.removeDataItem,
        handleModal: this.props.handleModal,
        item: item,
        btnText: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Fix", "really-simple-ssl"),
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
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress-container"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-progress-bar",
      style: {
        width: progress
      }
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-current-scan-action"
    }, state === 'running' && action), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()), {
      columns: columns,
      data: data,
      dense: true,
      pagination: true,
      paginationResetDefaultPage: resetPaginationToggle // optionally, a hook to reset pagination to page 1
      ,
      noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("No results", "really-simple-ssl") //or your component
      // subHeader
      // subHeaderComponent=<subHeaderComponentMemo/>

    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      disabled: startDisabled,
      onClick: e => this.start(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Scan", "really-simple-ssl-pro")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      disabled: stopDisabled,
      onClick: e => this.stop(e)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Pause", "really-simple-ssl-pro")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Show ignored URLs"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      value: showIgnoredUrls,
      type: "checkbox",
      id: "rsssl_show_ignored_urls",
      onClick: e => this.toggleIgnoredUrls(e)
    })));
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
  const {
    removeNotice
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useDispatch)(_wordpress_notices__WEBPACK_IMPORTED_MODULE_3__.store);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SnackbarList, {
    className: "edit-site-notices",
    notices: notices,
    onRemove: removeNotice
  });
};

/* harmony default export */ __webpack_exports__["default"] = (Notices); // <div className="rsssl-settings-saved rsssl-settings-saved--fade-in">
// 	<div className="rsssl-settings-saved__text_and_icon">
// 		<span><div className="rsssl-tooltip-icon dashicons-before rsssl-icon rsssl-success check"><svg width="18"
// 																									   height="18"
// 																									   viewBox="0 0 1792 1792"
// 																									   xmlns="http://www.w3.org/2000/svg"><path
// 			d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"></path></svg></div></span>
// 		<span><?php _e('Changes saved successfully', 'really-simple-ssl') ?> </span>
// 	</div>
// </div>

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








class PermissionsPolicy extends _wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor() {
    super(...arguments);
    this.state = {
      filterValue: 0,
      enable_permissions_policy: 0
    };
  }

  componentDidMount() {
    let field = this.props.fields.filter(field => field.id === 'enable_permissions_policy')[0];
    this.setState({
      enable_permissions_policy: field.value
    });
  }

  togglePermissionsPolicyStatus(e, enforce) {
    console.log("enforce");
    console.log(enforce);
    let fields = this.props.fields; //look up permissions policy enable field //enable_permissions_policy

    let field = fields.filter(field => field.id === 'enable_permissions_policy')[0]; //enforce this setting

    field.value = enforce;
    this.setState({
      enable_permissions_policy: enforce
    });
    let saveFields = [];
    saveFields.push(field);
    this.props.updateField(field);
    _utils_api__WEBPACK_IMPORTED_MODULE_5__.setFields(saveFields).then(response => {//this.props.showSavedSettingsNotice();
    });
  }

  doFilter(e) {
    this.setState({
      filterValue: e.target.value
    });
  }

  render() {
    let field = this.props.field;
    let fieldValue = field.value;
    let options = this.props.options;
    const {
      enable_permissions_policy,
      filterValue
    } = this.state;

    const Filter = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
      onChange: e => this.doFilter(e)
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "-1",
      selected: filterValue == -1
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("All", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "1",
      selected: filterValue == 1
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Allowed", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      value: "0",
      selected: filterValue == 0
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Blocked", "really-simple-ssl"))));

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

    if (filterValue != -1) {
      data = data.filter(item => item.status == filterValue);
    }

    for (const item of data) {
      let disabled = false;

      if (item.status != 1) {
        item.value = '()';
        disabled = true;
      }

      item.valueControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl, {
        help: "",
        value: item.value,
        disabled: disabled,
        options: options,
        label: "",
        onChange: fieldValue => this.onChangeHandlerDataTable(fieldValue, item, 'value')
      });
      item.statusControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ChangeStatus__WEBPACK_IMPORTED_MODULE_3__["default"], {
        item: item,
        onChangeHandlerDataTable: this.props.onChangeHandlerDataTable
      });
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
      className: this.props.highLightClass
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Object(function webpackMissingModule() { var e = new Error("Cannot find module 'react-data-table-component'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()), {
      columns: columns,
      data: data,
      dense: true,
      pagination: true,
      subHeader: true,
      subHeaderComponent: (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Filter, null)
    }), enable_permissions_policy != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      onClick: e => this.togglePermissionsPolicyStatus(e, true)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Enforce", "really-simple-ssl")), enable_permissions_policy == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
      className: "button",
      onClick: e => this.togglePermissionsPolicyStatus(e, false)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Disable", "really-simple-ssl"))));
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

    if (!isAPILoaded) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_1__["default"], null);
    }

    let selectedFields = fields.filter(field => field.menu_id === selectedMenuItem);
    let groups = [];

    for (const selectedField of selectedFields) {
      if (!(0,_utils_lib__WEBPACK_IMPORTED_MODULE_2__.in_array)(selectedField.group_id, groups)) {
        groups.push(selectedField.group_id);
      }
    } //convert progress notices to an array useful for the help blocks


    let notices = [];

    for (const notice of progress.notices) {
      let noticeField = false; //notices that are linked to a field.

      if (notice.show_with_options) {
        noticeField = selectedFields.filter(field => notice.show_with_options && notice.show_with_options.includes(field.id));
        if (noticeField.length === 0) noticeField = false;
      } //notices that are linked to a menu id.


      if (noticeField || notice.menu_id === selectedMenuItem) {
        let help = {};
        help.title = notice.output.title ? notice.output.title : false;
        help.label = notice.output.label;
        help.id = notice.field_id;
        help.text = notice.output.msg;
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
    let selectedMenuItemObject;

    for (const item of menu.menu_items) {
      if (item.id === selectedMenuItem) {
        selectedMenuItemObject = item;
      } else if (item.menu_items) {
        selectedMenuItemObject = item.menu_items.filter(menuItem => menuItem.id === selectedMenuItem)[0];
      }

      if (selectedMenuItemObject) {
        break;
      }
    }

    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-settings rsssl-column-2"
    }, groups.map((group, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SettingsGroup__WEBPACK_IMPORTED_MODULE_3__["default"], {
      dropItemFromModal: this.props.dropItemFromModal,
      selectMenu: this.props.selectMenu,
      handleModal: this.props.handleModal,
      showSavedSettingsNotice: this.props.showSavedSettingsNotice,
      updateField: this.props.updateField,
      pageProps: this.props.pageProps,
      setPageProps: this.props.setPageProps,
      fieldsUpdateComplete: this.props.fieldsUpdateComplete,
      key: i,
      index: i,
      highLightField: this.props.highLightField,
      highLightedField: this.props.highLightedField,
      selectedMenuItem: selectedMenuItemObject,
      saveChangedFields: this.props.saveChangedFields,
      group: group,
      fields: selectedFields
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-footer"
    }, this.props.selectedMenuItem !== menuItems[0].id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      href: `#settings/${this.props.previousMenuItem}`,
      onClick: () => this.props.previousStep(true)
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Previous', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Button, {
      variant: "secondary",
      onClick: this.props.save
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Save', 'really-simple-ssl')), this.props.selectedMenuItem !== menuItems[menuItems.length - 1].id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "button button-primary",
      href: `#settings/${this.props.nextMenuItem}`,
      onClick: this.props.saveAndContinue
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Save and Continue', 'really-simple-ssl')))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-wizard-help"
    }, notices.map((field, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Help__WEBPACK_IMPORTED_MODULE_4__["default"], {
      key: i,
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
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);





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
    this.upgrade = 'https://really-simple-ssl.com/pro';
    this.fields = this.props.fields;
  }

  componentDidMount() {
    this.getLicenseStatus = this.getLicenseStatus.bind(this);
  }

  getLicenseStatus() {
    if (this.props.pageProps.hasOwnProperty('licenseStatus')) {
      return this.props.pageProps['licenseStatus'];
    }

    return 'invalid';
  }

  handleMenuLink(id) {
    this.props.selectMenu(id);
  }

  render() {
    let selectedMenuItem = this.props.selectedMenuItem;
    let selectedFields = []; //get all fields with group_id this.props.group_id

    for (const selectedField of this.props.fields) {
      if (selectedField.group_id === this.props.group) {
        selectedFields.push(selectedField);
      }
    } //set group default to current menu item


    let activeGroup = selectedMenuItem;

    if (selectedMenuItem.hasOwnProperty('groups')) {
      let currentGroup = selectedMenuItem.groups.filter(group => group.id === this.props.group);

      if (currentGroup.length > 0) {
        activeGroup = currentGroup[0];
      }
    }

    let status = 'invalid';
    let msg = activeGroup.premium_text ? activeGroup.premium_text : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Learn more about %sPremium%s", "really-simple-ssl");

    if (rsssl_settings.pro_plugin_active) {
      status = this.getLicenseStatus();

      if (status === 'empty' || status === 'deactivated') {
        msg = rsssl_settings.messageInactive;
      } else {
        msg = rsssl_settings.messageInvalid;
      }
    }

    let disabled = status !== 'valid' && activeGroup.premium; //if a feature can only be used on networkwide or single site setups, pass that info here.

    let networkwide_error = !rsssl_settings.networkwide_active && activeGroup.networkwide;
    this.upgrade = activeGroup.upgrade ? activeGroup.upgrade : this.upgrade;
    let helplinkText = activeGroup.helpLink_text ? activeGroup.helpLink_text : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Instructions manual", "really-simple-ssl");
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item rsssl-" + activeGroup.id
    }, activeGroup && activeGroup.title && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-header"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", {
      className: "rsssl-h4"
    }, activeGroup.title), activeGroup && activeGroup.helpLink && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-controls"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
      target: "_blank",
      className: "rsssl-helplink",
      text: helplinkText,
      url: activeGroup.helpLink
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-grid-item-content"
    }, activeGroup && activeGroup.intro && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-settings-block-intro"
    }, activeGroup.intro), selectedFields.map((field, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Field__WEBPACK_IMPORTED_MODULE_1__["default"], {
      dropItemFromModal: this.props.dropItemFromModal,
      handleModal: this.props.handleModal,
      showSavedSettingsNotice: this.props.showSavedSettingsNotice,
      updateField: this.props.updateField,
      setPageProps: this.props.setPageProps,
      fieldsUpdateComplete: this.props.fieldsUpdateComplete,
      key: i,
      index: i,
      highLightField: this.props.highLightField,
      highLightedField: this.props.highLightedField,
      saveChangedFields: this.props.saveChangedFields,
      field: field,
      fields: selectedFields
    })), disabled && !networkwide_error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-premium"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Premium", "really-simple-ssl")), rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, msg, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: "rsssl-locked-link",
      href: "#",
      onClick: () => this.handleMenuLink('license')
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Check license", "really-simple-ssl"))), !rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
      target: "_blank",
      text: msg,
      url: this.upgrade
    }))), networkwide_error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-locked-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-progress-status rsssl-warning"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Network feature", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("This feature is only available networkwide.", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Network settings", "really-simple-ssl"),
      url: rsssl_settings.network_link
    }))))));
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
    this.state = {
      fields: '',
      menu: '',
      menuItems: '',
      isAPILoaded: false,
      changedFields: '',
      progress: ''
    };
  }

  componentDidMount() {
    this.save = this.save.bind(this);
    this.saveAndContinue = this.saveAndContinue.bind(this);
    this.wizardNextPrevious = this.wizardNextPrevious.bind(this);
    this.saveChangedFields = this.saveChangedFields.bind(this);
    this.addVisibleToMenuItems = this.addVisibleToMenuItems.bind(this);
    this.updateFieldsListWithConditions = this.updateFieldsListWithConditions.bind(this);
    this.filterMenuItems = this.filterMenuItems.bind(this);
    this.showSavedSettingsNotice = this.showSavedSettingsNotice.bind(this);
    this.props.menu.menu_items = this.addVisibleToMenuItems(this.props.menu.menu_items);
    this.updateFieldsListWithConditions();
    let menu = this.props.menu;
    let fields = this.props.fields;
    let progress = this.props.progress; //if count >1, it's a wizard

    let menuItems = menu.menu_items;
    let changedFields = [];
    let selectedMenuItem = this.props.selectedMenuItem;
    this.menu = menu;
    this.menuItems = menuItems;
    this.fields = fields;
    this.selectedMenuItem = selectedMenuItem;
    this.changedFields = changedFields;
    this.setState({
      isAPILoaded: true,
      fields: this.props.fields,
      menu: this.props.menu,
      progress: this.props.progress,
      menuItems: menuItems,
      changedFields: changedFields
    });
  }

  addVisibleToMenuItems(menuItems) {
    const newMenuItems = menuItems;

    for (const [index, value] of menuItems.entries()) {
      newMenuItems[index].visible = true;

      if (value.hasOwnProperty('menu_items')) {
        newMenuItems[index].menu_items = this.addVisibleToMenuItems(value.menu_items);
      }
    }

    return newMenuItems;
  }

  filterMenuItems(menuItems) {
    const newMenuItems = menuItems;

    for (const [index, value] of menuItems.entries()) {
      const searchResult = this.props.fields.filter(field => {
        return field.menu_id === value.id && field.visible;
      });

      if (searchResult.length === 0) {
        value.visible = false;
      } else {
        value.visible = true;

        if (value.hasOwnProperty('menu_items')) {
          newMenuItems[index].menu_items = this.filterMenuItems(value.menu_items);
        }
      }
    }

    return newMenuItems;
  }

  updateFieldsListWithConditions() {
    for (const field of this.props.fields) {
      console.log(field.id);

      if (field.hasOwnProperty('react_conditions')) {
        console.log("has react conditions");

        if (this.validateConditions(field.react_conditions, this.props.fields)) {
          console.log(field.react_conditions);
          console.log("react conditinos validated");
        }
      }

      let enabled = !(field.hasOwnProperty('react_conditions') && !this.validateConditions(field.react_conditions, this.props.fields));
      this.props.fields[this.props.fields.indexOf(field)].conditionallyDisabled = !enabled;
    }

    this.filterMenuItems(this.props.menu.menu_items);
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

  save() {
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
      this.setState({
        changedFields: [],
        progress: response.data.progress
      });
      this.showSavedSettingsNotice();
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
    this.save();
  }

  validateConditions(conditions, fields) {
    let relation = conditions.relation === 'OR' ? 'OR' : 'AND';
    delete conditions['relation'];
    let conditionApplies = true;

    for (const key in conditions) {
      if (conditions.hasOwnProperty(key)) {
        let invert = key.indexOf('!') === 0;
        let thisConditionApplies = true;
        let subConditionsArray = conditions[key];

        if (subConditionsArray.hasOwnProperty('relation')) {
          thisConditionApplies = this.validateConditions(subConditionsArray, fields);
        } else {
          for (const conditionField in subConditionsArray) {
            if (subConditionsArray.hasOwnProperty(conditionField)) {
              let conditionValue = subConditionsArray[conditionField];
              let conditionFields = fields.filter(field => field.id === conditionField);

              if (conditionFields.hasOwnProperty(0)) {
                if (conditionFields[0].type === 'checkbox') {
                  let actualValue = +conditionFields[0].value;
                  conditionValue = +conditionValue;
                  thisConditionApplies = actualValue === conditionValue;
                } else {
                  thisConditionApplies = conditionFields[0].value === conditionValue;
                }
              }
            }
          }

          if (invert) {
            thisConditionApplies = !thisConditionApplies;
          }
        }

        if (relation === 'AND') {
          conditionApplies = conditionApplies && thisConditionApplies;
        } else {
          conditionApplies = conditionApplies || thisConditionApplies;
        }
      }
    }

    return conditionApplies ? 1 : 0;
  }

  render() {
    const {
      menu,
      progress,
      selectedStep,
      isAPILoaded,
      changedFields
    } = this.state;

    if (!isAPILoaded) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], null);
    }

    let fieldsUpdateComplete = changedFields.length === 0;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Menu_Menu__WEBPACK_IMPORTED_MODULE_4__["default"], {
      isAPILoaded: isAPILoaded,
      menuItems: this.state.menu.menu_items,
      menu: this.menu,
      selectMenu: this.props.selectMenu,
      selectStep: this.props.selectStep,
      selectedStep: this.props.selectedStep,
      selectedMenuItem: this.props.selectedMenuItem,
      getPreviousAndNextMenuItems: this.props.getPreviousAndNextMenuItems
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Settings__WEBPACK_IMPORTED_MODULE_6__["default"], {
      dropItemFromModal: this.props.dropItemFromModal,
      selectMenu: this.props.selectMenu,
      handleModal: this.props.handleModal,
      showSavedSettingsNotice: this.showSavedSettingsNotice,
      updateField: this.props.updateField,
      pageProps: this.props.pageProps,
      setPageProps: this.props.setPageProps,
      fieldsUpdateComplete: fieldsUpdateComplete,
      highLightField: this.props.highLightField,
      highLightedField: this.props.highLightedField,
      isAPILoaded: isAPILoaded,
      fields: this.fields,
      progress: progress,
      saveChangedFields: this.saveChangedFields,
      menu: menu,
      save: this.save,
      saveAndContinue: this.saveAndContinue,
      selectedMenuItem: this.props.selectedMenuItem,
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
      let url = 'https://really-simple-ssl.com/support' + '?customername=' + encodeURIComponent(response.data.customer_name) + '&email=' + response.data.email + '&scanresults=' + encodeURIComponent(response.data.scan_results) + '&licensekey=' + encodeURIComponent(response.data.license_key) + '&supportrequest=' + encodeURIComponent(encodedMessage) + '&htaccesscontents=' + response.data.htaccess_contents;
      +'&debuglog=' + encodeURIComponent(response.data.system_status);
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
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
      disabled: textAreaDisabled,
      placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Type your question here", "really-simple-ssl"),
      onChange: message => this.onChangeHandler(message)
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
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

/***/ "./src/utils/api.js":
/*!**************************!*\
  !*** ./src/utils/api.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "activateSSL": function() { return /* binding */ activateSSL; },
/* harmony export */   "activateSSLNetworkwide": function() { return /* binding */ activateSSLNetworkwide; },
/* harmony export */   "getBlock": function() { return /* binding */ getBlock; },
/* harmony export */   "getFields": function() { return /* binding */ getFields; },
/* harmony export */   "getOnboarding": function() { return /* binding */ getOnboarding; },
/* harmony export */   "onboardingActions": function() { return /* binding */ onboardingActions; },
/* harmony export */   "overrideSSLDetection": function() { return /* binding */ overrideSSLDetection; },
/* harmony export */   "runTest": function() { return /* binding */ runTest; },
/* harmony export */   "setFields": function() { return /* binding */ setFields; }
/* harmony export */ });
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! axios */ "../../../../../../../node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_0__);

/*
 * Makes a get request to the fields list
 *
 * @param {string|boolean} restBase - rest base for the query.
 * @param {object} args
 * @returns {AxiosPromise<any>}
 */

const getFields = () => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  return axios__WEBPACK_IMPORTED_MODULE_0___default().get(rsssl_settings.site_url + 'reallysimplessl/v1/fields/get', config);
};
/*
 * Post our data to the back-end
 * @param data
 * @returns {Promise<AxiosResponse<any>>}
 */

const setFields = data => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  return axios__WEBPACK_IMPORTED_MODULE_0___default().post(rsssl_settings.site_url + 'reallysimplessl/v1/fields/set', data, config);
};
const getBlock = block => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  return axios__WEBPACK_IMPORTED_MODULE_0___default().get(rsssl_settings.site_url + 'reallysimplessl/v1/block/' + block, config);
};
const runTest = (test, state, data) => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };

  if (data) {
    data = encodeURIComponent(JSON.stringify(data));
  }

  return axios__WEBPACK_IMPORTED_MODULE_0___default().get(rsssl_settings.site_url + 'reallysimplessl/v1/tests/' + test + '?state=' + state + '&data=' + data, config);
};
const getOnboarding = () => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  console.log("run get onboarding");
  return axios__WEBPACK_IMPORTED_MODULE_0___default().get(rsssl_settings.site_url + 'reallysimplessl/v1/onboarding', config);
};
const overrideSSLDetection = data => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  return axios__WEBPACK_IMPORTED_MODULE_0___default().post(rsssl_settings.site_url + 'reallysimplessl/v1/override_ssl_detection', data, config);
};
const activateSSL = data => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  return axios__WEBPACK_IMPORTED_MODULE_0___default().post(rsssl_settings.site_url + 'reallysimplessl/v1/activate_ssl', data, config);
};
const activateSSLNetworkwide = data => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  return axios__WEBPACK_IMPORTED_MODULE_0___default().post(rsssl_settings.site_url + 'reallysimplessl/v1/activate_ssl_networkwide', data, config);
};
const onboardingActions = data => {
  let config = {
    headers: {
      'X-WP-Nonce': rsssl_settings.nonce
    }
  };
  return axios__WEBPACK_IMPORTED_MODULE_0___default().post(rsssl_settings.site_url + 'reallysimplessl/v1/onboarding_actions', data, config);
};

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
/**
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

/***/ }),

/***/ "../../../../../../../node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/*!**************************************************************************************!*\
  !*** ../../../../../../../node_modules/@babel/runtime/helpers/esm/defineProperty.js ***!
  \**************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _defineProperty; }
/* harmony export */ });
function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

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
}();
/******/ })()
;
//# sourceMappingURL=index.js.map