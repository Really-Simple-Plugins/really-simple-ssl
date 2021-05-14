'use strict';

const { nano } = require('./utils');

class Timer {
  constructor() {
    this.date = {};
    this.hr = {};
  }

  start() {
    this.date.start = new Date();
    this.hr.start = process.hrtime();
    return this;
  }

  end() {
    this.date.end = new Date();
    this.hr.end = process.hrtime();
    this.hr.duration = process.hrtime(this.hr.start);
    return this;
  }

  get diff() {
    return nano(this.hr.end) - nano(this.hr.start);
  }

  get duration() {
    return this.hr.duration ? require('pretty-time')(this.hr.duration) : '';
  }
}

/**
 * Expose `Timer`
 */

module.exports = Timer;
