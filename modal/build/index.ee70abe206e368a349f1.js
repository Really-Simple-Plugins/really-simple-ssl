/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/components/DeactivationModal/DeactivationModal.js":
/*!***************************************************************!*\
  !*** ./src/components/DeactivationModal/DeactivationModal.js ***!
  \***************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Modal_RssslModal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../Modal/RssslModal */ "./src/components/Modal/RssslModal.js");


const {
  __
} = wp.i18n;
const {
  useState,
  useEffect
} = wp.element;
const DeactivationModal = () => {
  const [isOpen, setOpen] = useState(true);
  onConfirm = () => {
    const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
    //click the targetPluginLink
    targetPluginLink.click();
  };
  useEffect(() => {
    // Add an event listener to elements with the "my-link" class

    const handleClick = event => {
      event.preventDefault();
      setOpen(true);
    };

    // Attach the click event listener to each link element
    const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
    targetPluginLink.addEventListener('click', handleClick);

    // Clean up the event listeners when the component unmounts
    return () => {
      targetPluginLink.removeEventListener('click', handleClick);
    };
  }, []);
  let content = "TEST";
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Modal_RssslModal__WEBPACK_IMPORTED_MODULE_1__["default"], {
    title: __("Are you sure?", "really-simple-ssl"),
    onConfirm: onConfirm(),
    content: content,
    isOpen: isOpen,
    setOpen: setOpen
  }));
};
/* harmony default export */ __webpack_exports__["default"] = (DeactivationModal);

/***/ }),

/***/ "./src/components/Modal/RssslModal.js":
/*!********************************************!*\
  !*** ./src/components/Modal/RssslModal.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _RssslModal_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./RssslModal.scss */ "./src/components/Modal/RssslModal.scss");

/** @jsx wp.element.createElement */
const {
  Modal,
  Button
} = wp.components;
const {
  useState,
  useEffect
} = wp.element;
const {
  __
} = wp.i18n;

const RssslModal = ({
  title,
  content,
  confirmBtnTxt,
  onConfirm,
  isOpen,
  setOpen
}) => {
  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, isOpen && wp.element.createElement("div", {
    className: "rsssl-modal"
  }, wp.element.createElement(Modal, {
    title: title,
    onRequestClose: () => setOpen(false),
    open: isOpen
  }, wp.element.createElement("div", {
    className: "rsssl-modal-body"
  }, content), wp.element.createElement("div", {
    className: "rsssl-modal-footer"
  }, wp.element.createElement("div", null, wp.element.createElement("img", {
    className: "rsssl-logo",
    src: rsssl_modal.plugin_url + "assets/img/really-simple-ssl-logo.svg",
    alt: "Really Simple SSL"
  })), wp.element.createElement("div", null, wp.element.createElement(Button, {
    isPrimary: true,
    onClick: () => setOpen(false)
  }, __("Cancel", "really-simple-ssl")))))));
};
/* harmony default export */ __webpack_exports__["default"] = (RssslModal);

/***/ }),

/***/ "./src/components/Modal/RssslModal.scss":
/*!**********************************************!*\
  !*** ./src/components/Modal/RssslModal.scss ***!
  \**********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

module.exports = window["wp"]["element"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_DeactivationModal_DeactivationModal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/DeactivationModal/DeactivationModal */ "./src/components/DeactivationModal/DeactivationModal.js");

/** @jsx wp.element.createElement */


document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('rsssl-modal-root');
  if (container) {
    console.log("found container");
    if (_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot) {
      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot)(container).render(wp.element.createElement(_components_DeactivationModal_DeactivationModal__WEBPACK_IMPORTED_MODULE_1__["default"], null));
    } else {
      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)(wp.element.createElement(_components_DeactivationModal_DeactivationModal__WEBPACK_IMPORTED_MODULE_1__["default"], null), container);
    }
  }
});
/*
    * This event listener is used to open the modal window when the user clicks on the "Deactivate" link
 */
// function initEventListener() {
//     const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
//     if (targetPluginLink) {
//         targetPluginLink.addEventListener('click', function(e) {
//             e.preventDefault();
//             window.showRssslModal();
//         });
//     }
// }
}();
/******/ })()
;
//# sourceMappingURL=index.ee70abe206e368a349f1.js.map