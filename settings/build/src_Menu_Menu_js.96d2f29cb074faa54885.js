"use strict";
(self["webpackChunkreally_simple_ssl"] = self["webpackChunkreally_simple_ssl"] || []).push([["src_Menu_Menu_js"],{

/***/ "./src/Menu/Menu.js":
/*!**************************!*\
  !*** ./src/Menu/Menu.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Placeholder_MenuPlaceholder__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../Placeholder/MenuPlaceholder */ "./src/Placeholder/MenuPlaceholder.js");
/* harmony import */ var _MenuItem__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./MenuItem */ "./src/Menu/MenuItem.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _MenuData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./MenuData */ "./src/Menu/MenuData.js");
/* harmony import */ var _Settings_License_LicenseData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Settings/License/LicenseData */ "./src/Settings/License/LicenseData.js");






/**
 * Menu block, rendering the entire menu
 */
const Menu = () => {
  const {
    subMenu,
    hasPremiumItems,
    subMenuLoaded
  } = (0,_MenuData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    licenseStatus
  } = (0,_Settings_License_LicenseData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  if (!subMenuLoaded) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_MenuPlaceholder__WEBPACK_IMPORTED_MODULE_1__["default"], null);
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-wizard-menu rsssl-grid-item"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item-header"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
    className: "rsssl-h4"
  }, subMenu.title)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item-content"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-wizard-menu-items"
  }, subMenu.menu_items.map((menuItem, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_MenuItem__WEBPACK_IMPORTED_MODULE_2__["default"], {
    key: "menuItem-" + i,
    menuItem: menuItem,
    isMainMenu: true
  })), hasPremiumItems && !rsssl_settings.is_premium && licenseStatus !== 'valid' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-premium-menu-item"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    target: "_blank",
    href: rsssl_settings.upgrade_link,
    className: "button button-black"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Upgrade', 'really-simple-ssl')))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item-footer"
  }));
};
/* harmony default export */ __webpack_exports__["default"] = (Menu);

/***/ }),

/***/ "./src/Menu/MenuItem.js":
/*!******************************!*\
  !*** ./src/Menu/MenuItem.js ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _MenuData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./MenuData */ "./src/Menu/MenuData.js");



const MenuItem = props => {
  const {
    selectedSubMenuItem,
    selectedMainMenuItem,
    subMenu,
    menu
  } = (0,_MenuData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  const menuIsSelected = isSelectedMenuItem(selectedSubMenuItem, props.menuItem);
  const ensureArray = data => {
    return Array.isArray(data) ? data : [data];
  };
  let menuClass = menuIsSelected ? ' rsssl-active' : '';
  menuClass += props.menuItem.featured ? ' rsssl-featured' : '';
  menuClass += props.menuItem.new ? ' rsssl-new' : '';
  menuClass += props.menuItem.premium && !rsssl_settings.pro_plugin_active ? ' rsssl-premium' : '';
  let menuLink = props.menuItem.directLink || '#' + selectedMainMenuItem + '/' + props.menuItem.id;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, props.menuItem.visible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, props.isMainMenu ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-main-menu"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-menu-item" + menuClass
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: menuLink
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, props.menuItem.title), props.menuItem.featured && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-menu-item-beta-pill"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Beta', 'really-simple-ssl')), props.menuItem.new && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-menu-item-new-pill"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('New', 'really-simple-ssl'))))) : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-menu-item" + menuClass
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: menuLink
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, props.menuItem.title), props.menuItem.featured && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-menu-item-beta-pill"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Beta', 'really-simple-ssl')), props.menuItem.new && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-menu-item-new-pill"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('New', 'really-simple-ssl')))), props.menuItem.menu_items && menuIsSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-submenu-item"
  }, ensureArray(props.menuItem.menu_items).map((subMenuItem, i) => subMenuItem.visible && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(MenuItem, {
    key: "submenuItem" + i,
    menuItem: subMenuItem,
    isMainMenu: false
  })))));
};
/* harmony default export */ __webpack_exports__["default"] = (MenuItem);

/**
 * Utility function to check if selected menu item is the current menu item or a child of the current menu item
 * @param selectedSubMenuItem
 * @param menuItem
 * @returns {boolean}
 */
const isSelectedMenuItem = (selectedSubMenuItem, menuItem) => {
  if (selectedSubMenuItem === menuItem.id) {
    return true;
  }
  if (menuItem.menu_items) {
    for (const item of menuItem.menu_items) {
      if (item.id === selectedSubMenuItem) {
        return true;
      }
    }
  }
  return false;
};

/***/ }),

/***/ "./src/Placeholder/MenuPlaceholder.js":
/*!********************************************!*\
  !*** ./src/Placeholder/MenuPlaceholder.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

const MenuPlaceholder = () => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-wizard-menu rsssl-grid-item rsssl-menu-placeholder"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item-header"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
    className: "rsssl-h4"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item-content"
  }));
};
/* harmony default export */ __webpack_exports__["default"] = (MenuPlaceholder);

/***/ }),

/***/ "./src/Settings/License/LicenseData.js":
/*!*********************************************!*\
  !*** ./src/Settings/License/LicenseData.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");

const UseLicenseData = (0,zustand__WEBPACK_IMPORTED_MODULE_0__.create)((set, get) => ({
  licenseStatus: rsssl_settings.licenseStatus,
  setLicenseStatus: licenseStatus => set(state => ({
    licenseStatus
  }))
}));
/* harmony default export */ __webpack_exports__["default"] = (UseLicenseData);

/***/ })

}]);
//# sourceMappingURL=src_Menu_Menu_js.96d2f29cb074faa54885.js.map