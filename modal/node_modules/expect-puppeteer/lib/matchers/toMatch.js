"use strict";

exports.__esModule = true;
exports.default = void 0;

var _utils = require("../utils");

var _options = require("../options");

async function toMatch(instance, matcher, options) {
  options = (0, _options.defaultOptions)(options);
  const {
    page,
    handle
  } = await (0, _utils.getContext)(instance, () => document.body);
  const {
    text,
    regexp
  } = (0, _utils.expandSearchExpr)(matcher);

  try {
    await page.waitForFunction((handle, text, regexp) => {
      if (!handle) return false;

      if (regexp !== null) {
        const [, pattern, flags] = regexp.match(/\/(.*)\/(.*)?/);
        return handle.textContent.replace(/\s+/g, ' ').trim().match(new RegExp(pattern, flags)) !== null;
      }

      if (text !== null) {
        return handle.textContent.replace(/\s+/g, ' ').trim().includes(text);
      }

      return false;
    }, options, handle, text, regexp);
  } catch (error) {
    throw (0, _utils.enhanceError)(error, `Text not found "${matcher}"`);
  }
}

var _default = toMatch;
exports.default = _default;