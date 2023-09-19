'use strict';

var resolveBin = require('..');

// package.json:
// "bin": {
//   "dependency-cruiser": "bin/dependency-cruise.js",
//   "dependency-cruise": "bin/dependency-cruise.js",
//   "depcruise": "bin/dependency-cruise.js",
//   "depcruise-fmt": "bin/depcruise-fmt.js",
//   "depcruise-wrap-stream-in-html": "bin/wrap-stream-in-html.js"
// },

resolveBin('dependency-cruiser', function (err, bin) {
  if (err) return console.error(err);
  console.log(bin);
});

// => [..]/resolve-bin/node_modules/dependency-cruiser/bin/dependency-cruise.js

resolveBin('dependency-cruiser', {executable:"depcruise"}, function (err, bin) {
  if (err) return console.error(err);
  console.log(bin);
});

// => [..]/resolve-bin/node_modules/dependency-cruiser/bin/dependency-cruise.js

var path = resolveBin.sync('dependency-cruiser', { executable: 'depcruise' });
console.log(path);

// => [..]/resolve-bin/node_modules/dependency-cruiser/bin/dependency-cruise.js
