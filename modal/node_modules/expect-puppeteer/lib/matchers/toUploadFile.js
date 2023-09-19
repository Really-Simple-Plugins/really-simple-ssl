"use strict";

exports.__esModule = true;
exports.default = void 0;

var _toMatchElement = _interopRequireDefault(require("./toMatchElement"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

async function toUploadFile(instance, selector, file, options) {
  const input = await (0, _toMatchElement.default)(instance, selector, options);
  const files = Array.isArray(file) ? file : [file];
  await input.uploadFile(...files);
}

var _default = toUploadFile;
exports.default = _default;