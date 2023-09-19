"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = _default;
/**
 * Check whether a Node is a custom property set
 *
 * @param {import('postcss').Rule} node
 * @returns {boolean}
 */
function _default(node) {
  var _node$raws, _node$raws$prop, _node$raws2, _node$raws2$value;
  var prop = (node === null || node === void 0 ? void 0 : (_node$raws = node.raws) === null || _node$raws === void 0 ? void 0 : (_node$raws$prop = _node$raws.prop) === null || _node$raws$prop === void 0 ? void 0 : _node$raws$prop.raw) || node.prop;
  var value = (node === null || node === void 0 ? void 0 : (_node$raws2 = node.raws) === null || _node$raws2 === void 0 ? void 0 : (_node$raws2$value = _node$raws2.value) === null || _node$raws2$value === void 0 ? void 0 : _node$raws2$value.raw) || node.value;
  return node.type === "decl" && prop.startsWith("--") && value.startsWith("{") && value.endsWith("}");
}