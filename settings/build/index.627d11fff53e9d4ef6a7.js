/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/axios/index.js":
/*!*************************************!*\
  !*** ./node_modules/axios/index.js ***!
  \*************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = __webpack_require__(/*! ./lib/axios */ "./node_modules/axios/lib/axios.js");

/***/ }),

/***/ "./node_modules/axios/lib/adapters/xhr.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/adapters/xhr.js ***!
  \************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var settle = __webpack_require__(/*! ./../core/settle */ "./node_modules/axios/lib/core/settle.js");
var cookies = __webpack_require__(/*! ./../helpers/cookies */ "./node_modules/axios/lib/helpers/cookies.js");
var buildURL = __webpack_require__(/*! ./../helpers/buildURL */ "./node_modules/axios/lib/helpers/buildURL.js");
var buildFullPath = __webpack_require__(/*! ../core/buildFullPath */ "./node_modules/axios/lib/core/buildFullPath.js");
var parseHeaders = __webpack_require__(/*! ./../helpers/parseHeaders */ "./node_modules/axios/lib/helpers/parseHeaders.js");
var isURLSameOrigin = __webpack_require__(/*! ./../helpers/isURLSameOrigin */ "./node_modules/axios/lib/helpers/isURLSameOrigin.js");
var createError = __webpack_require__(/*! ../core/createError */ "./node_modules/axios/lib/core/createError.js");
var defaults = __webpack_require__(/*! ../defaults */ "./node_modules/axios/lib/defaults.js");
var Cancel = __webpack_require__(/*! ../cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

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

/***/ "./node_modules/axios/lib/axios.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/axios.js ***!
  \*****************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");
var Axios = __webpack_require__(/*! ./core/Axios */ "./node_modules/axios/lib/core/Axios.js");
var mergeConfig = __webpack_require__(/*! ./core/mergeConfig */ "./node_modules/axios/lib/core/mergeConfig.js");
var defaults = __webpack_require__(/*! ./defaults */ "./node_modules/axios/lib/defaults.js");

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
axios.Cancel = __webpack_require__(/*! ./cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");
axios.CancelToken = __webpack_require__(/*! ./cancel/CancelToken */ "./node_modules/axios/lib/cancel/CancelToken.js");
axios.isCancel = __webpack_require__(/*! ./cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");
axios.VERSION = (__webpack_require__(/*! ./env/data */ "./node_modules/axios/lib/env/data.js").version);

// Expose all/spread
axios.all = function all(promises) {
  return Promise.all(promises);
};
axios.spread = __webpack_require__(/*! ./helpers/spread */ "./node_modules/axios/lib/helpers/spread.js");

// Expose isAxiosError
axios.isAxiosError = __webpack_require__(/*! ./helpers/isAxiosError */ "./node_modules/axios/lib/helpers/isAxiosError.js");

module.exports = axios;

// Allow use of default import syntax in TypeScript
module.exports["default"] = axios;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/Cancel.js":
/*!*************************************************!*\
  !*** ./node_modules/axios/lib/cancel/Cancel.js ***!
  \*************************************************/
/***/ ((module) => {

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

/***/ "./node_modules/axios/lib/cancel/CancelToken.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/cancel/CancelToken.js ***!
  \******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var Cancel = __webpack_require__(/*! ./Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

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

/***/ "./node_modules/axios/lib/cancel/isCancel.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/cancel/isCancel.js ***!
  \***************************************************/
/***/ ((module) => {

"use strict";


module.exports = function isCancel(value) {
  return !!(value && value.__CANCEL__);
};


/***/ }),

/***/ "./node_modules/axios/lib/core/Axios.js":
/*!**********************************************!*\
  !*** ./node_modules/axios/lib/core/Axios.js ***!
  \**********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var buildURL = __webpack_require__(/*! ../helpers/buildURL */ "./node_modules/axios/lib/helpers/buildURL.js");
var InterceptorManager = __webpack_require__(/*! ./InterceptorManager */ "./node_modules/axios/lib/core/InterceptorManager.js");
var dispatchRequest = __webpack_require__(/*! ./dispatchRequest */ "./node_modules/axios/lib/core/dispatchRequest.js");
var mergeConfig = __webpack_require__(/*! ./mergeConfig */ "./node_modules/axios/lib/core/mergeConfig.js");
var validator = __webpack_require__(/*! ../helpers/validator */ "./node_modules/axios/lib/helpers/validator.js");

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

/***/ "./node_modules/axios/lib/core/InterceptorManager.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/core/InterceptorManager.js ***!
  \***********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

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

/***/ "./node_modules/axios/lib/core/buildFullPath.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/core/buildFullPath.js ***!
  \******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var isAbsoluteURL = __webpack_require__(/*! ../helpers/isAbsoluteURL */ "./node_modules/axios/lib/helpers/isAbsoluteURL.js");
var combineURLs = __webpack_require__(/*! ../helpers/combineURLs */ "./node_modules/axios/lib/helpers/combineURLs.js");

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

/***/ "./node_modules/axios/lib/core/createError.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/core/createError.js ***!
  \****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var enhanceError = __webpack_require__(/*! ./enhanceError */ "./node_modules/axios/lib/core/enhanceError.js");

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

/***/ "./node_modules/axios/lib/core/dispatchRequest.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/core/dispatchRequest.js ***!
  \********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var transformData = __webpack_require__(/*! ./transformData */ "./node_modules/axios/lib/core/transformData.js");
var isCancel = __webpack_require__(/*! ../cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");
var defaults = __webpack_require__(/*! ../defaults */ "./node_modules/axios/lib/defaults.js");
var Cancel = __webpack_require__(/*! ../cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

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

/***/ "./node_modules/axios/lib/core/enhanceError.js":
/*!*****************************************************!*\
  !*** ./node_modules/axios/lib/core/enhanceError.js ***!
  \*****************************************************/
/***/ ((module) => {

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

/***/ "./node_modules/axios/lib/core/mergeConfig.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/core/mergeConfig.js ***!
  \****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "./node_modules/axios/lib/utils.js");

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

/***/ "./node_modules/axios/lib/core/settle.js":
/*!***********************************************!*\
  !*** ./node_modules/axios/lib/core/settle.js ***!
  \***********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var createError = __webpack_require__(/*! ./createError */ "./node_modules/axios/lib/core/createError.js");

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

/***/ "./node_modules/axios/lib/core/transformData.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/core/transformData.js ***!
  \******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var defaults = __webpack_require__(/*! ./../defaults */ "./node_modules/axios/lib/defaults.js");

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

/***/ "./node_modules/axios/lib/defaults.js":
/*!********************************************!*\
  !*** ./node_modules/axios/lib/defaults.js ***!
  \********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var normalizeHeaderName = __webpack_require__(/*! ./helpers/normalizeHeaderName */ "./node_modules/axios/lib/helpers/normalizeHeaderName.js");
var enhanceError = __webpack_require__(/*! ./core/enhanceError */ "./node_modules/axios/lib/core/enhanceError.js");

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
    adapter = __webpack_require__(/*! ./adapters/xhr */ "./node_modules/axios/lib/adapters/xhr.js");
  } else if (typeof process !== 'undefined' && Object.prototype.toString.call(process) === '[object process]') {
    // For node use HTTP adapter
    adapter = __webpack_require__(/*! ./adapters/http */ "./node_modules/axios/lib/adapters/xhr.js");
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

/***/ "./node_modules/axios/lib/env/data.js":
/*!********************************************!*\
  !*** ./node_modules/axios/lib/env/data.js ***!
  \********************************************/
/***/ ((module) => {

module.exports = {
  "version": "0.25.0"
};

/***/ }),

/***/ "./node_modules/axios/lib/helpers/bind.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/helpers/bind.js ***!
  \************************************************/
/***/ ((module) => {

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

/***/ "./node_modules/axios/lib/helpers/buildURL.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/helpers/buildURL.js ***!
  \****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

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

/***/ "./node_modules/axios/lib/helpers/combineURLs.js":
/*!*******************************************************!*\
  !*** ./node_modules/axios/lib/helpers/combineURLs.js ***!
  \*******************************************************/
/***/ ((module) => {

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

/***/ "./node_modules/axios/lib/helpers/cookies.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/helpers/cookies.js ***!
  \***************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

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

/***/ "./node_modules/axios/lib/helpers/isAbsoluteURL.js":
/*!*********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isAbsoluteURL.js ***!
  \*********************************************************/
/***/ ((module) => {

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

/***/ "./node_modules/axios/lib/helpers/isAxiosError.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isAxiosError.js ***!
  \********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

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

/***/ "./node_modules/axios/lib/helpers/isURLSameOrigin.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isURLSameOrigin.js ***!
  \***********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

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

/***/ "./node_modules/axios/lib/helpers/normalizeHeaderName.js":
/*!***************************************************************!*\
  !*** ./node_modules/axios/lib/helpers/normalizeHeaderName.js ***!
  \***************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "./node_modules/axios/lib/utils.js");

module.exports = function normalizeHeaderName(headers, normalizedName) {
  utils.forEach(headers, function processHeader(value, name) {
    if (name !== normalizedName && name.toUpperCase() === normalizedName.toUpperCase()) {
      headers[normalizedName] = value;
      delete headers[name];
    }
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/parseHeaders.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/parseHeaders.js ***!
  \********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

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

/***/ "./node_modules/axios/lib/helpers/spread.js":
/*!**************************************************!*\
  !*** ./node_modules/axios/lib/helpers/spread.js ***!
  \**************************************************/
/***/ ((module) => {

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

/***/ "./node_modules/axios/lib/helpers/validator.js":
/*!*****************************************************!*\
  !*** ./node_modules/axios/lib/helpers/validator.js ***!
  \*****************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var VERSION = (__webpack_require__(/*! ../env/data */ "./node_modules/axios/lib/env/data.js").version);

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

/***/ "./node_modules/axios/lib/utils.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/utils.js ***!
  \*****************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");

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

/***/ "./src/Header.js":
/*!***********************!*\
  !*** ./src/Header.js ***!
  \***********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Settings_Notices__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Settings/Notices */ "./src/Settings/Notices.js");
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Menu/MenuData */ "./src/Menu/MenuData.js");





const Header = () => {
  const {
    menu,
    selectedMainMenuItem,
    fetchMenuData
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_3__["default"])();
  let plugin_url = rsssl_settings.plugin_url;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    fetchMenuData();
  }, []);
  let menuItems = menu.filter(item => item !== null);
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
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, menuItems.map((menu_item, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: "menu-" + i
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: selectedMainMenuItem === menu_item.id ? 'active' : '',
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
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Go Pro", "really-simple-ssl")))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Settings_Notices__WEBPACK_IMPORTED_MODULE_2__["default"], {
    className: "rsssl-wizard-notices"
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Header);

/***/ }),

/***/ "./src/Menu/MenuData.js":
/*!******************************!*\
  !*** ./src/Menu/MenuData.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_getAnchor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/getAnchor */ "./src/utils/getAnchor.js");


const useMenu = (0,zustand__WEBPACK_IMPORTED_MODULE_1__.create)((set, get) => ({
  menu: [],
  subMenuLoaded: false,
  previousMenuItem: false,
  nextMenuItem: false,
  selectedMainMenuItem: false,
  selectedSubMenuItem: false,
  selectedFilter: false,
  activeGroupId: false,
  hasPremiumItems: false,
  subMenu: {
    title: ' ',
    menu_items: []
  },
  setSelectedSubMenuItem: async selectedSubMenuItem => {
    let selectedMainMenuItem = getMainMenuForSubMenu(selectedSubMenuItem);
    set(state => ({
      selectedSubMenuItem,
      selectedMainMenuItem
    }));
    // window.location.href=rsssl_settings.dashboard_url+'#'+selectedMainMenuItem+'/'+selectedSubMenuItem;
    window.location.hash = selectedMainMenuItem + '/' + selectedSubMenuItem;
  },
  setSelectedMainMenuItem: selectedMainMenuItem => {
    set(state => ({
      selectedMainMenuItem
    }));
    // window.location.href=rsssl_settings.dashboard_url+'#'+selectedMainMenuItem;
    window.location.hash = selectedMainMenuItem;
  },
  //we need to get the main menu item directly from the anchor, otherwise we have to wait for the menu to load in page.js
  fetchSelectedMainMenuItem: () => {
    let selectedMainMenuItem = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_0__["default"])('main') || 'dashboard';
    set(state => ({
      selectedMainMenuItem: selectedMainMenuItem
    }));
  },
  fetchSelectedSubMenuItem: async () => {
    let selectedSubMenuItem = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_0__["default"])('menu') || 'general';
    set(state => ({
      selectedSubMenuItem: selectedSubMenuItem
    }));
  },
  fetchMenuData: fields => {
    let menu = rsssl_settings.menu;
    menu = Object.values(menu);
    const selectedMainMenuItem = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_0__["default"])('main') || 'dashboard';
    menu = menu.filter(item => !item.default_hidden || selectedMainMenuItem === item.id);
    if (typeof fields !== 'undefined') {
      let subMenu = getSubMenu(menu, selectedMainMenuItem);
      const selectedSubMenuItem = getSelectedSubMenuItem(subMenu, fields);
      subMenu.menu_items = dropEmptyMenuItems(subMenu.menu_items, fields, selectedSubMenuItem);
      const {
        nextMenuItem,
        previousMenuItem
      } = getPreviousAndNextMenuItems(menu, selectedSubMenuItem, fields);
      const hasPremiumItems = subMenu.menu_items.filter(item => {
        return item.premium === true;
      }).length > 0;
      set(state => ({
        subMenuLoaded: true,
        menu: menu,
        nextMenuItem: nextMenuItem,
        previousMenuItem: previousMenuItem,
        selectedMainMenuItem: selectedMainMenuItem,
        selectedSubMenuItem: selectedSubMenuItem,
        subMenu: subMenu,
        hasPremiumItems: hasPremiumItems
      }));
    } else {
      set(state => ({
        menu: menu,
        selectedMainMenuItem: selectedMainMenuItem
      }));
    }
  },
  getDefaultSubMenuItem: async fields => {
    let subMenuLoaded = get().subMenuLoaded;
    if (!subMenuLoaded) {
      await get().fetchMenuData(fields);
    }
    let subMenu = get().subMenu;
    let fallBackMenuItem = subMenuLoaded && subMenu.hasOwnProperty(0) ? subMenu[0].id : 'general';
    let anchor = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_0__["default"])('menu');
    let foundAnchorInMenu = false;
    //check if this anchor actually exists in our current submenu. If not, clear it
    for (const key in undefined.menu.menu_items) {
      if (subMenu.hasOwnProperty(key) && subMenu[key].id === anchor) {
        foundAnchorInMenu = true;
      }
    }
    if (!foundAnchorInMenu) anchor = false;
    return anchor ? anchor : fallBackMenuItem;
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useMenu);

// Parses menu items and nested items in single array
const menuItemParser = function (parsedMenuItems) {
  let menuItems = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  let fields = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  if (!Array.isArray(menuItems)) {
    console.error('menuItems is not an array:', menuItems);
    return parsedMenuItems;
  }
  menuItems.forEach(menuItem => {
    if (menuItem.visible) {
      parsedMenuItems.push(menuItem.id);
      if (menuItem.hasOwnProperty('menu_items')) {
        menuItem.menu_items = dropEmptyMenuItems(menuItem.menu_items, fields);
        menuItemParser(parsedMenuItems, menuItem.menu_items, fields);
      }
    }
  });
  return parsedMenuItems;
};

// const menuItemParser = (parsedMenuItems, menuItems, fields) => {
//     menuItems.forEach((menuItem) => {
//         if( menuItem.visible ) {
//             parsedMenuItems.push(menuItem.id);
//             if( menuItem.hasOwnProperty('menu_items') ) {
//                 menuItem.menu_items = dropEmptyMenuItems(menuItem.menu_items, fields );
//                 menuItemParser(parsedMenuItems, menuItem.menu_items, fields);
//             }
//         }
//     });
//     return parsedMenuItems;
// }

const getPreviousAndNextMenuItems = (menu, selectedSubMenuItem, fields) => {
  let previousMenuItem;
  let nextMenuItem;
  const parsedMenuItems = [];
  menuItemParser(parsedMenuItems, menu, fields);
  // Finds current menu item index
  const currentMenuItemIndex = parsedMenuItems.findIndex(menuItem => menuItem === selectedSubMenuItem);
  if (currentMenuItemIndex !== -1) {
    previousMenuItem = parsedMenuItems[currentMenuItemIndex === 0 ? '' : currentMenuItemIndex - 1];
    //if the previous menu item has a submenu, we should move one more back, because it will select the current sub otherwise.
    const previousMenuHasSubMenu = getMenuItemByName(previousMenuItem, menu).hasOwnProperty('menu_items');
    if (previousMenuHasSubMenu) {
      previousMenuItem = parsedMenuItems[currentMenuItemIndex === 0 ? '' : currentMenuItemIndex - 2];
    }
    nextMenuItem = parsedMenuItems[currentMenuItemIndex === parsedMenuItems.length - 1 ? '' : currentMenuItemIndex + 1];
    previousMenuItem = previousMenuItem ? previousMenuItem : parsedMenuItems[0];
    nextMenuItem = nextMenuItem ? nextMenuItem : parsedMenuItems[parsedMenuItems.length - 1];
  }
  return {
    nextMenuItem,
    previousMenuItem
  };
};
const dropEmptyMenuItems = (menuItems, fields) => {
  if (!Array.isArray(fields)) {
    console.error('Fields is not an array or is undefined', fields);
    return menuItems; // Exit early to avoid further processing
  }

  const newMenuItems = [];
  for (const menuItem of menuItems) {
    let menuItemFields = fields.filter(field => field.menu_id === menuItem.id && field.visible);
    if (menuItemFields.length === 0 && !menuItem.hasOwnProperty('menu_items')) {
      // Do nothing. We don't push it to the newMenuItems
    } else {
      let newMenuItem = {
        ...menuItem,
        visible: true
      }; // Deep copy of menuItem with visible set to true
      if (menuItem.hasOwnProperty('menu_items')) {
        newMenuItem.menu_items = dropEmptyMenuItems(menuItem.menu_items, fields);
      }
      newMenuItems.push(newMenuItem);
    }
  }
  return newMenuItems;
};

// const dropEmptyMenuItems = (menuItems, fields) => {
//     const newMenuItems = menuItems;
//     for (const [index, menuItem] of menuItems.entries()) {
//         let menuItemFields = fields.filter((field) => {
//             return (field.menu_id === menuItem.id )
//         });
//
//         menuItemFields = menuItemFields.filter((field) => {
//             return ( field.visible )
//         });
//         if ( menuItemFields.length === 0 && !menuItem.hasOwnProperty('menu_items') )  {
//             newMenuItems[index].visible = false;
//         } else {
//             newMenuItems[index].visible = true;
//             if( menuItem.hasOwnProperty('menu_items') ) {
//                 newMenuItems[index].menu_items = dropEmptyMenuItems(menuItem.menu_items, fields);
//             }
//         }
//
//
//     }
//     return newMenuItems;
// }

/*
* filter sidebar menu from complete menu structure
*/
const getSubMenu = (menu, selectedMainMenuItem) => {
  let subMenu = [];
  for (const key in menu) {
    if (menu.hasOwnProperty(key) && menu[key].id === selectedMainMenuItem) {
      subMenu = menu[key];
    }
  }
  subMenu = addVisibleToMenuItems(subMenu);
  return subMenu;
};

/*
* Get the main menu item for a submenu item
*/
const getMainMenuForSubMenu = findMenuItem => {
  let menu = rsssl_settings.menu;
  for (const mainKey in menu) {
    let mainMenuItem = menu[mainKey];
    if (mainMenuItem.id === findMenuItem) {
      return mainMenuItem.id;
    }
    if (mainMenuItem.menu_items) {
      for (const subKey in mainMenuItem.menu_items) {
        let subMenuItem = mainMenuItem.menu_items[subKey];
        if (subMenuItem.id === findMenuItem) {
          return mainMenuItem.id;
        }
        if (subMenuItem.menu_items) {
          for (const sub2Key in subMenuItem.menu_items) {
            let sub2MenuItem = subMenuItem.menu_items[sub2Key];
            if (sub2MenuItem.id === findMenuItem) {
              return mainMenuItem.id;
            }
          }
        }
      }
    }
  }
  return false;
};

/**
 * Get the current selected menu item based on the hash, selecting subitems if the main one is empty.
 */
const getSelectedSubMenuItem = (subMenu, fields) => {
  let fallBackMenuItem = subMenu && subMenu.menu_items.hasOwnProperty(0) ? subMenu.menu_items[0].id : 'general';
  let foundAnchorInMenu;

  //get flat array of menu items
  let parsedMenuItems = menuItemParser([], subMenu.menu_items);
  let anchor = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_0__["default"])('menu');
  //check if this anchor actually exists in our current submenu. If not, clear it
  foundAnchorInMenu = parsedMenuItems.filter(menu_item => menu_item === anchor);
  if (!foundAnchorInMenu) {
    anchor = false;
  }
  let selectedMenuItem = anchor ? anchor : fallBackMenuItem;
  //check if menu item has fields. If not, try a subitem
  let fieldsInMenu = fields.filter(field => field.menu_id === selectedMenuItem);
  if (fieldsInMenu.length === 0) {
    //look up the current menu item
    let menuItem = getMenuItemByName(selectedMenuItem, subMenu.menu_items);
    if (menuItem && menuItem.menu_items && menuItem.menu_items.hasOwnProperty(0)) {
      selectedMenuItem = menuItem.menu_items[0].id;
    }
  }
  return selectedMenuItem;
};

//Get a menu item by name from the menu array
const getMenuItemByName = (name, menuItems) => {
  for (const key in menuItems) {
    let menuItem = menuItems[key];
    if (menuItem.id === name) {
      return menuItem;
    }
    if (menuItem.menu_items) {
      let found = getMenuItemByName(name, menuItem.menu_items);
      if (found) return found;
    }
  }
  return false;
};
const addVisibleToMenuItems = menu => {
  if (typeof menu === 'string') {
    return menu; // If menu is a string, just return it as is
  }

  let newMenuItems = menu.menu_items;
  if (!Array.isArray(menu.menu_items)) {
    newMenuItems = [];
    for (const key in menu.menu_items) {
      if (typeof menu.menu_items[key] === 'object') {
        newMenuItems.push(menu.menu_items[key]);
      }
    }
  }
  for (let [index, menuItem] of newMenuItems.entries()) {
    if (typeof menuItem === 'object') {
      menuItem.visible = true;
      if (menuItem.hasOwnProperty('menu_items')) {
        menuItem = addVisibleToMenuItems(menuItem);
      }
      newMenuItems[index] = menuItem;
    }
  }
  menu.menu_items = newMenuItems;
  menu.visible = true;
  return menu;
};

// const addVisibleToMenuItems = (menu) => {
//     let newMenuItems = menu.menu_items;
//     if (!Array.isArray(menu.menu_items)) {
//         //wait whut not an array, well let us fix that
//         newMenuItems = [];
//         for (const key in menu.menu_items) {
//             newMenuItems.push(menu.menu_items[key]);
//         }
//     }
//     for (let [index, menuItem] of menu.menu_items.entries()) {
//         menuItem.visible = true;
//         if( menuItem.hasOwnProperty('menu_items') ) {
//             menuItem = addVisibleToMenuItems(menuItem);
//         }
//         newMenuItems[index] = menuItem;
//     }
//     menu.menu_items = newMenuItems;
//     menu.visible = true;
//     return menu;
// }

/***/ }),

/***/ "./src/Modal/ModalData.js":
/*!********************************!*\
  !*** ./src/Modal/ModalData.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");

const useModalData = (0,zustand__WEBPACK_IMPORTED_MODULE_0__.create)((set, get) => ({
  modalData: [],
  buttonsDisabled: false,
  showModal: false,
  ignoredItems: [],
  fixedItems: [],
  item: false,
  setIgnoredItemId: ignoredItemId => {
    let ignoredItems = get().ignoredItems;
    ignoredItems.push(ignoredItemId);
    set({
      ignoredItems: ignoredItems
    });
  },
  setFixedItemId: fixedItemId => {
    let fixedItems = get().fixedItems;
    fixedItems.push(fixedItemId);
    set({
      fixedItems: fixedItems
    });
  },
  handleModal: (showModal, modalData, item) => {
    set({
      showModal: showModal,
      modalData: modalData,
      item: item
    });
  },
  setModalData: modalData => {
    set({
      modalData: modalData
    });
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useModalData);

/***/ }),

/***/ "./src/Onboarding/OnboardingData.js":
/*!******************************************!*\
  !*** ./src/Onboarding/OnboardingData.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var immer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! immer */ "./node_modules/immer/dist/immer.esm.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);




const useOnboardingData = (0,zustand__WEBPACK_IMPORTED_MODULE_2__.create)((set, get) => ({
  steps: [],
  currentStepIndex: 0,
  currentStep: {},
  error: false,
  networkProgress: 0,
  networkActivationStatus: '',
  certificateValid: '',
  networkwide: false,
  sslEnabled: false,
  overrideSSL: false,
  showOnboardingModal: false,
  modalStatusLoaded: false,
  dataLoaded: false,
  processing: false,
  email: '',
  includeTips: false,
  sendTestEmail: true,
  actionStatus: '',
  setIncludeTips: includeTips => {
    set(state => ({
      includeTips
    }));
  },
  setSendTestEmail: sendTestEmail => {
    set(state => ({
      sendTestEmail
    }));
  },
  setEmail: email => {
    set(state => ({
      email
    }));
  },
  setShowOnboardingModal: showOnboardingModal => {
    set(state => ({
      showOnboardingModal
    }));
  },
  setProcessing: processing => {
    set(state => ({
      processing
    }));
  },
  setOverrideSSL: overrideSSL => {
    set(state => ({
      overrideSSL
    }));
  },
  setNetworkActivationStatus: networkActivationStatus => {
    set(state => ({
      networkActivationStatus
    }));
  },
  setCurrentStepIndex: currentStepIndex => {
    const currentStep = get().steps[currentStepIndex];
    set(state => ({
      currentStepIndex,
      currentStep
    }));
  },
  dismissModal: () => {
    let data = {};
    data.dismiss = true;
    set(state => ({
      showOnboardingModal: false
    }));
    _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('dismiss_modal', data).then(response => {});
  },
  saveEmail: () => {
    let data = {};
    data.email = get().email;
    data.includeTips = get().includeTips;
    data.sendTestEmail = get().sendTestEmail;
    set(state => ({
      processing: true
    }));
    _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('update_email', data).then(response => {
      set(state => ({
        processing: false
      }));
      get().setCurrentStepIndex(get().currentStepIndex + 1);
    });
  },
  updateItemStatus: (action, status, id) => {
    const currentStepIndex = get().currentStepIndex;
    const itemIndex = get().steps[currentStepIndex].items.findIndex(item => {
      return item.id === id;
    });
    set((0,immer__WEBPACK_IMPORTED_MODULE_3__.produce)(state => {
      let step = get().currentStep;
      let stepCopy = {
        ...step
      };
      let itemsCopy = [...step.items];
      let itemCopy = {
        ...step.items[itemIndex]
      };
      itemCopy.status = status;
      itemCopy.current_action = action;
      itemsCopy[itemIndex] = itemCopy;
      stepCopy.items = itemsCopy;
      state.steps[currentStepIndex] = stepCopy;
      state.currentStep = state.steps[currentStepIndex];
    }));
  },
  fetchOnboardingModalStatus: async () => {
    _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('get_modal_status').then(response => {
      set({
        showOnboardingModal: !response.dismissed,
        modalStatusLoaded: true
      });
    });
  },
  setShowOnBoardingModal: showOnboardingModal => set(state => ({
    showOnboardingModal
  })),
  actionHandler: async (id, action, event) => {
    set({
      actionStatus: 'processing'
    });
    event.preventDefault();
    get().updateItemStatus(action, 'processing', id);
    let next = await processAction(action, id);
    get().updateItemStatus(next.action, next.status, id);
    if (next.action !== 'none' && next.action !== 'completed') {
      next = await processAction(next.action, id);
      get().updateItemStatus(next.action, next.status, id);
    } else {
      set({
        actionStatus: 'completed'
      });
    }
  },
  getSteps: async forceRefresh => {
    const {
      steps,
      networkActivationStatus,
      certificateValid,
      networkProgress,
      networkwide,
      overrideSSL,
      error,
      sslEnabled
    } = await retrieveSteps(forceRefresh);
    //if ssl is already enabled, the server will send only one step. In that case we can skip the below.
    //it's only needed when SSL is activated just now, client side.
    let currentStepIndex = 0;
    if (sslEnabled || networkwide && networkActivationStatus === 'completed') {
      currentStepIndex = 1;
    }
    set({
      steps: steps,
      currentStepIndex: currentStepIndex,
      currentStep: steps[currentStepIndex],
      networkActivationStatus: networkActivationStatus,
      certificateValid: certificateValid,
      networkProgress: networkProgress,
      networkwide: networkwide,
      overrideSSL: overrideSSL,
      sslEnabled: sslEnabled,
      dataLoaded: true,
      error: error
    });
    if (networkActivationStatus === 'completed') {
      set({
        networkProgress: 100
      });
    }
  },
  refreshSSLStatus: e => {
    e.preventDefault();
    set({
      processing: true
    });
    set((0,immer__WEBPACK_IMPORTED_MODULE_3__.produce)(state => {
      const stepIndex = state.steps.findIndex(step => {
        return step.id === 'activate_ssl';
      });
      const step = state.steps[stepIndex];
      step.items.forEach(function (item, j) {
        if (item.status === 'error') {
          step.items[j].status = 'processing';
          step.items[j].title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Re-checking SSL certificate, please wait...", "really-simple-ssl");
        }
      });
      state.steps[stepIndex] = step;
    }));
    setTimeout(async function () {
      const {
        steps,
        certificateValid,
        error
      } = await retrieveSteps(true);
      set({
        steps: steps,
        certificateValid: certificateValid,
        processing: false,
        error: error
      });
    }, 1000); //add a delay, otherwise it's so fast the user may not trust it.
  },

  activateSSLNetworkWide: () => {
    if (get().networkProgress >= 100) {
      set({
        sslEnabled: true,
        networkActivationStatus: 'completed'
      });
      return;
    }
    set(() => ({
      processing: true
    }));
    _utils_api__WEBPACK_IMPORTED_MODULE_0__.runTest('activate_ssl_networkwide').then(response => {
      if (response.success) {
        set({
          networkProgress: response.progress,
          processing: false
        });
        if (response.progress >= 100) {
          set({
            sslEnabled: true,
            networkActivationStatus: 'completed'
          });
        }
      }
    });
  }
}));
const retrieveSteps = forceRefresh => {
  let data = {};
  data.forceRefresh = forceRefresh;
  return _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('onboarding_data', data).then(response => {
    let steps = response.steps;
    let sslEnabled = response.ssl_enabled;
    let networkActivationStatus = response.network_activation_status;
    let certificateValid = response.certificate_valid;
    let networkProgress = response.network_progress;
    let networkwide = response.networkwide;
    let overrideSSL = response.ssl_detection_overridden;
    let error = response.error;
    return {
      steps,
      networkActivationStatus,
      certificateValid,
      networkProgress,
      networkwide,
      overrideSSL,
      error,
      sslEnabled
    };
  });
};
const processAction = (action, id) => {
  let data = {};
  data.id = id;
  let next = {};
  return _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction(action, data).then(async response => {
    if (response.success) {
      next.action = response.next_action;
      next.status = 'success';
      return next;
    } else {
      next.action = 'failed';
      next.status = 'error';
      return next;
    }
  }).catch(error => {
    next.action = 'failed';
    next.status = 'error';
    return next;
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useOnboardingData);

/***/ }),

/***/ "./src/Page.js":
/*!*********************!*\
  !*** ./src/Page.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Header__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Header */ "./src/Header.js");
/* harmony import */ var _Placeholder_PagePlaceholder__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Placeholder/PagePlaceholder */ "./src/Placeholder/PagePlaceholder.js");
/* harmony import */ var _utils_getAnchor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./utils/getAnchor */ "./src/utils/getAnchor.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Menu/MenuData */ "./src/Menu/MenuData.js");
/* harmony import */ var _Onboarding_OnboardingData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Onboarding/OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Modal_ModalData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Modal/ModalData */ "./src/Modal/ModalData.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);










const Page = () => {
  const {
    error,
    fields,
    changedFields,
    fetchFieldsData,
    updateFieldsData,
    fieldsLoaded
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    showOnboardingModal,
    fetchOnboardingModalStatus,
    modalStatusLoaded
  } = (0,_Onboarding_OnboardingData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  const {
    selectedMainMenuItem,
    fetchMenuData
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const {
    showModal
  } = (0,_Modal_ModalData__WEBPACK_IMPORTED_MODULE_7__["default"])();
  const [Settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [DashboardPage, setDashboardPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [Notices, setNotices] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [Menu, setMenu] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!modalStatusLoaded) {
      fetchOnboardingModalStatus();
    }
  }, []);

  //load the chunk translations passed to us from the rsssl_settings object
  //only works in build mode, not in dev mode.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    rsssl_settings.json_translations.forEach(translationsString => {
      let translations = JSON.parse(translationsString);
      let localeData = translations.locale_data['really-simple-ssl'] || translations.locale_data.messages;
      localeData[""].domain = 'really-simple-ssl';
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__.setLocaleData)(localeData, 'really-simple-ssl');
    });
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (selectedMainMenuItem !== 'dashboard') {
      if (!Settings) {
        Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_mui_material_Tooltip_Tooltip_js"), __webpack_require__.e("src_Settings_Settings_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ./Settings/Settings */ "./src/Settings/Settings.js")).then(_ref => {
          let {
            default: Settings
          } = _ref;
          setSettings(() => Settings);
        });
      }
      if (!Notices) {
        Promise.resolve(/*! import() */).then(__webpack_require__.bind(__webpack_require__, /*! ./Settings/Notices */ "./src/Settings/Notices.js")).then(_ref2 => {
          let {
            default: Notices
          } = _ref2;
          setNotices(() => Notices);
        });
      }
      if (!Menu) {
        __webpack_require__.e(/*! import() */ "src_Menu_Menu_js").then(__webpack_require__.bind(__webpack_require__, /*! ./Menu/Menu */ "./src/Menu/Menu.js")).then(_ref3 => {
          let {
            default: Menu
          } = _ref3;
          setMenu(() => Menu);
        });
      }
    }
    if (selectedMainMenuItem === 'dashboard' && !DashboardPage) {
      Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_mui_material_Tooltip_Tooltip_js"), __webpack_require__.e("src_Dashboard_DashboardPage_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ./Dashboard/DashboardPage */ "./src/Dashboard/DashboardPage.js")).then(async _ref4 => {
        let {
          default: DashboardPage
        } = _ref4;
        setDashboardPage(() => DashboardPage);
      });
    }
  }, [selectedMainMenuItem]);
  const [OnboardingModal, setOnboardingModal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (showOnboardingModal && !OnboardingModal) {
      Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_mui_material_Tooltip_Tooltip_js"), __webpack_require__.e("src_Onboarding_OnboardingModal_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ./Onboarding/OnboardingModal */ "./src/Onboarding/OnboardingModal.js")).then(_ref5 => {
        let {
          default: OnboardingModal
        } = _ref5;
        setOnboardingModal(() => OnboardingModal);
      });
    }
  }, [showOnboardingModal]);
  const [Modal, setModal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (showModal && !Modal) {
      Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_mui_material_Tooltip_Tooltip_js"), __webpack_require__.e("src_Modal_Modal_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ./Modal/Modal */ "./src/Modal/Modal.js")).then(_ref6 => {
        let {
          default: Modal
        } = _ref6;
        setModal(() => Modal);
      });
    }
  }, [showModal]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (fieldsLoaded) {
      fetchMenuData(fields);
      window.addEventListener('hashchange', e => {
        fetchMenuData(fields);
      });
    }
  }, [fields]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    let subMenuItem = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_3__["default"])('menu');
    updateFieldsData(subMenuItem);
  }, [changedFields]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    let subMenuItem = (0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_3__["default"])('menu');
    fetchFieldsData(subMenuItem);
  }, []);
  if (error) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_PagePlaceholder__WEBPACK_IMPORTED_MODULE_2__["default"], {
      error: error
    }));
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-wrapper"
  }, OnboardingModal && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(OnboardingModal, null), Modal && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Modal, null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Header__WEBPACK_IMPORTED_MODULE_1__["default"], null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem
  }, selectedMainMenuItem !== 'dashboard' && Settings && Menu && Notices && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Menu, null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Settings, null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Notices, {
    className: "rsssl-wizard-notices"
  })), selectedMainMenuItem === 'dashboard' && DashboardPage && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(DashboardPage, null))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Page);

/***/ }),

/***/ "./src/Placeholder/PagePlaceholder.js":
/*!********************************************!*\
  !*** ./src/Placeholder/PagePlaceholder.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Error__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/Error */ "./src/utils/Error.js");


const PagePlaceholder = props => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-header-container"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-header"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    className: "rsssl-logo",
    src: rsssl_settings.plugin_url + 'assets/img/really-simple-ssl-logo.svg',
    alt: "Really Simple SSL logo"
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-content-area rsssl-grid rsssl-dashboard rsssl-page-placeholder"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item  rsssl-column-2 rsssl-row-2 "
  }, props.error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Error__WEBPACK_IMPORTED_MODULE_1__["default"], {
    error: props.error
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item rsssl-row-2"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item rsssl-row-2"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item  rsssl-column-2"
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PagePlaceholder);

/***/ }),

/***/ "./src/Settings/FieldsData.js":
/*!************************************!*\
  !*** ./src/Settings/FieldsData.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var immer__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! immer */ "./node_modules/immer/dist/immer.esm.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_sleeper_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/sleeper.js */ "./src/utils/sleeper.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);






const fetchFields = () => {
  return _utils_api__WEBPACK_IMPORTED_MODULE_0__.getFields().then(response => {
    let fields = response.fields;
    let progress = response.progress;
    let error = response.error;
    return {
      fields,
      progress,
      error
    };
  }).catch(error => {
    console.error(error);
  });
};
const useFields = (0,zustand__WEBPACK_IMPORTED_MODULE_4__.create)((set, get) => ({
  fieldsLoaded: false,
  error: false,
  fields: [],
  changedFields: [],
  progress: [],
  nextButtonDisabled: false,
  refreshTests: false,
  highLightField: '',
  setHighLightField: highLightField => {
    set(state => ({
      highLightField
    }));
  },
  setRefreshTests: refreshTests => set(state => ({
    refreshTests
  })),
  handleNextButtonDisabled: nextButtonDisabled => set(state => ({
    nextButtonDisabled
  })),
  setChangedField: (id, value) => {
    set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
      //remove current reference
      const existingFieldIndex = state.changedFields.findIndex(field => {
        return field.id === id;
      });
      if (existingFieldIndex !== -1) {
        state.changedFields.splice(existingFieldIndex, 1);
      }

      //add again, with new value
      let field = {};
      field.id = id;
      field.value = value;
      state.changedFields.push(field);
    }));
  },
  showSavedSettingsNotice: text => {
    handleShowSavedSettingsNotice(text);
  },
  updateField: (id, value) => {
    set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
      let index = state.fields.findIndex(fieldItem => fieldItem.id === id);
      if (index !== -1) {
        state.fields[index].value = value;
      }
    }));
  },
  updateFieldAttribute: (id, attribute, value) => {
    set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
      let index = state.fields.findIndex(fieldItem => fieldItem.id === id);
      if (index !== -1) {
        state.fields[index][attribute] = value;
      }
    }));
  },
  updateSubField: (id, subItemId, value) => {
    set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
      let index = state.fields.findIndex(fieldItem => fieldItem.id === id);
      let itemValue = state.fields[index].value;
      if (!Array.isArray(itemValue)) {
        itemValue = [];
      }
      let subIndex = itemValue.findIndex(subItem => subItem.id === subItemId);
      if (subIndex !== -1) {
        state.fields[index].updateItemId = subItemId;
        state.fields[index].value[subIndex]['value'] = value;
        state.fields[index].value = itemValue.map(item => {
          const {
            deleteControl,
            valueControl,
            statusControl,
            ...rest
          } = item;
          return rest;
        });
      }
    }));
  },
  addHelpNotice: (id, label, text, title, url) => {
    //create help object
    let help = {};
    help.label = label;
    help.text = text;
    if (url) help.url = url;
    if (title) help.title = title;
    let fields = get().fields;
    let newFields = [];
    //add to selected field
    let fieldEdited = false;
    fields.forEach(function (fieldItem, i) {
      let newFieldItem = {
        ...fieldItem
      };
      if (fieldItem.id === id && !fieldItem.help) {
        fieldEdited = true;
        newFieldItem.help = help;
      }
      newFields.push(newFieldItem);
    });
    if (fieldEdited) {
      set({
        fields: newFields
      });
    }
  },
  fieldAlreadyEnabled: id => {
    let fieldIsChanged = get().changedFields.filter(field => field.id === id).length > 0;
    let fieldIsEnabled = get().getFieldValue(id);
    return !fieldIsChanged && fieldIsEnabled;
  },
  getFieldValue: id => {
    let fields = get().fields;
    let fieldItem = fields.filter(field => field.id === id)[0];
    if (fieldItem) {
      return fieldItem.value;
    }
    return false;
  },
  getField: id => {
    let fields = get().fields;
    let fieldItem = fields.filter(field => field.id === id)[0];
    if (fieldItem) {
      return fieldItem;
    }
    return false;
  },
  saveFields: (skipRefreshTests, showSavedNotice) => {
    let refreshTests = typeof skipRefreshTests !== 'undefined' ? skipRefreshTests : true;
    showSavedNotice = typeof showSavedNotice !== 'undefined' ? showSavedNotice : true;
    let fields = get().fields;
    fields = fields.filter(field => field.data_target !== 'banner');
    let changedFields = get().changedFields;
    let progress = get().progress;
    let saveFields = [];
    //data_target
    for (const field of fields) {
      let fieldIsIncluded = changedFields.filter(changedField => changedField.id === field.id).length > 0;
      //also check if there's no saved value yet for radio fields, by checking the never_saved attribute.
      //a radio or select field looks like it's completed, but won't save if it isn't changed.
      //this should not be the case for disabled fields, as these fields often are enabled server side because they're enabled outside Really Simple SSL.
      let select_or_radio = field.type === 'select' || field.type === 'radio';
      if (fieldIsIncluded || field.never_saved && !field.disabled && select_or_radio) {
        saveFields.push(field);
      }
    }

    //if no fields were changed, do nothing.
    if (saveFields.length > 0) {
      _utils_api__WEBPACK_IMPORTED_MODULE_0__.setFields(saveFields).then(response => {
        progress = response.progress;
        fields = response.fields;
        set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
          state.changedFields = [];
          state.fields = fields;
          state.progress = progress;
          state.refreshTests = refreshTests;
        }));
      });
    }
    if (showSavedNotice) {
      handleShowSavedSettingsNotice();
    }
  },
  updateFieldsData: selectedSubMenuItem => {
    let fields = get().fields;
    fields = updateFieldsListWithConditions(fields);
    const nextButtonDisabled = isNextButtonDisabled(fields, selectedSubMenuItem);
    set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
      state.fields = fields;
      state.nextButtonDisabled = nextButtonDisabled;
    }));
  },
  fetchFieldsData: async selectedSubMenuItem => {
    const {
      fields,
      progress,
      error
    } = await fetchFields();
    let conditionallyEnabledFields = updateFieldsListWithConditions(fields);
    let selectedFields = conditionallyEnabledFields.filter(field => field.menu_id === selectedSubMenuItem);
    set({
      fieldsLoaded: true,
      fields: conditionallyEnabledFields,
      selectedFields: selectedFields,
      progress: progress,
      error: error
    });
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useFields);

//check if all required fields have been enabled. If so, enable save/continue button
const isNextButtonDisabled = (fields, selectedMenuItem) => {
  let fieldsOnPage = [];
  //get all fields with group_id this.props.group_id
  for (const field of fields) {
    if (field.menu_id === selectedMenuItem) {
      fieldsOnPage.push(field);
    }
  }
  let requiredFields = fieldsOnPage.filter(field => field.required && !field.conditionallyDisabled && (field.value.length == 0 || !field.value));
  return requiredFields.length > 0;
};
const updateFieldsListWithConditions = fields => {
  let newFields = [];
  if (!fields || !Array.isArray(fields)) {
    return [];
  }
  fields.forEach(function (field, i) {
    let enabled = !(field.hasOwnProperty('react_conditions') && !validateConditions(field.react_conditions, fields, field.id));
    let previouslyEnabled = !field.conditionallyDisabled;
    //we want to update the changed fields if this field has just become visible. Otherwise the new field won't get saved.
    const newField = {
      ...field
    };
    newField.conditionallyDisabled = !enabled;
    newField.visible = !(!enabled && (newField.type === 'letsencrypt' || newField.condition_action === 'hide'));
    newFields.push(newField);
    //if this is a learning mode field, do not add it to the changed fields list
    if (!previouslyEnabled && newField.enabled && field.type !== 'learningmode') {
      set().setChangedField(field.id, field.value);
    }
  });
  return newFields;
};
const handleShowSavedSettingsNotice = text => {
  if (typeof text === 'undefined') {
    text = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Settings Saved', 'really-simple-ssl');
  }
  (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', text, {
    __unstableHTML: true,
    id: 'rsssl_settings_saved',
    type: 'snackbar',
    isDismissible: false
  }).then((0,_utils_sleeper_js__WEBPACK_IMPORTED_MODULE_1__["default"])(2000)).then(response => {
    (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_settings_saved');
  });
};
const validateConditions = (conditions, fields, fieldId) => {
  let relation = conditions[0].relation === 'OR' ? 'OR' : 'AND';
  let conditionApplies = relation === 'AND';
  for (const key in conditions) {
    if (conditions.hasOwnProperty(key)) {
      let thisConditionApplies = relation === 'AND';
      let subConditionsArray = conditions[key];
      if (subConditionsArray.hasOwnProperty('relation')) {
        thisConditionApplies = validateConditions(subConditionsArray, fields, fieldId);
      } else {
        for (let conditionField in subConditionsArray) {
          let invert = conditionField.indexOf('!') === 0;
          if (subConditionsArray.hasOwnProperty(conditionField)) {
            let conditionValue = subConditionsArray[conditionField];
            conditionField = conditionField.replace('!', '');
            let conditionFields = fields.filter(field => field.id === conditionField);
            if (conditionFields.hasOwnProperty(0)) {
              let field = conditionFields[0];
              let actualValue = field.value;
              if (field.type === 'text_checkbox') {
                thisConditionApplies = actualValue.hasOwnProperty('show') && actualValue['show'] == conditionValue; //can be 1/true or 0/false
              } else if (field.type === 'checkbox') {
                thisConditionApplies = actualValue == conditionValue; //can be 1/true or 0/false
              } else if (field.type === 'multicheckbox') {
                //multicheckbox conditions
                //loop through objects
                thisConditionApplies = false;
                let arrayValue = actualValue;
                if (arrayValue.length === 0) {
                  thisConditionApplies = false;
                } else {
                  for (const key of Object.keys(arrayValue)) {
                    if (!Array.isArray(conditionValue)) conditionValue = [conditionValue];
                    if (conditionValue.includes(arrayValue[key])) {
                      thisConditionApplies = true;
                      break;
                    }
                  }
                }
              } else if (field.type === 'radio') {
                //as the regions field can be both radio and multicheckbox, an array is possible for a radio field
                if (Array.isArray(conditionValue)) {
                  thisConditionApplies = conditionValue.includes(actualValue);
                } else {
                  thisConditionApplies = conditionValue === actualValue;
                }
              } else {
                if (conditionValue === true) {
                  thisConditionApplies = actualValue === 1 || actualValue === "1" || actualValue === true;
                } else if (conditionValue === false) {
                  thisConditionApplies = actualValue === 0 || actualValue === "0" || actualValue === false;
                } else if (conditionValue.indexOf('EMPTY') !== -1) {
                  thisConditionApplies = actualValue.length === 0;
                } else {
                  thisConditionApplies = String(actualValue).toLowerCase() === conditionValue.toLowerCase();
                }
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
        if (relation === 'AND') {
          conditionApplies = conditionApplies && thisConditionApplies;
        } else {
          conditionApplies = conditionApplies || thisConditionApplies;
        }
      }
    }
  }
  return conditionApplies ? 1 : 0;
};

/***/ }),

/***/ "./src/Settings/Notices.js":
/*!*********************************!*\
  !*** ./src/Settings/Notices.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Notices);

/***/ }),

/***/ "./src/utils/Error.js":
/*!****************************!*\
  !*** ./src/utils/Error.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Hyperlink__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Hyperlink */ "./src/utils/Hyperlink.js");



const Error = props => {
  if (props.error) {
    console.log("errors detected during the loading of the settings page");
    console.log(props.error);
  }
  let description = false;
  let url = 'https://really-simple-ssl.com/instructions/how-to-debug-a-blank-settings-page-in-really-simple-ssl/';
  let generic_rest_blocked_message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Please check if security settings on the server or a plugin is blocking the requests from Really Simple SSL.", "really-simple-ssl");
  let message = false;
  if (props.error) {
    message = props.error.message;
    if (typeof message !== 'string') {
      message = JSON.stringify(message);
    }
    if (props.error.code === 'rest_no_route') {
      description = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The Really Simple SSL Rest API is disabled.", "really-simple-ssl") + " " + generic_rest_blocked_message;
    } else if (props.error.data.status === '404') {
      description = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The Really Simple SSL Rest API returned a not found.", "really-simple-ssl") + " " + generic_rest_blocked_message;
    } else if (props.error.data.status === '403') {
      description = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The Really Simple SSL Rest API returned a 403 forbidden error.", "really-simple-ssl") + " " + generic_rest_blocked_message;
    }
    if (message.length > 100) {
      message = message.substring(0, 100) + '...';
    }
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, props.error && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-rest-error-message"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("A problem was detected during the loading of the settings", "really-simple-ssl")), description && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, description), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The request returned the following errors:", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, props.error.code && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Response code:", "really-simple-ssl"), "\xA0", props.error.code), props.error.data.status && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Status code:", "really-simple-ssl"), "\xA0", props.error.data.status), message && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Server response:", "really-simple-ssl"), "\xA0", message))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
    className: "button button-default",
    target: "_blank",
    text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("More information", "really-simple-ssl"),
    url: url
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Error);

/***/ }),

/***/ "./src/utils/Hyperlink.js":
/*!********************************!*\
  !*** ./src/utils/Hyperlink.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

const Hyperlink = props => {
  let label_pre = '';
  let label_post = '';
  let link_text = '';
  if (props.text.indexOf('%s') !== -1) {
    let parts = props.text.split(/%s/);
    label_pre = parts[0];
    link_text = parts[1];
    label_post = parts[2];
  } else {
    link_text = props.text;
  }
  let className = props.className ? props.className : 'rsssl-link';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, label_pre, " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: className,
    target: props.target,
    href: props.url
  }, link_text), label_post);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Hyperlink);

/***/ }),

/***/ "./src/utils/api.js":
/*!**************************!*\
  !*** ./src/utils/api.js ***!
  \**************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   doAction: () => (/* binding */ doAction),
/* harmony export */   getFields: () => (/* binding */ getFields),
/* harmony export */   getNonce: () => (/* binding */ getNonce),
/* harmony export */   runLetsEncryptTest: () => (/* binding */ runLetsEncryptTest),
/* harmony export */   runTest: () => (/* binding */ runTest),
/* harmony export */   setFields: () => (/* binding */ setFields)
/* harmony export */ });
/* harmony import */ var _getAnchor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getAnchor */ "./src/utils/getAnchor.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
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
const ajaxPost = (path, requestData) => {
  return new Promise(function (resolve, reject) {
    let url = siteUrl('ajax');
    let xhr = new XMLHttpRequest();
    xhr.open('POST', url);
    xhr.onload = function () {
      let response;
      try {
        response = JSON.parse(xhr.response);
      } catch (error) {
        resolve(invalidDataError(xhr.response, 500, 'invalid_data'));
      }
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(response);
      } else {
        resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText));
      }
    };
    xhr.onerror = function () {
      resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText));
    };
    let data = {};
    data['path'] = path;
    data['data'] = requestData;
    data = JSON.stringify(data, stripControls);
    xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
    xhr.send(data);
  });
};

/**
 * All data elements with 'Control' in the name are dropped, to prevent:
 * TypeError: Converting circular structure to JSON
 * @param key
 * @param value
 * @returns {any|undefined}
 */
const stripControls = (key, value) => {
  if (!key) {
    return value;
  }
  if (key && key.includes("Control")) {
    return undefined;
  }
  if (typeof value === "object") {
    return JSON.parse(JSON.stringify(value, stripControls));
  }
  return value;
};
const ajaxGet = path => {
  return new Promise(function (resolve, reject) {
    let url = siteUrl('ajax');
    url += '&rest_action=' + path.replace('?', '&');
    let xhr = new XMLHttpRequest();
    xhr.open('GET', url);
    xhr.onload = function () {
      let response;
      try {
        response = JSON.parse(xhr.response);
      } catch (error) {
        resolve(invalidDataError(xhr.response, 500, 'invalid_data'));
      }
      if (xhr.status >= 200 && xhr.status < 300) {
        if (!response.hasOwnProperty('request_success')) {
          resolve(invalidDataError(xhr.response, 500, 'invalid_data'));
        }
        resolve(response);
      } else {
        resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText));
      }
    };
    xhr.onerror = function () {
      resolve(invalidDataError(xhr.response, xhr.status, xhr.statusText));
    };
    xhr.send();
  });
};

/**
 * if the site is loaded over https, but the site url is not https, force to use https anyway, because otherwise we get mixed content issues.
 * @returns {*}
 */
const siteUrl = type => {
  let url;
  if (typeof type === 'undefined') {
    url = rsssl_settings.site_url;
  } else {
    url = rsssl_settings.admin_ajax_url;
  }
  if (window.location.protocol === "https:" && url.indexOf('https://') === -1) {
    return url.replace('http://', 'https://');
  }
  return url;
};
const invalidDataError = (apiResponse, status, code) => {
  let response = {};
  let error = {};
  let data = {};
  data.status = status;
  error.code = code;
  error.data = data;
  error.message = apiResponse;
  response.error = error;
  return response;
};
const apiGet = path => {
  if (usesPlainPermalinks()) {
    let config = {
      headers: {
        'X-WP-Nonce': rsssl_settings.nonce
      }
    };
    return axios__WEBPACK_IMPORTED_MODULE_1___default().get(siteUrl() + path, config).then(response => {
      if (!response.data.request_success) {
        return ajaxGet(path);
      }
      return response.data;
    }).catch(error => {
      //try with admin-ajax
      return ajaxGet(path);
    });
  } else {
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: path
    }).then(response => {
      if (!response.request_success) {
        return ajaxGet(path);
      }
      return response;
    }).catch(error => {
      return ajaxGet(path);
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
    return axios__WEBPACK_IMPORTED_MODULE_1___default().post(siteUrl() + path, data, config).then(response => {
      return response.data;
    }).catch(error => {
      return ajaxPost(path, data);
    });
  } else {
    return _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
      path: path,
      method: 'POST',
      data: data
    }).catch(error => {
      return ajaxPost(path, data);
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
const runTest = (test, state, data) => {
  if (!state) {
    state = false;
  }
  if (!data) {
    data = false;
  }
  data = encodeURIComponent(JSON.stringify(data));
  return apiGet('reallysimplessl/v1/tests/' + test + glue() + 'state=' + state + getNonce() + '&data=' + data);
};
const runLetsEncryptTest = (test, id) => {
  return apiGet('reallysimplessl/v1/tests/' + test + glue() + 'letsencrypt=1&id=' + id + getNonce());
};
const doAction = (action, data) => {
  const newData = {
    ...data
  };
  newData.nonce = rsssl_settings.rsssl_nonce;
  return apiPost('reallysimplessl/v1/do_action/' + action, newData);
};

/***/ }),

/***/ "./src/utils/getAnchor.js":
/*!********************************!*\
  !*** ./src/utils/getAnchor.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
  let urlPart = queryString[1];

  //for submenu, we have to get the string after the slash.
  if (level === 'anchor') {
    //if there is no slash, there is no menu level
    if (urlPart.indexOf('/') === -1) {
      return false;
    } else {
      let urlParts = urlPart.split('/');
      if (urlParts.length <= 2) {
        return false;
      } else {
        return urlParts[2];
      }
    }
  } else if (level === 'menu') {
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (getAnchor);

/***/ }),

/***/ "./src/utils/sleeper.js":
/*!******************************!*\
  !*** ./src/utils/sleeper.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (sleeper);

/***/ }),

/***/ "./node_modules/use-sync-external-store/cjs/use-sync-external-store-shim.development.js":
/*!**********************************************************************************************!*\
  !*** ./node_modules/use-sync-external-store/cjs/use-sync-external-store-shim.development.js ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
/**
 * @license React
 * use-sync-external-store-shim.development.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



if (true) {
  (function() {

          'use strict';

/* global __REACT_DEVTOOLS_GLOBAL_HOOK__ */
if (
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ !== 'undefined' &&
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStart ===
    'function'
) {
  __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStart(new Error());
}
          var React = __webpack_require__(/*! react */ "react");

var ReactSharedInternals = React.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;

function error(format) {
  {
    {
      for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
        args[_key2 - 1] = arguments[_key2];
      }

      printWarning('error', format, args);
    }
  }
}

function printWarning(level, format, args) {
  // When changing this logic, you might want to also
  // update consoleWithStackDev.www.js as well.
  {
    var ReactDebugCurrentFrame = ReactSharedInternals.ReactDebugCurrentFrame;
    var stack = ReactDebugCurrentFrame.getStackAddendum();

    if (stack !== '') {
      format += '%s';
      args = args.concat([stack]);
    } // eslint-disable-next-line react-internal/safe-string-coercion


    var argsWithFormat = args.map(function (item) {
      return String(item);
    }); // Careful: RN currently depends on this prefix

    argsWithFormat.unshift('Warning: ' + format); // We intentionally don't use spread (or .apply) directly because it
    // breaks IE9: https://github.com/facebook/react/issues/13610
    // eslint-disable-next-line react-internal/no-production-logging

    Function.prototype.apply.call(console[level], console, argsWithFormat);
  }
}

/**
 * inlined Object.is polyfill to avoid requiring consumers ship their own
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/is
 */
function is(x, y) {
  return x === y && (x !== 0 || 1 / x === 1 / y) || x !== x && y !== y // eslint-disable-line no-self-compare
  ;
}

var objectIs = typeof Object.is === 'function' ? Object.is : is;

// dispatch for CommonJS interop named imports.

var useState = React.useState,
    useEffect = React.useEffect,
    useLayoutEffect = React.useLayoutEffect,
    useDebugValue = React.useDebugValue;
var didWarnOld18Alpha = false;
var didWarnUncachedGetSnapshot = false; // Disclaimer: This shim breaks many of the rules of React, and only works
// because of a very particular set of implementation details and assumptions
// -- change any one of them and it will break. The most important assumption
// is that updates are always synchronous, because concurrent rendering is
// only available in versions of React that also have a built-in
// useSyncExternalStore API. And we only use this shim when the built-in API
// does not exist.
//
// Do not assume that the clever hacks used by this hook also work in general.
// The point of this shim is to replace the need for hacks by other libraries.

function useSyncExternalStore(subscribe, getSnapshot, // Note: The shim does not use getServerSnapshot, because pre-18 versions of
// React do not expose a way to check if we're hydrating. So users of the shim
// will need to track that themselves and return the correct value
// from `getSnapshot`.
getServerSnapshot) {
  {
    if (!didWarnOld18Alpha) {
      if (React.startTransition !== undefined) {
        didWarnOld18Alpha = true;

        error('You are using an outdated, pre-release alpha of React 18 that ' + 'does not support useSyncExternalStore. The ' + 'use-sync-external-store shim will not work correctly. Upgrade ' + 'to a newer pre-release.');
      }
    }
  } // Read the current snapshot from the store on every render. Again, this
  // breaks the rules of React, and only works here because of specific
  // implementation details, most importantly that updates are
  // always synchronous.


  var value = getSnapshot();

  {
    if (!didWarnUncachedGetSnapshot) {
      var cachedValue = getSnapshot();

      if (!objectIs(value, cachedValue)) {
        error('The result of getSnapshot should be cached to avoid an infinite loop');

        didWarnUncachedGetSnapshot = true;
      }
    }
  } // Because updates are synchronous, we don't queue them. Instead we force a
  // re-render whenever the subscribed state changes by updating an some
  // arbitrary useState hook. Then, during render, we call getSnapshot to read
  // the current value.
  //
  // Because we don't actually use the state returned by the useState hook, we
  // can save a bit of memory by storing other stuff in that slot.
  //
  // To implement the early bailout, we need to track some things on a mutable
  // object. Usually, we would put that in a useRef hook, but we can stash it in
  // our useState hook instead.
  //
  // To force a re-render, we call forceUpdate({inst}). That works because the
  // new object always fails an equality check.


  var _useState = useState({
    inst: {
      value: value,
      getSnapshot: getSnapshot
    }
  }),
      inst = _useState[0].inst,
      forceUpdate = _useState[1]; // Track the latest getSnapshot function with a ref. This needs to be updated
  // in the layout phase so we can access it during the tearing check that
  // happens on subscribe.


  useLayoutEffect(function () {
    inst.value = value;
    inst.getSnapshot = getSnapshot; // Whenever getSnapshot or subscribe changes, we need to check in the
    // commit phase if there was an interleaved mutation. In concurrent mode
    // this can happen all the time, but even in synchronous mode, an earlier
    // effect may have mutated the store.

    if (checkIfSnapshotChanged(inst)) {
      // Force a re-render.
      forceUpdate({
        inst: inst
      });
    }
  }, [subscribe, value, getSnapshot]);
  useEffect(function () {
    // Check for changes right before subscribing. Subsequent changes will be
    // detected in the subscription handler.
    if (checkIfSnapshotChanged(inst)) {
      // Force a re-render.
      forceUpdate({
        inst: inst
      });
    }

    var handleStoreChange = function () {
      // TODO: Because there is no cross-renderer API for batching updates, it's
      // up to the consumer of this library to wrap their subscription event
      // with unstable_batchedUpdates. Should we try to detect when this isn't
      // the case and print a warning in development?
      // The store changed. Check if the snapshot changed since the last time we
      // read from the store.
      if (checkIfSnapshotChanged(inst)) {
        // Force a re-render.
        forceUpdate({
          inst: inst
        });
      }
    }; // Subscribe to the store and return a clean-up function.


    return subscribe(handleStoreChange);
  }, [subscribe]);
  useDebugValue(value);
  return value;
}

function checkIfSnapshotChanged(inst) {
  var latestGetSnapshot = inst.getSnapshot;
  var prevValue = inst.value;

  try {
    var nextValue = latestGetSnapshot();
    return !objectIs(prevValue, nextValue);
  } catch (error) {
    return true;
  }
}

function useSyncExternalStore$1(subscribe, getSnapshot, getServerSnapshot) {
  // Note: The shim does not use getServerSnapshot, because pre-18 versions of
  // React do not expose a way to check if we're hydrating. So users of the shim
  // will need to track that themselves and return the correct value
  // from `getSnapshot`.
  return getSnapshot();
}

var canUseDOM = !!(typeof window !== 'undefined' && typeof window.document !== 'undefined' && typeof window.document.createElement !== 'undefined');

var isServerEnvironment = !canUseDOM;

var shim = isServerEnvironment ? useSyncExternalStore$1 : useSyncExternalStore;
var useSyncExternalStore$2 = React.useSyncExternalStore !== undefined ? React.useSyncExternalStore : shim;

exports.useSyncExternalStore = useSyncExternalStore$2;
          /* global __REACT_DEVTOOLS_GLOBAL_HOOK__ */
if (
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ !== 'undefined' &&
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStop ===
    'function'
) {
  __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStop(new Error());
}
        
  })();
}


/***/ }),

/***/ "./node_modules/use-sync-external-store/cjs/use-sync-external-store-shim/with-selector.development.js":
/*!************************************************************************************************************!*\
  !*** ./node_modules/use-sync-external-store/cjs/use-sync-external-store-shim/with-selector.development.js ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
/**
 * @license React
 * use-sync-external-store-shim/with-selector.development.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */



if (true) {
  (function() {

          'use strict';

/* global __REACT_DEVTOOLS_GLOBAL_HOOK__ */
if (
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ !== 'undefined' &&
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStart ===
    'function'
) {
  __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStart(new Error());
}
          var React = __webpack_require__(/*! react */ "react");
var shim = __webpack_require__(/*! use-sync-external-store/shim */ "./node_modules/use-sync-external-store/shim/index.js");

/**
 * inlined Object.is polyfill to avoid requiring consumers ship their own
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/is
 */
function is(x, y) {
  return x === y && (x !== 0 || 1 / x === 1 / y) || x !== x && y !== y // eslint-disable-line no-self-compare
  ;
}

var objectIs = typeof Object.is === 'function' ? Object.is : is;

var useSyncExternalStore = shim.useSyncExternalStore;

// for CommonJS interop.

var useRef = React.useRef,
    useEffect = React.useEffect,
    useMemo = React.useMemo,
    useDebugValue = React.useDebugValue; // Same as useSyncExternalStore, but supports selector and isEqual arguments.

function useSyncExternalStoreWithSelector(subscribe, getSnapshot, getServerSnapshot, selector, isEqual) {
  // Use this to track the rendered snapshot.
  var instRef = useRef(null);
  var inst;

  if (instRef.current === null) {
    inst = {
      hasValue: false,
      value: null
    };
    instRef.current = inst;
  } else {
    inst = instRef.current;
  }

  var _useMemo = useMemo(function () {
    // Track the memoized state using closure variables that are local to this
    // memoized instance of a getSnapshot function. Intentionally not using a
    // useRef hook, because that state would be shared across all concurrent
    // copies of the hook/component.
    var hasMemo = false;
    var memoizedSnapshot;
    var memoizedSelection;

    var memoizedSelector = function (nextSnapshot) {
      if (!hasMemo) {
        // The first time the hook is called, there is no memoized result.
        hasMemo = true;
        memoizedSnapshot = nextSnapshot;

        var _nextSelection = selector(nextSnapshot);

        if (isEqual !== undefined) {
          // Even if the selector has changed, the currently rendered selection
          // may be equal to the new selection. We should attempt to reuse the
          // current value if possible, to preserve downstream memoizations.
          if (inst.hasValue) {
            var currentSelection = inst.value;

            if (isEqual(currentSelection, _nextSelection)) {
              memoizedSelection = currentSelection;
              return currentSelection;
            }
          }
        }

        memoizedSelection = _nextSelection;
        return _nextSelection;
      } // We may be able to reuse the previous invocation's result.


      // We may be able to reuse the previous invocation's result.
      var prevSnapshot = memoizedSnapshot;
      var prevSelection = memoizedSelection;

      if (objectIs(prevSnapshot, nextSnapshot)) {
        // The snapshot is the same as last time. Reuse the previous selection.
        return prevSelection;
      } // The snapshot has changed, so we need to compute a new selection.


      // The snapshot has changed, so we need to compute a new selection.
      var nextSelection = selector(nextSnapshot); // If a custom isEqual function is provided, use that to check if the data
      // has changed. If it hasn't, return the previous selection. That signals
      // to React that the selections are conceptually equal, and we can bail
      // out of rendering.

      // If a custom isEqual function is provided, use that to check if the data
      // has changed. If it hasn't, return the previous selection. That signals
      // to React that the selections are conceptually equal, and we can bail
      // out of rendering.
      if (isEqual !== undefined && isEqual(prevSelection, nextSelection)) {
        return prevSelection;
      }

      memoizedSnapshot = nextSnapshot;
      memoizedSelection = nextSelection;
      return nextSelection;
    }; // Assigning this to a constant so that Flow knows it can't change.


    // Assigning this to a constant so that Flow knows it can't change.
    var maybeGetServerSnapshot = getServerSnapshot === undefined ? null : getServerSnapshot;

    var getSnapshotWithSelector = function () {
      return memoizedSelector(getSnapshot());
    };

    var getServerSnapshotWithSelector = maybeGetServerSnapshot === null ? undefined : function () {
      return memoizedSelector(maybeGetServerSnapshot());
    };
    return [getSnapshotWithSelector, getServerSnapshotWithSelector];
  }, [getSnapshot, getServerSnapshot, selector, isEqual]),
      getSelection = _useMemo[0],
      getServerSelection = _useMemo[1];

  var value = useSyncExternalStore(subscribe, getSelection, getServerSelection);
  useEffect(function () {
    inst.hasValue = true;
    inst.value = value;
  }, [value]);
  useDebugValue(value);
  return value;
}

exports.useSyncExternalStoreWithSelector = useSyncExternalStoreWithSelector;
          /* global __REACT_DEVTOOLS_GLOBAL_HOOK__ */
if (
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ !== 'undefined' &&
  typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStop ===
    'function'
) {
  __REACT_DEVTOOLS_GLOBAL_HOOK__.registerInternalModuleStop(new Error());
}
        
  })();
}


/***/ }),

/***/ "./node_modules/use-sync-external-store/shim/index.js":
/*!************************************************************!*\
  !*** ./node_modules/use-sync-external-store/shim/index.js ***!
  \************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


if (false) {} else {
  module.exports = __webpack_require__(/*! ../cjs/use-sync-external-store-shim.development.js */ "./node_modules/use-sync-external-store/cjs/use-sync-external-store-shim.development.js");
}


/***/ }),

/***/ "./node_modules/use-sync-external-store/shim/with-selector.js":
/*!********************************************************************!*\
  !*** ./node_modules/use-sync-external-store/shim/with-selector.js ***!
  \********************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


if (false) {} else {
  module.exports = __webpack_require__(/*! ../cjs/use-sync-external-store-shim/with-selector.development.js */ "./node_modules/use-sync-external-store/cjs/use-sync-external-store-shim/with-selector.development.js");
}


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

"use strict";
module.exports = window["React"];

/***/ }),

/***/ "react-dom":
/*!***************************!*\
  !*** external "ReactDOM" ***!
  \***************************/
/***/ ((module) => {

"use strict";
module.exports = window["ReactDOM"];

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "@wordpress/notices":
/*!*********************************!*\
  !*** external ["wp","notices"] ***!
  \*********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["notices"];

/***/ }),

/***/ "./node_modules/immer/dist/immer.esm.mjs":
/*!***********************************************!*\
  !*** ./node_modules/immer/dist/immer.esm.mjs ***!
  \***********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Immer: () => (/* binding */ un),
/* harmony export */   applyPatches: () => (/* binding */ pn),
/* harmony export */   castDraft: () => (/* binding */ K),
/* harmony export */   castImmutable: () => (/* binding */ $),
/* harmony export */   createDraft: () => (/* binding */ ln),
/* harmony export */   current: () => (/* binding */ R),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   enableAllPlugins: () => (/* binding */ J),
/* harmony export */   enableES5: () => (/* binding */ F),
/* harmony export */   enableMapSet: () => (/* binding */ C),
/* harmony export */   enablePatches: () => (/* binding */ T),
/* harmony export */   finishDraft: () => (/* binding */ dn),
/* harmony export */   freeze: () => (/* binding */ d),
/* harmony export */   immerable: () => (/* binding */ L),
/* harmony export */   isDraft: () => (/* binding */ r),
/* harmony export */   isDraftable: () => (/* binding */ t),
/* harmony export */   nothing: () => (/* binding */ H),
/* harmony export */   original: () => (/* binding */ e),
/* harmony export */   produce: () => (/* binding */ fn),
/* harmony export */   produceWithPatches: () => (/* binding */ cn),
/* harmony export */   setAutoFreeze: () => (/* binding */ sn),
/* harmony export */   setUseProxies: () => (/* binding */ vn)
/* harmony export */ });
function n(n){for(var r=arguments.length,t=Array(r>1?r-1:0),e=1;e<r;e++)t[e-1]=arguments[e];if(true){var i=Y[n],o=i?"function"==typeof i?i.apply(null,t):i:"unknown error nr: "+n;throw Error("[Immer] "+o)}throw Error("[Immer] minified error nr: "+n+(t.length?" "+t.map((function(n){return"'"+n+"'"})).join(","):"")+". Find the full error at: https://bit.ly/3cXEKWf")}function r(n){return!!n&&!!n[Q]}function t(n){var r;return!!n&&(function(n){if(!n||"object"!=typeof n)return!1;var r=Object.getPrototypeOf(n);if(null===r)return!0;var t=Object.hasOwnProperty.call(r,"constructor")&&r.constructor;return t===Object||"function"==typeof t&&Function.toString.call(t)===Z}(n)||Array.isArray(n)||!!n[L]||!!(null===(r=n.constructor)||void 0===r?void 0:r[L])||s(n)||v(n))}function e(t){return r(t)||n(23,t),t[Q].t}function i(n,r,t){void 0===t&&(t=!1),0===o(n)?(t?Object.keys:nn)(n).forEach((function(e){t&&"symbol"==typeof e||r(e,n[e],n)})):n.forEach((function(t,e){return r(e,t,n)}))}function o(n){var r=n[Q];return r?r.i>3?r.i-4:r.i:Array.isArray(n)?1:s(n)?2:v(n)?3:0}function u(n,r){return 2===o(n)?n.has(r):Object.prototype.hasOwnProperty.call(n,r)}function a(n,r){return 2===o(n)?n.get(r):n[r]}function f(n,r,t){var e=o(n);2===e?n.set(r,t):3===e?n.add(t):n[r]=t}function c(n,r){return n===r?0!==n||1/n==1/r:n!=n&&r!=r}function s(n){return X&&n instanceof Map}function v(n){return q&&n instanceof Set}function p(n){return n.o||n.t}function l(n){if(Array.isArray(n))return Array.prototype.slice.call(n);var r=rn(n);delete r[Q];for(var t=nn(r),e=0;e<t.length;e++){var i=t[e],o=r[i];!1===o.writable&&(o.writable=!0,o.configurable=!0),(o.get||o.set)&&(r[i]={configurable:!0,writable:!0,enumerable:o.enumerable,value:n[i]})}return Object.create(Object.getPrototypeOf(n),r)}function d(n,e){return void 0===e&&(e=!1),y(n)||r(n)||!t(n)||(o(n)>1&&(n.set=n.add=n.clear=n.delete=h),Object.freeze(n),e&&i(n,(function(n,r){return d(r,!0)}),!0)),n}function h(){n(2)}function y(n){return null==n||"object"!=typeof n||Object.isFrozen(n)}function b(r){var t=tn[r];return t||n(18,r),t}function m(n,r){tn[n]||(tn[n]=r)}function _(){return false||U||n(0),U}function j(n,r){r&&(b("Patches"),n.u=[],n.s=[],n.v=r)}function g(n){O(n),n.p.forEach(S),n.p=null}function O(n){n===U&&(U=n.l)}function w(n){return U={p:[],l:U,h:n,m:!0,_:0}}function S(n){var r=n[Q];0===r.i||1===r.i?r.j():r.g=!0}function P(r,e){e._=e.p.length;var i=e.p[0],o=void 0!==r&&r!==i;return e.h.O||b("ES5").S(e,r,o),o?(i[Q].P&&(g(e),n(4)),t(r)&&(r=M(e,r),e.l||x(e,r)),e.u&&b("Patches").M(i[Q].t,r,e.u,e.s)):r=M(e,i,[]),g(e),e.u&&e.v(e.u,e.s),r!==H?r:void 0}function M(n,r,t){if(y(r))return r;var e=r[Q];if(!e)return i(r,(function(i,o){return A(n,e,r,i,o,t)}),!0),r;if(e.A!==n)return r;if(!e.P)return x(n,e.t,!0),e.t;if(!e.I){e.I=!0,e.A._--;var o=4===e.i||5===e.i?e.o=l(e.k):e.o,u=o,a=!1;3===e.i&&(u=new Set(o),o.clear(),a=!0),i(u,(function(r,i){return A(n,e,o,r,i,t,a)})),x(n,o,!1),t&&n.u&&b("Patches").N(e,t,n.u,n.s)}return e.o}function A(e,i,o,a,c,s,v){if( true&&c===o&&n(5),r(c)){var p=M(e,c,s&&i&&3!==i.i&&!u(i.R,a)?s.concat(a):void 0);if(f(o,a,p),!r(p))return;e.m=!1}else v&&o.add(c);if(t(c)&&!y(c)){if(!e.h.D&&e._<1)return;M(e,c),i&&i.A.l||x(e,c)}}function x(n,r,t){void 0===t&&(t=!1),!n.l&&n.h.D&&n.m&&d(r,t)}function z(n,r){var t=n[Q];return(t?p(t):n)[r]}function I(n,r){if(r in n)for(var t=Object.getPrototypeOf(n);t;){var e=Object.getOwnPropertyDescriptor(t,r);if(e)return e;t=Object.getPrototypeOf(t)}}function k(n){n.P||(n.P=!0,n.l&&k(n.l))}function E(n){n.o||(n.o=l(n.t))}function N(n,r,t){var e=s(r)?b("MapSet").F(r,t):v(r)?b("MapSet").T(r,t):n.O?function(n,r){var t=Array.isArray(n),e={i:t?1:0,A:r?r.A:_(),P:!1,I:!1,R:{},l:r,t:n,k:null,o:null,j:null,C:!1},i=e,o=en;t&&(i=[e],o=on);var u=Proxy.revocable(i,o),a=u.revoke,f=u.proxy;return e.k=f,e.j=a,f}(r,t):b("ES5").J(r,t);return(t?t.A:_()).p.push(e),e}function R(e){return r(e)||n(22,e),function n(r){if(!t(r))return r;var e,u=r[Q],c=o(r);if(u){if(!u.P&&(u.i<4||!b("ES5").K(u)))return u.t;u.I=!0,e=D(r,c),u.I=!1}else e=D(r,c);return i(e,(function(r,t){u&&a(u.t,r)===t||f(e,r,n(t))})),3===c?new Set(e):e}(e)}function D(n,r){switch(r){case 2:return new Map(n);case 3:return Array.from(n)}return l(n)}function F(){function t(n,r){var t=s[n];return t?t.enumerable=r:s[n]=t={configurable:!0,enumerable:r,get:function(){var r=this[Q];return true&&f(r),en.get(r,n)},set:function(r){var t=this[Q]; true&&f(t),en.set(t,n,r)}},t}function e(n){for(var r=n.length-1;r>=0;r--){var t=n[r][Q];if(!t.P)switch(t.i){case 5:a(t)&&k(t);break;case 4:o(t)&&k(t)}}}function o(n){for(var r=n.t,t=n.k,e=nn(t),i=e.length-1;i>=0;i--){var o=e[i];if(o!==Q){var a=r[o];if(void 0===a&&!u(r,o))return!0;var f=t[o],s=f&&f[Q];if(s?s.t!==a:!c(f,a))return!0}}var v=!!r[Q];return e.length!==nn(r).length+(v?0:1)}function a(n){var r=n.k;if(r.length!==n.t.length)return!0;var t=Object.getOwnPropertyDescriptor(r,r.length-1);if(t&&!t.get)return!0;for(var e=0;e<r.length;e++)if(!r.hasOwnProperty(e))return!0;return!1}function f(r){r.g&&n(3,JSON.stringify(p(r)))}var s={};m("ES5",{J:function(n,r){var e=Array.isArray(n),i=function(n,r){if(n){for(var e=Array(r.length),i=0;i<r.length;i++)Object.defineProperty(e,""+i,t(i,!0));return e}var o=rn(r);delete o[Q];for(var u=nn(o),a=0;a<u.length;a++){var f=u[a];o[f]=t(f,n||!!o[f].enumerable)}return Object.create(Object.getPrototypeOf(r),o)}(e,n),o={i:e?5:4,A:r?r.A:_(),P:!1,I:!1,R:{},l:r,t:n,k:i,o:null,g:!1,C:!1};return Object.defineProperty(i,Q,{value:o,writable:!0}),i},S:function(n,t,o){o?r(t)&&t[Q].A===n&&e(n.p):(n.u&&function n(r){if(r&&"object"==typeof r){var t=r[Q];if(t){var e=t.t,o=t.k,f=t.R,c=t.i;if(4===c)i(o,(function(r){r!==Q&&(void 0!==e[r]||u(e,r)?f[r]||n(o[r]):(f[r]=!0,k(t)))})),i(e,(function(n){void 0!==o[n]||u(o,n)||(f[n]=!1,k(t))}));else if(5===c){if(a(t)&&(k(t),f.length=!0),o.length<e.length)for(var s=o.length;s<e.length;s++)f[s]=!1;else for(var v=e.length;v<o.length;v++)f[v]=!0;for(var p=Math.min(o.length,e.length),l=0;l<p;l++)o.hasOwnProperty(l)||(f[l]=!0),void 0===f[l]&&n(o[l])}}}}(n.p[0]),e(n.p))},K:function(n){return 4===n.i?o(n):a(n)}})}function T(){function e(n){if(!t(n))return n;if(Array.isArray(n))return n.map(e);if(s(n))return new Map(Array.from(n.entries()).map((function(n){return[n[0],e(n[1])]})));if(v(n))return new Set(Array.from(n).map(e));var r=Object.create(Object.getPrototypeOf(n));for(var i in n)r[i]=e(n[i]);return u(n,L)&&(r[L]=n[L]),r}function f(n){return r(n)?e(n):n}var c="add";m("Patches",{$:function(r,t){return t.forEach((function(t){for(var i=t.path,u=t.op,f=r,s=0;s<i.length-1;s++){var v=o(f),p=i[s];"string"!=typeof p&&"number"!=typeof p&&(p=""+p),0!==v&&1!==v||"__proto__"!==p&&"constructor"!==p||n(24),"function"==typeof f&&"prototype"===p&&n(24),"object"!=typeof(f=a(f,p))&&n(15,i.join("/"))}var l=o(f),d=e(t.value),h=i[i.length-1];switch(u){case"replace":switch(l){case 2:return f.set(h,d);case 3:n(16);default:return f[h]=d}case c:switch(l){case 1:return"-"===h?f.push(d):f.splice(h,0,d);case 2:return f.set(h,d);case 3:return f.add(d);default:return f[h]=d}case"remove":switch(l){case 1:return f.splice(h,1);case 2:return f.delete(h);case 3:return f.delete(t.value);default:return delete f[h]}default:n(17,u)}})),r},N:function(n,r,t,e){switch(n.i){case 0:case 4:case 2:return function(n,r,t,e){var o=n.t,s=n.o;i(n.R,(function(n,i){var v=a(o,n),p=a(s,n),l=i?u(o,n)?"replace":c:"remove";if(v!==p||"replace"!==l){var d=r.concat(n);t.push("remove"===l?{op:l,path:d}:{op:l,path:d,value:p}),e.push(l===c?{op:"remove",path:d}:"remove"===l?{op:c,path:d,value:f(v)}:{op:"replace",path:d,value:f(v)})}}))}(n,r,t,e);case 5:case 1:return function(n,r,t,e){var i=n.t,o=n.R,u=n.o;if(u.length<i.length){var a=[u,i];i=a[0],u=a[1];var s=[e,t];t=s[0],e=s[1]}for(var v=0;v<i.length;v++)if(o[v]&&u[v]!==i[v]){var p=r.concat([v]);t.push({op:"replace",path:p,value:f(u[v])}),e.push({op:"replace",path:p,value:f(i[v])})}for(var l=i.length;l<u.length;l++){var d=r.concat([l]);t.push({op:c,path:d,value:f(u[l])})}i.length<u.length&&e.push({op:"replace",path:r.concat(["length"]),value:i.length})}(n,r,t,e);case 3:return function(n,r,t,e){var i=n.t,o=n.o,u=0;i.forEach((function(n){if(!o.has(n)){var i=r.concat([u]);t.push({op:"remove",path:i,value:n}),e.unshift({op:c,path:i,value:n})}u++})),u=0,o.forEach((function(n){if(!i.has(n)){var o=r.concat([u]);t.push({op:c,path:o,value:n}),e.unshift({op:"remove",path:o,value:n})}u++}))}(n,r,t,e)}},M:function(n,r,t,e){t.push({op:"replace",path:[],value:r===H?void 0:r}),e.push({op:"replace",path:[],value:n})}})}function C(){function r(n,r){function t(){this.constructor=n}a(n,r),n.prototype=(t.prototype=r.prototype,new t)}function e(n){n.o||(n.R=new Map,n.o=new Map(n.t))}function o(n){n.o||(n.o=new Set,n.t.forEach((function(r){if(t(r)){var e=N(n.A.h,r,n);n.p.set(r,e),n.o.add(e)}else n.o.add(r)})))}function u(r){r.g&&n(3,JSON.stringify(p(r)))}var a=function(n,r){return(a=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(n,r){n.__proto__=r}||function(n,r){for(var t in r)r.hasOwnProperty(t)&&(n[t]=r[t])})(n,r)},f=function(){function n(n,r){return this[Q]={i:2,l:r,A:r?r.A:_(),P:!1,I:!1,o:void 0,R:void 0,t:n,k:this,C:!1,g:!1},this}r(n,Map);var o=n.prototype;return Object.defineProperty(o,"size",{get:function(){return p(this[Q]).size}}),o.has=function(n){return p(this[Q]).has(n)},o.set=function(n,r){var t=this[Q];return u(t),p(t).has(n)&&p(t).get(n)===r||(e(t),k(t),t.R.set(n,!0),t.o.set(n,r),t.R.set(n,!0)),this},o.delete=function(n){if(!this.has(n))return!1;var r=this[Q];return u(r),e(r),k(r),r.t.has(n)?r.R.set(n,!1):r.R.delete(n),r.o.delete(n),!0},o.clear=function(){var n=this[Q];u(n),p(n).size&&(e(n),k(n),n.R=new Map,i(n.t,(function(r){n.R.set(r,!1)})),n.o.clear())},o.forEach=function(n,r){var t=this;p(this[Q]).forEach((function(e,i){n.call(r,t.get(i),i,t)}))},o.get=function(n){var r=this[Q];u(r);var i=p(r).get(n);if(r.I||!t(i))return i;if(i!==r.t.get(n))return i;var o=N(r.A.h,i,r);return e(r),r.o.set(n,o),o},o.keys=function(){return p(this[Q]).keys()},o.values=function(){var n,r=this,t=this.keys();return(n={})[V]=function(){return r.values()},n.next=function(){var n=t.next();return n.done?n:{done:!1,value:r.get(n.value)}},n},o.entries=function(){var n,r=this,t=this.keys();return(n={})[V]=function(){return r.entries()},n.next=function(){var n=t.next();if(n.done)return n;var e=r.get(n.value);return{done:!1,value:[n.value,e]}},n},o[V]=function(){return this.entries()},n}(),c=function(){function n(n,r){return this[Q]={i:3,l:r,A:r?r.A:_(),P:!1,I:!1,o:void 0,t:n,k:this,p:new Map,g:!1,C:!1},this}r(n,Set);var t=n.prototype;return Object.defineProperty(t,"size",{get:function(){return p(this[Q]).size}}),t.has=function(n){var r=this[Q];return u(r),r.o?!!r.o.has(n)||!(!r.p.has(n)||!r.o.has(r.p.get(n))):r.t.has(n)},t.add=function(n){var r=this[Q];return u(r),this.has(n)||(o(r),k(r),r.o.add(n)),this},t.delete=function(n){if(!this.has(n))return!1;var r=this[Q];return u(r),o(r),k(r),r.o.delete(n)||!!r.p.has(n)&&r.o.delete(r.p.get(n))},t.clear=function(){var n=this[Q];u(n),p(n).size&&(o(n),k(n),n.o.clear())},t.values=function(){var n=this[Q];return u(n),o(n),n.o.values()},t.entries=function(){var n=this[Q];return u(n),o(n),n.o.entries()},t.keys=function(){return this.values()},t[V]=function(){return this.values()},t.forEach=function(n,r){for(var t=this.values(),e=t.next();!e.done;)n.call(r,e.value,e.value,this),e=t.next()},n}();m("MapSet",{F:function(n,r){return new f(n,r)},T:function(n,r){return new c(n,r)}})}function J(){F(),C(),T()}function K(n){return n}function $(n){return n}var G,U,W="undefined"!=typeof Symbol&&"symbol"==typeof Symbol("x"),X="undefined"!=typeof Map,q="undefined"!=typeof Set,B="undefined"!=typeof Proxy&&void 0!==Proxy.revocable&&"undefined"!=typeof Reflect,H=W?Symbol.for("immer-nothing"):((G={})["immer-nothing"]=!0,G),L=W?Symbol.for("immer-draftable"):"__$immer_draftable",Q=W?Symbol.for("immer-state"):"__$immer_state",V="undefined"!=typeof Symbol&&Symbol.iterator||"@@iterator",Y={0:"Illegal state",1:"Immer drafts cannot have computed properties",2:"This object has been frozen and should not be mutated",3:function(n){return"Cannot use a proxy that has been revoked. Did you pass an object from inside an immer function to an async process? "+n},4:"An immer producer returned a new value *and* modified its draft. Either return a new value *or* modify the draft.",5:"Immer forbids circular references",6:"The first or second argument to `produce` must be a function",7:"The third argument to `produce` must be a function or undefined",8:"First argument to `createDraft` must be a plain object, an array, or an immerable object",9:"First argument to `finishDraft` must be a draft returned by `createDraft`",10:"The given draft is already finalized",11:"Object.defineProperty() cannot be used on an Immer draft",12:"Object.setPrototypeOf() cannot be used on an Immer draft",13:"Immer only supports deleting array indices",14:"Immer only supports setting array indices and the 'length' property",15:function(n){return"Cannot apply patch, path doesn't resolve: "+n},16:'Sets cannot have "replace" patches.',17:function(n){return"Unsupported patch operation: "+n},18:function(n){return"The plugin for '"+n+"' has not been loaded into Immer. To enable the plugin, import and call `enable"+n+"()` when initializing your application."},20:"Cannot use proxies if Proxy, Proxy.revocable or Reflect are not available",21:function(n){return"produce can only be called on things that are draftable: plain objects, arrays, Map, Set or classes that are marked with '[immerable]: true'. Got '"+n+"'"},22:function(n){return"'current' expects a draft, got: "+n},23:function(n){return"'original' expects a draft, got: "+n},24:"Patching reserved attributes like __proto__, prototype and constructor is not allowed"},Z=""+Object.prototype.constructor,nn="undefined"!=typeof Reflect&&Reflect.ownKeys?Reflect.ownKeys:void 0!==Object.getOwnPropertySymbols?function(n){return Object.getOwnPropertyNames(n).concat(Object.getOwnPropertySymbols(n))}:Object.getOwnPropertyNames,rn=Object.getOwnPropertyDescriptors||function(n){var r={};return nn(n).forEach((function(t){r[t]=Object.getOwnPropertyDescriptor(n,t)})),r},tn={},en={get:function(n,r){if(r===Q)return n;var e=p(n);if(!u(e,r))return function(n,r,t){var e,i=I(r,t);return i?"value"in i?i.value:null===(e=i.get)||void 0===e?void 0:e.call(n.k):void 0}(n,e,r);var i=e[r];return n.I||!t(i)?i:i===z(n.t,r)?(E(n),n.o[r]=N(n.A.h,i,n)):i},has:function(n,r){return r in p(n)},ownKeys:function(n){return Reflect.ownKeys(p(n))},set:function(n,r,t){var e=I(p(n),r);if(null==e?void 0:e.set)return e.set.call(n.k,t),!0;if(!n.P){var i=z(p(n),r),o=null==i?void 0:i[Q];if(o&&o.t===t)return n.o[r]=t,n.R[r]=!1,!0;if(c(t,i)&&(void 0!==t||u(n.t,r)))return!0;E(n),k(n)}return n.o[r]===t&&(void 0!==t||r in n.o)||Number.isNaN(t)&&Number.isNaN(n.o[r])||(n.o[r]=t,n.R[r]=!0),!0},deleteProperty:function(n,r){return void 0!==z(n.t,r)||r in n.t?(n.R[r]=!1,E(n),k(n)):delete n.R[r],n.o&&delete n.o[r],!0},getOwnPropertyDescriptor:function(n,r){var t=p(n),e=Reflect.getOwnPropertyDescriptor(t,r);return e?{writable:!0,configurable:1!==n.i||"length"!==r,enumerable:e.enumerable,value:t[r]}:e},defineProperty:function(){n(11)},getPrototypeOf:function(n){return Object.getPrototypeOf(n.t)},setPrototypeOf:function(){n(12)}},on={};i(en,(function(n,r){on[n]=function(){return arguments[0]=arguments[0][0],r.apply(this,arguments)}})),on.deleteProperty=function(r,t){return true&&isNaN(parseInt(t))&&n(13),on.set.call(this,r,t,void 0)},on.set=function(r,t,e){return true&&"length"!==t&&isNaN(parseInt(t))&&n(14),en.set.call(this,r[0],t,e,r[0])};var un=function(){function e(r){var e=this;this.O=B,this.D=!0,this.produce=function(r,i,o){if("function"==typeof r&&"function"!=typeof i){var u=i;i=r;var a=e;return function(n){var r=this;void 0===n&&(n=u);for(var t=arguments.length,e=Array(t>1?t-1:0),o=1;o<t;o++)e[o-1]=arguments[o];return a.produce(n,(function(n){var t;return(t=i).call.apply(t,[r,n].concat(e))}))}}var f;if("function"!=typeof i&&n(6),void 0!==o&&"function"!=typeof o&&n(7),t(r)){var c=w(e),s=N(e,r,void 0),v=!0;try{f=i(s),v=!1}finally{v?g(c):O(c)}return"undefined"!=typeof Promise&&f instanceof Promise?f.then((function(n){return j(c,o),P(n,c)}),(function(n){throw g(c),n})):(j(c,o),P(f,c))}if(!r||"object"!=typeof r){if(void 0===(f=i(r))&&(f=r),f===H&&(f=void 0),e.D&&d(f,!0),o){var p=[],l=[];b("Patches").M(r,f,p,l),o(p,l)}return f}n(21,r)},this.produceWithPatches=function(n,r){if("function"==typeof n)return function(r){for(var t=arguments.length,i=Array(t>1?t-1:0),o=1;o<t;o++)i[o-1]=arguments[o];return e.produceWithPatches(r,(function(r){return n.apply(void 0,[r].concat(i))}))};var t,i,o=e.produce(n,r,(function(n,r){t=n,i=r}));return"undefined"!=typeof Promise&&o instanceof Promise?o.then((function(n){return[n,t,i]})):[o,t,i]},"boolean"==typeof(null==r?void 0:r.useProxies)&&this.setUseProxies(r.useProxies),"boolean"==typeof(null==r?void 0:r.autoFreeze)&&this.setAutoFreeze(r.autoFreeze)}var i=e.prototype;return i.createDraft=function(e){t(e)||n(8),r(e)&&(e=R(e));var i=w(this),o=N(this,e,void 0);return o[Q].C=!0,O(i),o},i.finishDraft=function(r,t){var e=r&&r[Q]; true&&(e&&e.C||n(9),e.I&&n(10));var i=e.A;return j(i,t),P(void 0,i)},i.setAutoFreeze=function(n){this.D=n},i.setUseProxies=function(r){r&&!B&&n(20),this.O=r},i.applyPatches=function(n,t){var e;for(e=t.length-1;e>=0;e--){var i=t[e];if(0===i.path.length&&"replace"===i.op){n=i.value;break}}e>-1&&(t=t.slice(e+1));var o=b("Patches").$;return r(n)?o(n,t):this.produce(n,(function(n){return o(n,t)}))},e}(),an=new un,fn=an.produce,cn=an.produceWithPatches.bind(an),sn=an.setAutoFreeze.bind(an),vn=an.setUseProxies.bind(an),pn=an.applyPatches.bind(an),ln=an.createDraft.bind(an),dn=an.finishDraft.bind(an);/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (fn);
//# sourceMappingURL=immer.esm.js.map


/***/ }),

/***/ "./node_modules/zustand/esm/index.mjs":
/*!********************************************!*\
  !*** ./node_modules/zustand/esm/index.mjs ***!
  \********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   create: () => (/* binding */ create),
/* harmony export */   createStore: () => (/* reexport safe */ zustand_vanilla__WEBPACK_IMPORTED_MODULE_0__.createStore),
/* harmony export */   "default": () => (/* binding */ react),
/* harmony export */   useStore: () => (/* binding */ useStore)
/* harmony export */ });
/* harmony import */ var zustand_vanilla__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! zustand/vanilla */ "./node_modules/zustand/esm/vanilla.mjs");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var use_sync_external_store_shim_with_selector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! use-sync-external-store/shim/with-selector.js */ "./node_modules/use-sync-external-store/shim/with-selector.js");





const { useSyncExternalStoreWithSelector } = use_sync_external_store_shim_with_selector_js__WEBPACK_IMPORTED_MODULE_2__;
function useStore(api, selector = api.getState, equalityFn) {
  if (( false ? 0 : void 0) !== "production" && equalityFn) {
    console.warn(
      "[DEPRECATED] Use `createWithEqualityFn` from 'zustand/traditional'. https://github.com/pmndrs/zustand/discussions/1937"
    );
  }
  const slice = useSyncExternalStoreWithSelector(
    api.subscribe,
    api.getState,
    api.getServerState || api.getState,
    selector,
    equalityFn
  );
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useDebugValue)(slice);
  return slice;
}
const createImpl = (createState) => {
  if (( false ? 0 : void 0) !== "production" && typeof createState !== "function") {
    console.warn(
      "[DEPRECATED] Passing a vanilla store will be unsupported in a future version. Instead use `import { useStore } from 'zustand'`."
    );
  }
  const api = typeof createState === "function" ? (0,zustand_vanilla__WEBPACK_IMPORTED_MODULE_0__.createStore)(createState) : createState;
  const useBoundStore = (selector, equalityFn) => useStore(api, selector, equalityFn);
  Object.assign(useBoundStore, api);
  return useBoundStore;
};
const create = (createState) => createState ? createImpl(createState) : createImpl;
var react = (createState) => {
  if (( false ? 0 : void 0) !== "production") {
    console.warn(
      "[DEPRECATED] Default export is deprecated. Instead use `import { create } from 'zustand'`."
    );
  }
  return create(createState);
};




/***/ }),

/***/ "./node_modules/zustand/esm/vanilla.mjs":
/*!**********************************************!*\
  !*** ./node_modules/zustand/esm/vanilla.mjs ***!
  \**********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createStore: () => (/* binding */ createStore),
/* harmony export */   "default": () => (/* binding */ vanilla)
/* harmony export */ });
const createStoreImpl = (createState) => {
  let state;
  const listeners = /* @__PURE__ */ new Set();
  const setState = (partial, replace) => {
    const nextState = typeof partial === "function" ? partial(state) : partial;
    if (!Object.is(nextState, state)) {
      const previousState = state;
      state = (replace != null ? replace : typeof nextState !== "object") ? nextState : Object.assign({}, state, nextState);
      listeners.forEach((listener) => listener(state, previousState));
    }
  };
  const getState = () => state;
  const subscribe = (listener) => {
    listeners.add(listener);
    return () => listeners.delete(listener);
  };
  const destroy = () => {
    if (( false ? 0 : void 0) !== "production") {
      console.warn(
        "[DEPRECATED] The `destroy` method will be unsupported in a future version. Instead use unsubscribe function returned by subscribe. Everything will be garbage-collected if store is garbage-collected."
      );
    }
    listeners.clear();
  };
  const api = { setState, getState, subscribe, destroy };
  state = createState(setState, getState, api);
  return api;
};
const createStore = (createState) => createState ? createStoreImpl(createState) : createStoreImpl;
var vanilla = (createState) => {
  if (( false ? 0 : void 0) !== "production") {
    console.warn(
      "[DEPRECATED] Default export is deprecated. Instead use import { createStore } from 'zustand/vanilla'."
    );
  }
  return createStore(createState);
};




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
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames based on template
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"902ea4078780eff558ca","src_Menu_Menu_js":"c275f909410871ede758","src_Dashboard_DashboardPage_js":"cc315f71743912c1ce7b","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-data-tabl-8e8716":"64aaccd4757f22c45708","src_Settings_Field_js":"2e8d0bb433e02bbd984f","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
========
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"b2e4e831a6e4dfefbb5c","src_Menu_Menu_js":"c275f909410871ede758","src_Dashboard_DashboardPage_js":"4f7f9660ad30371d9445","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-toastify_-1fccac":"140655a5d7db93a2497a","src_Settings_Field_js":"c881cc9d8901c328bd91","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
>>>>>>>> 070f831e1 (fixed reset function, however could not resolve warning React issue??):settings/build/index.aa620953ab532f245d16.js
========
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"b2e4e831a6e4dfefbb5c","src_Menu_Menu_js":"66458ee78e9eb4343d4a","src_Dashboard_DashboardPage_js":"4f7f9660ad30371d9445","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-toastify_-1fccac":"140655a5d7db93a2497a","src_Settings_Field_js":"369ae94bbfc5ec881341","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
>>>>>>>> 79d2a7197 (fixed the sorting bug):settings/build/index.a000b00bee3981302fab.js
========
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"b2e4e831a6e4dfefbb5c","src_Menu_Menu_js":"66458ee78e9eb4343d4a","src_Dashboard_DashboardPage_js":"4f7f9660ad30371d9445","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-toastify_-1fccac":"140655a5d7db93a2497a","src_Settings_Field_js":"da5e9d4a892c8646ddd5","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
>>>>>>>> 29a4a9d08 (fixed sorting and pagination in CountryTable):settings/build/index.181bf0df2331d42bd74f.js
========
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"b2e4e831a6e4dfefbb5c","src_Menu_Menu_js":"66458ee78e9eb4343d4a","src_Dashboard_DashboardPage_js":"4f7f9660ad30371d9445","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-toastify_-1fccac":"140655a5d7db93a2497a","src_Settings_Field_js":"2badb76fe17e0f5ae377","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
>>>>>>>> d27cc5e94 (applied fix to pagination eventlog as well):settings/build/index.48d4bc28110e34e3de44.js
========
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"b2e4e831a6e4dfefbb5c","src_Menu_Menu_js":"66458ee78e9eb4343d4a","src_Dashboard_DashboardPage_js":"4f7f9660ad30371d9445","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-toastify_-1fccac":"140655a5d7db93a2497a","src_Settings_Field_js":"7ba1f0e62fb35091748b","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
>>>>>>>> f9bd341d4 (added some more translatable strings and capitalized some strings):settings/build/index.3ddab66f899f8c32785d.js
========
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"b2e4e831a6e4dfefbb5c","src_Menu_Menu_js":"66458ee78e9eb4343d4a","src_Dashboard_DashboardPage_js":"4f7f9660ad30371d9445","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-toastify_-1fccac":"140655a5d7db93a2497a","src_Settings_Field_js":"aa2831b77222f8346fb7","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
>>>>>>>> a8fb88908 (fixed ip input):settings/build/index.d0a2692b8fff6d7b5939.js
========
/******/ 			return "" + chunkId + "." + {"vendors-node_modules_mui_material_Tooltip_Tooltip_js":"0cc86ac6c861846722b1","src_Settings_Settings_js":"b2e4e831a6e4dfefbb5c","src_Menu_Menu_js":"66458ee78e9eb4343d4a","src_Dashboard_DashboardPage_js":"4f7f9660ad30371d9445","src_Onboarding_OnboardingModal_js":"85fbed60f8e666a7e677","src_Modal_Modal_js":"e34b60e44e1022d3fe24","vendors-node_modules_material-ui_core_esm_TextField_TextField_js-node_modules_react-toastify_-1fccac":"140655a5d7db93a2497a","src_Settings_Field_js":"092b69202ddf0f282672","vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js":"515dd4c5b9e6e345a1ea","vendors-node_modules_material-ui_core_esm_styles_index_js":"b2604edf5f43bcfce41a"}[chunkId] + ".js";
>>>>>>>> 3004c8a13 (fixed a load of issues with ip address datatable):settings/build/index.87c6f127d472a8eaafa2.js
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get mini-css chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.miniCssF = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return undefined;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/load script */
/******/ 	(() => {
/******/ 		var inProgress = {};
/******/ 		var dataWebpackPrefix = "really-simple-ssl:";
/******/ 		// loadScript function to load a script via script tag
/******/ 		__webpack_require__.l = (url, done, key, chunkId) => {
/******/ 			if(inProgress[url]) { inProgress[url].push(done); return; }
/******/ 			var script, needAttach;
/******/ 			if(key !== undefined) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				for(var i = 0; i < scripts.length; i++) {
/******/ 					var s = scripts[i];
/******/ 					if(s.getAttribute("src") == url || s.getAttribute("data-webpack") == dataWebpackPrefix + key) { script = s; break; }
/******/ 				}
/******/ 			}
/******/ 			if(!script) {
/******/ 				needAttach = true;
/******/ 				script = document.createElement('script');
/******/ 		
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.setAttribute("data-webpack", dataWebpackPrefix + key);
/******/ 		
/******/ 				script.src = url;
/******/ 			}
/******/ 			inProgress[url] = [done];
/******/ 			var onScriptComplete = (prev, event) => {
/******/ 				// avoid mem leaks in IE.
/******/ 				script.onerror = script.onload = null;
/******/ 				clearTimeout(timeout);
/******/ 				var doneFns = inProgress[url];
/******/ 				delete inProgress[url];
/******/ 				script.parentNode && script.parentNode.removeChild(script);
/******/ 				doneFns && doneFns.forEach((fn) => (fn(event)));
/******/ 				if(prev) return prev(event);
/******/ 			}
/******/ 			var timeout = setTimeout(onScriptComplete.bind(null, undefined, { type: 'timeout', target: script }), 120000);
/******/ 			script.onerror = onScriptComplete.bind(null, script.onerror);
/******/ 			script.onload = onScriptComplete.bind(null, script.onload);
/******/ 			needAttach && document.head.appendChild(script);
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript)
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && !scriptUrl) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl;
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"index": 0
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.f.j = (chunkId, promises) => {
/******/ 				// JSONP chunk loading for javascript
/******/ 				var installedChunkData = __webpack_require__.o(installedChunks, chunkId) ? installedChunks[chunkId] : undefined;
/******/ 				if(installedChunkData !== 0) { // 0 means "already installed".
/******/ 		
/******/ 					// a Promise means "currently loading".
/******/ 					if(installedChunkData) {
/******/ 						promises.push(installedChunkData[2]);
/******/ 					} else {
/******/ 						if(true) { // all chunks have JS
/******/ 							// setup Promise in chunk cache
/******/ 							var promise = new Promise((resolve, reject) => (installedChunkData = installedChunks[chunkId] = [resolve, reject]));
/******/ 							promises.push(installedChunkData[2] = promise);
/******/ 		
/******/ 							// start chunk loading
/******/ 							var url = __webpack_require__.p + __webpack_require__.u(chunkId);
/******/ 							// create error before stack unwound to get useful stacktrace later
/******/ 							var error = new Error();
/******/ 							var loadingEnded = (event) => {
/******/ 								if(__webpack_require__.o(installedChunks, chunkId)) {
/******/ 									installedChunkData = installedChunks[chunkId];
/******/ 									if(installedChunkData !== 0) installedChunks[chunkId] = undefined;
/******/ 									if(installedChunkData) {
/******/ 										var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 										var realSrc = event && event.target && event.target.src;
/******/ 										error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 										error.name = 'ChunkLoadError';
/******/ 										error.type = errorType;
/******/ 										error.request = realSrc;
/******/ 										installedChunkData[1](error);
/******/ 									}
/******/ 								}
/******/ 							};
/******/ 							__webpack_require__.l(url, loadingEnded, "chunk-" + chunkId, chunkId);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 		};
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		// no on chunks loaded
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 		
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkreally_simple_ssl"] = self["webpackChunkreally_simple_ssl"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
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
    if (_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot) {
      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot)(container).render((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Page__WEBPACK_IMPORTED_MODULE_1__["default"], null));
    } else {
      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Page__WEBPACK_IMPORTED_MODULE_1__["default"], null), container);
    }
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
})();

/******/ })()
;
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
<<<<<<<< HEAD:settings/build/index.627d11fff53e9d4ef6a7.js
//# sourceMappingURL=index.627d11fff53e9d4ef6a7.js.map
========
//# sourceMappingURL=index.aa620953ab532f245d16.js.map
>>>>>>>> 070f831e1 (fixed reset function, however could not resolve warning React issue??):settings/build/index.aa620953ab532f245d16.js
========
//# sourceMappingURL=index.a000b00bee3981302fab.js.map
>>>>>>>> 79d2a7197 (fixed the sorting bug):settings/build/index.a000b00bee3981302fab.js
========
//# sourceMappingURL=index.181bf0df2331d42bd74f.js.map
>>>>>>>> 29a4a9d08 (fixed sorting and pagination in CountryTable):settings/build/index.181bf0df2331d42bd74f.js
========
//# sourceMappingURL=index.48d4bc28110e34e3de44.js.map
>>>>>>>> d27cc5e94 (applied fix to pagination eventlog as well):settings/build/index.48d4bc28110e34e3de44.js
========
//# sourceMappingURL=index.3ddab66f899f8c32785d.js.map
>>>>>>>> f9bd341d4 (added some more translatable strings and capitalized some strings):settings/build/index.3ddab66f899f8c32785d.js
========
//# sourceMappingURL=index.d0a2692b8fff6d7b5939.js.map
>>>>>>>> a8fb88908 (fixed ip input):settings/build/index.d0a2692b8fff6d7b5939.js
========
//# sourceMappingURL=index.87c6f127d472a8eaafa2.js.map
>>>>>>>> 3004c8a13 (fixed a load of issues with ip address datatable):settings/build/index.87c6f127d472a8eaafa2.js
