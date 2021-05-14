'use strict';

const Task = require('./task');
const Timer = require('./timer');
const Events = require('events');
const { createOptions, flatten, noop } = require('./utils');

/**
 * Factory for creating a custom `Tasks` class that extends the
 * given `Emitter`. Or, simply call the factory function to use
 * the built-in emitter.
 *
 * ```js
 * // custom emitter
 * const Emitter = require('events');
 * const Tasks = require('composer/lib/tasks')(Emitter);
 * // built-in emitter
 * const Tasks = require('composer/lib/tasks')();
 * const composer = new Tasks();
 * ```
 * @name .factory
 * @param {function} `Emitter` Event emitter.
 * @return {Class} Returns a custom `Tasks` class.
 * @api public
 */

const factory = (Emitter = Events) => {

  /**
   * Create an instance of `Tasks` with the given `options`.
   *
   * ```js
   * const Tasks = require('composer').Tasks;
   * const composer = new Tasks();
   * ```
   * @class
   * @name Tasks
   * @param {object} `options`
   * @api public
   */

  class Tasks extends Emitter {
    constructor(options = {}) {
      super(!Emitter.name.includes('Emitter') ? options : null);
      this.options = options;
      this.taskStack = new Map();
      this.tasks = new Map();
      this.taskId = 0;

      if (this.off === void 0 && typeof this.removeListener === 'function') {
        this.off = this.removeListener.bind(this);
      }
    }

    /**
     * Define a task. Tasks run asynchronously, either in series (by default) or parallel
     * (when `options.parallel` is true). In order for the build to determine when a task is
     * complete, _one of the following_ things must happen: 1) the callback must be called, 2) a
     * promise must be returned, or 3) a stream must be returned. Inside tasks, the "this"
     * object is a composer Task instance created for each task with useful properties like
     * the task name, options and timing information, which can be useful for logging, etc.
     *
     * ```js
     * // 1. callback
     * app.task('default', cb => {
     *   // do stuff
     *   cb();
     * });
     * // 2. promise
     * app.task('default', () => {
     *   return Promise.resolve(null);
     * });
     * // 3. stream (using vinyl-fs or your stream of choice)
     * app.task('default', function() {
     *   return vfs.src('foo/*.js');
     * });
     * ```
     * @name .task
     * @param {String} `name` The task name.
     * @param {Object|Array|String|Function} `deps` Any of the following: task dependencies, callback(s), or options object, defined in any order.
     * @param {Function} `callback` (optional) If the last argument is a function, it will be called after all of the task's dependencies have been run.
     * @return {undefined}
     * @api public
     */

    task(name, ...rest) {
      if (typeof name !== 'string') {
        throw new TypeError('expected task "name" to be a string');
      }
      const { options, tasks } = createOptions(this, false, ...rest);
      const callback = typeof tasks[tasks.length - 1] === 'function' ? tasks.pop() : noop;
      return this.setTask(name, options, tasks, callback);
    }

    /**
     * Set a task on `app.tasks`
     * @name .setTask
     * @param {string} name Task name
     * @param {object} name Task options
     * @param {object|array|string|function} `deps` Task dependencies
     * @param {Function} `callback` (optional) Final callback function to call after all task dependencies have been run.
     * @return {object} Returns the instance.
     */

    setTask(name, options = {}, deps = [], callback) {
      const task = new Task({ name, options, deps, callback, app: this });
      const emit = (key = 'task') => this.emit(key, task);
      task.on('error', this.emit.bind(this, 'error'));
      task.on('preparing', () => emit('task-preparing'));
      task.on('starting', task => {
        this.taskStack.set(task.name, task);
        emit();
      });
      task.on('finished', task => {
        this.taskStack.delete(task.name);
        emit();
      });
      this.tasks.set(name, task);
      task.status = 'registered';
      emit('task-registered');
      return this;
    }

    /**
     * Get a task from `app.tasks`.
     * @name .getTask
     * @param {string} name
     * @return {object} Returns the task object.
     */

    getTask(name) {
      if (!this.tasks.has(name)) {
        throw this.formatError(name, 'task');
      }
      return this.tasks.get(name);
    }

    /**
     * Returns true if all values in the array are registered tasks.
     * @name .isTasks
     * @param {array} tasks
     * @return {boolean}
     */

    isTasks(arr) {
      return Array.isArray(arr) && arr.every(name => this.tasks.has(name));
    }

    /**
     * Create an array of tasks to run by resolving registered tasks from the values
     * in the given array.
     * @name .expandTasks
     * @param {...[string|function|glob]} tasks
     * @return {array}
     */

    expandTasks(...args) {
      let vals = flatten(args).filter(Boolean);
      let keys = [...this.tasks.keys()];
      let tasks = [];

      for (let task of vals) {
        if (typeof task === 'function') {
          let name = `task-${this.taskId++}`;
          this.task(name, task);
          tasks.push(name);
          continue;
        }

        if (typeof task === 'string') {
          if (/\*/.test(task)) {
            let matches = match(keys, task);
            if (matches.length === 0) {
              throw new Error(`glob "${task}" does not match any registered tasks`);
            }
            tasks.push.apply(tasks, matches);
            continue;
          }

          tasks.push(task);
          continue;
        }

        let msg = 'expected task dependency to be a string or function, but got: ';
        throw new TypeError(msg + typeof task);
      }
      return tasks;
    }

    /**
     * Run one or more tasks.
     *
     * ```js
     * const build = app.series(['foo', 'bar', 'baz']);
     * // promise
     * build().then(console.log).catch(console.error);
     * // or callback
     * build(function() {
     *   if (err) return console.error(err);
     * });
     * ```
     * @name .build
     * @param {object|array|string|function} `tasks` One or more tasks to run, options, or callback function. If no tasks are defined, the default task is automatically run.
     * @param {function} `callback` (optional)
     * @return {undefined}
     * @api public
     */

    async build(...args) {
      let state = { status: 'starting', time: new Timer(), app: this };
      state.time.start();
      this.emit('build', state);

      args = flatten(args);
      let cb = typeof args[args.length - 1] === 'function' ? args.pop() : null;

      let { options, tasks } = createOptions(this, true, ...args);
      if (!tasks.length) tasks = ['default'];

      let each = options.parallel ? this.parallel : this.series;
      let build = each.call(this, options, ...tasks);
      let promise = build()
        .then(() => {
          state.time.end();
          state.status = 'finished';
          this.emit('build', state);
        });

      return resolveBuild(promise, cb);
    }

    /**
     * Compose a function to run the given tasks in series.
     *
     * ```js
     * const build = app.series(['foo', 'bar', 'baz']);
     * // promise
     * build().then(console.log).catch(console.error);
     * // or callback
     * build(function() {
     *   if (err) return console.error(err);
     * });
     * ```
     * @name .series
     * @param {object|array|string|function} `tasks` Tasks to run, options, or callback function. If no tasks are defined, the `default` task is automatically run, if one exists.
     * @param {function} `callback` (optional)
     * @return {promise|undefined} Returns a promise if no callback is passed.
     * @api public
     */

    series(...args) {
      let stack = new Set();
      let compose = this.iterator('series', async(tasks, options, resolve) => {
        for (let ele of tasks) {
          let task = this.getTask(ele);
          task.series = true;

          if (task.skip(options) || stack.has(task)) {
            continue;
          }

          task.once('finished', () => stack.delete(task));
          task.once('starting', () => stack.add(task));
          let run = task.run(options);

          if (task.deps.length) {
            let opts = Object.assign({}, options, task.options);
            let each = opts.parallel ? this.parallel : this.series;
            let build = each.call(this, ...task.deps);
            await build();
          }

          await run();
        }

        resolve();
      });

      return compose(...args);
    }

    /**
     * Compose a function to run the given tasks in parallel.
     *
     * ```js
     * // call the returned function to start the build
     * const build = app.parallel(['foo', 'bar', 'baz']);
     * // promise
     * build().then(console.log).catch(console.error);
     * // callback
     * build(function() {
     *   if (err) return console.error(err);
     * });
     * // example task usage
     * app.task('default', build);
     * ```
     * @name .parallel
     * @param {object|array|string|function} `tasks` Tasks to run, options, or callback function. If no tasks are defined, the `default` task is automatically run, if one exists.
     * @param {function} `callback` (optional)
     * @return {promise|undefined} Returns a promise if no callback is passed.
     * @api public
     */

    parallel(...args) {
      let stack = new Set();
      let compose = this.iterator('parallel', (tasks, options, resolve) => {
        let pending = [];

        for (let ele of tasks) {
          let task = this.getTask(ele);
          task.parallel = true;

          if (task.skip(options) || stack.has(task)) {
            continue;
          }

          task.once('finished', () => stack.delete(task));
          task.once('starting', () => stack.add(task));
          let run = task.run(options);

          if (task.deps.length) {
            let opts = Object.assign({}, options, task.options);
            let each = opts.parallel ? this.parallel : this.series;
            let build = each.call(this, ...task.deps);
            pending.push(build().then(() => run()));
          } else {
            pending.push(run());
          }
        }

        resolve(Promise.all(pending));
      });

      return compose(...args);
    }

    /**
     * Create an async iterator function that ensures that either a promise is
     * returned or the user-provided callback is called.
     * @param {function} `fn` Function to invoke inside the promise.
     * @return {function}
     */

    iterator(type, fn) {
      return (...args) => {
        let { options, tasks } = createOptions(this, true, ...args);

        return cb => {
          let promise = new Promise(async(resolve, reject) => {
            if (tasks.length === 0) {
              resolve();
              return;
            }

            try {
              let p = fn(tasks, options, resolve);
              if (type === 'series') await p;
            } catch (err) {
              reject(err);
            }
          });

          return resolveBuild(promise, cb);
        };
      };
    }

    /**
     * Format task and generator errors.
     * @name .formatError
     * @param {String} `name`
     * @return {Error}
     */

    formatError(name) {
      return new Error(`task "${name}" is not registered`);
    }

    /**
     * Static method for creating a custom Tasks class with the given `Emitter.
     * @name .create
     * @param {Function} `Emitter`
     * @return {Class} Returns the custom class.
     * @api public
     * @static
     */

    static create(Emitter) {
      return factory(Emitter);
    }
  }
  return Tasks;
};

function resolveBuild(promise, cb) {
  if (typeof cb === 'function') {
    promise.then(val => cb(null, val)).catch(cb);
  } else {
    return promise;
  }
}

function match(keys, pattern) {
  let chars = [...pattern].map(ch => ({ '*': '.*?', '.': '\\.' }[ch] || ch));
  let regex = new RegExp(chars.join(''));
  return keys.filter(key => regex.test(key));
}

module.exports = factory();
