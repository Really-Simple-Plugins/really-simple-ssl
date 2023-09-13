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
console.log("RssslModal component should be rendered now.");
document.addEventListener('DOMContentLoaded', function () {
  var root = wp.element.createRoot(document.getElementById('rsssl-modal-root'));
  root.render(wp.element.createElement(RssslModal, null));
  console.log("RssslModal component should be rendered now.");
});

/** @jsx wp.element.createElement */
var _wp$element = wp.element,
  useState = _wp$element.useState,
  useEffect = _wp$element.useEffect;
function Modal(_ref) {
  var isOpen = _ref.isOpen,
    onClose = _ref.onClose,
    title = _ref.title,
    children = _ref.children;
  if (!isOpen) return null;
  return wp.element.createElement("div", {
    style: overlayStyle
  }, wp.element.createElement("div", {
    style: modalStyle
  }, wp.element.createElement("h2", null, title), wp.element.createElement("button", {
    onClick: onClose
  }, "Close"), children));
}
var overlayStyle = {
  position: 'fixed',
  top: 0,
  left: 0,
  right: 0,
  bottom: 0,
  backgroundColor: 'rgba(0, 0, 0, 0.7)',
  display: 'flex',
  justifyContent: 'center',
  alignItems: 'center',
  zIndex: 1000
};
var modalStyle = {
  backgroundColor: '#fff',
  padding: '20px',
  borderRadius: '5px',
  maxWidth: '500px',
  minHeight: '300px',
  margin: '0 auto',
  zIndex: 1001
};
function RssslModal() {
  var _useState = useState(false),
    _useState2 = _slicedToArray(_useState, 2),
    isOpen = _useState2[0],
    setOpen = _useState2[1];
  console.log("RssslModal component rendered");
  useEffect(function () {
    var showModalListener = function showModalListener() {
      console.log("showMyPluginModalEvent detected");
      setOpen(true);
    };
    document.addEventListener('showRssslModalEvent', showModalListener);

    // Cleanup the listener on component unmount
    return function () {
      document.removeEventListener('showRssslModalEvent', showModalListener);
    };
  }, []);
  return wp.element.createElement(Modal, {
    isOpen: isOpen,
    title: "Are you sure?",
    onClose: function onClose() {
      return setOpen(false);
    }
  });
}
var _default = RssslModal;
exports["default"] = _default;

},{}]},{},[1])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJhc3NldHMvanMvc3JjL3Jzc3NsLXBsdWdpbi5qc3giXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7Ozs7Ozs7Ozs7Ozs7QUNBQTtBQUNBLE9BQU8sQ0FBQyxHQUFHLENBQUMsOENBQThDLENBQUM7QUFDM0QsUUFBUSxDQUFDLGdCQUFnQixDQUFDLGtCQUFrQixFQUFFLFlBQVc7RUFDckQsSUFBTSxJQUFJLEdBQUcsRUFBRSxDQUFDLE9BQU8sQ0FBQyxVQUFVLENBQUMsUUFBUSxDQUFDLGNBQWMsQ0FBQyxrQkFBa0IsQ0FBQyxDQUFDO0VBQy9FLElBQUksQ0FBQyxNQUFNLENBQUMsRUFBQSxDQUFBLE9BQUEsQ0FBQSxhQUFBLENBQUMsVUFBVSxNQUFFLENBQUMsQ0FBQztFQUMzQixPQUFPLENBQUMsR0FBRyxDQUFDLDhDQUE4QyxDQUFDO0FBQy9ELENBQUMsQ0FBQzs7QUFHRjtBQUNBLElBQUEsV0FBQSxHQUFnQyxFQUFFLENBQUMsT0FBTztFQUFsQyxRQUFRLEdBQUEsV0FBQSxDQUFSLFFBQVE7RUFBRSxTQUFTLEdBQUEsV0FBQSxDQUFULFNBQVM7QUFDM0IsU0FBUyxLQUFLLENBQUEsSUFBQSxFQUF1QztFQUFBLElBQXBDLE1BQU0sR0FBQSxJQUFBLENBQU4sTUFBTTtJQUFFLE9BQU8sR0FBQSxJQUFBLENBQVAsT0FBTztJQUFFLEtBQUssR0FBQSxJQUFBLENBQUwsS0FBSztJQUFFLFFBQVEsR0FBQSxJQUFBLENBQVIsUUFBUTtFQUM3QyxJQUFJLENBQUMsTUFBTSxFQUFFLE9BQU8sSUFBSTtFQUV4QixPQUNJLEVBQUEsQ0FBQSxPQUFBLENBQUEsYUFBQTtJQUFLLEtBQUssRUFBRTtFQUFhLEdBQ3JCLEVBQUEsQ0FBQSxPQUFBLENBQUEsYUFBQTtJQUFLLEtBQUssRUFBRTtFQUFXLEdBQ25CLEVBQUEsQ0FBQSxPQUFBLENBQUEsYUFBQSxhQUFLLEtBQVUsQ0FBQyxFQUNoQixFQUFBLENBQUEsT0FBQSxDQUFBLGFBQUE7SUFBUSxPQUFPLEVBQUU7RUFBUSxHQUFDLE9BQWEsQ0FBQyxFQUN2QyxRQUNBLENBQ0osQ0FBQztBQUVkO0FBRUEsSUFBTSxZQUFZLEdBQUc7RUFDakIsUUFBUSxFQUFFLE9BQU87RUFDakIsR0FBRyxFQUFFLENBQUM7RUFDTixJQUFJLEVBQUUsQ0FBQztFQUNQLEtBQUssRUFBRSxDQUFDO0VBQ1IsTUFBTSxFQUFFLENBQUM7RUFDVCxlQUFlLEVBQUUsb0JBQW9CO0VBQ3JDLE9BQU8sRUFBRSxNQUFNO0VBQ2YsY0FBYyxFQUFFLFFBQVE7RUFDeEIsVUFBVSxFQUFFLFFBQVE7RUFDcEIsTUFBTSxFQUFFO0FBQ1osQ0FBQztBQUVELElBQU0sVUFBVSxHQUFHO0VBQ2YsZUFBZSxFQUFFLE1BQU07RUFDdkIsT0FBTyxFQUFFLE1BQU07RUFDZixZQUFZLEVBQUUsS0FBSztFQUNuQixRQUFRLEVBQUUsT0FBTztFQUNqQixTQUFTLEVBQUUsT0FBTztFQUNsQixNQUFNLEVBQUUsUUFBUTtFQUNoQixNQUFNLEVBQUU7QUFDWixDQUFDO0FBRUQsU0FBUyxVQUFVLENBQUEsRUFBRztFQUNsQixJQUFBLFNBQUEsR0FBNEIsUUFBUSxDQUFDLEtBQUssQ0FBQztJQUFBLFVBQUEsR0FBQSxjQUFBLENBQUEsU0FBQTtJQUFuQyxNQUFNLEdBQUEsVUFBQTtJQUFFLE9BQU8sR0FBQSxVQUFBO0VBRXZCLE9BQU8sQ0FBQyxHQUFHLENBQUMsK0JBQStCLENBQUM7RUFFNUMsU0FBUyxDQUFDLFlBQU07SUFDWixJQUFNLGlCQUFpQixHQUFHLFNBQXBCLGlCQUFpQixDQUFBLEVBQVM7TUFDNUIsT0FBTyxDQUFDLEdBQUcsQ0FBQyxpQ0FBaUMsQ0FBQztNQUM5QyxPQUFPLENBQUMsSUFBSSxDQUFDO0lBQ2pCLENBQUM7SUFFRCxRQUFRLENBQUMsZ0JBQWdCLENBQUMscUJBQXFCLEVBQUUsaUJBQWlCLENBQUM7O0lBRW5FO0lBQ0EsT0FBTyxZQUFNO01BQ1QsUUFBUSxDQUFDLG1CQUFtQixDQUFDLHFCQUFxQixFQUFFLGlCQUFpQixDQUFDO0lBQzFFLENBQUM7RUFDTCxDQUFDLEVBQUUsRUFBRSxDQUFDO0VBQ04sT0FDSSxFQUFBLENBQUEsT0FBQSxDQUFBLGFBQUEsQ0FBQyxLQUFLO0lBQ0YsTUFBTSxFQUFFLE1BQU87SUFDZixLQUFLLEVBQUUsZUFBZ0I7SUFDdkIsT0FBTyxFQUFFLFNBQUEsUUFBQTtNQUFBLE9BQU0sT0FBTyxDQUFDLEtBQUssQ0FBQztJQUFBO0VBQUMsQ0FHM0IsQ0FBQztBQUVoQjtBQUFDLElBQUEsUUFBQSxHQUVjLFVBQVU7QUFBQSxPQUFBLGNBQUEsUUFBQSIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uKCl7ZnVuY3Rpb24gcihlLG4sdCl7ZnVuY3Rpb24gbyhpLGYpe2lmKCFuW2ldKXtpZighZVtpXSl7dmFyIGM9XCJmdW5jdGlvblwiPT10eXBlb2YgcmVxdWlyZSYmcmVxdWlyZTtpZighZiYmYylyZXR1cm4gYyhpLCEwKTtpZih1KXJldHVybiB1KGksITApO3ZhciBhPW5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIraStcIidcIik7dGhyb3cgYS5jb2RlPVwiTU9EVUxFX05PVF9GT1VORFwiLGF9dmFyIHA9bltpXT17ZXhwb3J0czp7fX07ZVtpXVswXS5jYWxsKHAuZXhwb3J0cyxmdW5jdGlvbihyKXt2YXIgbj1lW2ldWzFdW3JdO3JldHVybiBvKG58fHIpfSxwLHAuZXhwb3J0cyxyLGUsbix0KX1yZXR1cm4gbltpXS5leHBvcnRzfWZvcih2YXIgdT1cImZ1bmN0aW9uXCI9PXR5cGVvZiByZXF1aXJlJiZyZXF1aXJlLGk9MDtpPHQubGVuZ3RoO2krKylvKHRbaV0pO3JldHVybiBvfXJldHVybiByfSkoKSIsIi8qKiBAanN4IHdwLmVsZW1lbnQuY3JlYXRlRWxlbWVudCAqL1xuY29uc29sZS5sb2coXCJSc3NzbE1vZGFsIGNvbXBvbmVudCBzaG91bGQgYmUgcmVuZGVyZWQgbm93LlwiKTtcbmRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCBmdW5jdGlvbigpIHtcbiAgICBjb25zdCByb290ID0gd3AuZWxlbWVudC5jcmVhdGVSb290KGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdyc3NzbC1tb2RhbC1yb290JykpO1xuICAgIHJvb3QucmVuZGVyKDxSc3NzbE1vZGFsIC8+KTtcbiAgICBjb25zb2xlLmxvZyhcIlJzc3NsTW9kYWwgY29tcG9uZW50IHNob3VsZCBiZSByZW5kZXJlZCBub3cuXCIpO1xufSk7XG5cblxuLyoqIEBqc3ggd3AuZWxlbWVudC5jcmVhdGVFbGVtZW50ICovXG5jb25zdCB7IHVzZVN0YXRlLCB1c2VFZmZlY3QgfSA9IHdwLmVsZW1lbnQ7XG5mdW5jdGlvbiBNb2RhbCh7IGlzT3Blbiwgb25DbG9zZSwgdGl0bGUsIGNoaWxkcmVuIH0pIHtcbiAgICBpZiAoIWlzT3BlbikgcmV0dXJuIG51bGw7XG5cbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IHN0eWxlPXtvdmVybGF5U3R5bGV9PlxuICAgICAgICAgICAgPGRpdiBzdHlsZT17bW9kYWxTdHlsZX0+XG4gICAgICAgICAgICAgICAgPGgyPnt0aXRsZX08L2gyPlxuICAgICAgICAgICAgICAgIDxidXR0b24gb25DbGljaz17b25DbG9zZX0+Q2xvc2U8L2J1dHRvbj5cbiAgICAgICAgICAgICAgICB7Y2hpbGRyZW59XG4gICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn1cblxuY29uc3Qgb3ZlcmxheVN0eWxlID0ge1xuICAgIHBvc2l0aW9uOiAnZml4ZWQnLFxuICAgIHRvcDogMCxcbiAgICBsZWZ0OiAwLFxuICAgIHJpZ2h0OiAwLFxuICAgIGJvdHRvbTogMCxcbiAgICBiYWNrZ3JvdW5kQ29sb3I6ICdyZ2JhKDAsIDAsIDAsIDAuNyknLFxuICAgIGRpc3BsYXk6ICdmbGV4JyxcbiAgICBqdXN0aWZ5Q29udGVudDogJ2NlbnRlcicsXG4gICAgYWxpZ25JdGVtczogJ2NlbnRlcicsXG4gICAgekluZGV4OiAxMDAwXG59O1xuXG5jb25zdCBtb2RhbFN0eWxlID0ge1xuICAgIGJhY2tncm91bmRDb2xvcjogJyNmZmYnLFxuICAgIHBhZGRpbmc6ICcyMHB4JyxcbiAgICBib3JkZXJSYWRpdXM6ICc1cHgnLFxuICAgIG1heFdpZHRoOiAnNTAwcHgnLFxuICAgIG1pbkhlaWdodDogJzMwMHB4JyxcbiAgICBtYXJnaW46ICcwIGF1dG8nLFxuICAgIHpJbmRleDogMTAwMVxufTtcblxuZnVuY3Rpb24gUnNzc2xNb2RhbCgpIHtcbiAgICBjb25zdCBbIGlzT3Blbiwgc2V0T3BlbiBdID0gdXNlU3RhdGUoZmFsc2UpO1xuXG4gICAgY29uc29sZS5sb2coXCJSc3NzbE1vZGFsIGNvbXBvbmVudCByZW5kZXJlZFwiKTtcblxuICAgIHVzZUVmZmVjdCgoKSA9PiB7XG4gICAgICAgIGNvbnN0IHNob3dNb2RhbExpc3RlbmVyID0gKCkgPT4ge1xuICAgICAgICAgICAgY29uc29sZS5sb2coXCJzaG93TXlQbHVnaW5Nb2RhbEV2ZW50IGRldGVjdGVkXCIpO1xuICAgICAgICAgICAgc2V0T3Blbih0cnVlKTtcbiAgICAgICAgfTtcblxuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdzaG93UnNzc2xNb2RhbEV2ZW50Jywgc2hvd01vZGFsTGlzdGVuZXIpO1xuXG4gICAgICAgIC8vIENsZWFudXAgdGhlIGxpc3RlbmVyIG9uIGNvbXBvbmVudCB1bm1vdW50XG4gICAgICAgIHJldHVybiAoKSA9PiB7XG4gICAgICAgICAgICBkb2N1bWVudC5yZW1vdmVFdmVudExpc3RlbmVyKCdzaG93UnNzc2xNb2RhbEV2ZW50Jywgc2hvd01vZGFsTGlzdGVuZXIpO1xuICAgICAgICB9O1xuICAgIH0sIFtdKTtcbiAgICByZXR1cm4gKFxuICAgICAgICA8TW9kYWxcbiAgICAgICAgICAgIGlzT3Blbj17aXNPcGVufVxuICAgICAgICAgICAgdGl0bGU9e1wiQXJlIHlvdSBzdXJlP1wifVxuICAgICAgICAgICAgb25DbG9zZT17KCkgPT4gc2V0T3BlbihmYWxzZSl9XG4gICAgICAgID5cbiAgICAgICAgICAgIHsvKiBZb3VyIG1vZGFsIGNvbnRlbnQgaGVyZSAqL31cbiAgICAgICAgPC9Nb2RhbD5cbiAgICApO1xufVxuXG5leHBvcnQgZGVmYXVsdCBSc3NzbE1vZGFsO1xuIl19
