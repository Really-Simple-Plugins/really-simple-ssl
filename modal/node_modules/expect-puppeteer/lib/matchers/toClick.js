"use strict";

exports.__esModule = true;
exports.default = void 0;

var _toMatchElement = _interopRequireDefault(require("./toMatchElement"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

async function toClick(instance, selector, options) {
  const element = await (0, _toMatchElement.default)(instance, selector, options);
  await element.click(options);
}

var _default = toClick;
exports.default = _default;