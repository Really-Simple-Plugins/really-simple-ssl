'use strict';

/**
 * Get hr time in nanoseconds
 */

const nano = time => +time[0] * 1e9 + +time[1];

/**
 * Flatten an array
 */

const flatten = arr => [].concat.apply([], arr);

/**
 * Return true if `val` is an object
 */

const isObject = val => val !== null && typeof val === 'object' && !Array.isArray(val);

/**
 * Create an options object from the given arguments.
 * @param {object} `app`
 * @param {...[function|string|object]} `rest`
 * @return {object}
 */

const createOptions = (app, expand, ...rest) => {
  const args = flatten(rest);
  const config = args.find(val => isObject(val) && !val.isTask) || {};
  const options = Object.assign({}, app.options, config);
  const tasks = expand === true
    ? app.expandTasks(args.filter(val => val && val !== config))
    : args.filter(val => val && val !== config);
  return { tasks, options };
};

/**
 * Noop for tasks
 */

const noop = cb => cb();

/**
 * Create a non-enumerable property on `obj`
 */

const define = (obj, key, value) => {
  Reflect.defineProperty(obj, key, {
    configurable: true,
    enumerable: false,
    writable: true,
    value
  });
};

/**
 * Expose "utils"
 */

module.exports = {
  createOptions,
  define,
  flatten,
  isObject,
  nano,
  noop
};
