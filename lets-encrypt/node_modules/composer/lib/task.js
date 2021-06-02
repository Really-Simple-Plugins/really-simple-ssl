'use strict';

const util = require('util');
const Events = require('events');
const Timer = require('./timer');
const { define, noop } = require('./utils');

class Task extends Events {
  constructor(task = {}) {
    if (typeof task.name !== 'string') {
      throw new TypeError('expected task name to be a string');
    }
    super();
    define(this, 'isTask', true);
    define(this, 'app', task.app);
    this.name = task.name;
    this.status = 'pending';
    this.options = Object.assign({ deps: [] }, task.options);
    this.callback = task.callback || noop;
    this.deps = [...task.deps || [], ...this.options.deps];
    this.time = new Timer();
    if (this.setMaxListeners) {
      this.setMaxListeners(0);
    }
  }

  [util.inspect.custom]() {
    return `<Task "${this.name}" deps: [${this.deps.join(', ')}]>`;
  }

  run(options) {
    let finished = false;
    let orig = Object.assign({}, this.options);
    this.options = Object.assign({}, this.options, options);
    this.status = 'preparing';
    this.emit('preparing', this);

    if (this.skip(options)) {
      return () => Promise.resolve(null);
    }

    this.time = new Timer();
    this.time.start();
    this.status = 'starting';
    this.emit('starting', this);

    return () => new Promise(async(resolve, reject) => {
      const finish = (err, value) => {
        if (finished) return;
        finished = true;
        try {
          this.options = orig;
          this.time.end();
          this.status = 'finished';
          this.emit('finished', this);
          if (err) {
            define(err, 'task', this);
            reject(err);
            this.emit('error', err);
          } else {
            resolve(value);
          }
        } catch (err) {
          reject(err);
        }
      };

      try {
        if (typeof this.callback !== 'function') {
          finish();
          return;
        }

        let res = this.callback.call(this, finish);
        if (res instanceof Promise) {
          let val = await res;
          if (val) res = val;
        }

        if (isEmitter(res)) {
          res.on('error', finish);
          res.on('finish', finish);
          res.on('end', finish);
          return;
        }

        if (this.callback.length === 0) {
          if (res && res.then) {
            res.then(() => finish());
          } else {
            finish(null, res);
          }
        }

      } catch (err) {
        finish(err);
      }
    });
  }

  skip(options) {
    let app = this.app || {};
    let opts = Object.assign({}, app.options, this.options, options);

    if (opts.run === false) {
      return true;
    }

    if (Array.isArray(opts.skip)) {
      return opts.skip.includes(this.name);
    }

    switch (typeof opts.skip) {
      case 'boolean':
        return opts.skip === true;
      case 'function':
        return opts.skip(this) === true;
      case 'string':
        return opts.skip === this.name;
      default: {
        return false;
      }
    }
  }
}

function isEmitter(val) {
  return val && (typeof val.on === 'function' || typeof val.pipe === 'function');
}

module.exports = Task;
