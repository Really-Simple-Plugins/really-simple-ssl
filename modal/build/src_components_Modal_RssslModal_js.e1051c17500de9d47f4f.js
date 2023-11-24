"use strict";
(self["webpackChunkreally_simple_ssl_modal"] = self["webpackChunkreally_simple_ssl_modal"] || []).push([["src_components_Modal_RssslModal_js"],{

/***/ "./src/components/Modal/RssslModal.js":
/*!********************************************!*\
  !*** ./src/components/Modal/RssslModal.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _RssslModal_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./RssslModal.scss */ "./src/components/Modal/RssslModal.scss");
/* harmony import */ var _checkbox_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./checkbox.scss */ "./src/components/Modal/checkbox.scss");
/* harmony import */ var _settings_src_utils_ErrorBoundary__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../../settings/src/utils/ErrorBoundary */ "../settings/src/utils/ErrorBoundary.js");

/** @jsx wp.element.createElement */






const RssslModal = ({
  title,
  subTitle,
  buttons,
  content,
  list,
  confirmAction,
  confirmText,
  alternativeAction,
  alternativeText,
  alternativeClassName,
  isOpen,
  setOpen,
  className,
  footer
}) => {
  const [Icon, setIcon] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  let pluginUrl = typeof rsssl_modal !== 'undefined' ? rsssl_modal.plugin_url : rsssl_settings.plugin_url;
  alternativeClassName = alternativeClassName ? alternativeClassName : 'rsssl-warning';
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (!Icon) {
      Promise.all(/*! import() */[__webpack_require__.e("vendors-settings_node_modules_react-tooltip_dist_react-tooltip_min_mjs"), __webpack_require__.e("settings_src_utils_Icon_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ../../../../settings/src/utils/Icon */ "../settings/src/utils/Icon.js")).then(({
        default: Icon
      }) => {
        setIcon(() => Icon);
      });
    }
  }, []);
  let modalCustomClass = className ? ' ' + className : "";
  return wp.element.createElement(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, isOpen && wp.element.createElement(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, wp.element.createElement(_settings_src_utils_ErrorBoundary__WEBPACK_IMPORTED_MODULE_6__["default"], {
    fallback: "Error loading modal"
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Modal, {
    className: "rsssl-modal" + modalCustomClass,
    shouldCloseOnClickOutside: false,
    shouldCloseOnEsc: false,
    title: title,
    onRequestClose: () => setOpen(false),
    open: isOpen
  }, wp.element.createElement("div", {
    className: "rsssl-modal-body"
  }, subTitle && wp.element.createElement("p", null, subTitle), content && wp.element.createElement(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, content), list && Icon && wp.element.createElement("ul", null, list.map((item, index) => wp.element.createElement("li", {
    key: index
  }, wp.element.createElement(Icon, {
    name: item.icon,
    color: item.color
  }), item.text)))), wp.element.createElement("div", {
    className: "rsssl-modal-footer"
  }, !footer && wp.element.createElement("div", {
    className: "rsssl-modal-footer-image"
  }, wp.element.createElement("img", {
    className: "rsssl-logo",
    src: pluginUrl + "assets/img/really-simple-ssl-logo.svg",
    alt: "Really Simple SSL"
  })), footer && wp.element.createElement("div", {
    className: "rsssl-modal-footer-feedback"
  }, footer), wp.element.createElement("div", {
    className: "rsssl-modal-footer-buttons"
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    onClick: () => setOpen(false)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Cancel", "really-simple-ssl")), buttons && wp.element.createElement(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, buttons), !buttons && wp.element.createElement(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, alternativeText && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    className: alternativeClassName,
    onClick: () => alternativeAction()
  }, alternativeText), confirmText && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    isPrimary: true,
    onClick: () => confirmAction()
  }, confirmText))))))));
};
/* harmony default export */ __webpack_exports__["default"] = (RssslModal);

/***/ }),

/***/ "../settings/src/utils/ErrorBoundary.js":
/*!**********************************************!*\
  !*** ../settings/src/utils/ErrorBoundary.js ***!
  \**********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! prop-types */ "../settings/node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_1__);



class ErrorBoundary extends react__WEBPACK_IMPORTED_MODULE_0__.Component {
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null
    };
    this.resetError = this.resetError.bind(this);
  }
  static getDerivedStateFromError(error) {
    return {
      hasError: true
    };
  }
  componentDidCatch(error, errorInfo) {
    this.setState({
      error,
      errorInfo
    });
    // You can also log the error to an error reporting service
    console.log('ErrorBoundary', error, errorInfo);
  }
  resetError() {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null
    });
  }
  render() {
    if (this.state.hasError) {
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", null, "Something went wrong."), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, this.props.fallback), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        onClick: this.resetError
      }, "Try Again"));
    }
    return this.props.children;
  }
}
ErrorBoundary.propTypes = {
  children: (prop_types__WEBPACK_IMPORTED_MODULE_1___default().node),
  fallback: (prop_types__WEBPACK_IMPORTED_MODULE_1___default().node)
};
/* harmony default export */ __webpack_exports__["default"] = (ErrorBoundary);

/***/ }),

/***/ "./src/components/Modal/RssslModal.scss":
/*!**********************************************!*\
  !*** ./src/components/Modal/RssslModal.scss ***!
  \**********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/components/Modal/checkbox.scss":
/*!********************************************!*\
  !*** ./src/components/Modal/checkbox.scss ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

}]);
//# sourceMappingURL=src_components_Modal_RssslModal_js.e1051c17500de9d47f4f.js.map