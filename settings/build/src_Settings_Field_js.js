"use strict";
(globalThis["webpackChunkreally_simple_ssl"] = globalThis["webpackChunkreally_simple_ssl"] || []).push([["src_Settings_Field_js"],{

/***/ "./src/Dashboard/Progress/ProgressData.js":
/*!************************************************!*\
  !*** ./src/Dashboard/Progress/ProgressData.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");


const useProgress = (0,zustand__WEBPACK_IMPORTED_MODULE_1__.create)((set, get) => ({
  filter: 'all',
  progressText: '',
  notices: [],
  error: false,
  percentageCompleted: 0,
  progressLoaded: false,
  setFilter: filter => {
    sessionStorage.rsssl_task_filter = filter;
    set(state => ({
      filter
    }));
  },
  fetchFilter: () => {
    if (typeof Storage !== "undefined" && sessionStorage.rsssl_task_filter) {
      let filter = sessionStorage.rsssl_task_filter;
      set(state => ({
        filter: filter
      }));
    }
  },
  getProgressData: async () => {
    const {
      percentage,
      text,
      notices,
      error
    } = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.runTest('progressData', 'refresh').then(response => {
      return response;
    });
    set(state => ({
      notices: notices,
      percentageCompleted: percentage,
      progressText: text,
      progressLoaded: true,
      error: error
    }));
  },
  dismissNotice: async noticeId => {
    let notices = get().notices;
    notices = notices.filter(function (notice) {
      return notice.id !== noticeId;
    });
    set(state => ({
      notices: notices
    }));
    const {
      percentage
    } = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.runTest('dismiss_task', noticeId);
    set({
      percentageCompleted: percentage
    });
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useProgress);

/***/ }),

/***/ "./src/Dashboard/TaskElement.js":
/*!**************************************!*\
  !*** ./src/Dashboard/TaskElement.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _Progress_ProgressData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Progress/ProgressData */ "./src/Dashboard/Progress/ProgressData.js");
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../Menu/MenuData */ "./src/Menu/MenuData.js");









const TaskElement = props => {
  const {
    dismissNotice
  } = (0,_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_7__["default"])();
  const {
    getField,
    setHighLightField,
    fetchFieldsData
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  const {
    setSelectedSubMenuItem
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_8__["default"])();
  const handleClick = async () => {
    setHighLightField(props.notice.output.highlight_field_id);
    let highlightField = getField(props.notice.output.highlight_field_id);
    await setSelectedSubMenuItem(highlightField.menu_id);
  };
  const handleClearCache = cache_id => {
    let data = {};
    data.cache_id = cache_id;
    _utils_api__WEBPACK_IMPORTED_MODULE_4__.doAction('clear_cache', data).then(response => {
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Re-started test', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_clear_cache',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_5__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_clear_cache');
      });
      fetchFieldsData();
    });
  };
  let notice = props.notice;
  let premium = notice.output.icon === 'premium';
  //treat links to rsssl.com and internal links different.
  let urlIsExternal = notice.output.url && notice.output.url.indexOf('really-simple-ssl.com') !== -1;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-task-element"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: 'rsssl-task-status rsssl-' + notice.output.icon
  }, notice.output.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "rsssl-task-message",
    dangerouslySetInnerHTML: {
      __html: notice.output.msg
    }
  }), urlIsExternal && notice.output.url && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    target: "_blank",
    href: notice.output.url
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("More info", "really-simple-ssl")), notice.output.clear_cache_id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-task-enable button button-secondary",
    onClick: () => handleClearCache(notice.output.clear_cache_id)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Re-check", "really-simple-ssl")), !premium && !urlIsExternal && notice.output.url && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "rsssl-task-enable button button-secondary",
    href: notice.output.url
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("View", "really-simple-ssl")), !premium && notice.output.highlight_field_id && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-task-enable button button-secondary",
    onClick: () => handleClick()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("View", "really-simple-ssl")), notice.output.plusone && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-plusone"
  }, "1"), notice.output.dismissible && notice.output.status !== 'completed' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-task-dismiss"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    onClick: e => dismissNotice(notice.id)
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_2__["default"], {
    name: "times"
  }))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TaskElement);

/***/ }),

/***/ "./src/LetsEncrypt/Activate.js":
/*!*************************************!*\
  !*** ./src/LetsEncrypt/Activate.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Onboarding_Onboarding__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../Onboarding/Onboarding */ "./src/Onboarding/Onboarding.js");


const Activate = () => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-lets-encrypt-tests"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Onboarding_Onboarding__WEBPACK_IMPORTED_MODULE_1__["default"], null));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Activate);

/***/ }),

/***/ "./src/LetsEncrypt/Directories.js":
/*!****************************************!*\
  !*** ./src/LetsEncrypt/Directories.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var react_use__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! react-use */ "./node_modules/react-use/esm/useUpdateEffect.js");
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../Menu/MenuData */ "./src/Menu/MenuData.js");










const Directories = props => {
  const {
    addHelpNotice,
    updateField,
    setChangedField,
    saveFields,
    fetchFieldsData
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_7__["default"])();
  const {
    setSelectedSubMenuItem
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_8__["default"])();
  let action = props.action;
  (0,react_use__WEBPACK_IMPORTED_MODULE_9__["default"])(() => {
    if (action && action.action === 'challenge_directory_reachable' && action.status === 'error') {
      addHelpNotice(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The challenge directory is used to verify the domain ownership.", "really-simple-ssl"));
    }
    if (action && action.action === 'check_key_directory' && action.status === 'error') {
      addHelpNotice(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The key directory is needed to store the generated keys.", "really-simple-ssl") + ' ' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("By placing it outside the root folder, it is not publicly accessible.", "really-simple-ssl"));
    }
    if (action && action.action === 'check_certs_directory' && action.status === 'error') {
      addHelpNotice(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The certificate will get stored in this directory.", "really-simple-ssl") + ' ' + (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("By placing it outside the root folder, it is not publicly accessible.", "really-simple-ssl"));
    }
  });
  if (!action) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  const handleSwitchToDNS = async () => {
    updateField('verification_type', 'dns');
    setChangedField('verification_type', 'dns');
    await saveFields(true, true);
    await _utils_api__WEBPACK_IMPORTED_MODULE_2__.runLetsEncryptTest('update_verification_type', 'dns').then(response => {
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switched to DNS', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_switched_to_dns',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_4__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_switched_to_dns');
      });
    });
    await setSelectedSubMenuItem('le-dns-verification');
    await fetchFieldsData('le-directories');
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, action.status === 'error' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Next step", "really-simple-ssl")), action.status === 'error' && action.action === 'challenge_directory_reachable' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If the challenge directory cannot be created, or is not reachable, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to DNS verification', 'really-simple-ssl'))), rsssl_settings.hosting_dashboard === 'cpanel' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_5__["default"], {
    target: "_blank",
    text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If you also want to secure subdomains like mail.domain.com, cpanel.domain.com, you have to use the %sDNS%s challenge.", "really-simple-ssl"),
    url: "https://really-simple-ssl.com/lets-encrypt-authorization-with-dns"
  }), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Please note that auto-renewal with a DNS challenge might not be possible.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to DNS verification', 'really-simple-ssl'))), action.status === 'error' && action.action === 'check_challenge_directory' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Create a challenge directory", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Navigate in FTP or File Manager to the root of your WordPress installation:", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Create a folder called “.well-known”', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Inside the folder called “.well-known” create a new folder called “acme-challenge”, with 644 writing permissions.', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Click the refresh button.', 'really-simple-ssl'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Or you can switch to DNS verification", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If the challenge directory cannot be created, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to DNS verification', 'really-simple-ssl'))), action.status === 'error' && action.action === 'check_key_directory' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Create a key directory", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Create a folder called “ssl”', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Inside the folder called “ssl” create a new folder called “keys”, with 644 writing permissions.', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Click the refresh button.', 'really-simple-ssl')))), action.status === 'error' && action.action === 'check_certs_directory' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Create a certs directory", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Create a folder called “ssl”', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Inside the folder called “ssl” create a new folder called “certs”, with 644 writing permissions.', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    className: "rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Click the refresh button.', 'really-simple-ssl')))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Directories);

/***/ }),

/***/ "./src/LetsEncrypt/DnsVerification.js":
/*!********************************************!*\
  !*** ./src/LetsEncrypt/DnsVerification.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var react_use__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! react-use */ "./node_modules/react-use/esm/useUpdateEffect.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Menu/MenuData */ "./src/Menu/MenuData.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");











const DnsVerification = props => {
  const {
    fields,
    addHelpNotice,
    updateField,
    setChangedField,
    saveFields,
    fetchFieldsData,
    getFieldValue
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const {
    selectedSubMenuItem,
    setSelectedSubMenuItem
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  const [tokens, setTokens] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  let action = props.action;
  (0,react_use__WEBPACK_IMPORTED_MODULE_9__["default"])(() => {
    if (action && action.action === 'challenge_directory_reachable' && action.status === 'error') {
      addHelpNotice(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The challenge directory is used to verify the domain ownership.", "really-simple-ssl"));
    }
    let newTokens = action ? action.output : false;
    if (typeof newTokens === "undefined" || newTokens.length === 0) {
      newTokens = false;
    }
    if (newTokens) {
      setTokens(newTokens);
    }
  });
  const handleSwitchToDir = async () => {
    await setSelectedSubMenuItem('le-directories');
    await updateField('verification_type', 'dir');
    await setChangedField('verification_type', 'dir');
    await saveFields(true, true);
    await _utils_api__WEBPACK_IMPORTED_MODULE_7__.runLetsEncryptTest('update_verification_type', 'dir').then(response => {
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switched to Directory', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_switched_to_dns',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_8__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.dispatch)('core/notices').removeNotice('rsssl_switched_to_dns');
      });
    });
    await fetchFieldsData('le-directories');
  };
  const handleSwitchToDNS = async () => {
    await _utils_api__WEBPACK_IMPORTED_MODULE_7__.runLetsEncryptTest('update_verification_type', 'dns').then(response => {
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switched to DNS', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_switched_to_dns',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_8__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.dispatch)('core/notices').removeNotice('rsssl_switched_to_dns');
      });
    });
    await setSelectedSubMenuItem('le-dns-verification');
  };
  let verificationType = getFieldValue('verification_type');
  if (verificationType === 'dir') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, tokens && tokens.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Next step", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Add the following token as text record to your DNS records. We recommend to use a short TTL during installation, in case you need to change it.", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_3__["default"], {
    target: "_blank",
    text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Read more", "really-simple-ssl"),
    url: "https://really-simple-ssl.com/how-to-add-a-txt-record-to-dns"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-text-records"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-domain"
  }, "@/", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("domain", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-field"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Value", "really-simple-ssl"))), tokens.map((tokenData, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-"
  }, "_acme-challenge.", tokenData.domain), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-dns-field rsssl-selectable"
  }, tokenData.token))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("DNS verification active. You can switch back to directory verification here.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
    variant: "secondary",
    onClick: () => handleSwitchToDir()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Switch to directory verification', 'really-simple-ssl'))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DnsVerification);

/***/ }),

/***/ "./src/LetsEncrypt/Generation.js":
/*!***************************************!*\
  !*** ./src/LetsEncrypt/Generation.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");








const Generation = props => {
  let action = props.action;
  if (!action) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  const handleSkipDNS = () => {
    return _utils_api__WEBPACK_IMPORTED_MODULE_2__.runLetsEncryptTest('skip_dns_check').then(response => {
      props.restartTests();
      const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Skip DNS verification', 'really-simple-ssl'), {
        __unstableHTML: true,
        id: 'rsssl_skip_dns',
        type: 'snackbar',
        isDismissible: true
      }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_4__["default"])(3000)).then(response => {
        (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_skip_dns');
      });
    });
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, action.status === 'error' && action.action === 'verify_dns' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("We could not check the DNS records. If you just added the record, please check in a few minutes.", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_5__["default"], {
    target: "_blank",
    text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("You can manually check the DNS records in an %sonline tool%s.", "really-simple-ssl"),
    url: "https://mxtoolbox.com/SuperTool.aspx"
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("If you're sure it's set correctly, you can click the button to skip the DNS check.", "really-simple-ssl"), "\xA0"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    variant: "secondary",
    onClick: () => handleSkipDNS()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Skip DNS check', 'really-simple-ssl'))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Generation);

/***/ }),

/***/ "./src/LetsEncrypt/Installation.js":
/*!*****************************************!*\
  !*** ./src/LetsEncrypt/Installation.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var react_use__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react-use */ "./node_modules/react-use/esm/useUpdateEffect.js");
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");








const Installation = props => {
  const {
    addHelpNotice
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const [installationData, setInstallationData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  let action = props.action;
  (0,react_use__WEBPACK_IMPORTED_MODULE_6__["default"])(() => {
    if (action && action.status === 'warning' && installationData && installationData.generated_by_rsssl) {
      addHelpNotice(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("This is the certificate, which you need to install in your hosting dashboard.", "really-simple-ssl"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate (CRT)", "really-simple-ssl"));
      addHelpNotice(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The private key can be uploaded or pasted in the appropriate field on your hosting dashboard.", "really-simple-ssl"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Private Key (KEY)", "really-simple-ssl"));
      addHelpNotice(props.field.id, 'default', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The CA Bundle will sometimes be automatically detected. If not, you can use this file.", "really-simple-ssl"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate Authority Bundle (CABUNDLE)", "really-simple-ssl"));
    }
    if (action && (action.status === 'error' || action.status === 'warning')) {
      _utils_api__WEBPACK_IMPORTED_MODULE_2__.runLetsEncryptTest('installation_data').then(response => {
        if (response) {
          setInstallationData(response.output);
        }
      });
    }
  });
  const handleCopyAction = type => {
    let success;
    let data = document.querySelector('.rsssl-' + type).innerText;
    const el = document.createElement('textarea');
    el.value = data; //str is your string to copy
    document.body.appendChild(el);
    el.select();
    try {
      success = document.execCommand("copy");
    } catch (e) {
      success = false;
    }
    document.body.removeChild(el);
    const notice = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').createNotice('success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Copied!', 'really-simple-ssl'), {
      __unstableHTML: true,
      id: 'rsssl_copied_data',
      type: 'snackbar',
      isDismissible: true
    }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_4__["default"])(3000)).then(response => {
      (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.dispatch)('core/notices').removeNotice('rsssl_copied_data');
    });
  };
  if (!action) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  if (!installationData) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-test-results"
  }, !installationData.generated_by_rsssl && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The certificate is not generated by Really Simple SSL, so there are no installation files here", "really-simple-ssl")), installationData.generated_by_rsssl && action.status === 'warning' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Next step", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-template-intro"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Install your certificate.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate (CRT)", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-certificate-data rsssl-certificate",
    id: "rsssl-certificate"
  }, installationData.certificate_content), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: installationData.download_url + "&type=certificate",
    className: "button button-secondary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Download", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    onClick: e => handleCopyAction('certificate'),
    className: "button button-primary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Copy content", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Private Key (KEY)", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-certificate-data rsssl-key",
    id: "rsssl-key"
  }, installationData.key_content), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: installationData.download_url + "&type=private_key",
    className: "button button-secondary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Download", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button-primary",
    onClick: e => handleCopyAction('key')
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Copy content", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Certificate Authority Bundle (CABUNDLE)", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-certificate-data rsssl-cabundle",
    id: "rsssl-cabundle"
  }, installationData.ca_bundle_content), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: installationData.download_url + "&type=intermediate",
    className: "button button-secondary"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Download", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: "button button-primary",
    onClick: e => handleCopyAction('cabundle')
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Copy content", "really-simple-ssl"))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Installation);

/***/ }),

/***/ "./src/LetsEncrypt/LetsEncrypt.js":
/*!****************************************!*\
  !*** ./src/LetsEncrypt/LetsEncrypt.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _utils_sleeper__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/sleeper */ "./src/utils/sleeper.js");
/* harmony import */ var _Directories__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Directories */ "./src/LetsEncrypt/Directories.js");
/* harmony import */ var _DnsVerification__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./DnsVerification */ "./src/LetsEncrypt/DnsVerification.js");
/* harmony import */ var _Generation__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Generation */ "./src/LetsEncrypt/Generation.js");
/* harmony import */ var _Activate__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Activate */ "./src/LetsEncrypt/Activate.js");
/* harmony import */ var _Installation__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Installation */ "./src/LetsEncrypt/Installation.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _letsEncryptData__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./letsEncryptData */ "./src/LetsEncrypt/letsEncryptData.js");













const LetsEncrypt = props => {
  const {
    handleNextButtonDisabled,
    getFieldValue
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_10__["default"])();
  const {
    actionsList,
    setActionsList,
    setActionsListItem,
    setActionsListProperty,
    actionIndex,
    setActionIndex,
    attemptCount,
    setAttemptCount,
    progress,
    setProgress,
    refreshTests,
    setRefreshTests
  } = (0,_letsEncryptData__WEBPACK_IMPORTED_MODULE_11__["default"])();
  const sleep = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(1000);
  const intervalId = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const previousActionIndex = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(-1);
  const maxIndex = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(1);
  const refProgress = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(0);
  const lastAction = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)({});
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    reset();
  }, [props.field.id]);
  const getActions = () => {
    let propActions = props.field.actions;
    if (props.field.id === 'generation') {
      propActions = adjustActionsForDNS(propActions);
    }
    maxIndex.current = propActions.length;
    return propActions;
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (actionsList.length > 0 && actionIndex === -1) {
      setActionIndex(0);
      runTest(0, 0);
    }
  }, [actionsList]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    // startInterval();
  }, []);
  const startInterval = () => {
    intervalId.current = setInterval(() => {
      if (refProgress.current < 100) {
        setProgress(refProgress.current + 0.2);
      }
    }, 100);
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    previousActionIndex.current = actionIndex;
    setProgress(100 / maxIndex.current * actionIndex);

    //ensure that progress does not get to 100 when retries are still running
    let currentAction = actionsList[actionIndex];
    if (currentAction && currentAction.do === 'retry' && attemptCount > 1) {
      setProgress(90);
    }
  }, [actionIndex]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    refProgress.current = progress;
  }, [progress]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (refreshTests) {
      setRefreshTests(false);
      reset();
      actionsList.forEach(function (action, i) {
        setActionsListProperty(i, 'status', 'inactive');
      });
    }
  }, [refreshTests]);
  const statuses = {
    'inactive': {
      'icon': 'circle-times',
      'color': 'grey'
    },
    'warning': {
      'icon': 'circle-times',
      'color': 'orange'
    },
    'error': {
      'icon': 'circle-times',
      'color': 'red'
    },
    'success': {
      'icon': 'circle-check',
      'color': 'green'
    }
  };
  const reset = () => {
    // clearInterval(intervalId.current);
    // startInterval();
    handleNextButtonDisabled(true);
    setActionsList(getActions());
    setProgress(0);
    refProgress.current = 0;
    setActionIndex(-1);
    previousActionIndex.current = -1;
  };
  const adjustActionsForDNS = actions => {
    //find verification_type
    let verification_type = getFieldValue('verification_type');
    if (!verification_type) verification_type = 'dir';
    if (verification_type === 'dns') {
      //check if dns verification already is added
      let dnsVerificationAdded = false;
      actions.forEach(function (action, i) {
        if (action.action === "verify_dns") {
          dnsVerificationAdded = true;
        }
      });

      //find bundle index
      let create_bundle_index = -1;
      actions.forEach(function (action, i) {
        if (action.action === "create_bundle_or_renew") {
          create_bundle_index = i;
        }
      });
      if (!dnsVerificationAdded && create_bundle_index > 0) {
        //store create bundle action
        let actionsCopy = [...actions];
        let createBundleAction = actionsCopy[create_bundle_index];
        //overwrite create bundle action
        let newAction = {};
        newAction.action = 'verify_dns';
        newAction.description = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__.__)("Verifying DNS records...", "really-simple-ssl");
        newAction.attempts = 2;
        actionsCopy[create_bundle_index] = newAction;
        actionsCopy.push(createBundleAction);
        actions = actionsCopy;
      }
    }
    return actions;
  };
  const processTestResult = async (action, newActionIndex) => {
    // clearInterval(intervalId.current);

    if (action.status === 'success') {
      setAttemptCount(0);
    } else {
      if (!Number.isInteger(action.attemptCount)) {
        setAttemptCount(0);
      }
      setAttemptCount(attemptCount + 1);
    }

    //used for dns verification actions
    let event = new CustomEvent('rsssl_le_response', {
      detail: action
    });
    document.dispatchEvent(event);
    //if all tests are finished with success
    //finalize happens when halfway through our tests it's finished. We can skip all others.
    if (action.do === 'finalize') {
      actionsList.forEach(function (action, i) {
        if (i > newActionIndex) {
          setActionsListProperty(i, 'hide', true);
        }
      });
      setActionIndex(maxIndex.current + 1);
      handleNextButtonDisabled(false);
    } else if (action.do === 'continue' || action.do === 'skip') {
      //new action, so reset the attempts count
      setAttemptCount(1);
      //skip:  drop previous completely, skip to next.
      if (action.do === 'skip') {
        setActionsListProperty(newActionIndex, 'hide', true);
      }
      //move to next action, but not if we're already on the max
      if (maxIndex.current - 1 > newActionIndex) {
        setActionIndex(newActionIndex + 1);
        await runTest(newActionIndex + 1);
      } else {
        setActionIndex(newActionIndex + 1);
        handleNextButtonDisabled(false);
      }
    } else if (action.do === 'retry') {
      if (attemptCount >= action.attempts) {
        setActionIndex(maxIndex.current);
      } else {
        setActionIndex(newActionIndex);
        await runTest(newActionIndex);
      }
    } else if (action.do === 'stop') {
      setActionIndex(maxIndex.current);
    }
  };
  const runTest = async newActionIndex => {
    let currentAction = {
      ...actionsList[newActionIndex]
    };
    if (!currentAction) return;
    let test = currentAction.action;
    const startTime = new Date();
    await _utils_api__WEBPACK_IMPORTED_MODULE_1__.runLetsEncryptTest(test, props.field.id).then(response => {
      const endTime = new Date();
      let timeDiff = endTime - startTime; //in ms
      const elapsedTime = Math.round(timeDiff);
      currentAction.status = response.status ? response.status : 'inactive';
      currentAction.hide = false;
      currentAction.description = response.message;
      currentAction.do = response.action;
      currentAction.output = response.output ? response.output : false;
      sleep.current = 500;
      if (elapsedTime < 1500) {
        sleep.current = 1500 - elapsedTime;
      }
      setActionsListItem(newActionIndex, currentAction);
    }).then((0,_utils_sleeper__WEBPACK_IMPORTED_MODULE_2__["default"])(sleep.current)).then(() => {
      processTestResult(currentAction, newActionIndex);
    });
  };
  const getStyles = newProgress => {
    return Object.assign({}, {
      width: newProgress + "%"
    });
  };
  const getStatusIcon = action => {
    if (!statuses.hasOwnProperty(action.status)) {
      return statuses['inactive'].icon;
    }
    return statuses[action.status].icon;
  };
  const getStatusColor = action => {
    if (!statuses.hasOwnProperty(action.status)) {
      return statuses['inactive'].color;
    }
    return statuses[action.status].color;
  };
  if (!props.field.actions) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  let progressCopy = progress;
  if (maxIndex.current === actionIndex + 1) {
    progressCopy = 100;
  }

  //filter out skipped actions
  let actionsOutput = actionsList.filter(action => action.hide !== true);
  //ensure the sub components have an action to look at, also if the action has been dropped after last test.
  let action = actionsList[actionIndex];
  if (action) {
    lastAction.current = action;
  } else {
    action = lastAction.current;
  }
  let progressBarColor = action.status === 'error' ? 'rsssl-orange' : '';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-lets-encrypt-tests"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-progress-bar"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-progress"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'rsssl-bar ' + progressBarColor,
    style: getStyles(progressCopy)
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl_letsencrypt_container rsssl-progress-container field-group"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, actionsOutput.map((action, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: "action-" + i
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_9__["default"], {
    name: getStatusIcon(action),
    color: getStatusColor(action)
  }), action.do === 'retry' && attemptCount >= 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_8__.__)("Attempt %s.", "really-simple-ssl").replace('%s', attemptCount), " "), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    dangerouslySetInnerHTML: {
      __html: action.description
    }
  }))))), props.field.id === 'directories' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Directories__WEBPACK_IMPORTED_MODULE_3__["default"], {
    field: props.field,
    action: action
  }), props.field.id === 'dns-verification' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_DnsVerification__WEBPACK_IMPORTED_MODULE_4__["default"], {
    field: props.field,
    action: action
  }), props.field.id === 'generation' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Generation__WEBPACK_IMPORTED_MODULE_5__["default"], {
    field: props.field,
    action: action
  }), props.field.id === 'installation' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Installation__WEBPACK_IMPORTED_MODULE_7__["default"], {
    field: props.field,
    action: action
  }), props.field.id === 'activate' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Activate__WEBPACK_IMPORTED_MODULE_6__["default"], {
    field: props.field,
    action: action
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (LetsEncrypt);

/***/ }),

/***/ "./src/Modal/ModalControl.js":
/*!***********************************!*\
  !*** ./src/Modal/ModalControl.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _ModalData__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ModalData */ "./src/Modal/ModalData.js");



/**
 * Button to open the modal
 * @param props
 * @returns {JSX.Element}
 * @constructor
 */
const ModalControl = props => {
  const {
    handleModal
  } = (0,_ModalData__WEBPACK_IMPORTED_MODULE_1__["default"])();
  const onClickHandler = () => {
    handleModal(true, props.modalData, props.item);
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    className: "button button-" + props.btnStyle,
    onClick: e => onClickHandler(e)
  }, props.btnText);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ModalControl);

/***/ }),

/***/ "./src/Onboarding/Onboarding.js":
/*!**************************************!*\
  !*** ./src/Onboarding/Onboarding.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Menu/MenuData */ "./src/Menu/MenuData.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../Dashboard/Progress/ProgressData */ "./src/Dashboard/Progress/ProgressData.js");
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Settings_RiskConfiguration_RiskData__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../Settings/RiskConfiguration/RiskData */ "./src/Settings/RiskConfiguration/RiskData.js");












const Onboarding = props => {
  const {
    fetchFieldsData,
    updateField,
    updateFieldsData,
    getFieldValue
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_7__["default"])();
  const {
    getProgressData
  } = (0,_Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_8__["default"])();
  const {
    fetchVulnerabilities
  } = (0,_Settings_RiskConfiguration_RiskData__WEBPACK_IMPORTED_MODULE_10__["default"])();
  const {
    dismissModal,
    actionHandler,
    getSteps,
    error,
    certificateValid,
    networkwide,
    sslEnabled,
    dataLoaded,
    processing,
    setProcessing,
    steps,
    currentStep,
    currentStepIndex,
    setCurrentStepIndex,
    overrideSSL,
    setOverrideSSL,
    networkActivationStatus,
    setNetworkActivationStatus,
    networkProgress,
    refreshSSLStatus,
    activateSSLNetworkWide,
    email,
    setEmail,
    saveEmail,
    includeTips,
    setIncludeTips,
    sendTestEmail,
    setSendTestEmail
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_9__["default"])();
  const {
    setSelectedMainMenuItem,
    selectedMainMenuItem
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  const statuses = {
    'inactive': {
      'icon': 'info',
      'color': 'orange'
    },
    'warning': {
      'icon': 'circle-times',
      'color': 'orange'
    },
    'error': {
      'icon': 'circle-times',
      'color': 'red'
    },
    'success': {
      'icon': 'circle-check',
      'color': 'green'
    },
    'processing': {
      'icon': 'file-download',
      'color': 'red'
    }
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (networkwide && networkActivationStatus === 'main_site_activated') {
      activateSSLNetworkWide();
    }
  }, [networkActivationStatus, networkProgress]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const run = async () => {
      await getSteps(false);
      if (dataLoaded && sslEnabled && currentStepIndex === 0) {
        setCurrentStepIndex(1);
      }
      if (getFieldValue('notifications_email_address') !== '' && email === '') {
        setEmail(getFieldValue('notifications_email_address'));
      }
    };
    run();
  }, []);

  //ensure all fields are updated, and progress is retrieved again
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const runUpdate = async () => {
      //in currentStep.items, find item with id 'hardening'
      //if it has status 'completed' fetchFieldsData again.
      if (currentStep && currentStep.items) {
        let hardeningItem = currentStep.items.find(item => {
          return item.id === 'hardening';
        });
        if (hardeningItem && hardeningItem.status === 'success') {
          await fetchFieldsData('hardening');
          await getProgressData();
          await fetchVulnerabilities();
        }
      }
    };
    runUpdate();
  }, [currentStep]);
  const activateSSL = () => {
    setProcessing(true);
    _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest('activate_ssl').then(async response => {
      setProcessing(false);
      setCurrentStepIndex(currentStepIndex + 1);
      //change url to https, after final check
      if (response.success) {
        if (response.site_url_changed) {
          window.location.reload();
        } else {
          if (networkwide) {
            setNetworkActivationStatus('main_site_activated');
          }
        }
      }
    }).then(async () => {
      await getProgressData();
      await fetchFieldsData(selectedMainMenuItem);
    });
  };
  const parseStepItems = items => {
    return items && items.map((item, index) => {
      let {
        title,
        description,
        current_action,
        action,
        status,
        button,
        id,
        read_more
      } = item;
      if (id === 'ssl_enabled' && networkwide) {
        if (networkProgress >= 100) {
          status = 'success';
          title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("SSL has been activated network wide", "really-simple-ssl");
        } else {
          status = 'processing';
          title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Processing activation of subsites networkwide", "really-simple-ssl");
        }
      }
      const statusIcon = item.status !== 'success' && item.is_plugin && item.current_action === 'none' ? 'empty' : statuses[status].icon;
      const statusColor = statuses[status].color;
      const currentActions = {
        'activate_setting': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Activating...', "really-simple-ssl"),
        'activate': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Activating...', "really-simple-ssl"),
        'install_plugin': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Installing...', "really-simple-ssl"),
        'error': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Failed', "really-simple-ssl"),
        'completed': (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Finished', "really-simple-ssl")
      };
      let buttonTitle = '';
      if (button) {
        buttonTitle = button;
        if (current_action !== 'none') {
          buttonTitle = currentActions[current_action];
          if (current_action === 'failed') {
            buttonTitle = currentActions['error'];
          }
        }
      }
      let showLink = button && button === buttonTitle;
      let showAsPlugin = item.status !== 'success' && item.is_plugin && item.current_action === 'none';
      let isPluginClass = showAsPlugin ? 'rsssl-is-plugin' : '';
      title = showAsPlugin ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("b", null, title) : title;
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
        key: "pluginItem-" + index,
        className: isPluginClass
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
        name: statusIcon,
        color: statusColor
      }), title, description && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0-\xA0", description), id === 'ssl_enabled' && networkwide && networkActivationStatus === 'main_site_activated' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0-\xA0", networkProgress < 100 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("working", "really-simple-ssl"), "\xA0", networkProgress, "%"), networkProgress >= 100 && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("completed", "really-simple-ssl")), button && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0-\xA0", showLink && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
        isLink: true,
        onClick: e => actionHandler(id, action, e)
      }, buttonTitle), !showLink && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, buttonTitle)), showAsPlugin && read_more && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
        target: "_blank",
        href: read_more,
        className: "button button-default rsssl-read-more"
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Read More", "really-simple-ssl")));
    });
  };
  const goToDashboard = () => {
    if (props.isModal) dismissModal();
    setSelectedMainMenuItem('dashboard');
  };
  const goToLetsEncrypt = () => {
    if (props.isModal) dismissModal();
    window.location.href = rsssl_settings.letsencrypt_url;
  };
  const saveEmailAndUpdateFields = async () => {
    await saveEmail();
    updateField('send_notifications_email', true);
    updateField('notifications_email_address', email);
    updateFieldsData();
  };
  const controlButtons = () => {
    let ActivateSSLText = networkwide ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Activate SSL networkwide", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Activate SSL", "really-simple-ssl");
    if (currentStepIndex === 0) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        disabled: processing || !certificateValid && !overrideSSL,
        className: "button button-primary",
        onClick: () => {
          activateSSL();
        }
      }, ActivateSSLText), certificateValid && !rsssl_settings.pro_plugin_active && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
        target: "_blank",
        href: rsssl_settings.upgrade_link,
        className: "button button-default"
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Improve Security with PRO", "really-simple-ssl")), !certificateValid && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        className: "button button-default",
        onClick: () => {
          goToLetsEncrypt();
        }
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Install SSL", "really-simple-ssl")), !certificateValid && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToggleControl, {
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Override SSL detection", "really-simple-ssl"),
        checked: overrideSSL,
        onChange: value => {
          setOverrideSSL(value);
          let data = {};
          data.overrideSSL = value;
          _utils_api__WEBPACK_IMPORTED_MODULE_2__.doAction('override_ssl_detection', data);
        }
      }));
    }
    if (currentStepIndex > 0 && currentStepIndex < steps.length - 1) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        disabled: processing,
        className: "button button-primary",
        onClick: () => saveEmailAndUpdateFields()
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Save and continue', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        disabled: processing,
        className: "button button-default",
        onClick: () => {
          setCurrentStepIndex(currentStepIndex + 1);
        }
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Skip', 'really-simple-ssl')));
    }

    //for last step only
    if (steps.length - 1 === currentStepIndex) {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        className: "button button-primary",
        onClick: () => {
          goToDashboard();
        }
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Go to Dashboard', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
        className: "button button-default",
        onClick: () => dismissModal()
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Dismiss', 'really-simple-ssl')));
    }
  };
  if (error) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_5__["default"], {
      lines: "3",
      error: error
    });
  }
  let step = currentStep;
  let processingClass = processing ? 'rsssl-processing' : '';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, !dataLoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-onboarding-placeholder"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: "file-download",
    color: "grey"
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Fetching next step...", "really-simple-ssl"))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_5__["default"], {
    lines: "3"
  }))), dataLoaded && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-content-step " + processingClass
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, parseStepItems(step.items)), currentStep.id === 'email' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "email",
    value: email,
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Your email address", "really-simple-ssl"),
    onChange: e => setEmail(e.target.value)
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    onChange: e => setIncludeTips(e.target.checked),
    type: "checkbox",
    checked: includeTips
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Include 6 Tips & Tricks to get started with Really Simple SSL.", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://really-simple-ssl.com/legal/privacy-statement/",
    target: "_blank"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Privacy Statement", "really-simple-ssl")))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    onChange: e => setSendTestEmail(e.target.checked),
    type: "checkbox",
    checked: sendTestEmail
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Send a notification test email - Notification emails are sent from your server.", "really-simple-ssl")))), certificateValid && step.info_text && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-description",
    dangerouslySetInnerHTML: {
      __html: step.info_text
    }
  }), currentStepIndex === 0 && !certificateValid && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-description"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "#",
    onClick: e => refreshSSLStatus(e)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Refresh SSL status", "really-simple-ssl")), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("The SSL detection method is not 100% accurate.", "really-simple-ssl"), "\xA0", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("If you’re certain an SSL certificate is present, and refresh SSL status does not work, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-content-step-footer"
  }, controlButtons())));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Onboarding);

/***/ }),

/***/ "./src/Settings/Button.js":
/*!********************************!*\
  !*** ./src/Settings/Button.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./FieldsData */ "./src/Settings/FieldsData.js");






/**
 * Render a help notice in the sidebar
 */
const Button = props => {
  const {
    addHelpNotice
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const onClickHandler = action => {
    let data = {};
    _utils_api__WEBPACK_IMPORTED_MODULE_3__.doAction(action, data).then(response => {
      let label = response.success ? 'success' : 'warning';
      let title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Test notification by email", 'really-simple-ssl');
      let text = response.message;
      addHelpNotice(props.field.id, label, text, title, false);
    });
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, props.field.url && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_2__["default"], {
    className: "button button-default",
    text: props.field.button_text,
    url: props.field.url
  }), props.field.action && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => onClickHandler(props.field.action),
    className: "button button-default"
  }, props.field.button_text));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Button);

/***/ }),

/***/ "./src/Settings/CheckboxControl.js":
/*!*****************************************!*\
  !*** ./src/Settings/CheckboxControl.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

/*
* The tooltip can't be included in the native toggleControl, so we have to build our own.
*/

const CheckboxControl = props => {
  const [isOpen, setIsOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [ConfirmDialog, setConfirmDialog] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (!ConfirmDialog) {
      Promise.resolve(/*! import() */).then(__webpack_require__.t.bind(__webpack_require__, /*! @wordpress/components */ "@wordpress/components", 23)).then(_ref => {
        let {
          default: __experimentalConfirmDialog
        } = _ref;
        setConfirmDialog(() => __experimentalConfirmDialog);
      });
    }
  }, []);
  const onChangeHandler = e => {
    //wordpress <6.0 does not have the confirmdialog component
    if (!ConfirmDialog) {
      executeAction();
      return;
    }
    if (props.field.warning && props.field.warning.length > 0 && !props.field.value) {
      setIsOpen(true);
    } else {
      executeAction();
    }
  };
  const handleConfirm = async () => {
    setIsOpen(false);
    executeAction();
  };
  const handleCancel = () => {
    setIsOpen(false);
  };
  const executeAction = e => {
    let fieldValue = !props.field.value;
    props.onChangeHandler(fieldValue);
  };
  const handleKeyDown = e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      onChangeHandler(true);
    }
  };
  let field = props.field;
  let is_checked = field.value ? 'is-checked' : '';
  let is_disabled = props.disabled ? 'is-disabled' : '';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, ConfirmDialog && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ConfirmDialog, {
    isOpen: isOpen,
    onConfirm: handleConfirm,
    onCancel: handleCancel
  }, field.warning), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control components-toggle-control"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control__field"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    "data-wp-component": "HStack",
    className: "components-flex components-h-stack"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "components-form-toggle " + is_checked + ' ' + is_disabled
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    onKeyDown: e => handleKeyDown(e),
    checked: field.value,
    className: "components-form-toggle__input",
    onChange: e => onChangeHandler(e),
    id: field.id,
    type: "checkbox",
    disabled: props.disabled
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "components-form-toggle__track"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "components-form-toggle__thumb"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: field.id,
    className: "components-toggle-control__label"
  }, props.label)))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (CheckboxControl);

/***/ }),

/***/ "./src/Settings/EventLog/DynamicDataTable.js":
/*!***************************************************!*\
  !*** ./src/Settings/EventLog/DynamicDataTable.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_data_table_component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js");
/* harmony import */ var _DynamicDataTableStore__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./DynamicDataTableStore */ "./src/Settings/EventLog/DynamicDataTableStore.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");






const DynamicDataTable = props => {
  const {
    DynamicDataTable,
    dataLoaded,
    pagination,
    dataActions,
    handleTableRowsChange,
    fetchDynamicData,
    handleTableSort,
    handleTablePageChange,
    handleTableSearch
  } = (0,_DynamicDataTableStore__WEBPACK_IMPORTED_MODULE_4__["default"])();

  //we create the columns
  let columns = [];
  //getting the fields from the props
  let field = props.field;
  //we loop through the fields
  field.columns.forEach(function (item, i) {
    let newItem = buildColumn(item);
    columns.push(newItem);
  });
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (!dataLoaded) {
      fetchDynamicData(field.action);
    }
  });
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (dataActions) {
      fetchDynamicData(field.action);
    }
  }, [dataActions]);
  const customStyles = {
    headCells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for head cells
        paddingRight: '0'
      }
    },
    cells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for data cells
        paddingRight: '0'
      }
    }
  };
  (0,react_data_table_component__WEBPACK_IMPORTED_MODULE_3__.createTheme)('really-simple-plugins', {
    divider: {
      default: 'transparent'
    }
  }, 'light');

  //only show the datatable if the data is loaded
  if (!dataLoaded && columns.length === 0 && DynamicDataTable.length === 0) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner__inner"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner__icon"
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner__text"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Loading...", "really-simple-ssl"))));
  }
  let searchableColumns = [];
  //setting the searchable columns
  columns.map(column => {
    if (column.searchable) {
      searchableColumns.push(column.column);
    }
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-search-bar"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-search-bar__inner"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-search-bar__icon"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    className: "rsssl-search-bar__input",
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Search", "really-simple-ssl"),
    onChange: event => handleTableSearch(event.target.value, searchableColumns)
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_data_table_component__WEBPACK_IMPORTED_MODULE_3__["default"], {
    columns: columns,
    data: DynamicDataTable.data,
    dense: true,
    pagination: true,
    paginationServer: true,
    paginationTotalRows: pagination.totalRows,
    onChangeRowsPerPage: handleTableRowsChange,
    onChangePage: handleTablePageChange,
    sortServer: true,
    onSort: handleTableSort,
    paginationRowsPerPageOptions: [10, 25, 50, 100],
    noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("No results", "really-simple-ssl"),
    persistTableHead: true,
    theme: "really-simple-plugins",
    customStyles: customStyles
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DynamicDataTable);
function buildColumn(column) {
  return {
    name: column.name,
    sortable: column.sortable,
    searchable: column.searchable,
    width: column.width,
    visible: column.visible,
    column: column.column,
    selector: row => row[column.column]
  };
}

/***/ }),

/***/ "./src/Settings/EventLog/DynamicDataTableStore.js":
/*!********************************************************!*\
  !*** ./src/Settings/EventLog/DynamicDataTableStore.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var immer__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! immer */ "./node_modules/immer/dist/immer.esm.mjs");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* Creates A Store For Risk Data using Zustand */





const DynamicDataTableStore = (0,zustand__WEBPACK_IMPORTED_MODULE_3__.create)((set, get) => ({
  processing: false,
  dataLoaded: false,
  pagination: {},
  dataActions: {},
  DynamicDataTable: [],
  fetchDynamicData: async action => {
    try {
      const response = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction(action, get().dataActions);
      //now we set the EventLog
      console.log(response);
      if (response) {
        set({
          DynamicDataTable: response,
          dataLoaded: true,
          processing: false,
          pagination: response.pagination
        });
      }
    } catch (e) {
      console.log(e);
    }
  },
  handleTableSearch: async (search, searchColumns) => {
    //Add the search to the dataActions
    set((0,immer__WEBPACK_IMPORTED_MODULE_4__.produce)(state => {
      state.dataActions = {
        ...state.dataActions,
        search,
        searchColumns
      };
    }));
  },
  handleTablePageChange: async (page, pageSize) => {
    //Add the page and pageSize to the dataActions
    set((0,immer__WEBPACK_IMPORTED_MODULE_4__.produce)(state => {
      state.dataActions = {
        ...state.dataActions,
        page,
        pageSize
      };
    }));
  },
  handleTableRowsChange: async (currentRowsPerPage, currentPage) => {
    //Add the page and pageSize to the dataActions
    set((0,immer__WEBPACK_IMPORTED_MODULE_4__.produce)(state => {
      state.dataActions = {
        ...state.dataActions,
        currentRowsPerPage,
        currentPage
      };
    }));
  },
  //this handles all pagination and sorting
  handleTableSort: async (column, sortDirection) => {
    //Add the column and sortDirection to the dataActions
    set((0,immer__WEBPACK_IMPORTED_MODULE_4__.produce)(state => {
      state.dataActions = {
        ...state.dataActions,
        sortColumn: column,
        sortDirection
      };
    }));
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DynamicDataTableStore);

/***/ }),

/***/ "./src/Settings/Field.js":
/*!*******************************!*\
  !*** ./src/Settings/Field.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _License_License__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./License/License */ "./src/Settings/License/License.js");
/* harmony import */ var _Password__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Password */ "./src/Settings/Password.js");
/* harmony import */ var _SelectControl__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./SelectControl */ "./src/Settings/SelectControl.js");
/* harmony import */ var _Host__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Host */ "./src/Settings/Host.js");
/* harmony import */ var _utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/Hyperlink */ "./src/utils/Hyperlink.js");
/* harmony import */ var _LetsEncrypt_LetsEncrypt__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../LetsEncrypt/LetsEncrypt */ "./src/LetsEncrypt/LetsEncrypt.js");
/* harmony import */ var _LetsEncrypt_Activate__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../LetsEncrypt/Activate */ "./src/LetsEncrypt/Activate.js");
/* harmony import */ var _MixedContentScan_MixedContentScan__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./MixedContentScan/MixedContentScan */ "./src/Settings/MixedContentScan/MixedContentScan.js");
/* harmony import */ var _PermissionsPolicy__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./PermissionsPolicy */ "./src/Settings/PermissionsPolicy.js");
/* harmony import */ var _CheckboxControl__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./CheckboxControl */ "./src/Settings/CheckboxControl.js");
/* harmony import */ var _Support__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./Support */ "./src/Settings/Support.js");
/* harmony import */ var _LearningMode_LearningMode__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./LearningMode/LearningMode */ "./src/Settings/LearningMode/LearningMode.js");
/* harmony import */ var _RiskConfiguration_RiskComponent__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./RiskConfiguration/RiskComponent */ "./src/Settings/RiskConfiguration/RiskComponent.js");
/* harmony import */ var _RiskConfiguration_vulnerabilitiesOverview__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./RiskConfiguration/vulnerabilitiesOverview */ "./src/Settings/RiskConfiguration/vulnerabilitiesOverview.js");
/* harmony import */ var _LimitLoginAttempts_IpAddressModule__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./LimitLoginAttempts/IpAddressModule */ "./src/Settings/LimitLoginAttempts/IpAddressModule.js");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./Button */ "./src/Settings/Button.js");
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _PostDropDown__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./PostDropDown */ "./src/Settings/PostDropDown.js");
/* harmony import */ var _RiskConfiguration_NotificationTester__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./RiskConfiguration/NotificationTester */ "./src/Settings/RiskConfiguration/NotificationTester.js");
/* harmony import */ var _utils_getAnchor__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ../utils/getAnchor */ "./src/utils/getAnchor.js");
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ../Menu/MenuData */ "./src/Menu/MenuData.js");
/* harmony import */ var _EventLog_DynamicDataTable__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./EventLog/DynamicDataTable */ "./src/Settings/EventLog/DynamicDataTable.js");



























const Field = props => {
  let scrollAnchor = React.createRef();
  const {
    updateField,
    setChangedField,
    highLightField
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_20__["default"])();
  const [anchor, setAnchor] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const {
    selectedFilter,
    setSelectedFilter
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_24__["default"])();
  const handleFilterChange = value => {
    setSelectedFilter(value); // Update selectedFilter when the filter value changes
  };

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    //check if the url contains the query variable 'anchor'
    setAnchor((0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_23__["default"])('anchor'));
    handleAnchor();
    if (highLightField === props.field.id && scrollAnchor.current) {
      scrollAnchor.current.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    handleAnchor();
  }, [anchor]);
  window.addEventListener('hashchange', e => {
    setAnchor((0,_utils_getAnchor__WEBPACK_IMPORTED_MODULE_23__["default"])('anchor'));
  });
  const handleAnchor = () => {
    if (anchor && anchor === props.field.id) {
      scrollAnchor.current.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  };
  const onChangeHandler = fieldValue => {
    let field = props.field;
    updateField(field.id, fieldValue);

    //we can configure other fields if a field is enabled, or set to a certain value.
    let configureFieldCondition = false;
    if (field.configure_on_activation) {
      if (field.configure_on_activation.hasOwnProperty('condition') && props.field.value == field.configure_on_activation.condition) {
        configureFieldCondition = true;
      }
      let configureField = field.configure_on_activation[0];
      for (let fieldId in configureField) {
        if (configureFieldCondition && configureField.hasOwnProperty(fieldId)) {
          updateField(fieldId, configureField[fieldId]);
        }
      }
    }
    setChangedField(field.id, fieldValue);
  };
  const labelWrap = field => {
    let tooltipColor = field.warning ? 'red' : 'black';
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "cmplz-label-text"
    }, field.label), field.tooltip && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_19__["default"], {
      name: "info-open",
      tooltip: field.tooltip,
      color: tooltipColor
    }));
  };
  let field = props.field;
  let fieldValue = field.value;
  let disabled = field.disabled;
  let highLightClass = 'rsssl-field-wrap';
  if (highLightField === props.field.id) {
    highLightClass = 'rsssl-field-wrap rsssl-highlight';
  }
  let options = [];
  if (field.options) {
    for (var key in field.options) {
      if (field.options.hasOwnProperty(key)) {
        let item = {};
        item.label = field.options[key];
        item.value = key;
        options.push(item);
      }
    }
  }

  //if a feature can only be used on networkwide or single site setups, pass that info here.
  if (!rsssl_settings.networkwide_active && field.networkwide_required) {
    disabled = true;
    field.comment = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("This feature is only available networkwide.", "really-simple-ssl"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Hyperlink__WEBPACK_IMPORTED_MODULE_7__["default"], {
      target: "_blank",
      text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Network settings", "really-simple-ssl"),
      url: rsssl_settings.network_link
    }));
  }
  if (field.conditionallyDisabled) {
    disabled = true;
  }
  if (!field.visible) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null);
  }
  if (field.type === 'checkbox') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_CheckboxControl__WEBPACK_IMPORTED_MODULE_12__["default"], {
      label: labelWrap(field),
      field: field,
      disabled: disabled,
      onChangeHandler: fieldValue => onChangeHandler(fieldValue)
    }), field.comment && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-comment",
      dangerouslySetInnerHTML: {
        __html: field.comment
      }
    }));
  }
  if (field.type === 'hidden') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "hidden",
      value: field.value
    });
  }
  if (field.type === 'radio') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.RadioControl, {
      label: labelWrap(field),
      onChange: fieldValue => onChangeHandler(fieldValue),
      selected: fieldValue,
      options: options
    }));
  }
  if (field.type === 'text' || field.type === 'email') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
      required: field.required,
      placeholder: field.placeholder,
      disabled: disabled,
      help: field.comment,
      label: labelWrap(field),
      onChange: fieldValue => onChangeHandler(fieldValue),
      value: fieldValue
    }));
  }
  if (field.type === 'button') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: 'rsssl-field-button ' + highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, field.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Button__WEBPACK_IMPORTED_MODULE_18__["default"], {
      field: field
    }));
  }
  if (field.type === 'password') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Password__WEBPACK_IMPORTED_MODULE_4__["default"], {
      index: props.index,
      field: field
    }));
  }
  if (field.type === 'textarea') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
      label: field.label,
      help: field.comment,
      value: fieldValue,
      onChange: fieldValue => onChangeHandler(fieldValue),
      disabled: field.disabled
    }));
  }
  if (field.type === 'license') {
    let field = props.field;
    let fieldValue = field.value;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_License_License__WEBPACK_IMPORTED_MODULE_3__["default"], {
      index: props.index,
      field: field,
      fieldValue: fieldValue
    }));
  }
  if (field.type === 'number') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalNumberControl, {
      onChange: fieldValue => onChangeHandler(fieldValue),
      help: field.comment,
      label: field.label,
      value: fieldValue
    }));
  }
  if (field.type === 'email') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: undefined.highLightClass,
      ref: undefined.scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextControl, {
      help: field.comment,
      label: field.label,
      onChange: fieldValue => undefined.onChangeHandler(fieldValue),
      value: fieldValue
    }));
  }
  if (field.type === 'host') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Host__WEBPACK_IMPORTED_MODULE_6__["default"], {
      index: props.index,
      field: props.field
    }));
  }
  if (field.type === 'select') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_SelectControl__WEBPACK_IMPORTED_MODULE_5__["default"], {
      disabled: disabled,
      label: labelWrap(field),
      onChangeHandler: fieldValue => onChangeHandler(fieldValue),
      value: fieldValue,
      options: options,
      field: field
    }));
  }
  if (field.type === 'support') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Support__WEBPACK_IMPORTED_MODULE_13__["default"], null));
  }
  if (field.type === 'postdropdown') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_PostDropDown__WEBPACK_IMPORTED_MODULE_21__["default"], {
      field: props.field
    }));
  }
  if (field.type === 'permissionspolicy') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_PermissionsPolicy__WEBPACK_IMPORTED_MODULE_11__["default"], {
      disabled: disabled,
      field: props.field,
      options: options
    }));
  }
  if (field.type === 'learningmode') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LearningMode_LearningMode__WEBPACK_IMPORTED_MODULE_14__["default"], {
      disabled: disabled,
      field: props.field
    }));
  }
  if (field.type === 'riskcomponent') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_RiskConfiguration_RiskComponent__WEBPACK_IMPORTED_MODULE_15__["default"], {
      field: props.field
    }));
  }
  if (field.type === 'mixedcontentscan') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_MixedContentScan_MixedContentScan__WEBPACK_IMPORTED_MODULE_10__["default"], {
      field: props.field
    }));
  }
  if (field.type === 'vulnerabilitiestable') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_RiskConfiguration_vulnerabilitiesOverview__WEBPACK_IMPORTED_MODULE_16__["default"], {
      field: props.field
    }));
  }
  if (field.type === 'ipaddressmodule') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LimitLoginAttempts_IpAddressModule__WEBPACK_IMPORTED_MODULE_17__["default"], {
      field: props.field,
      selectedFilter: selectedFilter // Pass selectedFilter as a prop to IpAddressModule
    }));
  }

  if (field.type === 'dynamic-datatable') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_EventLog_DynamicDataTable__WEBPACK_IMPORTED_MODULE_25__["default"], {
      field: props.field,
      action: props.field.action
    }));
  }
  if (field.type === 'notificationtester') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: 'rsssl-field-button ' + highLightClass,
      ref: scrollAnchor
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_RiskConfiguration_NotificationTester__WEBPACK_IMPORTED_MODULE_22__["default"], {
      field: props.field,
      labelWrap: labelWrap
    }));
  }
  if (field.type === 'letsencrypt') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LetsEncrypt_LetsEncrypt__WEBPACK_IMPORTED_MODULE_8__["default"], {
      field: field
    });
  }
  if (field.type === 'activate') {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_LetsEncrypt_Activate__WEBPACK_IMPORTED_MODULE_9__["default"], {
      field: field
    });
  }
  return 'not found field type ' + field.type;
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Field);

/***/ }),

/***/ "./src/Settings/Host.js":
/*!******************************!*\
  !*** ./src/Settings/Host.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FieldsData */ "./src/Settings/FieldsData.js");




const Host = props => {
  const {
    updateField,
    setChangedField,
    saveFields,
    handleNextButtonDisabled
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  const disabled = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const onChangeHandler = async fieldValue => {
    let field = props.field;
    //force update, and get new fields.
    handleNextButtonDisabled(true);
    disabled.current = true;
    updateField(field.id, fieldValue);
    setChangedField(field.id, fieldValue);
    await saveFields(true, false);
    handleNextButtonDisabled(false);
    disabled.current = false;
  };
  let fieldValue = props.field.value;
  let field = props.field;
  let options = [];
  if (field.options) {
    for (var key in field.options) {
      if (field.options.hasOwnProperty(key)) {
        let item = {};
        item.label = field.options[key];
        item.value = key;
        options.push(item);
      }
    }
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl, {
    label: field.label,
    onChange: fieldValue => onChangeHandler(fieldValue),
    value: fieldValue,
    options: options,
    disabled: disabled.current
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Host);

/***/ }),

/***/ "./src/Settings/LearningMode/ChangeStatus.js":
/*!***************************************************!*\
  !*** ./src/Settings/LearningMode/ChangeStatus.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _LearningModeData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LearningModeData */ "./src/Settings/LearningMode/LearningModeData.js");



const ChangeStatus = props => {
  const {
    updateStatus
  } = (0,_LearningModeData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  let statusClass = props.item.status == 1 ? 'button button-primary rsssl-status-allowed' : 'button button-default rsssl-status-revoked';
  let label = props.item.status == 1 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Revoke", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Allow", "really-simple-ssl");
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => updateStatus(props.item.status, props.item, props.field.id),
    className: statusClass
  }, label);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ChangeStatus);

/***/ }),

/***/ "./src/Settings/LearningMode/Delete.js":
/*!*********************************************!*\
  !*** ./src/Settings/LearningMode/Delete.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _LearningModeData__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LearningModeData */ "./src/Settings/LearningMode/LearningModeData.js");


const Delete = props => {
  const {
    deleteData
  } = (0,_LearningModeData__WEBPACK_IMPORTED_MODULE_1__["default"])();
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    type: "button",
    className: " rsssl-learning-mode-delete",
    onClick: () => deleteData(props.item, props.field.id)
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    "aria-hidden": "true",
    focusable: "false",
    role: "img",
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: "0 0 320 512",
    height: "16"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    fill: "#000000",
    d: "M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Delete);

/***/ }),

/***/ "./src/Settings/LearningMode/LearningMode.js":
/*!***************************************************!*\
  !*** ./src/Settings/LearningMode/LearningMode.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _ChangeStatus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ChangeStatus */ "./src/Settings/LearningMode/ChangeStatus.js");
/* harmony import */ var _Delete__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Delete */ "./src/Settings/LearningMode/Delete.js");
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./../FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _LearningModeData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./LearningModeData */ "./src/Settings/LearningMode/LearningModeData.js");








const LearningMode = props => {
  const {
    updateField,
    getFieldValue,
    getField,
    setChangedField,
    highLightField,
    saveFields
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const {
    fetchLearningModeData,
    learningModeData,
    dataLoaded
  } = (0,_LearningModeData__WEBPACK_IMPORTED_MODULE_6__["default"])();

  //used to show if a feature is already enforced by a third party
  const [enforcedByThirdparty, setEnforcedByThirdparty] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  //toggle from enforced to not enforced
  const [enforce, setEnforce] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  //toggle from learning mode to not learning mode
  const [learningMode, setLearningMode] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  //set learning mode to completed
  const [learningModeCompleted, setLearningModeCompleted] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [hasError, setHasError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  //check if learningmode has been enabled at least once
  const [lmEnabledOnce, setLmEnabledOnce] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  //filter the data
  const [filterValue, setFilterValue] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(-1);
  //the value that is used to enable or disable this feature. On or of.
  const [controlField, setControlField] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [DataTable, setDataTable] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [theme, setTheme] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    Promise.resolve(/*! import() */).then(__webpack_require__.bind(__webpack_require__, /*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js")).then(_ref => {
      let {
        default: DataTable,
        createTheme
      } = _ref;
      setDataTable(() => DataTable);
      setTheme(() => createTheme('really-simple-plugins', {
        divider: {
          default: 'transparent'
        }
      }, 'light'));
    });
  }, []);

  /**
   * Styling
   */
  const conditionalRowStyles = [{
    when: row => row.status == 0,
    classNames: ['rsssl-datatables-revoked']
  }];
  const customStyles = {
    headCells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for head cells
        paddingRight: '0'
      }
    },
    cells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for data cells
        paddingRight: '0'
      }
    }
  };
  ;

  /**
   * Initialize
   */
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const run = async () => {
      await fetchLearningModeData(props.field.id);
      let controlField = getField(props.field.control_field);
      let enforced_by_thirdparty = controlField.value === 'enforced-by-thirdparty';
      let enforce = enforced_by_thirdparty || controlField.value === 'enforce';
      setControlField(controlField);
      setEnforcedByThirdparty(enforced_by_thirdparty);
      setLearningModeCompleted(controlField.value === 'completed');
      setHasError(controlField.value === 'error');
      setLmEnabledOnce(getFieldValue(props.field.control_field + '_lm_enabled_once'));
      setEnforce(enforce);
      setLearningMode(controlField.value === 'learning_mode');
    };
    run();
  }, [enforce, learningMode]);
  const toggleEnforce = (e, enforceValue) => {
    e.preventDefault();
    //enforce this setting
    let controlFieldValue = enforceValue == 1 ? 'enforce' : 'disabled';
    setEnforce(enforceValue);
    setLearningModeCompleted(0);
    setLearningMode(0);
    setChangedField(controlField.id, controlFieldValue);
    updateField(controlField.id, controlFieldValue);
    saveFields(true, false);
    fetchLearningModeData();
  };
  const toggleLearningMode = async e => {
    e.preventDefault();
    let lmEnabledOnceField = getField(props.field.control_field + '_lm_enabled_once');
    if (learningMode) {
      setLmEnabledOnce(1);
      updateField(lmEnabledOnceField.id, 1);
    }
    let controlFieldValue;
    if (learningMode || learningModeCompleted) {
      setLearningMode(0);
      controlFieldValue = 'disabled';
    } else {
      setLearningMode(1);
      controlFieldValue = 'learning_mode';
    }
    setLearningModeCompleted(0);
    setChangedField(controlField.id, controlFieldValue);
    updateField(controlField.id, controlFieldValue);
    setChangedField(lmEnabledOnceField.id, lmEnabledOnceField.value);
    updateField(lmEnabledOnceField, lmEnabledOnceField.value);
    await saveFields(true, false);
  };
  const Filter = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    onChange: e => setFilterValue(e.target.value),
    value: filterValue
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "-1"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("All", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "1"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Allowed", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "0"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Blocked", "really-simple-ssl"))));
  let field = props.field;
  let configuringString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)(" The %s is now in report-only mode and will collect directives. This might take a while. Afterwards you can Exit, Edit and Enforce these Directives.", "really-simple-ssl").replace('%s', field.label);
  let disabledString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("%s has been disabled.", "really-simple-ssl").replace('%s', field.label);
  let enforcedString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("%s is enforced.", "really-simple-ssl").replace('%s', field.label);
  let enforceDisabled = !lmEnabledOnce;
  if (enforcedByThirdparty) disabledString = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("%s is already set outside Really Simple SSL.", "really-simple-ssl").replace('%s', field.label);
  let highLightClass = 'rsssl-field-wrap';
  if (highLightField === props.field.id) {
    highLightClass = 'rsssl-field-wrap rsssl-highlight';
  }
  //build our header
  let columns = [];
  field.columns.forEach(function (item, i) {
    let newItem = {
      name: item.name,
      sortable: item.sortable,
      width: item.width,
      selector: row => row[item.column]
    };
    columns.push(newItem);
  });
  let data = learningModeData;
  data = data.filter(item => item.status < 2);
  if (filterValue != -1) {
    data = data.filter(item => item.status == filterValue);
  }
  for (const item of data) {
    if (item.login_status) item.login_statusControl = item.login_status == 1 ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("success", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("failed", "really-simple-ssl");
    item.statusControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ChangeStatus__WEBPACK_IMPORTED_MODULE_2__["default"], {
      item: item,
      field: props.field
    });
    item.deleteControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Delete__WEBPACK_IMPORTED_MODULE_3__["default"], {
      item: item,
      field: props.field
    });
  }
  if (!DataTable || !theme) return null;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, !dataLoaded || data.length == 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-learningmode-placeholder"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null))), data.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(DataTable, {
    columns: columns,
    data: data,
    dense: true,
    pagination: true,
    noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("No results", "really-simple-ssl"),
    persistTableHead: true,
    theme: theme,
    customStyles: customStyles,
    conditionalRowStyles: conditionalRowStyles
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: "2",
    className: "rsssl-learning-mode-footer "
  }, hasError && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-learning-mode-error"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Error detected", "really-simple-ssl")), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("%s cannot be implemented due to server limitations. Check your notices for the detected issue.", "really-simple-ssl").replace('%s', field.label), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "rsssl-learning-mode-link",
    href: "#",
    onClick: e => toggleEnforce(e, false)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Disable", "really-simple-ssl")))), !hasError && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, enforce != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    disabled: enforceDisabled,
    className: "button button-primary",
    onClick: e => toggleEnforce(e, true)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enforce", "really-simple-ssl")), !enforcedByThirdparty && enforce == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    className: "button",
    onClick: e => toggleEnforce(e, false)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Disable", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    disabled: enforce,
    checked: learningMode == 1,
    value: learningMode,
    onChange: e => toggleLearningMode(e)
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enable Learning Mode to configure automatically", "really-simple-ssl")), enforce == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-shield-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: "shield",
    size: "80px"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-learning-mode-enforced"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enforced", "really-simple-ssl")), enforcedString, "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "rsssl-learning-mode-link",
    href: "#",
    onClick: e => toggleEnforce(e)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Disable to configure", "really-simple-ssl")))), learningMode == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-learning-mode"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Learning Mode", "really-simple-ssl")), configuringString, "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "rsssl-learning-mode-link",
    href: "#",
    onClick: e => toggleLearningMode(e)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Exit", "really-simple-ssl")))), learningModeCompleted == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-learning-mode-completed"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Learning Mode", "really-simple-ssl")), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("We finished the configuration.", "really-simple-ssl"), "\xA0", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "rsssl-learning-mode-link",
    href: "#",
    onClick: e => toggleLearningMode(e)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Review the settings and enforce the policy", "really-simple-ssl")))), rsssl_settings.pro_plugin_active && props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked "
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked-overlay"
  }, !enforcedByThirdparty && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-disabled"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Disabled", "really-simple-ssl")), enforcedByThirdparty && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-learning-mode-enforced"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Enforced", "really-simple-ssl")), disabledString))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Filter, null))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (LearningMode);

/***/ }),

/***/ "./src/Settings/LearningMode/LearningModeData.js":
/*!*******************************************************!*\
  !*** ./src/Settings/LearningMode/LearningModeData.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");


const UseLearningMode = (0,zustand__WEBPACK_IMPORTED_MODULE_1__.create)((set, get) => ({
  learningModeData: [],
  dataLoaded: false,
  fetchLearningModeData: async type => {
    let data = {};
    data.type = type;
    data.lm_action = 'get';
    let learningModeData = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('learning_mode_data', data).then(response => {
      return response;
    });
    if (typeof learningModeData === 'object') {
      learningModeData = Object.values(learningModeData);
    }
    if (!Array.isArray(learningModeData)) {
      learningModeData = [];
    }
    set({
      learningModeData: learningModeData,
      dataLoaded: true
    });
  },
  updateStatus: async (enabled, updateItem, type) => {
    let learningModeData = get().learningModeData;
    let data = {};
    data.type = type;
    data.updateItemId = updateItem.id;
    data.enabled = enabled == 1 ? 0 : 1;
    data.lm_action = 'update';

    //for fast UX feel, update the state before we post
    for (const item of learningModeData) {
      if (updateItem.id === item.id && item.status) {
        item.status = data.enabled;
      }
    }
    set({
      learningModeData: learningModeData
    });
    learningModeData = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('learning_mode_data', data).then(response => {
      return response;
    });
    if (typeof learningModeData === 'object') {
      learningModeData = Object.values(learningModeData);
    }
    if (!Array.isArray(learningModeData)) {
      learningModeData = [];
    }
    set({
      learningModeData: learningModeData,
      dataLoaded: true
    });
  },
  deleteData: async (deleteItem, type) => {
    let learningModeData = get().learningModeData;
    let data = {};
    data.type = type;
    data.updateItemId = deleteItem.id;
    data.lm_action = 'delete';
    //for fast UX feel, update the state before we post
    learningModeData.forEach(function (item, i) {
      if (item.id === deleteItem.id) {
        learningModeData.splice(i, 1);
      }
    });
    set({
      learningModeData: learningModeData
    });
    learningModeData = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('learning_mode_data', data).then(response => {
      return response;
    });
    if (typeof learningModeData === 'object') {
      learningModeData = Object.values(learningModeData);
    }
    if (!Array.isArray(learningModeData)) {
      learningModeData = [];
    }
    set({
      learningModeData: learningModeData,
      dataLoaded: true
    });
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UseLearningMode);

/***/ }),

/***/ "./src/Settings/License/License.js":
/*!*****************************************!*\
  !*** ./src/Settings/License/License.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Dashboard_TaskElement__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../Dashboard/TaskElement */ "./src/Dashboard/TaskElement.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./../FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _LicenseData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./LicenseData */ "./src/Settings/License/LicenseData.js");







const License = props => {
  const {
    fields,
    setChangedField,
    updateField
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    licenseStatus,
    setLicenseStatus
  } = (0,_LicenseData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const [noticesLoaded, setNoticesLoaded] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [fieldsUpdateComplete, setFieldsUpdateComplete] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [notices, setNotices] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const getLicenseNotices = () => {
    return _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest('licenseNotices', 'refresh').then(response => {
      return response;
    });
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    getLicenseNotices().then(response => {
      setLicenseStatus(response.licenseStatus);
      setNotices(response.notices);
      setNoticesLoaded(true);
    });
  }, [fields]);
  const onChangeHandler = fieldValue => {
    setChangedField(field.id, fieldValue);
    updateField(field.id, fieldValue);
  };
  const toggleActivation = () => {
    setNoticesLoaded(false);
    if (licenseStatus === 'valid') {
      _utils_api__WEBPACK_IMPORTED_MODULE_2__.runTest('deactivate_license').then(response => {
        setLicenseStatus(response.licenseStatus);
        setNotices(response.notices);
        setNoticesLoaded(true);
      });
    } else {
      let data = {};
      data.license = props.field.value;
      _utils_api__WEBPACK_IMPORTED_MODULE_2__.doAction('activate_license', data).then(response => {
        setLicenseStatus(response.licenseStatus);
        setNotices(response.notices);
        setNoticesLoaded(true);
      });
    }
  };
  let field = props.field;
  /**
   * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
   */
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control__field"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "components-base-control__label",
    htmlFor: field.id
  }, field.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-license-field"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    className: "components-text-control__input",
    type: "password",
    id: field.id,
    value: field.value,
    onChange: e => onChangeHandler(e.target.value)
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    className: "button button-default",
    onClick: () => toggleActivation()
  }, licenseStatus === 'valid' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Deactivate", "really-simple-ssl")), licenseStatus !== 'valid' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Activate", "really-simple-ssl"))))), noticesLoaded && notices.map((notice, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Dashboard_TaskElement__WEBPACK_IMPORTED_MODULE_1__["default"], {
    key: i,
    index: i,
    notice: notice,
    highLightField: ""
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (License);

/***/ }),

/***/ "./src/Settings/LimitLoginAttempts/IpAddressModule.js":
/*!************************************************************!*\
  !*** ./src/Settings/LimitLoginAttempts/IpAddressModule.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_data_table_component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js");
/* harmony import */ var _LimitLoginAttemptsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./LimitLoginAttemptsData */ "./src/Settings/LimitLoginAttempts/LimitLoginAttemptsData.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);







const IpAddressModule = props => {
  const {
    selectedFilter
  } = props;
  const {
    EventLog,
    dataLoaded,
    fetchEventLog
  } = (0,_LimitLoginAttemptsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    fields,
    fieldAlreadyEnabled,
    getFieldValue
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  let field = props.field;
  let columns = [];
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (selectedFilter) {
      console.log("selectedFilter", selectedFilter);
      if (!dataLoaded) {
        fetchEventLog(selectedFilter).then(r => console.log(r));
      }
    }
  });
  function buildColumn(column) {
    return {
      name: column.name,
      sortable: column.sortable,
      width: column.width,
      visible: column.visible,
      selector: row => row[column.column]
    };
  }
  const customStyles = {
    headCells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for head cells
        paddingRight: '0'
      }
    },
    cells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for data cells
        paddingRight: '0'
      }
    }
  };
  (0,react_data_table_component__WEBPACK_IMPORTED_MODULE_3__.createTheme)('really-simple-plugins', {
    divider: {
      default: 'transparent'
    }
  }, 'light');
  field.columns.forEach(function (item, i) {
    let newItem = buildColumn(item);
    columns.push(newItem);
  });

  //only show the datatable if the data is loaded
  if (!dataLoaded && !selectedFilter && columns.length === 0) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner__inner"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner__icon"
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-spinner__text"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Loading...", "really-simple-ssl"))));
  }
  let dummyData = [['127.0.0.1', 'testuser1', '', '', ''], ['', '', '', '', ''], ['', '', '', '', '']];
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-add-row"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isSecondary: true,
    onClick: () => console.log("add row")
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Add row", "really-simple-ssl"))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_data_table_component__WEBPACK_IMPORTED_MODULE_3__["default"], {
    columns: columns,
    data: EventLog,
    dense: true,
    pagination: true,
    noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("No results", "really-simple-ssl"),
    persistTableHead: true,
    theme: "really-simple-plugins",
    customStyles: customStyles
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (IpAddressModule);

/***/ }),

/***/ "./src/Settings/LimitLoginAttempts/LimitLoginAttemptsData.js":
/*!*******************************************************************!*\
  !*** ./src/Settings/LimitLoginAttempts/LimitLoginAttemptsData.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* Creates A Store For Risk Data using Zustand */





const LimitLoginAttemptsData = (0,zustand__WEBPACK_IMPORTED_MODULE_3__.create)((set, get) => ({
  processing: false,
  dataLoaded: false,
  EventLog: [],
  fetchEventLog: async selectedFilter => {
    set({
      processing: true
    });
    try {
      let response = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction(selectedFilter);
      set({
        EventLog: response,
        dataLoaded: true,
        processing: false
      });
    } catch (e) {
      console.log(e);
    }
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (LimitLoginAttemptsData);

/***/ }),

/***/ "./src/Settings/MixedContentScan/MixedContentData.js":
/*!***********************************************************!*\
  !*** ./src/Settings/MixedContentScan/MixedContentData.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");


const UseMixedContent = (0,zustand__WEBPACK_IMPORTED_MODULE_1__.create)((set, get) => ({
  mixedContentData: [],
  dataLoaded: false,
  fixedItemId: false,
  action: '',
  nonce: '',
  completedStatus: 'never',
  progress: 0,
  scanStatus: false,
  fetchMixedContentData: async () => {
    set({
      scanStatus: 'running'
    });
    const {
      data,
      progress,
      state,
      action,
      nonce,
      completed_status
    } = await getScanIteration(false);
    set({
      scanStatus: state,
      mixedContentData: data,
      progress: progress,
      action: action,
      nonce: nonce,
      completedStatus: completed_status,
      dataLoaded: true
    });
  },
  start: async () => {
    const {
      data,
      progress,
      state,
      action,
      nonce,
      completed_status
    } = await getScanIteration('start');
    set({
      scanStatus: state,
      mixedContentData: data,
      progress: progress,
      action: action,
      nonce: nonce,
      completedStatus: completed_status,
      dataLoaded: true
    });
  },
  runScanIteration: async () => {
    let currentState = get().scanStatus;
    if (currentState === 'stop') {
      return;
    }
    const {
      data,
      progress,
      state,
      action,
      nonce,
      completed_status
    } = await getScanIteration(currentState);
    if (get().scanStatus !== 'stop') {
      set({
        scanStatus: state,
        mixedContentData: data,
        progress: progress,
        action: action,
        nonce: nonce,
        completedStatus: completed_status,
        dataLoaded: true
      });
    }
  },
  stop: async () => {
    set({
      scanStatus: 'stop'
    });
    const {
      data,
      progress,
      state,
      action,
      nonce,
      completed_status
    } = await getScanIteration('stop');
    set({
      scanStatus: 'stop',
      mixedContentData: data,
      progress: progress,
      action: action,
      nonce: nonce,
      completedStatus: completed_status
    });
  },
  removeDataItem: removeItem => {
    let data = get().mixedContentData;
    for (const item of data) {
      if (item.id === removeItem.id) {
        item.fixed = true;
      }
    }
    set({
      mixedContentData: data
    });
  },
  ignoreDataItem: ignoreItem => {
    let data = get().mixedContentData;
    for (const item of data) {
      if (item.id === ignoreItem.id) {
        item.ignored = true;
      }
    }
    set({
      mixedContentData: data
    });
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UseMixedContent);
const getScanIteration = async state => {
  return await _utils_api__WEBPACK_IMPORTED_MODULE_0__.runTest('mixed_content_scan', state).then(response => {
    let data = response.data;
    if (typeof data === 'object') {
      data = Object.values(data);
    }
    if (!Array.isArray(data)) {
      data = [];
    }
    response.data = data;
    if (state === 'stop') {
      response.state = 'stop';
    }
    return response;
  });
};

/***/ }),

/***/ "./src/Settings/MixedContentScan/MixedContentScan.js":
/*!***********************************************************!*\
  !*** ./src/Settings/MixedContentScan/MixedContentScan.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Modal_ModalControl__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../Modal/ModalControl */ "./src/Modal/ModalControl.js");
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _MixedContentData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./MixedContentData */ "./src/Settings/MixedContentScan/MixedContentData.js");
/* harmony import */ var _Modal_ModalData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../Modal/ModalData */ "./src/Modal/ModalData.js");








const MixedContentScan = props => {
  const {
    fixedItems,
    ignoredItems
  } = (0,_Modal_ModalData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  const {
    fetchMixedContentData,
    mixedContentData,
    runScanIteration,
    start,
    stop,
    dataLoaded,
    action,
    scanStatus,
    progress,
    completedStatus,
    nonce,
    removeDataItem,
    ignoreDataItem
  } = (0,_MixedContentData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const [showIgnoredUrls, setShowIgnoredUrls] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [resetPaginationToggle, setResetPaginationToggle] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [DataTable, setDataTable] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [theme, setTheme] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    Promise.resolve(/*! import() */).then(__webpack_require__.bind(__webpack_require__, /*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js")).then(_ref => {
      let {
        default: DataTable,
        createTheme
      } = _ref;
      setDataTable(() => DataTable);
      setTheme(() => createTheme('really-simple-plugins', {
        divider: {
          default: 'transparent'
        }
      }, 'light'));
    });
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    fetchMixedContentData();
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (scanStatus === 'running') {
      runScanIteration();
    }
  }, [progress, scanStatus]);
  const toggleIgnoredUrls = e => {
    setShowIgnoredUrls(!showIgnoredUrls);
  };
  let field = props.field;
  let columns = [];
  field.columns.forEach(function (item, i) {
    let newItem = {
      name: item.name,
      sortable: item.sortable,
      grow: item.grow,
      selector: row => row[item.column],
      right: !!item.right
    };
    columns.push(newItem);
  });
  let dataTable = dataLoaded ? mixedContentData : [];
  for (const item of dataTable) {
    item.warningControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "rsssl-task-status rsssl-warning"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Warning", "really-simple-ssl"));

    //check if an item was recently fixed or ignored, and update the table
    if (fixedItems.includes(item.id)) {
      item.fixed = true;
    }
    if (ignoredItems.includes(item.id)) {
      item.ignored = true;
    }
    //give fix and details the url as prop
    if (item.fix) {
      item.fix.url = item.blocked_url;
      item.fix.nonce = nonce;
    }
    if (item.details) {
      item.details.url = item.blocked_url;
      item.details.nonce = nonce;
      item.details.ignored = item.ignored;
    }
    if (item.location.length > 0) {
      if (item.location.indexOf('http://') !== -1 || item.location.indexOf('https://') !== -1) {
        item.locationControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
          href: item.location,
          target: "_blank"
        }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("View", "really-simple-ssl"));
      } else {
        item.locationControl = item.location;
      }
    }
    item.detailsControl = item.details && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Modal_ModalControl__WEBPACK_IMPORTED_MODULE_3__["default"], {
      handleModal: props.handleModal,
      item: item,
      id: item.id,
      btnText: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Details", "really-simple-ssl"),
      btnStyle: "secondary",
      modalData: item.details
    });
    item.fixControl = item.fix && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Modal_ModalControl__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "button button-primary",
      handleModal: props.handleModal,
      item: item,
      id: item.id,
      btnText: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Fix", "really-simple-ssl"),
      btnStyle: "primary",
      modalData: item.fix
    });
  }
  if (!showIgnoredUrls) {
    dataTable = dataTable.filter(item => !item.ignored);
  }

  //filter also recently fixed items
  dataTable = dataTable.filter(item => !item.fixed);
  let progressOutput = progress + '%';
  let startDisabled = scanStatus === 'running';
  let stopDisabled = scanStatus !== 'running';
  const customStyles = {
    headCells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for head cells
        paddingRight: '0'
      }
    },
    cells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for data cells
        paddingRight: '0'
      }
    }
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-progress-container"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-progress-bar",
    style: {
      width: progressOutput
    }
  })), scanStatus === 'running' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-current-scan-action"
  }, action), dataTable.length === 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-mixed-content-description"
  }, scanStatus !== 'running' && completedStatus === 'never' && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("No results. Start your first scan", "really-simple-ssl"), scanStatus !== 'running' && completedStatus === 'completed' && (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Everything is now served over SSL", "really-simple-ssl")), (scanStatus === 'running' || completedStatus !== 'completed') && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-mixed-content-placeholder"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null)), scanStatus !== 'running' && completedStatus === 'completed' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-shield-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
    name: "shield",
    size: "80px"
  }))), DataTable && dataTable.length > 0 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'rsssl-mixed-content-datatable'
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(DataTable, {
    columns: columns,
    data: dataTable,
    dense: true,
    pagination: true,
    paginationResetDefaultPage: resetPaginationToggle // optionally, a hook to reset pagination to page 1
    ,
    noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("No results", "really-simple-ssl") //or your component
    ,
    theme: theme,
    customStyles: customStyles
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-grid-item-content-footer"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    className: "button",
    disabled: startDisabled,
    onClick: () => start()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Start scan", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    className: "button",
    disabled: stopDisabled,
    onClick: () => stop()
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Stop", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToggleControl, {
    checked: showIgnoredUrls == 1,
    onChange: e => toggleIgnoredUrls(e)
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Show ignored URLs', 'really-simple-ssl'))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MixedContentScan);

/***/ }),

/***/ "./src/Settings/Password.js":
/*!**********************************!*\
  !*** ./src/Settings/Password.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FieldsData */ "./src/Settings/FieldsData.js");


const Password = props => {
  const {
    updateField,
    setChangedField
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_1__["default"])();
  const onChangeHandler = fieldValue => {
    updateField(props.field.id, fieldValue);
    setChangedField(props.field.id, fieldValue);
  };

  /**
   * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
   */
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control__field"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "components-base-control__label",
    htmlFor: props.field.id
  }, props.field.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    className: "components-text-control__input",
    type: "password",
    id: props.field.id,
    value: props.field.value,
    onChange: e => onChangeHandler(e.target.value)
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Password);

/***/ }),

/***/ "./src/Settings/PermissionsPolicy.js":
/*!*******************************************!*\
  !*** ./src/Settings/PermissionsPolicy.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./FieldsData */ "./src/Settings/FieldsData.js");






const PermissionsPolicy = props => {
  const {
    fields,
    updateField,
    updateSubField,
    setChangedField,
    saveFields
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const [enablePermissionsPolicy, setEnablePermissionsPolicy] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [DataTable, setDataTable] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [theme, setTheme] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    Promise.resolve(/*! import() */).then(__webpack_require__.bind(__webpack_require__, /*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js")).then(_ref => {
      let {
        default: DataTable,
        createTheme
      } = _ref;
      setDataTable(() => DataTable);
      setTheme(() => createTheme('really-simple-plugins', {
        divider: {
          default: 'transparent'
        }
      }, 'light'));
    });
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    let field = fields.filter(field => field.id === 'enable_permissions_policy')[0];
    setEnablePermissionsPolicy(field.value);
  }, []);
  const onChangeHandler = (value, clickedItem) => {
    let field = props.field;
    if (typeof field.value === 'object') {
      updateField(field.id, Object.values(field.value));
    }

    //the updateItemId allows us to update one specific item in a field set.
    updateSubField(field.id, clickedItem.id, value);
    setChangedField(field.id, value);
    saveFields(true, false);
  };
  const togglePermissionsPolicyStatus = (e, enforce) => {
    e.preventDefault();
    //look up permissions policy enable field //enable_permissions_policy
    let field = fields.filter(field => field.id === 'enable_permissions_policy')[0];
    //enforce setting
    setEnablePermissionsPolicy(enforce);
    updateField(field.id, enforce);
    setChangedField(field.id, field.value);
    saveFields(true, false);
  };
  let field = props.field;
  let fieldValue = field.value;
  let options = props.options;
  columns = [];
  field.columns.forEach(function (item, i) {
    let newItem = {
      name: item.name,
      sortable: item.sortable,
      width: item.width,
      selector: row => row[item.column]
    };
    columns.push(newItem);
  });
  let data = field.value;
  if (typeof data === 'object') {
    data = Object.values(data);
  }
  if (!Array.isArray(data)) {
    data = [];
  }
  let disabled = false;
  let outputData = [];
  for (const item of data) {
    let itemCopy = {
      ...item
    };
    itemCopy.valueControl = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl, {
      help: "",
      value: item.value,
      disabled: disabled,
      options: options,
      label: "",
      onChange: fieldValue => onChangeHandler(fieldValue, item, 'value')
    });
    outputData.push(itemCopy);
  }
  const customStyles = {
    headCells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for head cells
        paddingRight: '0'
      }
    },
    cells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for data cells
        paddingRight: '0'
      }
    }
  };
  if (!DataTable || !theme) return null;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(DataTable, {
    columns: columns,
    data: outputData,
    dense: true,
    pagination: false,
    customStyles: customStyles,
    theme: theme
  }), enablePermissionsPolicy != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    className: "button button-primary",
    onClick: e => togglePermissionsPolicyStatus(e, true)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Enforce", "really-simple-ssl")), enablePermissionsPolicy == 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-shield-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_3__["default"], {
    name: "shield",
    size: "80px"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-learning-mode-enforced"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Enforced", "really-simple-ssl")), props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Permissions Policy is set outside Really Simple SSL.", "really-simple-ssl"), "\xA0"), !props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Permissions Policy is enforced.", "really-simple-ssl"), "\xA0"), !props.disabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    className: "rsssl-learning-mode-link",
    href: "#",
    onClick: e => togglePermissionsPolicyStatus(e, false)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Disable", "really-simple-ssl")))), props.disabled && enablePermissionsPolicy != 1 && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-locked-overlay"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-progress-status rsssl-disabled"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Disabled", "really-simple-ssl")), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("The Permissions Policy has been disabled.", "really-simple-ssl"))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PermissionsPolicy);

/***/ }),

/***/ "./src/Settings/PostDropDown.js":
/*!**************************************!*\
  !*** ./src/Settings/PostDropDown.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _material_ui_core_TextField__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @material-ui/core/TextField */ "./node_modules/@material-ui/core/esm/TextField/TextField.js");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FieldsData */ "./src/Settings/FieldsData.js");


/**
 * This file contains the PostDropdown component.
 *
 * This component displays a dropdown menu that allows the user to select a post
 * from a list of posts fetched from the WordPress database. The selected post
 * is then used to set a value in an options array stored in the WordPress
 * database. The component also allows the user to search for posts by typing
 * in a search box.
 */






const PostDropdown = _ref => {
  let {
    field
  } = _ref;
  const [posts, setPosts] = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [selectedPost, setSelectedPost] = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)("");
  const {
    updateField,
    setChangedField
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const [ThemeProvider, setThemeProvider] = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  const [theme, setTheme] = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  const [Autocomplete, setAutocomplete] = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    __webpack_require__.e(/*! import() */ "vendors-node_modules_material-ui_lab_esm_Autocomplete_index_js").then(__webpack_require__.bind(__webpack_require__, /*! @material-ui/lab/Autocomplete */ "./node_modules/@material-ui/lab/esm/Autocomplete/index.js")).then(_ref2 => {
      let {
        default: Autocomplete
      } = _ref2;
      setAutocomplete(() => Autocomplete);
    });
    __webpack_require__.e(/*! import() */ "vendors-node_modules_material-ui_core_esm_styles_index_js").then(__webpack_require__.bind(__webpack_require__, /*! @material-ui/core/styles */ "./node_modules/@material-ui/core/esm/styles/index.js")).then(_ref3 => {
      let {
        createTheme,
        ThemeProvider
      } = _ref3;
      setThemeProvider(() => ThemeProvider);
      setTheme(() => createTheme({
        typography: {
          fontSize: 12,
          fontFamily: 'inherit'
        },
        overrides: {
          MuiInputBase: {
            root: {
              fontSize: '12px',
              fontFamily: 'inherit',
              height: '40px'
            }
          },
          MuiList: {
            root: {
              fontSize: '8px'
            }
          },
          MuiAutocomplete: {
            inputRoot: {
              '& .MuiAutocomplete-input': {
                padding: '0 !important',
                border: 0
              },
              flexWrap: 'inherit'
            },
            popper: {
              fontSize: '12px'
            },
            paper: {
              fontSize: '12px'
            },
            option: {
              fontSize: '12px'
            },
            root: {
              padding: 0
            }
          }
        }
      }));
    });
  }, []);

  // Fetch the list of posts from the WordPress database when the component mounts.
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default()({
      path: '/wp/v2/pages?per_page=100'
    }).then(data => {
      const formattedData = data.map(post => ({
        title: post.title.rendered,
        id: post.id
      }));
      setPosts([{
        'title': "404 (default)",
        'id': "404_default"
      }, ...formattedData]);
    });
  }, []);

  // Fetch the data for the selected post from the WordPress database when the component mounts.
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (field.value !== '404_default') {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default()({
        path: `wp/v2/pages/${field.value}`
      }).then(data => {
        if (data.title) {
          setSelectedPost({
            'title': data.title.rendered,
            'id': field.value
          });
        } else {
          setSelectedPost({
            'title': "404 (default)",
            'id': '404_default'
          });
        }
      });
    } else {
      setSelectedPost({
        'title': "404 (default)",
        'id': '404_default'
      });
    }
  }, [field.value]);
  if (!Autocomplete || !ThemeProvider || !theme) {
    return null;
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("label", {
    htmlFor: "rsssl-filter-post-input"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Redirect to this post when someone tries to access /wp-admin or /wp-login.php. The default is a 404 page.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(ThemeProvider, {
    theme: theme
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(Autocomplete, {
    options: posts,
    getOptionLabel: option => option.title ? option.title : '',
    renderInput: params => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_material_ui_core_TextField__WEBPACK_IMPORTED_MODULE_6__["default"], (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, params, {
      variant: "outlined",
      placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Search for a post.', 'really-simple-ssl')
    })),
    getOptionSelected: (option, value) => {
      return option.id === value.id;
    },
    onChange: (event, newValue) => {
      let value = newValue && newValue.id ? newValue.id : '404_default';
      updateField(field.id, value);
      setChangedField(field.id, value);
    },
    value: selectedPost
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PostDropdown);

/***/ }),

/***/ "./src/Settings/RiskConfiguration/NotificationTester.js":
/*!**************************************************************!*\
  !*** ./src/Settings/RiskConfiguration/NotificationTester.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _RiskData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./RiskData */ "./src/Settings/RiskConfiguration/RiskData.js");






const NotificationTester = props => {
  const {
    fetchVulnerabilities,
    riskLevels
  } = (0,_RiskData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const {
    field
  } = props;
  const [disabled, setDisabled] = (0,react__WEBPACK_IMPORTED_MODULE_4__.useState)(true);
  const [mailNotificationsEnabled, setMailNotificationsEnabled] = (0,react__WEBPACK_IMPORTED_MODULE_4__.useState)(true);
  const [vulnerabilitiesEnabled, setVulnerabilitiesEnabled] = (0,react__WEBPACK_IMPORTED_MODULE_4__.useState)(false);
  const [vulnerabilitiesSaved, setVulnerabilitiesSaved] = (0,react__WEBPACK_IMPORTED_MODULE_4__.useState)(false);
  const {
    addHelpNotice,
    fields,
    getFieldValue,
    updateField,
    setChangedField,
    fieldAlreadyEnabled,
    fetchFieldsData,
    updateFieldAttribute
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  (0,react__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    let mailEnabled = getFieldValue('send_notifications_email') == 1;
    let vulnerabilities = fieldAlreadyEnabled('enable_vulnerability_scanner');
    setMailNotificationsEnabled(mailEnabled);
    let enableButton = mailEnabled && vulnerabilities;
    setDisabled(!enableButton);
    setMailNotificationsEnabled(mailEnabled);
    setVulnerabilitiesSaved(vulnerabilities);
    setVulnerabilitiesEnabled(getFieldValue('enable_vulnerability_scanner') == 1);
  }, [fields]);
  const doTestNotification = async () => {
    //Test the notifications
    setDisabled(true);
    _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('vulnerabilities_test_notification').then(() => {
      setDisabled(false);
      fetchFieldsData('vulnerabilities');
      fetchVulnerabilities();
      addHelpNotice(field.id, 'success', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('All notifications are triggered successfully, please check your email to double-check if you can receive emails.', 'really-simple-ssl'), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Test notifications', 'really-simple-ssl'), false);
    });
  };

  //ensure that risk levels are enabled cascading
  (0,react__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    let dashboardRiskLevel = getFieldValue('vulnerability_notification_dashboard');
    dashboardRiskLevel = riskLevels.hasOwnProperty(dashboardRiskLevel) ? riskLevels[dashboardRiskLevel] : 0;
    // let siteWideRiskLevel = getFieldValue('vulnerability_notification_sitewide');
    //the sitewide risk level should be at least as high as the dashboard risk level. Disable lower risk levels in sitewide
    //create an array of ints from 1 to dashboardRiskLevel, we drop the * from the array
    let priorDashboardRiskLevel = dashboardRiskLevel > 0 ? dashboardRiskLevel - 1 : dashboardRiskLevel;
    let dashboardRiskLevels = Array.from(Array(priorDashboardRiskLevel).keys()).map(x => x);
    //convert these integers back to risk levels
    //find the integer value in the riskLevels object, and return the key
    dashboardRiskLevels = dashboardRiskLevels.map(level => {
      return Object.keys(riskLevels).find(key => riskLevels[key] === level);
    });
    if (dashboardRiskLevels.length > 0) {
      updateFieldAttribute('vulnerability_notification_sitewide', 'disabled', dashboardRiskLevels);
      //if the current value is below the dashboardRisk Level, set it to the dashboardRiskLevel
      let siteWideRiskLevel = getFieldValue('vulnerability_notification_sitewide');
      siteWideRiskLevel = riskLevels.hasOwnProperty(siteWideRiskLevel) ? riskLevels[siteWideRiskLevel] : 0;
      if (siteWideRiskLevel < dashboardRiskLevel) {
        let newRiskLevel = Object.keys(riskLevels).find(key => riskLevels[key] === dashboardRiskLevel);
        updateField('vulnerability_notification_sitewide', newRiskLevel);
        setChangedField('vulnerability_notification_sitewide', newRiskLevel);
      }
    } else {
      updateFieldAttribute('vulnerability_notification_sitewide', 'disabled', false);
    }
  }, [getFieldValue('vulnerability_notification_dashboard')]);
  let fieldCopy = {
    ...field
  };
  if (!mailNotificationsEnabled) {
    fieldCopy.tooltip = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('You have not enabled the email notifications in the general settings.', 'really-simple-ssl');
    fieldCopy.warning = true;
  } else if (vulnerabilitiesEnabled && !vulnerabilitiesSaved) {
    fieldCopy.tooltip = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('The notification test only works if you save the setting first.', 'really-simple-ssl');
    fieldCopy.warning = true;
  }
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, props.labelWrap(fieldCopy)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => doTestNotification(),
    disabled: disabled,
    className: "button button-default"
  }, field.button_text));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (NotificationTester);

/***/ }),

/***/ "./src/Settings/RiskConfiguration/RiskComponent.js":
/*!*********************************************************!*\
  !*** ./src/Settings/RiskConfiguration/RiskComponent.js ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _RiskData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./RiskData */ "./src/Settings/RiskConfiguration/RiskData.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../FieldsData */ "./src/Settings/FieldsData.js");





const RiskComponent = props => {
  //first we put the data in a state
  const {
    riskData,
    dummyRiskData,
    processing,
    dataLoaded,
    fetchVulnerabilities,
    updateRiskData
  } = (0,_RiskData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  const {
    fields,
    fieldAlreadyEnabled,
    getFieldValue,
    setChangedField,
    updateField,
    saveFields
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const [measuresEnabled, setMeasuresEnabled] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [vulnerabilityDetectionEnabled, setVulnerabilityDetectionEnabled] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [DataTable, setDataTable] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [theme, setTheme] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    Promise.resolve(/*! import() */).then(__webpack_require__.bind(__webpack_require__, /*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js")).then(_ref => {
      let {
        default: DataTable,
        createTheme
      } = _ref;
      setDataTable(() => DataTable);
      setTheme(() => createTheme('really-simple-plugins', {
        divider: {
          default: 'transparent'
        }
      }, 'light'));
    });
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (fieldAlreadyEnabled('enable_vulnerability_scanner')) {
      if (!dataLoaded) {
        fetchVulnerabilities();
      }
    }
    let vulnerabilitiesEnabled = fieldAlreadyEnabled('enable_vulnerability_scanner');
    setVulnerabilityDetectionEnabled(vulnerabilitiesEnabled);
    let measuresOn = getFieldValue('measures_enabled') == 1;
    setMeasuresEnabled(measuresOn);
  }, [fields]);

  /**
   * Initialize
   */
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    let enabled = getFieldValue('measures_enabled') == 1;
    setMeasuresEnabled(enabled);
  }, []);

  //we create the columns
  let columns = [];
  //getting the fields from the props
  let field = props.field;
  //we loop through the fields
  field.columns.forEach(function (item, i) {
    let newItem = buildColumn(item);
    columns.push(newItem);
  });

  //now we get the options for the select control
  let options = props.field.options;
  //we divide the key into label and the value into value
  options = Object.entries(options).map(item => {
    return {
      label: item[1],
      value: item[0]
    };
  });

  //and we add the select control to the data
  let data = [...riskData];
  data = data.length === 0 ? [...dummyRiskData] : data;
  let disabled = !vulnerabilityDetectionEnabled || !measuresEnabled;
  for (const key in data) {
    let dataItem = {
      ...data[key]
    };
    dataItem.riskSelection = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
      disabled: processing || disabled,
      value: dataItem.value,
      onChange: e => onChangeHandler(e.target.value, dataItem)
    }, options.map((option, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
      key: i,
      value: option.value,
      disabled: dataItem.disabledRiskLevels && dataItem.disabledRiskLevels.includes(option.value)
    }, option.label)));
    data[key] = dataItem;
  }
  let processingClass = disabled ? 'rsssl-processing' : '';
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: processingClass
  }, DataTable && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(DataTable, {
    columns: columns,
    data: Object.values(data),
    theme: theme
  }));
  function buildColumn(column) {
    return {
      name: column.name,
      sortable: column.sortable,
      width: column.width,
      selector: row => row[column.column],
      grow: column.grow
    };
  }
  function onChangeHandler(fieldValue, item) {
    updateRiskData(item.id, fieldValue);
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (RiskComponent);

/***/ }),

/***/ "./src/Settings/RiskConfiguration/RiskData.js":
/*!****************************************************!*\
  !*** ./src/Settings/RiskConfiguration/RiskData.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var immer__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! immer */ "./node_modules/immer/dist/immer.esm.mjs");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_3__);

/* Creates A Store For Risk Data using Zustand */





const UseRiskData = (0,zustand__WEBPACK_IMPORTED_MODULE_4__.create)((set, get) => ({
  dummyRiskData: [{
    id: 'force_update',
    name: 'Force Update',
    value: 'l',
    description: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Force update the plugin or theme', 'really-simple-ssl')
  }, {
    id: 'quarantine',
    name: 'Quarantine',
    value: 'm',
    description: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Isolates the plugin or theme if no update can be performed', 'really-simple-ssl')
  }],
  riskData: [],
  riskLevels: {
    l: 1,
    m: 2,
    h: 3,
    c: 4
  },
  vulnerabilities: [],
  processing: false,
  dataLoaded: false,
  // Stuff we need for the WPVulData component
  updates: 0,
  //for letting the component know if there are updates available
  HighestRisk: false,
  //for storing the highest risk
  lastChecked: '',
  //for storing the last time the data was checked
  vulEnabled: false,
  //for storing the status of the vulnerability scan
  riskNaming: {},
  //for storing the risk naming
  introCompleted: false,
  //for storing the status of the first run
  vulList: [],
  //for storing the list of vulnerabilities
  setDataLoaded: value => set({
    dataLoaded: value
  }),
  //update Risk Data
  updateRiskData: async (field, value) => {
    set({
      processing: true
    });
    set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
      let index = state.riskData.findIndex(item => item.id === field);
      state.riskData[index].value = value;
      state.riskData = get().enforceCascadingRiskLevels(state.riskData);
    }));
    try {
      await _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('vulnerabilities_measures_set', {
        riskData: get().riskData
      });
      set({
        dataLoaded: true,
        processing: false
      });
    } catch (e) {
      console.log(e);
    }
  },
  setIntroCompleted: value => {
    set({
      introCompleted: value
    });
  },
  enforceCascadingRiskLevels: data => {
    if (data.length === 0) return data;
    //get risk levels for force_update
    let forceUpdateRiskLevel = data.filter(item => item.id === 'force_update')[0].value;
    let quarantineRiskLevel = data.filter(item => item.id === 'quarantine')[0].value;

    //get the integer value of the risk level
    forceUpdateRiskLevel = get().riskLevels.hasOwnProperty(forceUpdateRiskLevel) ? get().riskLevels[forceUpdateRiskLevel] : 5;
    quarantineRiskLevel = get().riskLevels.hasOwnProperty(quarantineRiskLevel) ? get().riskLevels[quarantineRiskLevel] : 5;
    let quarantineIndex = data.findIndex(item => item.id === 'quarantine');
    //if the quarantine risk level is lower than the force update risk level, we set it to the force update risk level
    if (quarantineRiskLevel < forceUpdateRiskLevel) {
      data[quarantineIndex].value = Object.keys(get().riskLevels).find(key => get().riskLevels[key] === forceUpdateRiskLevel);
    }
    //if the force update risk level is none, set quarantine also to none.
    if (forceUpdateRiskLevel === 5) {
      data[quarantineIndex].value = '*';
    }

    //disable all values below this value
    let disableUpTo = forceUpdateRiskLevel > 0 ? forceUpdateRiskLevel : 0;
    //create an array of integers up to the forceUpdateRiskLevel
    let disabledRiskLevels = Array.from(Array(disableUpTo).keys()).map(x => x);
    disabledRiskLevels = disabledRiskLevels.map(level => {
      return Object.keys(get().riskLevels).find(key => get().riskLevels[key] === level);
    });
    data[quarantineIndex].disabledRiskLevels = disabledRiskLevels;
    return data;
  },
  capitalizeFirstLetter: str => {
    return str.charAt(0).toUpperCase() + str.slice(1);
  },
  fetchFirstRun: async () => {
    await _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('vulnerabilities_scan_files');
  },
  /*
  * Functions
   */
  fetchVulnerabilities: async () => {
    let data = {};
    try {
      const fetched = await _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('hardening_data', data);
      let vulList = [];
      let vulnerabilities = 0;
      if (fetched.data.vulList) {
        vulnerabilities = fetched.data.vulnerabilities;
        vulList = fetched.data.vulList;
        if (typeof vulList === 'object') {
          //we make it an array
          vulList = Object.values(vulList);
        }
        vulList.forEach(function (item, i) {
          let updateUrl = item.update_available ? rsssl_settings.plugins_url + "?plugin_status=upgrade" : '#settings/vulnerabilities';
          item.vulnerability_action = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
            className: "rsssl-vulnerability-action"
          }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
            className: "button",
            href: "https://really-simple-ssl.com/vulnerabilities/" + item.rss_identifier,
            target: "_blank"
          }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Details", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
            disabled: !item.update_available,
            href: updateUrl,
            className: "button button-primary"
          }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Update", "really-simple-ssl")));
        });
      }
      let riskData = fetched.data.riskData;
      if (!Array.isArray(riskData)) {
        riskData = [];
      }
      riskData = get().enforceCascadingRiskLevels(riskData);
      set((0,immer__WEBPACK_IMPORTED_MODULE_5__.produce)(state => {
        state.vulnerabilities = vulnerabilities;
        state.vulList = vulList;
        state.updates = fetched.data.updates;
        state.dataLoaded = true;
        state.riskNaming = fetched.data.riskNaming;
        state.lastChecked = fetched.data.lastChecked;
        state.vulEnabled = fetched.data.vulEnabled;
        state.riskData = riskData;
      }));
    } catch (e) {
      console.error(e);
    }
  },
  vulnerabilityCount: () => {
    let vuls = get().vulList;
    //we group the data by risk level
    //first we make vuls an array
    let vulsArray = [];
    Object.keys(vuls).forEach(function (key) {
      vulsArray.push(vuls[key]);
    });
    let riskLevels = ['c', 'h', 'm', 'l'];
    //we count the amount of vulnerabilities per risk level
    return riskLevels.map(function (level) {
      return {
        level: level,
        count: vulsArray.filter(function (vul) {
          return vul.risk_level === level;
        }).length
      };
    });
  },
  vulnerabilityScore: () => {
    let score = 0;
    let vulnerabilitiesList = get().vulList;
    Object.keys(vulnerabilitiesList).forEach(function (key) {
      //if there are vulnerabilities with critical severity, score is 5
      if (vulnerabilitiesList[key].risk_level === 'c') {
        score = 5;
      } else if (score < 1) {
        score = 1;
      }
    });
    return score;
  },
  hardeningScore: () => {
    let score = 0;
    let vulnerabilitiesList = get().vulnerabilities;
    for (let i = 0; i < vulnerabilitiesList.length; i++) {
      score += vulnerabilitiesList[i].hardening_score;
    }
    return score;
  },
  activateVulnerabilityScanner: async () => {
    let data = {};
    try {
      const fetched = await _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('rsssl_scan_files');
      if (fetched.request_success) {
        //we get the data again
        const run = async () => {
          await get().fetchVulnerabilities();
        };
        run();
      }
    } catch (e) {
      console.error(e);
    }
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UseRiskData);

/***/ }),

/***/ "./src/Settings/RiskConfiguration/Runner.js":
/*!**************************************************!*\
  !*** ./src/Settings/RiskConfiguration/Runner.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _RiskData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./RiskData */ "./src/Settings/RiskConfiguration/RiskData.js");
/* harmony import */ var _RunnerData__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./RunnerData */ "./src/Settings/RiskConfiguration/RunnerData.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../Dashboard/Progress/ProgressData */ "./src/Dashboard/Progress/ProgressData.js");







const Runner = props => {
  //let us make a state for the loading
  const [loadingState, setLoadingState] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const {
    setChangedField,
    updateField,
    saveFields
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    getProgressData
  } = (0,_Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const {
    step,
    setStep
  } = (0,_RunnerData__WEBPACK_IMPORTED_MODULE_3__["default"])();
  const {
    fetchFirstRun,
    fetchVulnerabilities,
    setIntroCompleted
  } = (0,_RiskData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  let spin = loadingState ? "icon-spin" : "";

  //first step
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (step === 0 && props.currentStep === 1) {
      firstRunner();
    } else if (step === 1 && props.currentStep === 2) {
      secondRunner();
    } else if (step === 2 && props.currentStep === 3) {
      thirdRunner();
    } else if (step === 3 && props.currentStep === 4) {
      fourthRunner();
    }
  }, [step]);
  const firstRunner = async () => {
    await fetchFirstRun();
    completeCurrentRun();
  };
  const secondRunner = async () => {
    await fetchVulnerabilities();
    completeCurrentRun();
  };
  const thirdRunner = async () => {
    //after the first run is complete, and vulnerabilities data is loaded,
    //we reload the progress now to ensure we have all the vulnerabilities loaded on the dashboard.
    await getProgressData();
    completeCurrentRun();
  };
  const fourthRunner = async () => {
    //last run, store as completed
    setIntroCompleted(true);
    setChangedField('vulnerabilities_intro_shown', true);
    updateField('vulnerabilities_intro_shown', true);
    await saveFields(true, false);
    completeCurrentRun();
  };
  const completeCurrentRun = () => {
    setTimeout(function () {
      setLoadingState(false);
      setStep(step + 1);
    }, 1000);
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-details"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail-icon " + spin
  }, loadingState ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_1__["default"], {
    name: "spinner"
  }) : (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_1__["default"], {
    name: "circle-check",
    color: "green"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-detail-title"
  }, props.title)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Runner);

/***/ }),

/***/ "./src/Settings/RiskConfiguration/RunnerData.js":
/*!******************************************************!*\
  !*** ./src/Settings/RiskConfiguration/RunnerData.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");

const useRunnerData = (0,zustand__WEBPACK_IMPORTED_MODULE_0__.create)((set, get) => ({
  // loadingState : false,
  // setLoadingState: (state) => set({loadingState: state}),
  // title: '',
  // setTitle: (title) => set({title: title}),
  // time: 0,
  // setTime: (time) => set({time: time}),
  // delay: 0,
  // setDelay: (delay) => set({delay: delay}),
  step: 0,
  setStep: step => set({
    step: step
  })
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useRunnerData);

/***/ }),

/***/ "./src/Settings/RiskConfiguration/VulnerabilitiesIntro.js":
/*!****************************************************************!*\
  !*** ./src/Settings/RiskConfiguration/VulnerabilitiesIntro.js ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Runner__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Runner */ "./src/Settings/RiskConfiguration/Runner.js");
/* harmony import */ var _RunnerData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./RunnerData */ "./src/Settings/RiskConfiguration/RunnerData.js");






const VulnerabilitiesIntro = () => {
  //first we define a state for the steps
  const [isClosed, setClosed] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [disabled, setDisabled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const {
    step
  } = (0,_RunnerData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (step === 4) {
      setDisabled(false);
    }
  }, [step]);
  const closeOnX = () => {
    if (!disabled) {
      setClosed(true);
    }
  };
  //this function closes the modal when onClick is activated
  if (!isClosed) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Modal, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Introducing vulnerabilities', 'really-simple-ssl'),
      className: "rsssl-modal",
      onRequestClose: () => closeOnX(),
      shouldCloseOnClickOutside: true,
      shouldCloseOnEsc: true,
      overlayClassName: "rsssl-modal-overlay"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-header-extension"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("You have enabled vulnerability detection! Really Simple SSL will check your plugins, themes and WordPress core daily and report if any known vulnerabilities are found.", "really-simple-ssl")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
      className: "rsssl-intro-logo",
      src: rsssl_settings.plugin_url + '/assets/img/really-simple-ssl-intro.svg'
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "rsssl-ssl-intro-container"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Runner__WEBPACK_IMPORTED_MODULE_3__["default"], {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Preparing vulnerability detection", "really-simple-ssl"),
      name: "first_runner",
      loading: true,
      currentStep: 1
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Runner__WEBPACK_IMPORTED_MODULE_3__["default"], {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Collecting plugin, theme and core data", "really-simple-ssl"),
      name: "second_runner",
      loading: true,
      currentStep: 2
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Runner__WEBPACK_IMPORTED_MODULE_3__["default"], {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Scanning your WordPress configuration", "really-simple-ssl"),
      name: "third_runner",
      loading: true,
      currentStep: 3
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Runner__WEBPACK_IMPORTED_MODULE_3__["default"], {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Reporting enabled", "really-simple-ssl"),
      name: "fourth_runner",
      loading: true,
      currentStep: 4
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: 'rsssl-modal-footer'
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
      disabled: disabled,
      isPrimary: true,
      onClick: () => {
        setClosed(true);
        //we redirect to dashboard
        window.location.hash = "dashboard";
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Dashboard', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
      disabled: disabled,
      isSecondary: true,
      onClick: () => {
        setClosed(true);
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Dismiss', 'really-simple-ssl')))));
  }

  //in case the modal is closed we return null
  return null;
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VulnerabilitiesIntro);

/***/ }),

/***/ "./src/Settings/RiskConfiguration/vulnerabilitiesOverview.js":
/*!*******************************************************************!*\
  !*** ./src/Settings/RiskConfiguration/vulnerabilitiesOverview.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _RiskData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./RiskData */ "./src/Settings/RiskConfiguration/RiskData.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var react_data_table_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react-data-table-component */ "./node_modules/react-data-table-component/dist/index.cjs.js");
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _VulnerabilitiesIntro__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./VulnerabilitiesIntro */ "./src/Settings/RiskConfiguration/VulnerabilitiesIntro.js");
/* harmony import */ var _Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../Dashboard/Progress/ProgressData */ "./src/Dashboard/Progress/ProgressData.js");








const VulnerabilitiesOverview = props => {
  const {
    getProgressData
  } = (0,_Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_7__["default"])();
  const {
    dataLoaded,
    vulList,
    introCompleted,
    fetchVulnerabilities,
    setDataLoaded,
    fetchFirstRun
  } = (0,_RiskData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  const {
    fields,
    fieldAlreadyEnabled,
    getFieldValue
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const [showIntro, setShowIntro] = (0,react__WEBPACK_IMPORTED_MODULE_3__.useState)(false);
  //we create the columns
  let columns = [];
  //getting the fields from the props
  let field = props.field;
  let enabled = false;
  const customStyles = {
    headCells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for head cells
        paddingRight: '0'
      }
    },
    cells: {
      style: {
        paddingLeft: '0',
        // override the cell padding for data cells
        paddingRight: '0'
      }
    }
  };
  (0,react_data_table_component__WEBPACK_IMPORTED_MODULE_4__.createTheme)('really-simple-plugins', {
    divider: {
      default: 'transparent'
    }
  }, 'light');
  function buildColumn(column) {
    return {
      name: column.name,
      sortable: column.sortable,
      width: column.width,
      visible: column.visible,
      selector: row => row[column.column]
    };
  }
  let dummyData = [['', '', '', '', ''], ['', '', '', '', ''], ['', '', '', '', '']];
  field.columns.forEach(function (item, i) {
    let newItem = buildColumn(item);
    columns.push(newItem);
  });

  //get data if field was already enabled, so not changed right now.
  (0,react__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    if (fieldAlreadyEnabled('enable_vulnerability_scanner')) {
      if (getFieldValue('vulnerabilities_intro_shown') != 1 && !introCompleted) {
        setShowIntro(true);
      } else {
        //if just enabled, but intro already shown, just get the first run data.
        if (!dataLoaded) {
          initialize();
        }
      }
    }
  }, [fields, dataLoaded]);
  (0,react__WEBPACK_IMPORTED_MODULE_3__.useEffect)(() => {
    //if this value changes, reload vulnerabilities data
    if (getFieldValue('enable_vulnerability_scanner') == 1 && !fieldAlreadyEnabled('enable_vulnerability_scanner')) {
      setDataLoaded(false);
    }
  }, [fields]);
  const initialize = async () => {
    await fetchFirstRun();
    await fetchVulnerabilities();
    await getProgressData();
  };
  fields.forEach(function (item, i) {
    if (item.id === 'enable_vulnerability_scanner') {
      enabled = item.value;
    }
  });
  if (!enabled) {
    return (
      //If there is no data or vulnerabilities scanner is disabled we show some dummy data behind a mask
      (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, showIntro && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_VulnerabilitiesIntro__WEBPACK_IMPORTED_MODULE_6__["default"], null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_data_table_component__WEBPACK_IMPORTED_MODULE_4__["default"], {
        columns: columns,
        data: dummyData,
        dense: true,
        pagination: true,
        noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("No results", "really-simple-ssl"),
        persistTableHead: true,
        theme: "really-simple-plugins",
        customStyles: customStyles
      }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-locked"
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "rsssl-locked-overlay"
      }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
        className: "rsssl-task-status rsssl-open"
      }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Disabled', 'really-simple-ssl')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Activate vulnerability detection to enable this block.', 'really-simple-ssl')))))
    );
  }

  //we need to add a key to the data called action wich produces the action buttons
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, showIntro && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_VulnerabilitiesIntro__WEBPACK_IMPORTED_MODULE_6__["default"], null)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_data_table_component__WEBPACK_IMPORTED_MODULE_4__["default"], {
    columns: columns,
    data: vulList,
    dense: true,
    pagination: true,
    persistTableHead: true,
    noDataComponent: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("No vulnerabilities found", "really-simple-ssl"),
    theme: "really-simple-plugins",
    customStyles: customStyles
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VulnerabilitiesOverview);

/***/ }),

/***/ "./src/Settings/SelectControl.js":
/*!***************************************!*\
  !*** ./src/Settings/SelectControl.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

/*
* The native selectControl doesn't allow disabling per option.
*/

const SelectControl = props => {
  let field = props.field;
  let selectDisabled = !Array.isArray(props.disabled) && props.disabled;
  let optionsDisabled = Array.isArray(props.disabled) ? props.disabled : false;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "components-base-control__field"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    "data-wp-component": "HStack",
    className: "components-flex components-select-control"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    htmlFor: field.id,
    className: "components-toggle-control__label"
  }, props.label), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    disabled: selectDisabled,
    value: props.value,
    onChange: e => props.onChangeHandler(e.target.value)
  }, props.options.map((option, i) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    key: i,
    value: option.value,
    disabled: optionsDisabled && optionsDisabled.includes(option.value)
  }, option.label)))))), field.comment && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-comment",
    dangerouslySetInnerHTML: {
      __html: field.comment
    }
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SelectControl);

/***/ }),

/***/ "./src/Settings/Support.js":
/*!*********************************!*\
  !*** ./src/Settings/Support.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/api */ "./src/utils/api.js");





const Support = () => {
  const [message, setMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [sending, setSending] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const onChangeHandler = message => {
    setMessage(message);
  };
  const onClickHandler = () => {
    setSending(true);
    return _utils_api__WEBPACK_IMPORTED_MODULE_3__.runTest('supportData', 'refresh').then(response => {
      let encodedMessage = message.replace(/(?:\r\n|\r|\n)/g, '--br--');
      let url = 'https://really-simple-ssl.com/support' + '?customername=' + encodeURIComponent(response.customer_name) + '&email=' + response.email + '&domain=' + response.domain + '&scanresults=' + encodeURIComponent(response.scan_results) + '&licensekey=' + encodeURIComponent(response.license_key) + '&supportrequest=' + encodeURIComponent(encodedMessage) + '&htaccesscontents=' + response.htaccess_contents + '&debuglog=' + response.system_status;
      window.location.assign(url);
    });
  };
  let disabled = sending || message.length === 0;
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.TextareaControl, {
    disabled: sending,
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Type your question here", "really-simple-ssl"),
    onChange: message => onChangeHandler(message)
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
    disabled: disabled,
    variant: "secondary",
    onClick: e => onClickHandler(e)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Send', 'really-simple-ssl')));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Support);

/***/ })

}]);
//# sourceMappingURL=src_Settings_Field_js.js.map