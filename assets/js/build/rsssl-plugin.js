(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
/** @jsx wp.element.createElement */
var _wp$components = wp.components,
  Modal = _wp$components.Modal,
  Button = _wp$components.Button;
var _wp$element = wp.element,
  useState = _wp$element.useState,
  useEffect = _wp$element.useEffect;
function RssslModal() {
  var _useState = useState(false),
    _useState2 = _slicedToArray(_useState, 2),
    isOpen = _useState2[0],
    setOpen = _useState2[1];
  useEffect(function () {
    var showModalListener = function showModalListener() {
      console.log("showRssslModalEvent detected we should open the modal");
      setOpen(true);
    };
    document.addEventListener('showRssslModalEvent', showModalListener);

    // Cleanup the listener on component unmount
    return function () {
      document.removeEventListener('showRssslModalEvent', showModalListener);
    };
  }, []); // Removed [isOpen] to avoid unnecessary re-registrations of the event listener

  return wp.element.createElement("div", null, isOpen && wp.element.createElement(Modal, {
    title: "My Modal Title",
    onRequestClose: function onRequestClose() {
      return setOpen(false);
    }
  }, wp.element.createElement("p", null, "This is the modal content."), wp.element.createElement(Button, {
    onClick: function onClick() {
      return setOpen(false);
    }
  }, "Close Modal")));
}
var _default = RssslModal;
exports["default"] = _default;

},{}],2:[function(require,module,exports){
"use strict";

var _RssslModal = _interopRequireDefault(require("./components/RssslModal.jsx"));
function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }
/** @jsx wp.element.createElement */

document.addEventListener('DOMContentLoaded', function () {
  var root = wp.element.createRoot(document.getElementById('rsssl-modal-root'));
  root.render(wp.element.createElement(_RssslModal["default"], null));
});

},{"./components/RssslModal.jsx":1}]},{},[2])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJhc3NldHMvanMvc3JjL2NvbXBvbmVudHMvUnNzc2xNb2RhbC5qc3giLCJhc3NldHMvanMvc3JjL3Jzc3NsLXBsdWdpbi5qc3giXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7Ozs7Ozs7Ozs7Ozs7QUNBQTtBQUNBLElBQUEsY0FBQSxHQUEwQixFQUFFLENBQUMsVUFBVTtFQUEvQixLQUFLLEdBQUEsY0FBQSxDQUFMLEtBQUs7RUFBRSxNQUFNLEdBQUEsY0FBQSxDQUFOLE1BQU07QUFDckIsSUFBQSxXQUFBLEdBQWdDLEVBQUUsQ0FBQyxPQUFPO0VBQWxDLFFBQVEsR0FBQSxXQUFBLENBQVIsUUFBUTtFQUFFLFNBQVMsR0FBQSxXQUFBLENBQVQsU0FBUztBQUUzQixTQUFTLFVBQVUsQ0FBQSxFQUFHO0VBQ2xCLElBQUEsU0FBQSxHQUEwQixRQUFRLENBQUMsS0FBSyxDQUFDO0lBQUEsVUFBQSxHQUFBLGNBQUEsQ0FBQSxTQUFBO0lBQWxDLE1BQU0sR0FBQSxVQUFBO0lBQUUsT0FBTyxHQUFBLFVBQUE7RUFFdEIsU0FBUyxDQUFDLFlBQU07SUFDWixJQUFNLGlCQUFpQixHQUFHLFNBQXBCLGlCQUFpQixDQUFBLEVBQVM7TUFDNUIsT0FBTyxDQUFDLEdBQUcsQ0FBQyx1REFBdUQsQ0FBQztNQUNwRSxPQUFPLENBQUMsSUFBSSxDQUFDO0lBQ2pCLENBQUM7SUFFRCxRQUFRLENBQUMsZ0JBQWdCLENBQUMscUJBQXFCLEVBQUUsaUJBQWlCLENBQUM7O0lBRW5FO0lBQ0EsT0FBTyxZQUFNO01BQ1QsUUFBUSxDQUFDLG1CQUFtQixDQUFDLHFCQUFxQixFQUFFLGlCQUFpQixDQUFDO0lBQzFFLENBQUM7RUFDTCxDQUFDLEVBQUUsRUFBRSxDQUFDLENBQUMsQ0FBRTs7RUFFVCxPQUNJLEVBQUEsQ0FBQSxPQUFBLENBQUEsYUFBQSxjQUNLLE1BQU0sSUFDSCxFQUFBLENBQUEsT0FBQSxDQUFBLGFBQUEsQ0FBQyxLQUFLO0lBQ0YsS0FBSyxFQUFDLGdCQUFnQjtJQUN0QixjQUFjLEVBQUUsU0FBQSxlQUFBO01BQUEsT0FBTSxPQUFPLENBQUMsS0FBSyxDQUFDO0lBQUE7RUFBQyxHQUVyQyxFQUFBLENBQUEsT0FBQSxDQUFBLGFBQUEsWUFBRyw0QkFBNkIsQ0FBQyxFQUNqQyxFQUFBLENBQUEsT0FBQSxDQUFBLGFBQUEsQ0FBQyxNQUFNO0lBQUMsT0FBTyxFQUFFLFNBQUEsUUFBQTtNQUFBLE9BQU0sT0FBTyxDQUFDLEtBQUssQ0FBQztJQUFBO0VBQUMsR0FBQyxhQUFtQixDQUN2RCxDQUVWLENBQUM7QUFFZDtBQUFDLElBQUEsUUFBQSxHQUVjLFVBQVU7QUFBQSxPQUFBLGNBQUEsUUFBQTs7Ozs7QUNuQ3pCLElBQUEsV0FBQSxHQUFBLHNCQUFBLENBQUEsT0FBQTtBQUFxRCxTQUFBLHVCQUFBLEdBQUEsV0FBQSxHQUFBLElBQUEsR0FBQSxDQUFBLFVBQUEsR0FBQSxHQUFBLGdCQUFBLEdBQUE7QUFEckQ7O0FBR0EsUUFBUSxDQUFDLGdCQUFnQixDQUFDLGtCQUFrQixFQUFFLFlBQVc7RUFDckQsSUFBTSxJQUFJLEdBQUcsRUFBRSxDQUFDLE9BQU8sQ0FBQyxVQUFVLENBQUMsUUFBUSxDQUFDLGNBQWMsQ0FBQyxrQkFBa0IsQ0FBQyxDQUFDO0VBQy9FLElBQUksQ0FBQyxNQUFNLENBQUMsRUFBQSxDQUFBLE9BQUEsQ0FBQSxhQUFBLENBQUMsV0FBQSxXQUFVLE1BQUUsQ0FBQyxDQUFDO0FBQy9CLENBQUMsQ0FBQyIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uKCl7ZnVuY3Rpb24gcihlLG4sdCl7ZnVuY3Rpb24gbyhpLGYpe2lmKCFuW2ldKXtpZighZVtpXSl7dmFyIGM9XCJmdW5jdGlvblwiPT10eXBlb2YgcmVxdWlyZSYmcmVxdWlyZTtpZighZiYmYylyZXR1cm4gYyhpLCEwKTtpZih1KXJldHVybiB1KGksITApO3ZhciBhPW5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIraStcIidcIik7dGhyb3cgYS5jb2RlPVwiTU9EVUxFX05PVF9GT1VORFwiLGF9dmFyIHA9bltpXT17ZXhwb3J0czp7fX07ZVtpXVswXS5jYWxsKHAuZXhwb3J0cyxmdW5jdGlvbihyKXt2YXIgbj1lW2ldWzFdW3JdO3JldHVybiBvKG58fHIpfSxwLHAuZXhwb3J0cyxyLGUsbix0KX1yZXR1cm4gbltpXS5leHBvcnRzfWZvcih2YXIgdT1cImZ1bmN0aW9uXCI9PXR5cGVvZiByZXF1aXJlJiZyZXF1aXJlLGk9MDtpPHQubGVuZ3RoO2krKylvKHRbaV0pO3JldHVybiBvfXJldHVybiByfSkoKSIsIi8qKiBAanN4IHdwLmVsZW1lbnQuY3JlYXRlRWxlbWVudCAqL1xuY29uc3QgeyBNb2RhbCwgQnV0dG9uIH0gPSB3cC5jb21wb25lbnRzO1xuY29uc3QgeyB1c2VTdGF0ZSwgdXNlRWZmZWN0IH0gPSB3cC5lbGVtZW50O1xuXG5mdW5jdGlvbiBSc3NzbE1vZGFsKCkge1xuICAgIGNvbnN0IFtpc09wZW4sIHNldE9wZW5dID0gdXNlU3RhdGUoZmFsc2UpO1xuXG4gICAgdXNlRWZmZWN0KCgpID0+IHtcbiAgICAgICAgY29uc3Qgc2hvd01vZGFsTGlzdGVuZXIgPSAoKSA9PiB7XG4gICAgICAgICAgICBjb25zb2xlLmxvZyhcInNob3dSc3NzbE1vZGFsRXZlbnQgZGV0ZWN0ZWQgd2Ugc2hvdWxkIG9wZW4gdGhlIG1vZGFsXCIpO1xuICAgICAgICAgICAgc2V0T3Blbih0cnVlKTtcbiAgICAgICAgfTtcblxuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdzaG93UnNzc2xNb2RhbEV2ZW50Jywgc2hvd01vZGFsTGlzdGVuZXIpO1xuXG4gICAgICAgIC8vIENsZWFudXAgdGhlIGxpc3RlbmVyIG9uIGNvbXBvbmVudCB1bm1vdW50XG4gICAgICAgIHJldHVybiAoKSA9PiB7XG4gICAgICAgICAgICBkb2N1bWVudC5yZW1vdmVFdmVudExpc3RlbmVyKCdzaG93UnNzc2xNb2RhbEV2ZW50Jywgc2hvd01vZGFsTGlzdGVuZXIpO1xuICAgICAgICB9O1xuICAgIH0sIFtdKTsgIC8vIFJlbW92ZWQgW2lzT3Blbl0gdG8gYXZvaWQgdW5uZWNlc3NhcnkgcmUtcmVnaXN0cmF0aW9ucyBvZiB0aGUgZXZlbnQgbGlzdGVuZXJcblxuICAgIHJldHVybiAoXG4gICAgICAgIDxkaXY+XG4gICAgICAgICAgICB7aXNPcGVuICYmIChcbiAgICAgICAgICAgICAgICA8TW9kYWxcbiAgICAgICAgICAgICAgICAgICAgdGl0bGU9XCJNeSBNb2RhbCBUaXRsZVwiXG4gICAgICAgICAgICAgICAgICAgIG9uUmVxdWVzdENsb3NlPXsoKSA9PiBzZXRPcGVuKGZhbHNlKX1cbiAgICAgICAgICAgICAgICA+XG4gICAgICAgICAgICAgICAgICAgIDxwPlRoaXMgaXMgdGhlIG1vZGFsIGNvbnRlbnQuPC9wPlxuICAgICAgICAgICAgICAgICAgICA8QnV0dG9uIG9uQ2xpY2s9eygpID0+IHNldE9wZW4oZmFsc2UpfT5DbG9zZSBNb2RhbDwvQnV0dG9uPlxuICAgICAgICAgICAgICAgIDwvTW9kYWw+XG4gICAgICAgICAgICApfVxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufVxuXG5leHBvcnQgZGVmYXVsdCBSc3NzbE1vZGFsO1xuIiwiLyoqIEBqc3ggd3AuZWxlbWVudC5jcmVhdGVFbGVtZW50ICovXG5pbXBvcnQgUnNzc2xNb2RhbCBmcm9tICcuL2NvbXBvbmVudHMvUnNzc2xNb2RhbC5qc3gnO1xuXG5kb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24oKSB7XG4gICAgY29uc3Qgcm9vdCA9IHdwLmVsZW1lbnQuY3JlYXRlUm9vdChkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncnNzc2wtbW9kYWwtcm9vdCcpKTtcbiAgICByb290LnJlbmRlcig8UnNzc2xNb2RhbCAvPik7XG59KTsiXX0=
