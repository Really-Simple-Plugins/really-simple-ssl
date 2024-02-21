"use strict";
(globalThis["webpackChunkreally_simple_ssl"] = globalThis["webpackChunkreally_simple_ssl"] || []).push([["src_Onboarding_OnboardingModal_js"],{

/***/ "../modal/src/components/Modal/RssslModal.js":
/*!***************************************************!*\
  !*** ../modal/src/components/Modal/RssslModal.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _RssslModal_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./RssslModal.scss */ "../modal/src/components/Modal/RssslModal.scss");
/* harmony import */ var _settings_src_utils_ErrorBoundary__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../../../settings/src/utils/ErrorBoundary */ "./src/utils/ErrorBoundary.js");

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
      Promise.resolve(/*! import() */).then(__webpack_require__.bind(__webpack_require__, /*! ../../../../settings/src/utils/Icon */ "./src/utils/Icon.js")).then(({
        default: Icon
      }) => {
        setIcon(() => Icon);
      });
    }
  }, []);
  let modalCustomClass = className ? ' ' + className : "";
  return wp.element.createElement(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, isOpen && wp.element.createElement(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, wp.element.createElement(_settings_src_utils_ErrorBoundary__WEBPACK_IMPORTED_MODULE_5__["default"], {
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (RssslModal);

/***/ }),

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

/***/ "./src/Dashboard/SslLabs/SslLabsData.js":
/*!**********************************************!*\
  !*** ./src/Dashboard/SslLabs/SslLabsData.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");


const useSslLabs = (0,zustand__WEBPACK_IMPORTED_MODULE_1__.create)((set, get) => ({
  debug: false,
  //set to true for localhost testing, with wordpress.org as domain
  sslScanStatus: false,
  sslData: false,
  endpointData: [],
  dataLoaded: false,
  clearCache: false,
  requestActive: false,
  intervalId: false,
  setIntervalId: intervalId => set({
    intervalId
  }),
  setRequestActive: requestActive => set({
    requestActive
  }),
  setSslScanStatus: sslScanStatus => set({
    sslScanStatus
  }),
  setClearCache: clearCache => set({
    clearCache
  }),
  setSslData: sslData => set({
    sslData
  }),
  setEndpointData: endpointData => set({
    endpointData
  }),
  isLocalHost: () => {
    let debug = get().debug;
    return debug ? false : window.location.host.indexOf('localhost') !== -1;
  },
  host: () => {
    let debug = get().debug;
    return debug ? "wordpress.org" : window.location.host;
  },
  fetchSslData: async () => {
    _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('ssltest_get').then(response => {
      if (response.data.hasOwnProperty('host')) {
        let data = get().processSslData(response.data);
        set({
          sslData: data,
          endpointData: data.endpointData,
          dataLoaded: true
        });
      }
    });
  },
  getSslLabsData: e => {
    let clearCacheUrl = '';
    if (get().clearCache) {
      set({
        clearCache: false,
        sslData: false
      });
      clearCacheUrl = '&startNew=on';
    }
    const url = "https://api.ssllabs.com/api/v3/analyze?host=" + get().host() + clearCacheUrl;
    let data = {};
    data.url = url;
    return _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('ssltest_run', data).then(response => {
      if (response && !response.errors) {
        return JSON.parse(response);
      } else {
        return false;
      }
    });
  },
  runSslTest: () => {
    get().getSslLabsData().then(sslData => {
      if (sslData.status && sslData.status === 'ERROR') {
        sslData = get().processSslData(sslData);
        set({
          sslData: sslData,
          sslScanStatus: 'completed'
        });
        clearInterval(get().intervalId);
      } else if (sslData.endpoints && sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'Ready').length > 0) {
        let completedEndpoints = sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'Ready');
        let lastCompletedEndpointIndex = completedEndpoints.length - 1;
        let lastCompletedEndpoint = completedEndpoints[lastCompletedEndpointIndex];
        let ipAddress = lastCompletedEndpoint.ipAddress;
        get().getEndpointData(ipAddress).then(response => {
          let endpointData = get().endpointData;
          if (!Array.isArray(endpointData)) endpointData = [];
          if (!response.errors) {
            //if the endpoint already is stored, replace it.
            let foundEndpoint = false;
            if (endpointData.length > 0) {
              endpointData.forEach(function (endpoint, i) {
                if (endpoint.ipAddress === response.ipAddress) {
                  endpointData[i] = response;
                  foundEndpoint = true;
                }
              });
            }
            if (!foundEndpoint) {
              endpointData[endpointData.length] = response;
            }
            set({
              endpointData: endpointData
            });
            sslData.endpointData = endpointData;
          }
          if (!sslData.errors) {
            _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('store_ssl_labs', sslData);
          }
          sslData = get().processSslData(sslData);
          set({
            sslData: sslData,
            requestActive: false
          });
        });
      } else {
        //if there are no errors, this runs when the first endpoint is not completed yet
        sslData = get().processSslData(sslData);
        if (!sslData.errors) {
          _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('store_ssl_labs', sslData).then(response => {});
        }
        set({
          sslData: sslData,
          requestActive: false
        });
      }
    });
  },
  processSslData: sslData => {
    if (!sslData) {
      sslData = {};
    }
    let progress = sslData.progress ? sslData.progress : 0;
    let startTime = sslData.startTime ? sslData.startTime : '';
    let statusMessage = sslData.statusMessage ? sslData.statusMessage : '';
    let grade = sslData.grade ? sslData.grade : '?';
    let ipAddress = '';
    if (sslData.endpoints) {
      let completedEndpoints = sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'Ready');
      let completedEndpointsLength = completedEndpoints.length;
      let lastCompletedEndpoint = completedEndpoints[completedEndpointsLength - 1];
      let activeEndpoint = sslData.endpoints.filter(endpoint => endpoint.statusMessage === 'In progress')[0];
      let activeEndpointProgress = 0;
      if (activeEndpoint) {
        activeEndpointProgress = activeEndpoint.progress ? activeEndpoint.progress : 0;
        statusMessage = activeEndpoint.statusDetailsMessage;
        ipAddress = activeEndpoint.ipAddress;
      }
      if (lastCompletedEndpoint) grade = lastCompletedEndpoint.grade;
      progress = (completedEndpointsLength * 100 + activeEndpointProgress) / sslData.endpoints.length;
    }
    if (sslData.errors) {
      grade = '?';
      statusMessage = sslData.errors[0].message;
      progress = 100;
    }
    let summary = {};
    if (progress >= 100) {
      set({
        sslScanStatus: 'completed'
      });
    }
    summary.grade = grade;
    summary.startTime = startTime;
    summary.statusMessage = statusMessage;
    summary.ipAddress = ipAddress;
    summary.progress = progress;
    sslData.summary = summary;
    return sslData;
  },
  getEndpointData: ipAddress => {
    const url = 'https://api.ssllabs.com/api/v3/getEndpointData?host=' + get().host() + '&s=' + ipAddress;
    let data = {};
    data.url = url;
    return _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('ssltest_run', data).then(response => {
      if (response && !response.errors) {
        return JSON.parse(response);
      }
    });
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useSslLabs);

/***/ }),

/***/ "./src/Onboarding/Items/CheckboxItem.js":
/*!**********************************************!*\
  !*** ./src/Onboarding/Items/CheckboxItem.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);



const CheckboxItem = ({
  item,
  disabled
}) => {
  const {
    updateItemStatus,
    currentStep
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_1__["default"])();
  let {
    title,
    description,
    id,
    activated
  } = item;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "rsssl-modal-checkbox-container"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    disabled: disabled,
    checked: activated,
    value: id,
    id: id,
    onChange: e => updateItemStatus(currentStep.id, id, null, null, e.target.checked)
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "rsssl-checkmark"
  })), title, description && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, " - ", description));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.memo)(CheckboxItem));

/***/ }),

/***/ "./src/Onboarding/Items/ListItem.js":
/*!******************************************!*\
  !*** ./src/Onboarding/Items/ListItem.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../OnboardingData */ "./src/Onboarding/OnboardingData.js");





const ListItem = ({
  item
}) => {
  let {
    title,
    status,
    id
  } = item;
  const {
    overrideSSL,
    setOverrideSSL,
    certificateValid
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const statuses = {
    'inactive': {
      'icon': 'info',
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
    },
    'processing': {
      'icon': 'loading',
      'color': 'black'
    }
  };
  const statusIcon = item.status !== 'success' && item.current_action === 'none' ? 'empty' : statuses[status].icon;
  const statusColor = statuses[status].color;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_1__["default"], {
    name: statusIcon,
    color: statusColor
  }), title, id === 'certificate' && !certificateValid && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, "\xA0", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "#",
    onClick: e => refreshSSLStatus(e)
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Check again", "really-simple-ssl")))), id === 'certificate' && !certificateValid && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
    className: "rsssl-override-detection-toggle"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    onChange: e => setOverrideSSL(e.target.checked),
    type: "checkbox",
    checked: overrideSSL
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Override SSL detection.", "really-simple-ssl"))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.memo)(ListItem));

/***/ }),

/***/ "./src/Onboarding/Items/PremiumItem.js":
/*!*********************************************!*\
  !*** ./src/Onboarding/Items/PremiumItem.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);


const PremiumItem = ({
  item
}) => {
  let {
    title
  } = item;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-modal-premium-container"
  }, "PRO"), title);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.memo)(PremiumItem));

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
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _OnboardingControls__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./OnboardingControls */ "./src/Onboarding/OnboardingControls.js");
/* harmony import */ var _Steps_StepEmail__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./Steps/StepEmail */ "./src/Onboarding/Steps/StepEmail.js");
/* harmony import */ var _Steps_StepConfig__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./Steps/StepConfig */ "./src/Onboarding/Steps/StepConfig.js");
/* harmony import */ var _Steps_StepFeatures__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Steps/StepFeatures */ "./src/Onboarding/Steps/StepFeatures.js");
/* harmony import */ var _Steps_StepPlugins__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./Steps/StepPlugins */ "./src/Onboarding/Steps/StepPlugins.js");
/* harmony import */ var _Steps_StepPro__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./Steps/StepPro */ "./src/Onboarding/Steps/StepPro.js");
/* harmony import */ var _PremiumItem_scss__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./PremiumItem.scss */ "./src/Onboarding/PremiumItem.scss");
/* harmony import */ var _checkbox_scss__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./checkbox.scss */ "./src/Onboarding/checkbox.scss");
/* harmony import */ var _onboarding_scss__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./onboarding.scss */ "./src/Onboarding/onboarding.scss");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_16__);

















const Onboarding = ({
  isModal
}) => {
  const {
    fetchFieldsData,
    fieldsLoaded
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const {
    getSteps,
    error,
    networkwide,
    sslEnabled,
    dataLoaded,
    processing,
    currentStep,
    currentStepIndex,
    setCurrentStepIndex,
    networkActivationStatus,
    networkProgress,
    activateSSLNetworkWide
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (networkwide && networkActivationStatus === 'main_site_activated') {
      //run networkwide activation with a delay
      setTimeout(() => {
        activateSSLNetworkWide();
      }, 1000);
    }
  }, [networkActivationStatus, networkProgress]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!fieldsLoaded) {
      fetchFieldsData();
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const run = async () => {
      await getSteps(false);
      if (dataLoaded && sslEnabled && currentStepIndex === 0) {
        setCurrentStepIndex(1);
      }
    };
    run();
  }, []);
  if (error) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_4__["default"], {
      lines: "3",
      error: error
    });
  }
  let processingClass = ''; //processing ? 'rsssl-processing' : '';
  //get 'other_host_type' field from fields

  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, !dataLoaded && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-onboarding-placeholder"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_3__["default"], {
    name: "loading",
    color: "grey"
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Fetching next step...", "really-simple-ssl"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_4__["default"], {
    lines: "3"
  }))), dataLoaded && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: processingClass + " rsssl-" + currentStep.id
  }, currentStep.id === 'activate_ssl' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Steps_StepConfig__WEBPACK_IMPORTED_MODULE_9__["default"], {
    isModal: isModal
  })), currentStep.id === 'features' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Steps_StepFeatures__WEBPACK_IMPORTED_MODULE_10__["default"], null)), currentStep.id === 'email' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Steps_StepEmail__WEBPACK_IMPORTED_MODULE_8__["default"], null)), currentStep.id === 'plugins' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Steps_StepPlugins__WEBPACK_IMPORTED_MODULE_11__["default"], null)), currentStep.id === 'pro' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Steps_StepPro__WEBPACK_IMPORTED_MODULE_12__["default"], null)), !isModal && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_OnboardingControls__WEBPACK_IMPORTED_MODULE_7__["default"], {
    isModal: false
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Onboarding);

/***/ }),

/***/ "./src/Onboarding/OnboardingControls.js":
/*!**********************************************!*\
  !*** ./src/Onboarding/OnboardingControls.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Menu_MenuData__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Menu/MenuData */ "./src/Menu/MenuData.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Dashboard/Progress/ProgressData */ "./src/Dashboard/Progress/ProgressData.js");
/* harmony import */ var _Settings_RiskConfiguration_RiskData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Settings/RiskConfiguration/RiskData */ "./src/Settings/RiskConfiguration/RiskData.js");
/* harmony import */ var _Dashboard_SslLabs_SslLabsData__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../Dashboard/SslLabs/SslLabsData */ "./src/Dashboard/SslLabs/SslLabsData.js");









const OnboardingControls = ({
  isModal
}) => {
  const {
    getProgressData
  } = (0,_Dashboard_Progress_ProgressData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  const {
    updateField,
    setChangedField,
    updateFieldsData,
    fetchFieldsData,
    saveFields
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    setSelectedMainMenuItem,
    selectedSubMenuItem
  } = (0,_Menu_MenuData__WEBPACK_IMPORTED_MODULE_3__["default"])();
  const {
    fetchFirstRun,
    fetchVulnerabilities
  } = (0,_Settings_RiskConfiguration_RiskData__WEBPACK_IMPORTED_MODULE_7__["default"])();
  const {
    setSslScanStatus
  } = (0,_Dashboard_SslLabs_SslLabsData__WEBPACK_IMPORTED_MODULE_8__["default"])();
  const {
    dismissModal,
    activateSSL,
    certificateValid,
    setFooterStatus,
    networkwide,
    processing,
    setProcessing,
    steps,
    currentStepIndex,
    currentStep,
    setCurrentStepIndex,
    overrideSSL,
    email,
    saveEmail,
    pluginInstaller
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_5__["default"])();
  const goToDashboard = () => {
    if (isModal) {
      dismissModal(true);
    }
    setSelectedMainMenuItem('dashboard');
  };
  const saveAndContinue = async () => {
    let vulnerabilityDetectionEnabled = false;
    if (currentStep.id === 'features') {
      setCurrentStepIndex(currentStepIndex + 1);
      setProcessing(true);
      //loop through all items of currentStep.items
      for (const item of currentStep.items) {
        if (item.id === 'health_scan' && item.activated) {
          setFooterStatus((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Starting SSL health scan...", "really-simple-ssl"));
          setSslScanStatus('active');
        }
        for (const fieldId of Object.values(item.options)) {
          updateField(fieldId, item.activated);
          setChangedField(fieldId, item.activated);
        }
        if (item.id === 'vulnerability_detection') {
          vulnerabilityDetectionEnabled = item.activated;
        }
      }
      setFooterStatus((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Activating options...", "really-simple-ssl"));
      await saveFields(true, false);
      if (vulnerabilityDetectionEnabled) {
        setFooterStatus((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Initializing vulnerability detection...", "really-simple-ssl"));
        await fetchFirstRun();
        setFooterStatus((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Scanning for vulnerabilities...", "really-simple-ssl"));
        await fetchVulnerabilities();
      }
      setFooterStatus((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Updating dashboard...", "really-simple-ssl"));
      await getProgressData();
      setFooterStatus('');
      setProcessing(false);
    }
    if (currentStep.id === 'email') {
      await saveEmail();
      setCurrentStepIndex(currentStepIndex + 1);
      updateField('send_notifications_email', true);
      updateField('notifications_email_address', email);
      updateFieldsData(selectedSubMenuItem);
    }
    if (currentStep.id === 'plugins') {
      setCurrentStepIndex(currentStepIndex + 1);
      for (const item of currentStep.items) {
        if (item.action !== 'none' && item.action !== null) {
          // Add the promise returned by pluginInstaller to the array
          await pluginInstaller(item.id, item.action, item.title);
        }
      }
      setFooterStatus('');
    }
    if (currentStep.id === 'pro') {
      if (rsssl_settings.is_premium) {
        setProcessing(true);
        //loop through all items of currentStep.items
        for (const item of currentStep.items) {
          if (item.activated) {
            for (const fieldId of Object.values(item.options)) {
              updateField(fieldId, true);
              setChangedField(fieldId, true);
            }
          }
        }
        setFooterStatus((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Activating options...", "really-simple-ssl"));
        await saveFields(true, false);
        setFooterStatus((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Updating dashboard...", "really-simple-ssl"));
        await getProgressData();
        setFooterStatus('');
        setProcessing(false);
      }
      goToDashboard();
    }
  };
  const handleActivateSSL = async () => {
    await activateSSL();
    await getProgressData();
    await fetchFieldsData();
  };
  const goToLetsEncrypt = () => {
    if (isModal) dismissModal(true);
    window.location.href = rsssl_settings.letsencrypt_url;
  };
  let ActivateSSLText = networkwide ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Activate SSL networkwide", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Activate SSL", "really-simple-ssl");
  if (currentStepIndex === 0) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, isModal && !certificateValid && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      onClick: () => {
        goToLetsEncrypt();
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Install SSL", "really-simple-ssl")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      disabled: processing || !certificateValid && !overrideSSL,
      isPrimary: true,
      onClick: () => {
        handleActivateSSL();
      }
    }, ActivateSSLText));
  }
  if (currentStepIndex > 0 && currentStepIndex < steps.length - 1) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      onClick: () => {
        setCurrentStepIndex(currentStepIndex + 1);
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Skip', 'really-simple-ssl')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      isPrimary: true,
      onClick: () => saveAndContinue()
    }, currentStep.button));
  }

  //for last step only
  if (steps.length - 1 === currentStepIndex) {
    let upgradeText = rsssl_settings.is_bf ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Get 40% off", "really-simple-ssl") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Get PRO", "really-simple-ssl");
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      isPrimary: true,
      onClick: () => saveAndContinue()
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Finish', 'really-simple-ssl')), !rsssl_settings.pro_plugin_active && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Button, {
      rel: "noreferrer noopener",
      target: "_blank",
      isPrimary: true,
      href: rsssl_settings.upgrade_link
    }, upgradeText));
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (OnboardingControls);

/***/ }),

/***/ "./src/Onboarding/OnboardingModal.js":
/*!*******************************************!*\
  !*** ./src/Onboarding/OnboardingModal.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Onboarding__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Onboarding */ "./src/Onboarding/Onboarding.js");
/* harmony import */ var _Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Placeholder/Placeholder */ "./src/Placeholder/Placeholder.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _utils_Icon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/Icon */ "./src/utils/Icon.js");
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _modal_src_components_Modal_RssslModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../../modal/src/components/Modal/RssslModal */ "../modal/src/components/Modal/RssslModal.js");
/* harmony import */ var _OnboardingControls__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./OnboardingControls */ "./src/Onboarding/OnboardingControls.js");










const OnboardingModal = () => {
  const {
    footerStatus,
    showOnboardingModal,
    fetchOnboardingModalStatus,
    modalStatusLoaded,
    currentStep,
    dismissModal
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_6__["default"])();
  const {
    fieldsLoaded
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_7__["default"])();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!modalStatusLoaded) {
      fetchOnboardingModalStatus();
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (showOnboardingModal) {
      dismissModal(false);
    }
  }, [showOnboardingModal]);
  const modalContent = () => {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, !fieldsLoaded && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_5__["default"], {
      name: "loading"
    }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Please wait while we detect your setup", "really-simple-ssl"))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Placeholder_Placeholder__WEBPACK_IMPORTED_MODULE_3__["default"], {
      lines: "3"
    })), fieldsLoaded && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Onboarding__WEBPACK_IMPORTED_MODULE_2__["default"], {
      isModal: true
    }));
  };
  const setOpen = open => {
    if (!open) {
      dismissModal(true);
    }
  };
  const handleFooterStatus = () => {
    if (footerStatus.length === 0) {
      return false;
    }
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Icon__WEBPACK_IMPORTED_MODULE_5__["default"], {
      name: "loading",
      color: "grey"
    }), footerStatus);
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_modal_src_components_Modal_RssslModal__WEBPACK_IMPORTED_MODULE_8__["default"], {
    className: "rsssl-onboarding-modal",
    title: currentStep.title,
    subTitle: currentStep.subtitle,
    content: modalContent(),
    isOpen: showOnboardingModal,
    setOpen: setOpen,
    buttons: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_OnboardingControls__WEBPACK_IMPORTED_MODULE_9__["default"], {
      isModal: true
    }),
    footer: handleFooterStatus()
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (OnboardingModal);

/***/ }),

/***/ "./src/Onboarding/Steps/StepConfig.js":
/*!********************************************!*\
  !*** ./src/Onboarding/Steps/StepConfig.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../Settings/FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _Settings_Host_Host__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../Settings/Host/Host */ "./src/Settings/Host/Host.js");
/* harmony import */ var _Items_ListItem__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../Items/ListItem */ "./src/Onboarding/Items/ListItem.js");







const StepConfig = ({
  isModal
}) => {
  const {
    fetchFieldsData,
    getField,
    fieldsLoaded,
    updateField,
    setChangedField,
    saveFields
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    currentStep
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_3__["default"])();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!fieldsLoaded) {
      fetchFieldsData();
    }
  }, []);
  let otherHostsField = fieldsLoaded && getField('other_host_type');
  let items = currentStep.items ? currentStep.items : [];
  if (rsssl_settings.cloudflare) {
    let cfItem = {
      status: 'success',
      title: "CloudFlare",
      id: 'cf'
    };
    items.unshift(cfItem);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, isModal && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Settings_Host_Host__WEBPACK_IMPORTED_MODULE_5__["default"], {
    field: otherHostsField,
    showDisabledWhenSaving: false
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, items && items.map((item, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Items_ListItem__WEBPACK_IMPORTED_MODULE_6__["default"], {
    key: 'step-config-' + index,
    item: item
  })))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.memo)(StepConfig));

/***/ }),

/***/ "./src/Onboarding/Steps/StepEmail.js":
/*!*******************************************!*\
  !*** ./src/Onboarding/Steps/StepEmail.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../Settings/FieldsData */ "./src/Settings/FieldsData.js");





const StepEmail = () => {
  const {
    fetchFieldsData,
    getFieldValue,
    fieldsLoaded
  } = (0,_Settings_FieldsData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  const {
    email,
    setEmail,
    includeTips,
    setIncludeTips
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_3__["default"])();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!fieldsLoaded) {
      fetchFieldsData();
    }
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (getFieldValue('notifications_email_address') !== '' && email === '') {
      setEmail(getFieldValue('notifications_email_address'));
    }
  }, []);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "email",
    value: email,
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Your email address", "really-simple-ssl"),
    onChange: e => setEmail(e.target.value)
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    onChange: e => setIncludeTips(e.target.checked),
    type: "checkbox",
    checked: includeTips
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Include 6 Tips & Tricks to get started with Really Simple SSL.", "really-simple-ssl"), "\xA0", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://really-simple-ssl.com/legal/privacy-statement/",
    target: "_blank"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Privacy Statement", "really-simple-ssl")))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.memo)(StepEmail));

/***/ }),

/***/ "./src/Onboarding/Steps/StepFeatures.js":
/*!**********************************************!*\
  !*** ./src/Onboarding/Steps/StepFeatures.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Items_CheckboxItem__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Items/CheckboxItem */ "./src/Onboarding/Items/CheckboxItem.js");
/* harmony import */ var _Items_PremiumItem__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Items/PremiumItem */ "./src/Onboarding/Items/PremiumItem.js");





const StepFeatures = () => {
  const {
    currentStep
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  let items = currentStep.items ? currentStep.items : [];
  let freeItems = items.filter(item => !item.premium);
  let premiumItems = items.filter(item => item.premium);
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, freeItems && freeItems.map((item, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Items_CheckboxItem__WEBPACK_IMPORTED_MODULE_3__["default"], {
    key: 'step-features' + index,
    item: item
  })), premiumItems && premiumItems.map((item, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Items_PremiumItem__WEBPACK_IMPORTED_MODULE_4__["default"], {
    key: 'step-features' + index,
    item: item
  }))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.memo)(StepFeatures));

/***/ }),

/***/ "./src/Onboarding/Steps/StepPlugins.js":
/*!*********************************************!*\
  !*** ./src/Onboarding/Steps/StepPlugins.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Items_CheckboxItem__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Items/CheckboxItem */ "./src/Onboarding/Items/CheckboxItem.js");




const StepPlugins = () => {
  const {
    currentStep,
    currentStepIndex,
    setCurrentStepIndex
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    //if all plugins are already activated, we skip the plugins step
    let plugins = currentStep.items;
    if (plugins.filter(item => item.action !== 'none').length === 0) {
      setCurrentStepIndex(currentStepIndex + 1);
    }
  }, []);
  let plugins = currentStep.items;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, plugins && plugins.map((item, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Items_CheckboxItem__WEBPACK_IMPORTED_MODULE_3__["default"], {
    key: 'step-plugins' + index,
    item: item,
    disabled: item.action === 'none'
  }))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.memo)(StepPlugins));

/***/ }),

/***/ "./src/Onboarding/Steps/StepPro.js":
/*!*****************************************!*\
  !*** ./src/Onboarding/Steps/StepPro.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _OnboardingData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../OnboardingData */ "./src/Onboarding/OnboardingData.js");
/* harmony import */ var _Items_CheckboxItem__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Items/CheckboxItem */ "./src/Onboarding/Items/CheckboxItem.js");
/* harmony import */ var _Items_PremiumItem__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Items/PremiumItem */ "./src/Onboarding/Items/PremiumItem.js");





const StepPro = () => {
  const {
    currentStep
  } = (0,_OnboardingData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  let premiumItems = currentStep.items;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, !rsssl_settings.pro_plugin_active && premiumItems && premiumItems.map((item, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Items_PremiumItem__WEBPACK_IMPORTED_MODULE_4__["default"], {
    key: 'step-pro' + index,
    item: item
  })), rsssl_settings.pro_plugin_active && premiumItems && premiumItems.map((item, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Items_CheckboxItem__WEBPACK_IMPORTED_MODULE_3__["default"], {
    key: 'step-pro' + index,
    item: item
  }))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.memo)(StepPro));

/***/ }),

/***/ "./src/Placeholder/Placeholder.js":
/*!****************************************!*\
  !*** ./src/Placeholder/Placeholder.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Error__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/Error */ "./src/utils/Error.js");


const Placeholder = props => {
  let lines = props.lines;
  if (!lines) lines = 4;
  if (props.error) {
    lines = 0;
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-placeholder"
  }, props.error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_utils_Error__WEBPACK_IMPORTED_MODULE_1__["default"], {
    error: props.error
  }), Array.from({
    length: lines
  }).map((item, i) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "rsssl-placeholder-line",
    key: "placeholder-" + i
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Placeholder);

/***/ }),

/***/ "./src/Settings/AutoComplete/AutoCompleteControl.js":
/*!**********************************************************!*\
  !*** ./src/Settings/AutoComplete/AutoCompleteControl.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _mui_material__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mui/material */ "./node_modules/@mui/material/Autocomplete/Autocomplete.js");
/* harmony import */ var _material_ui_core_TextField__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @material-ui/core/TextField */ "./node_modules/@material-ui/core/esm/TextField/TextField.js");
/* harmony import */ var _AutoComplete_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AutoComplete.scss */ "./src/Settings/AutoComplete/AutoComplete.scss");
/* harmony import */ var _material_ui_styles__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @material-ui/styles */ "./node_modules/@material-ui/styles/esm/makeStyles/makeStyles.js");

/*
* The native selectControl doesn't allow disabling per option.
*/







const useStyles = (0,_material_ui_styles__WEBPACK_IMPORTED_MODULE_3__["default"])(() => ({
  autoComplete: {
    fontSize: "12px"
  }
}));
const AutoCompleteControl = ({
  field,
  disabled,
  value,
  options,
  label,
  onChange
}) => {
  let selectDisabled = !Array.isArray(disabled) && disabled;
  const classes = useStyles();
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_mui_material__WEBPACK_IMPORTED_MODULE_4__["default"], {
    classes: {
      input: classes.autoComplete,
      option: classes.autoComplete
    },
    disabled: selectDisabled,
    disablePortal: true,
    value: value,
    id: field.id,
    options: options,
    getOptionLabel: option => {
      if (option && option.label) {
        return option.label;
      }
      const selectedOption = options.find(item => item.value === option);
      if (selectedOption) {
        return selectedOption.label;
      }
      return option;
    },
    isOptionEqualToValue: (option, value) => {
      return option.value === value;
    },
    onChange: (event, newValue) => {
      let value = newValue && newValue.value ? newValue.value : '';
      onChange(value);
    },
    renderInput: params => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_material_ui_core_TextField__WEBPACK_IMPORTED_MODULE_5__["default"], {
      ...params,
      label: label,
      margin: "normal",
      variant: "outlined",
      fullWidth: true
    })
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AutoCompleteControl);

/***/ }),

/***/ "./src/Settings/Host/Host.js":
/*!***********************************!*\
  !*** ./src/Settings/Host/Host.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _FieldsData__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../FieldsData */ "./src/Settings/FieldsData.js");
/* harmony import */ var _AutoComplete_AutoCompleteControl__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../AutoComplete/AutoCompleteControl */ "./src/Settings/AutoComplete/AutoCompleteControl.js");
/* harmony import */ var _HostData__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./HostData */ "./src/Settings/Host/HostData.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);






const Host = ({
  field,
  showDisabledWhenSaving = true
}) => {
  const {
    updateField,
    setChangedField,
    saveFields,
    handleNextButtonDisabled
  } = (0,_FieldsData__WEBPACK_IMPORTED_MODULE_2__["default"])();
  const disabled = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(false);
  const {
    fetchHosts,
    hosts,
    hostsLoaded
  } = (0,_HostData__WEBPACK_IMPORTED_MODULE_4__["default"])();
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!hostsLoaded) {
      fetchHosts();
    }
  }, []);
  const onChangeHandler = async fieldValue => {
    //force update, and get new fields.
    handleNextButtonDisabled(true);
    if (showDisabledWhenSaving) {
      disabled.current = true;
    }
    updateField(field.id, fieldValue);
    setChangedField(field.id, fieldValue);
    await saveFields(true, false);
    handleNextButtonDisabled(false);
    disabled.current = false;
  };
  let loadedHosts = hostsLoaded ? hosts : [];
  let options = [];
  let item = {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Optional - Select your hosting provider.', 'really-simple-ssl'),
    value: ''
  };
  if (field.value.length === 0) {
    options.push(item);
  }
  for (let key in loadedHosts) {
    if (loadedHosts.hasOwnProperty(key)) {
      let item = {};
      item.label = loadedHosts[key].name;
      item.value = key;
      options.push(item);
    }
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_AutoComplete_AutoCompleteControl__WEBPACK_IMPORTED_MODULE_3__["default"], {
    className: "rsssl-select",
    field: field,
    label: field.label,
    onChange: fieldValue => onChangeHandler(fieldValue),
    value: field.value,
    options: options,
    disabled: disabled.current
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.memo)(Host));

/***/ }),

/***/ "./src/Settings/Host/HostData.js":
/*!***************************************!*\
  !*** ./src/Settings/Host/HostData.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");


const useHostData = (0,zustand__WEBPACK_IMPORTED_MODULE_1__.create)((set, get) => ({
  hosts: [],
  hostsLoaded: false,
  fetchHosts: async id => {
    try {
      const response = await _utils_api__WEBPACK_IMPORTED_MODULE_0__.doAction('get_hosts', {
        id: id
      });

      // Handle the response
      if (!response) {
        console.error('No response received from the server.');
        return;
      }
      let hosts = response.hosts;
      // Set the roles state with formatted data
      set({
        hosts: hosts,
        hostsLoaded: true
      });
    } catch (error) {
      console.error('Error:', error);
    }
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useHostData);

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
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var zustand__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! zustand */ "./node_modules/zustand/esm/index.mjs");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/api */ "./src/utils/api.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var immer__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! immer */ "./node_modules/immer/dist/immer.esm.mjs");

/* Creates A Store For Risk Data using Zustand */





const UseRiskData = (0,zustand__WEBPACK_IMPORTED_MODULE_3__.create)((set, get) => ({
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
  vulList: [],
  //for storing the list of vulnerabilities
  setDataLoaded: value => set({
    dataLoaded: value
  }),
  //update Risk Data
  updateRiskData: async (field, value) => {
    if (get().processing) return;
    set({
      processing: true
    });
    set((0,immer__WEBPACK_IMPORTED_MODULE_4__.produce)(state => {
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
    set({
      processing: false
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
  fetchFirstRun: async () => {
    if (get().processing) return;
    set({
      processing: true
    });
    await _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('vulnerabilities_scan_files');
    set({
      processing: false
    });
  },
  /*
  * Functions
   */
  fetchVulnerabilities: async () => {
    if (get().processing) return;
    set({
      processing: true
    });
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
          item.vulnerability_action = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
            className: "rsssl-action-buttons"
          }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
            className: "rsssl-button button-secondary",
            href: "https://really-simple-ssl.com/vulnerabilities/" + item.rss_identifier,
            target: "_blank",
            rel: "noopener noreferrer"
          }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Details", "really-simple-ssl")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
            disabled: !item.update_available,
            href: updateUrl,
            className: "rsssl-button button-primary"
          }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("Update", "really-simple-ssl")));
        });
      }
      let riskData = fetched.data.riskData;
      if (!Array.isArray(riskData)) {
        riskData = [];
      }
      riskData = get().enforceCascadingRiskLevels(riskData);
      set((0,immer__WEBPACK_IMPORTED_MODULE_4__.produce)(state => {
        state.vulnerabilities = vulnerabilities;
        state.vulList = vulList;
        state.updates = fetched.data.updates;
        state.dataLoaded = true;
        state.riskNaming = fetched.data.riskNaming;
        state.lastChecked = fetched.data.lastChecked;
        state.vulEnabled = fetched.data.vulEnabled;
        state.riskData = riskData;
        state.processing = false;
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
    try {
      const fetched = await _utils_api__WEBPACK_IMPORTED_MODULE_1__.doAction('rsssl_scan_files');
      if (fetched.request_success) {
        //we get the data again
        await get().fetchVulnerabilities();
      }
    } catch (e) {
      console.error(e);
    }
  }
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UseRiskData);

/***/ }),

/***/ "./src/utils/Icon.js":
/*!***************************!*\
  !*** ./src/utils/Icon.js ***!
  \***************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_tooltip__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-tooltip */ "./node_modules/react-tooltip/dist/react-tooltip.min.mjs");

// import Tooltip from '@mui/material/Tooltip';

// import {styled} from "@mui/material/styles";
// import {opacity} from "../../../../../../wp-includes/js/codemirror/csslint";

const IconHtml = React.forwardRef(function IconHtml(props, ref) {
  const {
    name,
    color,
    size
  } = props;
  // set defaults
  const iconName = name || 'bullet';
  const iconColor = color || 'black';
  const iconSize = size || 15;
  const iconColors = {
    'black': 'var(--rsp-black)',
    'green': 'var(--rsp-green)',
    'yellow': 'var(--rsp-yellow)',
    'orange': 'var(--rsp-yellow)',
    'red-faded': 'var(--rsp-red-faded)',
    'red': 'var(--rsp-red)',
    'grey': 'var(--rsp-grey-400)',
    'red-warning': 'var(--rsp-red-faded)'
  };
  let renderedIcon = '';
  if (iconName === 'bullet') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256z"
    }));
  }
  if (iconName === 'circle') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"
    }));
  }
  if (iconName === 'check') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"
    }));
  }
  if (iconName === 'warning') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M506.3 417l-213.3-364c-16.33-28-57.54-28-73.98 0l-213.2 364C-10.59 444.9 9.849 480 42.74 480h426.6C502.1 480 522.6 445 506.3 417zM232 168c0-13.25 10.75-24 24-24S280 154.8 280 168v128c0 13.25-10.75 24-23.1 24S232 309.3 232 296V168zM256 416c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 401.9 273.4 416 256 416z"
    }));
  }
  if (iconName === 'error') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM232 152C232 138.8 242.8 128 256 128s24 10.75 24 24v128c0 13.25-10.75 24-24 24S232 293.3 232 280V152zM256 400c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 385.9 273.4 400 256 400z"
    }));
  }
  if (iconName === 'times') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 320 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"
    }));
  }
  if (iconName === 'circle-check') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM371.8 211.8C382.7 200.9 382.7 183.1 371.8 172.2C360.9 161.3 343.1 161.3 332.2 172.2L224 280.4L179.8 236.2C168.9 225.3 151.1 225.3 140.2 236.2C129.3 247.1 129.3 264.9 140.2 275.8L204.2 339.8C215.1 350.7 232.9 350.7 243.8 339.8L371.8 211.8z"
    }));
  }
  if (iconName === 'circle-times') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM175 208.1L222.1 255.1L175 303C165.7 312.4 165.7 327.6 175 336.1C184.4 346.3 199.6 346.3 208.1 336.1L255.1 289.9L303 336.1C312.4 346.3 327.6 346.3 336.1 336.1C346.3 327.6 346.3 312.4 336.1 303L289.9 255.1L336.1 208.1C346.3 199.6 346.3 184.4 336.1 175C327.6 165.7 312.4 165.7 303 175L255.1 222.1L208.1 175C199.6 165.7 184.4 165.7 175 175C165.7 184.4 165.7 199.6 175 208.1V208.1z"
    }));
  }
  if (iconName === 'chevron-up') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 448 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M416 352c-8.188 0-16.38-3.125-22.62-9.375L224 173.3l-169.4 169.4c-12.5 12.5-32.75 12.5-45.25 0s-12.5-32.75 0-45.25l192-192c12.5-12.5 32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25C432.4 348.9 424.2 352 416 352z"
    }));
  }
  if (iconName === 'chevron-down') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 448 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"
    }));
  }
  if (iconName === 'chevron-right') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 320 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"
    }));
  }
  if (iconName === 'chevron-left') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 320 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M224 480c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25l192-192c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25L77.25 256l169.4 169.4c12.5 12.5 12.5 32.75 0 45.25C240.4 476.9 232.2 480 224 480z"
    }));
  }
  if (iconName === 'plus') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M432 256c0 17.69-14.33 32.01-32 32.01H256v144c0 17.69-14.33 31.99-32 31.99s-32-14.3-32-31.99v-144H48c-17.67 0-32-14.32-32-32.01s14.33-31.99 32-31.99H192v-144c0-17.69 14.33-32.01 32-32.01s32 14.32 32 32.01v144h144C417.7 224 432 238.3 432 256z"
    }));
  }
  if (iconName === 'minus') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M400 288h-352c-17.69 0-32-14.32-32-32.01s14.31-31.99 32-31.99h352c17.69 0 32 14.3 32 31.99S417.7 288 400 288z"
    }));
  }
  if (iconName === 'sync') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M483.515 28.485L431.35 80.65C386.475 35.767 324.485 8 256 8 123.228 8 14.824 112.338 8.31 243.493 7.971 250.311 13.475 256 20.301 256h28.045c6.353 0 11.613-4.952 11.973-11.294C66.161 141.649 151.453 60 256 60c54.163 0 103.157 21.923 138.614 57.386l-54.128 54.129c-7.56 7.56-2.206 20.485 8.485 20.485H492c6.627 0 12-5.373 12-12V36.971c0-10.691-12.926-16.045-20.485-8.486zM491.699 256h-28.045c-6.353 0-11.613 4.952-11.973 11.294C445.839 370.351 360.547 452 256 452c-54.163 0-103.157-21.923-138.614-57.386l54.128-54.129c7.56-7.56 2.206-20.485-8.485-20.485H20c-6.627 0-12 5.373-12 12v143.029c0 10.691 12.926 16.045 20.485 8.485L80.65 431.35C125.525 476.233 187.516 504 256 504c132.773 0 241.176-104.338 247.69-235.493.339-6.818-5.165-12.507-11.991-12.507z"
    }));
  }
  if (iconName === 'sync-error') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M256 79.1C178.5 79.1 112.7 130.1 89.2 199.7C84.96 212.2 71.34 218.1 58.79 214.7C46.23 210.5 39.48 196.9 43.72 184.3C73.6 95.8 157.3 32 256 32C337.5 32 408.8 75.53 448 140.6V104C448 90.75 458.7 80 472 80C485.3 80 496 90.75 496 104V200C496 213.3 485.3 224 472 224H376C362.7 224 352 213.3 352 200C352 186.7 362.7 176 376 176H412.8C383.7 118.1 324.4 80 256 80V79.1zM280 263.1C280 277.3 269.3 287.1 256 287.1C242.7 287.1 232 277.3 232 263.1V151.1C232 138.7 242.7 127.1 256 127.1C269.3 127.1 280 138.7 280 151.1V263.1zM224 352C224 334.3 238.3 319.1 256 319.1C273.7 319.1 288 334.3 288 352C288 369.7 273.7 384 256 384C238.3 384 224 369.7 224 352zM40 432C26.75 432 16 421.3 16 408V311.1C16 298.7 26.75 287.1 40 287.1H136C149.3 287.1 160 298.7 160 311.1C160 325.3 149.3 336 136 336H99.19C128.3 393 187.6 432 256 432C333.5 432 399.3 381.9 422.8 312.3C427 299.8 440.7 293 453.2 297.3C465.8 301.5 472.5 315.1 468.3 327.7C438.4 416.2 354.7 480 256 480C174.5 480 103.2 436.5 64 371.4V408C64 421.3 53.25 432 40 432V432z"
    }));
  }
  if (iconName === 'shortcode') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 448 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M128 32H32C14.4 32 0 46.4 0 64v384c0 17.6 14.4 32 32 32h96C145.7 480 160 465.7 160 448S145.7 416 128 416H64V96h64C145.7 96 160 81.67 160 64S145.7 32 128 32zM416 32h-96C302.3 32 288 46.33 288 63.1S302.3 96 319.1 96H384v320h-64C302.3 416 288 430.3 288 447.1S302.3 480 319.1 480H416c17.6 0 32-14.4 32-32V64C448 46.4 433.6 32 416 32z"
    }));
  }
  if (iconName === 'file') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 384 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M0 64C0 28.65 28.65 0 64 0H229.5C246.5 0 262.7 6.743 274.7 18.75L365.3 109.3C377.3 121.3 384 137.5 384 154.5V448C384 483.3 355.3 512 320 512H64C28.65 512 0 483.3 0 448V64zM336 448V160H256C238.3 160 224 145.7 224 128V48H64C55.16 48 48 55.16 48 64V448C48 456.8 55.16 464 64 464H320C328.8 464 336 456.8 336 448z"
    }));
  }
  if (iconName === 'file-disabled') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 640 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M639.1 487.1c0-7.119-3.153-14.16-9.191-18.89l-118.8-93.12l.0013-237.3c0-16.97-6.742-33.26-18.74-45.26l-74.63-74.64C406.6 6.742 390.3 0 373.4 0H192C156.7 0 128 28.65 128 64L128 75.01L38.82 5.11C34.41 1.672 29.19 0 24.04 0C10.19 0-.0002 11.3-.0002 23.1c0 7.12 3.153 14.16 9.192 18.89l591.1 463.1C605.6 510.3 610.8 512 615.1 512C629.8 512 639.1 500.6 639.1 487.1zM464 338.4l-287.1-225.7l-.002-48.51c0-8.836 7.164-16 15.1-16h160l-.0065 79.87c0 17.67 14.33 31.1 31.1 31.1L464 159.1V338.4zM448 463.1H192c-8.834 0-15.1-7.164-15.1-16L176 234.6L128 197L128 447.1c0 35.34 28.65 64 63.1 64H448c20.4 0 38.45-9.851 50.19-24.84l-37.72-29.56C457.5 461.4 453.2 463.1 448 463.1z"
    }));
  }
  if (iconName === 'loading') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[color],
      d: "M304 48c0-26.5-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48s48-21.5 48-48zm0 416c0-26.5-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48s48-21.5 48-48zM48 304c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48zm464-48c0-26.5-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48s48-21.5 48-48zM142.9 437c18.7-18.7 18.7-49.1 0-67.9s-49.1-18.7-67.9 0s-18.7 49.1 0 67.9s49.1 18.7 67.9 0zm0-294.2c18.7-18.7 18.7-49.1 0-67.9S93.7 56.2 75 75s-18.7 49.1 0 67.9s49.1 18.7 67.9 0zM369.1 437c18.7 18.7 49.1 18.7 67.9 0s18.7-49.1 0-67.9s-49.1-18.7-67.9 0s-18.7 49.1 0 67.9z"
    }));
  }
  if (iconName === 'file-download') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 384 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M216 342.1V240c0-13.25-10.75-24-24-24S168 226.8 168 240v102.1L128.1 303C124.3 298.3 118.2 296 112 296S99.72 298.3 95.03 303c-9.375 9.375-9.375 24.56 0 33.94l80 80c9.375 9.375 24.56 9.375 33.94 0l80-80c9.375-9.375 9.375-24.56 0-33.94s-24.56-9.375-33.94 0L216 342.1zM365.3 93.38l-74.63-74.64C278.6 6.742 262.3 0 245.4 0H64C28.65 0 0 28.65 0 64l.0065 384c0 35.34 28.65 64 64 64H320c35.2 0 64-28.8 64-64V138.6C384 121.7 377.3 105.4 365.3 93.38zM336 448c0 8.836-7.164 16-16 16H64.02c-8.838 0-16-7.164-16-16L48 64.13c0-8.836 7.164-16 16-16h160L224 128c0 17.67 14.33 32 32 32h79.1V448z"
    }));
  }
  if (iconName === 'calendar') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 448 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M152 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V448C448 483.3 419.3 512 384 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24V64zM48 448C48 456.8 55.16 464 64 464H384C392.8 464 400 456.8 400 448V192H48V448z"
    }));
  }
  if (iconName === 'calendar-error') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 576 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M151.1 64H296V24C296 10.75 306.7 0 320 0C333.3 0 344 10.75 344 24V64H384C419.3 64 448 92.65 448 128V192H47.1V448C47.1 456.8 55.16 464 63.1 464H284.5C296.7 482.8 312.5 499.1 330.8 512H64C28.65 512 0 483.3 0 448V128C0 92.65 28.65 64 64 64H104V24C104 10.75 114.7 0 128 0C141.3 0 152 10.75 152 24L151.1 64zM576 368C576 447.5 511.5 512 432 512C352.5 512 287.1 447.5 287.1 368C287.1 288.5 352.5 224 432 224C511.5 224 576 288.5 576 368zM432 416C418.7 416 408 426.7 408 440C408 453.3 418.7 464 432 464C445.3 464 456 453.3 456 440C456 426.7 445.3 416 432 416zM447.1 288C447.1 279.2 440.8 272 431.1 272C423.2 272 415.1 279.2 415.1 288V368C415.1 376.8 423.2 384 431.1 384C440.8 384 447.1 376.8 447.1 368V288z"
    }));
  }
  if (iconName === 'help') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM256 400c-18 0-32-14-32-32s13.1-32 32-32c17.1 0 32 14 32 32S273.1 400 256 400zM325.1 258L280 286V288c0 13-11 24-24 24S232 301 232 288V272c0-8 4-16 12-21l57-34C308 213 312 206 312 198C312 186 301.1 176 289.1 176h-51.1C225.1 176 216 186 216 198c0 13-11 24-24 24s-24-11-24-24C168 159 199 128 237.1 128h51.1C329 128 360 159 360 198C360 222 347 245 325.1 258z"
    }));
  }
  if (iconName === 'copy') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M502.6 70.63l-61.25-61.25C435.4 3.371 427.2 0 418.7 0H255.1c-35.35 0-64 28.66-64 64l.0195 256C192 355.4 220.7 384 256 384h192c35.2 0 64-28.8 64-64V93.25C512 84.77 508.6 76.63 502.6 70.63zM464 320c0 8.836-7.164 16-16 16H255.1c-8.838 0-16-7.164-16-16L239.1 64.13c0-8.836 7.164-16 16-16h128L384 96c0 17.67 14.33 32 32 32h47.1V320zM272 448c0 8.836-7.164 16-16 16H63.1c-8.838 0-16-7.164-16-16L47.98 192.1c0-8.836 7.164-16 16-16H160V128H63.99c-35.35 0-64 28.65-64 64l.0098 256C.002 483.3 28.66 512 64 512h192c35.2 0 64-28.8 64-64v-32h-47.1L272 448z"
    }));
  }
  if (iconName === 'info') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-144c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32z"
    }));
  }
  if (iconName === 'info-open') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0zM256 464c-114.7 0-208-93.31-208-208S141.3 48 256 48s208 93.31 208 208S370.7 464 256 464zM256 304c13.25 0 24-10.75 24-24v-128C280 138.8 269.3 128 256 128S232 138.8 232 152v128C232 293.3 242.8 304 256 304zM256 337.1c-17.36 0-31.44 14.08-31.44 31.44C224.6 385.9 238.6 400 256 400s31.44-14.08 31.44-31.44C287.4 351.2 273.4 337.1 256 337.1z"
    }));
  }
  if (iconName === 'list') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M184.1 38.2c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L39 113c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zm0 160c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L39 273c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zM256 96c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H288c-17.7 0-32-14.3-32-32zm0 160c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H288c-17.7 0-32-14.3-32-32zM192 416c0-17.7 14.3-32 32-32H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H224c-17.7 0-32-14.3-32-32zM80 464c-26.5 0-48-21.5-48-48s21.5-48 48-48s48 21.5 48 48s-21.5 48-48 48z"
    }));
  }
  if (iconName === 'empty') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      height: iconSize,
      width: iconSize,
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 280.8 363.67"
    });
  }
  if (iconName === 'external-link') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: iconColors[iconColor],
      d: "M384 32c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96C0 60.7 28.7 32 64 32H384zM160 144c-13.3 0-24 10.7-24 24s10.7 24 24 24h94.1L119 327c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l135-135V328c0 13.3 10.7 24 24 24s24-10.7 24-24V168c0-13.3-10.7-24-24-24H160z"
    }));
  }
  if (iconName === 'shield') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      height: iconSize,
      "aria-hidden": "true",
      focusable: "false",
      role: "img",
      id: "uuid-026a4e87-44db-4336-a398-3c29d25b7317",
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 280.8 363.67"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: "#f9c23e",
      d: "M280.8,62.4L140.5,0,0,62.2V213.3c0,10.7,1.6,21.3,4.9,31.5,9.5,29.9,28.2,52.8,54.4,69.5,26,16.6,52.4,32.4,78.6,48.6,2,1.2,3.4,.9,5.1-.2,19.9-12.3,39.8-24.5,59.6-36.8,12.6-7.8,25.5-15.1,36.5-25.1,26.4-24.2,41.4-53.6,41.5-89.9V62.4h.2Z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("rect", {
      className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
      x: "155",
      y: "266.8",
      width: "77.6",
      height: "6"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: "#1d2327",
      d: "M224.4,204.5h-1.8v-10.1c0-15.9-12.9-28.8-28.8-28.8s-28.8,12.9-28.8,28.8v10.1h-1.8c-4.6,0-8.3,3.7-8.3,8.3v51.3h77.6v-51.3c0-4.6-3.7-8.3-8.3-8.3h.2Zm-45.3-10.1c0-8.1,6.6-14.7,14.7-14.7s14.7,6.6,14.7,14.7v10.1h-29.5v-10.1h.1Zm36.6,32.9l-20.7,20.2c-.2,.2-.3,.4-.5,.6l-2,2c-.2,.2-.4,.4-.6,.5l-3.8,3.8-4.5-4.3-2-2c-.2-.2-.4-.4-.5-.6l-9.1-9.1c-2.4-2.4-2.4-6.4,0-8.8l2-2c2.4-2.4,6.4-2.4,8.8,0l5.3,5.3,16.9-16.4c2.4-2.4,6.4-2.4,8.8,0l2,2c2.4,2.4,2.4,6.4,0,8.8h-.1Z"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      fill: "#1d2327",
      d: "M125.2,192.3c-.5-2.9-.5-5.8-1-8.6-.5-2.4-2.6-4-4.8-3.9-2.3,0-4.2,1.9-4.7,4.3-.2,1,0,1.9,0,2.9,.8,14.6,7.2,26.3,18.2,35.7,2.2,1.9,4.5,3.5,6.9,4.8v-11.8c-7.4-5.8-12.9-14.1-14.6-23.3v-.1Z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
      d: "M96.4,236.1c-13-15-20-32.3-19.5-52.3,.3-13.1,6.1-23.6,16.6-31.2,11.5-8.5,24.5-10.9,38.3-7.1,12.7,3.5,22,10.7,27.4,22,2.1-2.7,4.5-5.2,7.2-7.4-4-7-9.7-12.9-17-17.4-17-10.4-34.9-11.7-52.9-3.1-19,9.1-28.7,24.7-29.3,45.8,0,5.2,.5,10.2,1.4,15.2,3.4,19.4,13.4,35.2,27.2,48.9,1.1,1.1,2.5,1.6,4.1,1.4,1.8-.2,3.2-1.3,3.8-3,.6-1.8,.4-3.6-1-5.1-2.1-2.2-4.2-4.4-6.2-6.7h-.1Z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
      d: "M68.1,89.4c1.1-.4,2.1-1,3.1-1.5,17.9-9.1,36.8-12.7,56.8-11.3,12.2,.8,23.9,3.8,35.1,8.7,3,1.3,5.9,2.8,8.9,4.1,2.7,1.1,5.3,0,6.4-2.4,1.1-2.3,0-5-2.3-6.3-11-5.7-22.4-10-34.6-12.3-4.2-.8-8.5-1.1-12.8-1.7h-17.1c-.3,0-.6,.2-.9,.2-11.2,.8-22,3.2-32.5,7.2-4.9,1.9-9.7,4.1-14.3,6.6-2.5,1.3-3.4,4.2-2.2,6.5,1.1,2.2,4,3.2,6.4,2.1v.1Z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
      d: "M61.1,153.5c13.6-21.6,33.6-31.5,58.7-32.1h6c.8,0,1.6,.2,2.3,.3,13.4,1.7,25.5,6.6,35.9,15.4,5.8,4.9,10.5,10.3,14.1,16.2,3.1-1.2,6.4-2,9.8-2.5-4.7-8.7-11.3-16.3-19.6-22.7-19-14.6-40.5-19.5-64.1-15.1-14.3,2.7-26.9,9-37.7,18.8-10.4,9.5-17.8,20.9-21.2,34.6-2.8,11.3-2.6,22.7-.9,34.1,1.1,7,2.9,13.9,5.4,20.5,.9,2.3,3,3.7,5.2,3.5,2.1-.2,3.9-2,4.3-4.3,.2-1.1-.2-2.2-.6-3.2-4.3-11.9-6.3-24.1-5.6-36.7,.5-9.6,2.8-18.7,8-26.8h0Z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
      d: "M139.8,240.6c-20.9-8.4-34.1-23.7-38.4-46.7-.8-4.3-1.4-8.7-.4-13,1.8-7.1,6.4-11.4,13.4-13.5,11.8-3.4,24.7,5.3,24.5,17.6,0,4.8,1.4,9.3,4,13.4,.3,.5,.6,.9,.9,1.3,1.6-2.4,3.7-4.6,6.1-6.2,0-.9,0-1.9,.2-2.8-.7-1.7-1.1-3.5-1.2-5.3-.3-6.1-1.6-11.9-5.5-16.8-6.8-8.8-15.9-12.4-27-11.5-11.3,.9-21.6,9.6-24.5,20.6-1.8,6.6-.9,13.3,.4,19.8,2.4,12.9,8.2,24,17.1,33.7,8.6,9.4,18.8,15.8,30.6,19.8v-10.4h-.2Z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "uuid-57af18f1-eed9-4dfe-9c3e-67e3c55f9bf4",
      d: "M47.5,133.2c6.8-8.8,15-16,24.6-21.6,20.8-12,43.2-15.2,66.6-11,14.8,2.7,28.2,8.7,39.9,18.2,6.3,5,11.6,11,16.4,17.4,1.9,2.5,4.8,2.8,7,1.1,2.1-1.7,2.4-4.5,.6-7-5.9-8.2-12.8-15.3-20.9-21.3-18.3-13.6-39.1-19.6-61.7-20-6.3,0-12.5,.6-18.6,1.6-15.7,2.8-30.1,8.6-42.9,18.1-8.3,6.2-15.5,13.5-21.5,22-1.6,2.3-1.3,5.1,.7,6.7,2.1,1.7,4.9,1.5,6.8-.7,1-1.2,1.9-2.5,2.9-3.7l.1,.2Z"
    }))));
  }
  if (iconName === 'file-search') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      height: iconSize,
      fill: "none",
      viewBox: "0 0 384 512"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M374.6 150.6l-141.3-141.3C227.4 3.371 219.2 0 210.7 0H64C28.65 0 0 28.65 0 64l.0065 384c0 35.34 28.65 64 64 64H320c35.35 0 64-28.66 64-64V173.3C384 164.8 380.6 156.6 374.6 150.6zM224 22.63L361.4 160H248C234.8 160 224 149.2 224 136V22.63zM368 448c0 26.47-21.53 48-48 48H64c-26.47 0-48-21.53-48-48V64c0-26.47 21.53-48 48-48h144v120c0 22.06 17.94 40 40 40h120V448zM176 208c-53.02 0-96 42.98-96 96s42.98 96 96 96c23.62 0 44.96-8.859 61.68-23l68.66 68.66C307.9 447.2 309.9 448 312 448s4.094-.7813 5.656-2.344c3.125-3.125 3.125-8.188 0-11.31l-68.66-68.66C263.1 348.1 272 327.6 272 304C272 250.1 229 208 176 208zM176 384C131.9 384 96 348.1 96 304S131.9 224 176 224S256 259.9 256 304S220.1 384 176 384z"
    }));
  }
  if (iconName === 'download') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      height: iconSize,
      fill: "none",
      viewBox: "0 0 512 512"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M480 352h-88C387.6 352 384 355.6 384 360s3.582 8 8 8H480c8.822 0 16 7.178 16 16v96c0 8.822-7.178 16-16 16H32c-8.822 0-16-7.178-16-16v-96c0-8.822 7.178-16 16-16h88C124.4 368 128 364.4 128 360S124.4 352 120 352H32c-17.67 0-32 14.33-32 32v96c0 17.67 14.33 32 32 32h448c17.67 0 32-14.33 32-32v-96C512 366.3 497.7 352 480 352zM464 432c0-17.6-14.4-32-32-32s-32 14.4-32 32c0 17.6 14.4 32 32 32S464 449.6 464 432zM416 432c0-8.822 7.178-16 16-16s16 7.178 16 16S440.8 448 432 448S416 440.8 416 432zM250.3 413.7c3.125 3.125 8.188 3.125 11.31 0l152-152C415.2 260.1 416 258.1 416 256s-.7813-4.094-2.344-5.656c-3.125-3.125-8.188-3.125-11.31 0L264 388.7V8C264 3.594 260.4 0 256 0S248 3.594 248 8v380.7L109.7 250.3c-3.125-3.125-8.188-3.125-11.31 0s-3.125 8.188 0 11.31L250.3 413.7z"
    }));
  }
  if (iconName === 'satellite-dish') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      color: iconColors[iconColor],
      height: iconSize,
      viewBox: "0 0 512 512"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M208 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c150.2 0 272 121.8 272 272c0 8.8 7.2 16 16 16s16-7.2 16-16C512 136.1 375.9 0 208 0zm0 96c-8.8 0-16 7.2-16 16s7.2 16 16 16c97.2 0 176 78.8 176 176c0 8.8 7.2 16 16 16s16-7.2 16-16c0-114.9-93.1-208-208-208zM32 304c0-24.5 5-47.7 13.9-68.8L276.8 466.1C255.7 475 232.5 480 208 480c-97.2 0-176-78.8-176-176zm33.5-94.5c-14-14-37.3-12.1-45.7 5.8C7.1 242.2 0 272.3 0 304C0 418.9 93.1 512 208 512c31.7 0 61.8-7.1 88.7-19.8c17.9-8.4 19.8-31.8 5.8-45.7L195.3 339.3l24-24c6.3 3 13.3 4.7 20.7 4.7c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48c0 7.4 1.7 14.4 4.7 20.7l-24 24L65.5 209.5zM224 272a16 16 0 1 1 32 0 16 16 0 1 1 -32 0z"
    }));
  }
  if (iconName === 'rotate-light') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      color: iconColors[iconColor],
      height: iconSize,
      viewBox: "0 0 512 512"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      d: "M426.1 301.2C406.2 376.5 337.6 432 256 432c-51 0-96.9-21.7-129-56.3l41-41c5.1-5.1 8-12.1 8-19.3c0-15.1-12.2-27.3-27.3-27.3H48c-8.8 0-16 7.2-16 16V404.7C32 419.8 44.2 432 59.3 432c7.2 0 14.2-2.9 19.3-8l25.7-25.7C142.3 438.7 196.2 464 256 464c97.4 0 179.2-67 201.8-157.4c2.4-9.7-5.2-18.6-15.2-18.6c-7.8 0-14.5 5.6-16.5 13.2zM385 136.3l-41 41c-5.1 5.1-8 12.1-8 19.3c0 15.1 12.2 27.3 27.3 27.3H464c8.8 0 16-7.2 16-16V107.3C480 92.2 467.8 80 452.7 80c-7.2 0-14.2 2.9-19.3 8l-25.7 25.7C369.7 73.3 315.8 48 256 48C158.6 48 76.8 115 54.2 205.4c-2.4 9.7 5.2 18.6 15.2 18.6c7.8 0 14.5-5.6 16.5-13.2C105.8 135.5 174.4 80 256 80c51 0 96.9 21.7 129.1 56.3zM448 192H374.6L448 118.6V192zM64 320h73.4L64 393.4V320z"
    }));
  }
  if (iconName === 'rotate-exclamation-light') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("defs", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("style", null, `
            .fa-secondary {
              opacity: 0.4;
              color: ${iconColors[iconColor]};
            }
          `)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-primary",
      d: "M280 152c0-13.3-10.7-24-24-24s-24 10.7-24 24V264c0 13.3 10.7 24 24 24s24-10.7 24-24V152zM256 384a32 32 0 1 0 0-64 32 32 0 1 0 0 64z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-secondary",
      d: "M432 117.4C391 65.4 327.4 32 256 32C158.4 32 75.5 94.4 44.8 181.3c-5.9 16.7 2.8 34.9 19.5 40.8s34.9-2.8 40.8-19.5C127.1 140.5 186.4 96 256 96c52.3 0 98.8 25.1 128 64H352c-17.7 0-32 14.3-32 32s14.3 32 32 32h84.3c.5 0 1 0 1.5 0H464c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v37.4zm35.2 213.2c5.9-16.7-2.8-34.9-19.5-40.8s-34.9 2.8-40.8 19.5C384.9 371.5 325.6 416 256 416c-52.3 0-98.8-25.1-128-64h32c17.7 0 32-14.3 32-32s-14.3-32-32-32H48c-17.7 0-32 14.3-32 32V432c0 17.7 14.3 32 32 32s32-14.3 32-32V394.6c41 52 104.6 85.4 176 85.4c97.6 0 180.5-62.4 211.2-149.3z"
    }));
  }
  if (iconName === 'radar-duotone') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("defs", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("style", null, `
            .fa-secondary {
              color: ${iconColors[iconColor]} !important;
            }        
          `)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-primary",
      d: "M497 49c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-182 182c-7.7-3.3-16.1-5.1-25-5.1c-35.3 0-64 28.7-64 64s28.7 64 64 64s64-28.7 64-64c0-8.9-1.8-17.3-5.1-25L497 49z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-secondary",
      d: "M350.9 127.2l-46.1 46.1c-14.3-8.4-31-13.3-48.8-13.3c-53 0-96 43-96 96s43 96 96 96s96-43 96-96h64c0 73.5-49.6 135.5-117.2 154.2C290.8 394.7 274.7 384 256 384s-34.8 10.7-42.8 26.2c-51.7-14.3-92.8-53.9-109.4-104.6c14.4-8.3 24.1-23.8 24.1-41.7c0-19.4-11.5-36.1-28-43.7C116.2 149.1 179.9 96 256 96c35.5 0 68.3 11.6 94.9 31.2zm22.8-22.8C341.2 79 300.4 64 256 64C163.1 64 85.7 129.9 67.9 217.6C47.2 222.9 32 241.7 32 264c0 23.8 17.3 43.6 40.1 47.4c19.3 64.3 71.5 114.4 137 130.9C213.8 463.8 233 480 256 480s42.2-16.2 46.9-37.8C386.3 421.3 448 345.9 448 256h64c0 141.4-114.6 256-256 256S0 397.4 0 256S114.6 0 256 0c62.1 0 118.9 22.1 163.3 58.8l-45.5 45.5z"
    }));
  }
  if (iconName === 'satellite-dish-duotone') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      height: iconSize
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("defs", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("style", null, `
            .fa-secondary {
              color: ${iconColors[iconColor]} !important;
            }        
          `)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-primary",
      d: "M60.6 220.6c-14.5-14.5-38.8-11.8-46.3 7.3C5.1 251.5 0 277.1 0 304C0 418.9 93.1 512 208 512c26.9 0 52.5-5.1 76.1-14.4c19-7.5 21.8-31.8 7.3-46.3L187.3 347.3l28.4-28.4c2.6 .7 5.4 1.1 8.3 1.1c17.7 0 32-14.3 32-32s-14.3-32-32-32s-32 14.3-32 32c0 2.9 .4 5.6 1.1 8.3l-28.4 28.4L60.6 220.6z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-secondary",
      d: "M224 0c-17.7 0-32 14.3-32 32s14.3 32 32 32c123.7 0 224 100.3 224 224c0 17.7 14.3 32 32 32s32-14.3 32-32C512 128.9 383.1 0 224 0zm0 96c-17.7 0-32 14.3-32 32s14.3 32 32 32c70.7 0 128 57.3 128 128c0 17.7 14.3 32 32 32s32-14.3 32-32c0-106-86-192-192-192z"
    }));
  }
  if (iconName === 'spinner') {
    renderedIcon = (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 512 512",
      width: "20",
      height: "20"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("defs", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("style", null, `
                            .fa-secondary {
                              opacity: 0.4;
                            }
                          `)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-primary",
      d: "M304 48a48 48 0 1 0 -96 0 48 48 0 1 0 96 0zM96 256A48 48 0 1 0 0 256a48 48 0 1 0 96 0zM75 142.9A48 48 0 1 0 142.9 75 48 48 0 1 0 75 142.9z"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
      className: "fa-secondary",
      d: "M369.1 75A48 48 0 1 1 437 142.9 48 48 0 1 1 369.1 75zM416 256a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM208 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM75 369.1A48 48 0 1 1 142.9 437 48 48 0 1 1 75 369.1zm294.2 0A48 48 0 1 1 437 437a48 48 0 1 1 -67.9-67.9z"
    }));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...props,
    ref: ref
  }, renderedIcon);
});
const Icon = ({
  name,
  color,
  size,
  tooltip
}) => {
  // set defaults if not set
  const iconName = name || 'bullet';
  const iconColor = color || 'black';
  let iconSize = size || 15;
  let tooltipClass = tooltip ? 'tooltip-' : '';
  let randomId = Math.floor(Math.random() * 1000000000);
  if (tooltip) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: 'rsssl-' + tooltipClass + 'icon rsssl-icon-' + iconName + ' rsssl-' + iconColor
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(IconHtml, {
      "data-for": ".rsssl-" + randomId,
      name: iconName,
      color: iconColor,
      size: iconSize,
      id: randomId,
      className: "rsssl-" + randomId,
      "data-tooltip-delay-hide": 200
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_tooltip__WEBPACK_IMPORTED_MODULE_1__.Tooltip, {
      style: {
        zIndex: 99
      },
      id: randomId,
      place: "bottom",
      anchorSelect: ".rsssl-" + randomId,
      content: tooltip
    }));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: 'rsssl-' + tooltipClass + 'icon rsssl-icon-' + iconName + ' rsssl-' + iconColor
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(IconHtml, {
    name: iconName,
    color: iconColor,
    size: iconSize
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Icon);

/***/ }),

/***/ "../modal/src/components/Modal/RssslModal.scss":
/*!*****************************************************!*\
  !*** ../modal/src/components/Modal/RssslModal.scss ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/Onboarding/PremiumItem.scss":
/*!*****************************************!*\
  !*** ./src/Onboarding/PremiumItem.scss ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/Onboarding/checkbox.scss":
/*!**************************************!*\
  !*** ./src/Onboarding/checkbox.scss ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/Onboarding/onboarding.scss":
/*!****************************************!*\
  !*** ./src/Onboarding/onboarding.scss ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/Settings/AutoComplete/AutoComplete.scss":
/*!*****************************************************!*\
  !*** ./src/Settings/AutoComplete/AutoComplete.scss ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

}]);
//# sourceMappingURL=src_Onboarding_OnboardingModal_js.js.map