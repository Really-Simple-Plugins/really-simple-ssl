/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

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

function RssslModal() {
  const [isOpen, setOpen] = useState(false);
  const handleOpen = () => {};
  useEffect(() => {
    const showModalListener = () => {
      setOpen(true);
    };
    document.addEventListener('showRssslModalEvent', showModalListener);

    // Cleanup the listener on component unmount
    return () => {
      document.removeEventListener('showRssslModalEvent', showModalListener);
    };
  }, [isOpen]); // Add isOpen as a dependency

  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, isOpen && wp.element.createElement(Modal, {
    className: "rsssl-modal",
    title: __("Are you sure?", "really-simple-ssl"),
    onRequestClose: () => setOpen(false),
    open: handleOpen()
  }, wp.element.createElement("div", {
    className: "rsssl-modal-body"
  }, wp.element.createElement("p", null, "My Modal Content")), wp.element.createElement("div", {
    className: "rsssl-modal-footer"
  }, wp.element.createElement("img", {
    className: "rsssl-logo",
    src: rsssl_modal.plugin_url + "assets/img/really-simple-ssl-logo.svg",
    alt: "Really Simple SSL"
  }), wp.element.createElement(Button, {
    isPrimary: true,
    onClick: () => setOpen(false)
  }, __("Cancel", "really-simple-ssl")))));
}
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
/* harmony import */ var _components_Modal_RssslModal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/Modal/RssslModal */ "./src/components/Modal/RssslModal.js");

/** @jsx wp.element.createElement */


document.addEventListener('DOMContentLoaded', () => {
  const root = wp.element.createRoot(document.getElementById('rsssl-modal-root'));
  root.render(wp.element.createElement(_components_Modal_RssslModal__WEBPACK_IMPORTED_MODULE_1__["default"], null));
});
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEventListener);
} else {
  initEventListener();
}
window.showRssslModal = function () {
  const event = new Event('showRssslModalEvent');
  document.dispatchEvent(event);
};

/*
    * This event listener is used to open the modal window when the user clicks on the "Deactivate" link
 */
function initEventListener() {
  const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
  if (targetPluginLink) {
    targetPluginLink.addEventListener('click', function (e) {
      e.preventDefault();
      window.showRssslModal();
    });
  }
}
}();
/******/ })()
;
//# sourceMappingURL=index.17f696faa08080b40016.js.map