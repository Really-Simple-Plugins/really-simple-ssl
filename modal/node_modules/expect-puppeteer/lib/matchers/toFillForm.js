"use strict";

exports.__esModule = true;
exports.default = void 0;

var _options = require("../options");

var _toFill = _interopRequireDefault(require("./toFill"));

var _toMatchElement = _interopRequireDefault(require("./toMatchElement"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/* eslint-disable no-restricted-syntax, no-await-in-loop */
async function toFillForm(instance, selector, values, options) {
  options = (0, _options.defaultOptions)(options);
  const form = await (0, _toMatchElement.default)(instance, selector, options);

  for (const name of Object.keys(values)) {
    await (0, _toFill.default)(form, `[name="${name}"]`, values[name], options);
  }
}

var _default = toFillForm;
exports.default = _default;