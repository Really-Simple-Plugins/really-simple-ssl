"use strict";(globalThis.webpackChunkreally_simple_ssl=globalThis.webpackChunkreally_simple_ssl||[]).push([[829],{3829:(e,s,t)=>{t.r(s),t.d(s,{default:()=>d});var l=t(9307);const m=()=>(0,l.createElement)("div",{className:"rsssl-wizard-menu rsssl-grid-item rsssl-menu-placeholder"},(0,l.createElement)("div",{className:"rsssl-grid-item-header"},(0,l.createElement)("h1",{className:"rsssl-h4"})),(0,l.createElement)("div",{className:"rsssl-grid-item-content"}));var a=t(5736),n=t(2485);const r=e=>{const{selectedSubMenuItem:s,selectedMainMenuItem:t,subMenu:m,menu:i}=(0,n.Z)(),c=u(s,e.menuItem);let d=c?" rsssl-active":"";d+=e.menuItem.featured?" rsssl-featured":"",d+=e.menuItem.new?" rsssl-new":"",d+=e.menuItem.premium&&!rsssl_settings.pro_plugin_active?" rsssl-premium":"";let p=e.menuItem.directLink||"#"+t+"/"+e.menuItem.id;return(0,l.createElement)(l.Fragment,null,e.menuItem.visible&&(0,l.createElement)(l.Fragment,null,e.isMainMenu?(0,l.createElement)("div",{className:"rsssl-main-menu"},(0,l.createElement)("div",{className:"rsssl-menu-item"+d},(0,l.createElement)("a",{href:p},(0,l.createElement)("span",null,e.menuItem.title),e.menuItem.featured&&(0,l.createElement)("span",{className:"rsssl-menu-item-beta-pill"},(0,a.__)("Beta","really-simple-ssl")),e.menuItem.new&&(0,l.createElement)("span",{className:"rsssl-menu-item-new-pill"},(0,a.__)("New","really-simple-ssl"))))):(0,l.createElement)("div",{className:"rsssl-menu-item"+d},(0,l.createElement)("a",{href:p},(0,l.createElement)("span",null,e.menuItem.title),e.menuItem.featured&&(0,l.createElement)("span",{className:"rsssl-menu-item-beta-pill"},(0,a.__)("Beta","really-simple-ssl")),e.menuItem.new&&(0,l.createElement)("span",{className:"rsssl-menu-item-new-pill"},(0,a.__)("New","really-simple-ssl")))),e.menuItem.menu_items&&c&&(0,l.createElement)("div",{className:"rsssl-submenu-item"},(E=e.menuItem.menu_items,Array.isArray(E)?E:[E]).map(((e,s)=>e.visible&&(0,l.createElement)(r,{key:"submenuItem"+s,menuItem:e,isMainMenu:!1}))))));var E},i=r,u=(e,s)=>{if(e===s.id)return!0;if(s.menu_items)for(const t of s.menu_items)if(t.id===e)return!0;return!1};var c=t(1789);const d=()=>{const{subMenu:e,hasPremiumItems:s,subMenuLoaded:t}=(0,n.Z)(),{licenseStatus:r}=(0,c.Z)();return t?(0,l.createElement)("div",{className:"rsssl-wizard-menu rsssl-grid-item"},(0,l.createElement)("div",{className:"rsssl-grid-item-header"},(0,l.createElement)("h1",{className:"rsssl-h4"},e.title)),(0,l.createElement)("div",{className:"rsssl-grid-item-content"},(0,l.createElement)("div",{className:"rsssl-wizard-menu-items"},e.menu_items.map(((e,s)=>(0,l.createElement)(i,{key:"menuItem-"+s,menuItem:e,isMainMenu:!0}))),s&&!rsssl_settings.is_premium&&"valid"!==r&&(0,l.createElement)("div",{className:"rsssl-premium-menu-item"},(0,l.createElement)("div",null,(0,l.createElement)("a",{target:"_blank",href:rsssl_settings.upgrade_link,className:"button button-black"},(0,a.__)("Upgrade","really-simple-ssl")))))),(0,l.createElement)("div",{className:"rsssl-grid-item-footer"})):(0,l.createElement)(m,null)}},1789:(e,s,t)=>{t.d(s,{Z:()=>l});const l=(0,t(270).Ue)(((e,s)=>({licenseStatus:rsssl_settings.licenseStatus,setLicenseStatus:s=>e((e=>({licenseStatus:s})))})))}}]);