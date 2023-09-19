'use strict';
/*jshint asi: true */

var test = require('tap').test
var path = require('path')
var resolveBin = require('../');

function relative(dir) {
  return path.relative(path.join(__dirname, '..'), dir)
}

test('\ntap', function (t) {
  resolveBin('tap', function (err, bin) {
    if (err) return t.fail(err);
    t.equal(relative(bin), 'node_modules/tap/bin/tap.js')
    t.end()
  });
})

test('\ntap sync', function (t) {
  var bin = resolveBin.sync('tap');
  t.equal(relative(bin), 'node_modules/tap/bin/tap.js');
  t.end();
})

test('\nmocha', function (t) {
  resolveBin('mocha', function (err, bin) {
    if (err) return t.fail(err);
    t.equal(relative(bin), 'node_modules/mocha/bin/mocha')
    t.end()
  });
})

test('\n_mocha', function (t) {
  resolveBin('mocha', { executable: "_mocha" }, function (err, bin) {
    if (err) throw err;
    t.equal(relative(bin), 'node_modules/mocha/bin/_mocha')
    t.end()
  });
})

test('\nno `executable` in options', function (t) {
  resolveBin('mocha', {}, function (err, bin) {
    if (err) throw err;
    t.equal(relative(bin), 'node_modules/mocha/bin/mocha')
    t.end()
  });
})

test('\nnon-existent module', function (t) {
  resolveBin('non-existent', function (err, bin) {
    t.ok(err, 'returns error')
    t.equal(err.code, 'MODULE_NOT_FOUND', 'saying module not found')
    t.similar(err.message, /non-existent/, 'stating module name')
    t.end()
  });
})

test('\nnon-existent module sync', function (t) {
  t.throws(function () {
    resolveBin.sync('non-existent')
  })
  t.end()
})

test('\nnon-existent executable inside module', function (t) {
  resolveBin('mocha', { executable: "no-such-bin" }, function (err, bin) {
    t.ok(err, 'returns error')
    t.similar(err.message, /no bin/i, 'stating module name')
    t.end()
  });
})

test('\nopen-cli (without "main" field in package.json)', function (t) {
  resolveBin('open-cli', function (err, bin) {
    if (err) return t.fail(err);
    t.equal(relative(bin), 'node_modules/open-cli/cli.js')
    t.end()
  });
})

test('\nopen-cli (without "main" field in package.json), sync', function (t) {
  var bin = resolveBin.sync('open-cli');
  t.equal(relative(bin), 'node_modules/open-cli/cli.js')
  t.end()
})

test('\ndependency-cruise (cannot import package.json)', function (t) {
  resolveBin('dependency-cruiser', function (err, bin) {
    if (err) return t.fail(err);
    t.equal(relative(bin), 'node_modules/dependency-cruiser/bin/dependency-cruise.js')
    t.end()
  });
})

test('\ndependency-cruise (cannot import package.json)', function (t) {
  resolveBin('dependency-cruiser', {executable:"depcruise"}, function (err, bin) {
    if (err) return t.fail(err);
    t.equal(relative(bin), 'node_modules/dependency-cruiser/bin/dependency-cruise.js')
    t.end()
  });
})

test('\ndependency-cruise (cannot import package.json)', function (t) {
  var bin = resolveBin.sync('dependency-cruiser');
  t.equal(relative(bin), 'node_modules/dependency-cruiser/bin/dependency-cruise.js')
  t.end()
})