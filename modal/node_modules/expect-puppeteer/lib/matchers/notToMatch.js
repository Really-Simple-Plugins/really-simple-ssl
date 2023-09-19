"use strict";

exports.__esModule = true;
exports.default = void 0;

var _utils = require("../utils");

var _options = require("../options");

async function notToMatch(instance, matcher, options) {
  options = (0, _options.defaultOptions)(options);
  const {
    page,
    handle
  } = await (0, _utils.getContext)(instance, () => document.body);

  try {
    await page.waitForFunction((handle, matcher) => {
      if (!handle) return false;
      return handle.textContent.match(new RegExp(matcher)) === null;
    }, options, handle, matcher);
  } catch (error) {
    throw (0, _utils.enhanceError)(error, `Text found "${matcher}"`);
  }
}

var _default = notToMatch;
exports.default = _default;