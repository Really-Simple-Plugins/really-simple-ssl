'use strict';

const util = require('util');
const Task = require('./task');
const Tasks = require('./tasks');
const parse = require('./parse');
const Events = require('events');
const { define } = require('./utils');

/**
 * Static factory method for creating a custom `Composer` class that
 * extends the given `Emitter`.
 *
 * ```js
 * // Composer extends a basic event emitter by default
 * const Composer = require('composer');
 * const composer = new Composer();
 *
 * // Create a custom Composer class with your even emitter of choice
 * const Emitter = require('some-emitter');
 * const CustomComposer = Composer.create(Emitter);
 * const composer = new CustomComposer();
 * ```
 * @name .create
 * @param {Function} `Emitter` Event emitter.
 * @return {Class} Returns a custom `Composer` class.
 * @api public
 */

const factory = (Emitter = Events) => {

  /**
   * Create a new instance of Composer.
   *
   * ```js
   * const composer = new Composer();
   * ```
   * @extends EventEmitter
   * @param {String} `name`
   * @param {Object} `options`
   * @return {Object} Returns an instance of Composer.
   * @api public
   */

  class Generator extends Tasks.create(Emitter) {
    constructor(name, options = {}) {
      if (name && typeof name !== 'string') {
        options = name;
        name = void 0;
      }
      // ensure that options aren't passed to generic
      // emitter to prevent unintended side-effects
      super(!/(Event)?Emitter/i.test(Emitter.name) ? options : null);
      if (this.setMaxListeners) this.setMaxListeners(0);
      this.name = name;
      this.options = options;
      this.namespaces = new Map();
      this.generators = new Map();
      this.isGenerate = true;
      if (!this.use) {
        require('use')(this);
      }
    }

    /**
     * Create a wrapped generator function with the given `name`, `config`, and `fn`.
     *
     * @param {String} `name`
     * @param {Object} `config` (optional)
     * @param {Function} `fn`
     * @return {Function}
     * @api public
     */

    toGenerator(name, config, fn) {
      if (typeof config === 'function' || this.isGenerator(config)) {
        fn = config;
        config = fn || {};
      }

      let alias = this.toAlias(name);
      let generator = options => {
        if (generator.instance && generator.once !== false) {
          return generator.instance;
        }

        let opts = Object.assign({}, config, options);
        let app = this.isGenerator(fn) ? fn : new this.constructor(opts);
        this.run(app);

        generator.instance = app;
        generator.called++;
        fn.called = generator.called;

        app.createGenerator = generator;
        app.alias = alias;
        app.name = name;
        app.fn = fn;

        define(app, 'parent', this);
        this.emit('generator', app);

        // emit errors that happen on initialization
        let listeners = {};
        let bubble = events => {
          for (let name of events) {
            let listener = listeners[name] || (listeners[name] = this.emit.bind(this, name));
            app.off(name, listener);
            app.on(name, listener);
          }
        };

        bubble(['error', 'task', 'build', 'plugin']);

        if (typeof fn === 'function') {
          fn.call(app, app, opts);
          // re-register emitters that we just registered a few lines ago,
          // to ensure that errors are bubbled up in the correct order
          bubble(['error', 'task', 'build', 'plugin']);
        }

        if (opts && opts.once === false) {
          generator.once = false;
        }
        return app;
      };

      define(generator, 'name', alias);
      define(generator, 'parent', this);
      define(generator, 'instance', null);
      generator.called = 0;
      generator.isGenerator = true;
      generator.alias = alias;
      generator.fn = fn;
      return generator;
    }

    /**
     * Returns true if the given value is a Composer generator object.
     *
     * @param {Object} `val`
     * @return {Boolean}
     * @api public
     */

    isGenerator(val) {
      return this.constructor.isGenerator(val);
    }

    /**
     * Alias to `.setGenerator`.
     *
     * ```js
     * app.register('foo', function(app, base) {
     *   // "app" is a private instance created for the generator
     *   // "base" is a shared instance
     * });
     * ```
     * @name .register
     * @param {String} `name` The generator's name
     * @param {Object|Function|String} `options` or generator
     * @param {Object|Function|String} `generator` Generator function, instance or filepath.
     * @return {Object} Returns the generator instance.
     * @api public
     */

    register(...args) {
      return this.setGenerator(...args);
    }

    /**
     * Get and invoke generator `name`, or register generator `name` with
     * the given `val` and `options`, then invoke and return the generator
     * instance. This method differs from `.register`, which lazily invokes
     * generator functions when `.generate` is called.
     *
     * ```js
     * app.generator('foo', function(app, options) {
     *   // "app" - private instance created for generator "foo"
     *   // "options" - options passed to the generator
     * });
     * ```
     * @name .generator
     * @param {String} `name`
     * @param {Function|Object} `fn` Generator function, instance or filepath.
     * @return {Object} Returns the generator instance or undefined if not resolved.
     * @api public
     */

    generator(name, options, fn) {
      if (typeof options === 'function') {
        fn = options;
        options = {};
      }

      if (typeof fn !== 'function') {
        return this.getGenerator(name, options);
      }

      this.setGenerator(name, options, fn);
      return this.getGenerator(name);
    }

    /**
     * Store a generator by file path or instance with the given
     * `name` and `options`.
     *
     * ```js
     * app.setGenerator('foo', function(app, options) {
     *   // "app" - new instance of Generator created for generator "foo"
     *   // "options" - options passed to the generator
     * });
     * ```
     * @name .setGenerator
     * @param {String} `name` The generator's name
     * @param {Object|Function|String} `options` or generator
     * @param {Object|Function|String} `generator` Generator function, instance or filepath.
     * @return {Object} Returns the generator instance.
     * @api public
     */

    setGenerator(name, options, fn) {
      const generator = this.toGenerator(name, options, fn);
      const alias = generator.alias;
      this.base.namespaces.set(`${this.namespace}.${alias}`, generator);
      this.generators.set(alias, generator);
      return this;
    }

    /**
     * Get generator `name` from `app.generators`, same as [findGenerator], but also invokes
     * the returned generator with the current instance. Dot-notation may be used for getting
     * sub-generators.
     *
     * ```js
     * const foo = app.getGenerator('foo');
     *
     * // get a sub-generator
     * const baz = app.getGenerator('foo.bar.baz');
     * ```
     * @name .getGenerator
     * @param {String} `name` Generator name.
     * @return {Object|undefined} Returns the generator instance or undefined.
     * @api public
     */

    getGenerator(name, options) {
      const fn = this.findGenerator(name);

      if (!this.isGenerator(fn)) {
        throw this.formatError(name);
      }

      if (typeof fn === 'function') {
        // return the generator instance if one has already created,
        // otherwise call the generator function with the parent instance
        return fn.instance || fn.call(fn.parent, options);
      }
      return fn;
    }

    /**
     * Find generator `name`, by first searching the cache, then searching the
     * cache of the `base` generator. Use this to get a generator without invoking it.
     *
     * ```js
     * // search by "alias"
     * const foo = app.findGenerator('foo');
     *
     * // search by "full name"
     * const foo = app.findGenerator('generate-foo');
     * ```
     * @name .findGenerator
     * @param {String} `name`
     * @param {Function} `options` Optionally supply a rename function on `options.toAlias`
     * @return {Object|undefined} Returns the generator instance if found, or undefined.
     * @api public
     */

    findGenerator(name) {
      if (!name) return null;
      let cached = this.base.namespaces.get(name);
      let names = [];
      let app = this;

      if (this.isGenerator(cached)) {
        return cached;
      }

      names = typeof name === 'string'
        ? name.split('.').map(n => this.toAlias(n))
        : name;

      let key = names.join('.');

      if (names.length === 1) {
        app = this.generators.get(key);

      } else {
        do {
          let alias = names.shift();
          let gen = app.generators.get(alias);

          if (!this.isGenerator(gen)) {
            return null;
          }

          // only invoke the generator if it's not the last one
          if (names.length) {
            app = gen.instance || app.getGenerator(alias);
          } else {
            app = gen;
          }

        } while (app && names.length);
      }

      return this.isGenerator(app) ? app : null;
    }

    /**
     * Returns true if the given name is a registered generator. Dot-notation may be
     * used to check for sub-generators.
     *
     * ```js
     * console.log(app.hasGenerator('foo'));
     * console.log(app.hasGenerator('foo.bar'));
     * ```
     * @param {String} `name`
     * @return {Boolean}
     * @api public
     */

    hasGenerator(name) {
      return this.findGenerator(name) != null;
    }

    /**
     * Run one or more tasks or sub-generators and returns a promise.
     *
     * ```js
     * // run tasks `bar` and `baz` on generator `foo`
     * app.generate('foo', ['bar', 'baz']);
     *
     * // or use shorthand
     * app.generate('foo:bar,baz');
     *
     * // run the `default` task on generator `foo`
     * app.generate('foo');
     *
     * // run the `default` task on the `default` generator, if defined
     * app.generate();
     * ```
     * @name .generate
     * @emits `generate` with the generator `name` and the array of `tasks` that are queued to run.
     * @param {String} `name`
     * @param {String|Array} `tasks`
     * @return {Promise}
     * @api public
     */

    async generate(...args) {
      let parsed = this.parseTasks(...args);
      let { tasks, missing, options, callback } = parsed;

      let promise = new Promise(async(resolve, reject) => {
        if (missing.length > 0) {
          reject(new Error('Invalid task(s) or generator(s): ' + missing.join(', ')));
          return;
        }

        let generator = name => {
          let app = this.hasGenerator(name) ? this.getGenerator(name, options) : this;
          this.emit('generate', app);
          return app;
        };

        if (options.parallel === true) {
          let pending = [];
          for (let ele of tasks) pending.push(generator(ele.name).build(ele.tasks));
          Promise.all(pending).then(resolve).catch(reject);
        } else {
          for (let ele of tasks) {
            await generator(ele.name).build(ele.tasks).catch(reject);
          }
          resolve();
        }
      });

      if (typeof callback === 'function') {
        promise.then(() => callback()).catch(callback);
        return;
      }

      return promise;
    }

    /**
     * Create a generator alias from the given `name`. By default, `generate-`
     * is stripped from beginning of the generator name.
     *
     * ```js
     * // customize the alias
     * const app = new Generate({ toAlias: require('camel-case') });
     * ```
     * @name .toAlias
     * @param {String} `name`
     * @param {Object} `options`
     * @return {String} Returns the alias.
     * @api public
     */

    toAlias(name, options) {
      if (typeof options === 'function') {
        return options(name);
      }
      if (options && typeof options.toAlias === 'function') {
        return options.toAlias(name);
      }
      if (typeof this.options.toAlias === 'function') {
        return this.options.toAlias(name);
      }
      return name ? name.replace(/^generate-/, '') : '';
    }

    /**
     * Returns true if every name in the given array is a registered generator.
     * @name .isGenerators
     * @param {Array} `names`
     * @return {Boolean}
     * @api public
     */

    isGenerators(names) {
      return names.every(name => this.hasGenerator(name));
    }

    /**
     * Format task and generator errors.
     * @name .formatError
     * @param {String} `name`
     * @return {Error}
     * @api public
     */

    formatError(name, type = 'generator', appname = 'generator') {
      let key = this.namespace || 'default';
      let suffix = '.';

      // if not the base instance, remove the first name segment
      if (this !== this.base) {
        key = key.split('.').slice(1).join('.');
        suffix = ` on ${appname} "${key}"`;
      }

      let message = `${type} "${name}" is not registered`;
      return new Error(message + suffix);
    }

    /**
     * Disable inspect. Returns a function to re-enable inspect. Useful for debugging.
     * @name .disableInspect
     * @api public
     */

    disableInspect() {
      let inspect = this[util.inspect.custom];
      this[util.inspect.custom] = void 0;

      return () => {
        define(this, util.inspect.custom, inspect);
      };
    }

    /**
     * Parse task arguments into an array of task configuration objects.
     */

    parseTasks(...args) {
      return parse(this.options.register)(this, ...args);
    }

    /**
     * Custom inspect function
     */

    [util.inspect.custom]() {
      if (typeof this.options.inspectFn === 'function') {
        return this.options.inspectFn(this);
      }
      const names = this.generators ? [...this.generators.keys()].join(', ') : '';
      const tasks = this.tasks ? [...this.tasks.keys()].join(', ') : '';
      return `<Generator "${this.namespace}" tasks: [${tasks}], generators: [${names}]>`;
    }

    /**
     * Get the first ancestor instance of Composer. Only works if `generator.parent` is
     * defined on child instances.
     * @name .base
     * @getter
     * @api public
     */

    get base() {
      return this.parent ? this.parent.base : this;
    }

    /**
     * Get or set the generator name.
     * @name .name
     * @getter
     * @param {String} [name="root"]
     * @return {String}
     * @api public
     */

    set name(val) {
      define(this, '_name', val);
    }
    get name() {
      return this._name || 'generate';
    }

    /**
     * Get or set the generator `alias`. By default, the generator alias is created
     * by passing the generator name to the [.toAlias](#toAlias) method.
     * @name .alias
     * @getter
     * @param {String} [alias="generate"]
     * @return {String}
     * @api public
     */

    set alias(val) {
      define(this, '_alias', val);
    }
    get alias() {
      return this._alias || this.toAlias(this.name, this.options);
    }

    /**
     * Get the generator namespace. The namespace is created by joining the generator's `alias`
     * to the alias of each ancestor generator.
     * @name .namespace
     * @getter
     * @param {String} [namespace="root"]
     * @return {String}
     * @api public
     */

    get namespace() {
      return this.parent ? this.parent.namespace + '.' + this.alias : this.alias;
    }

    /**
     * Get the depth of a generator - useful for debugging. The root generator
     * has a depth of `0`, sub-generators add `1` for each level of nesting.
     * @name .depth
     * @getter
     * @return {Number}
     * @api public
     */

    get depth() {
      return this.parent ? this.parent.depth + 1 : 0;
    }

    /**
     * Static method that returns a function for parsing task arguments.
     * @name Composer#parse
     * @param {Function} `register` Function that receives a name of a task or generator that cannot be found by the parse function. This allows the `register` function to dynamically register tasks or generators.
     * @return {Function} Returns a function for parsing task args.
     * @api public
     * @static
     */

    static parseTasks(register) {
      return parse(register);
    }

    /**
     * Static method that returns true if the given `val` is an instance of Generate.
     * @name Composer#isGenerator
     * @param {Object} `val`
     * @return {Boolean}
     * @api public
     * @static
     */

    static isGenerator(val) {
      return val instanceof this || (typeof val === 'function' && val.isGenerator === true);
    }

    /**
     * Static method for creating a custom Composer class with the given `Emitter.
     * @name Composer#create
     * @param {Function} `Emitter`
     * @return {Class} Returns the custom class.
     * @static
     * @api public
     */

    static create(Emitter) {
      return factory(Emitter);
    }

    /**
     * Static getter for getting the Tasks class with the same `Emitter` class as Composer.
     * @name Composer#Tasks
     * @param {Function} `Emitter`
     * @return {Class} Returns the Tasks class.
     * @getter
     * @static
     * @api public
     */

    static get Tasks() {
      return Tasks.create(Emitter);
    }

    /**
     * Static getter for getting the `Task` class.
     *
     * ```js
     * const { Task } = require('composer');
     * ```
     * @name Composer#Task
     * @getter
     * @static
     * @api public
     */

    static get Task() {
      return Task;
    }
  }

  return Generator;
};

/**
 * Expose `factory` function
 */

module.exports = factory();
