import {create} from 'zustand';
import getAnchor from "../utils/getAnchor";

const useMenu = create(( set, get ) => ({
    menu: [],
    subMenuLoaded:false,
    previousMenuItem:false,
    nextMenuItem:false,
    selectedMainMenuItem:false,
    selectedSubMenuItem:false,
    hasPremiumItems:false,
    subMenu:{title:' ',menu_items:[]},
    setSelectedSubMenuItem: (selectedSubMenuItem) => set(state => ({ selectedSubMenuItem })),
    setSelectedMainMenuItem: (selectedMainMenuItem) => set(state => ({ selectedMainMenuItem })),
    //we need to get the main menu item directly from the anchor, otherwise we have to wait for the menu to load in page.js
    fetchSelectedMainMenuItem: () => {
        let selectedMainMenuItem = getAnchor('main') || 'dashboard';
        set((state) => ({selectedMainMenuItem: selectedMainMenuItem}));
    },
    fetchSelectedSubMenuItem: async () => {
        let selectedSubMenuItem = getAnchor('menu') || 'general';
        set((state) => ({selectedSubMenuItem: selectedSubMenuItem}));
    },
    fetchMenuData: (fields) => {
        let menu = rsssl_settings.menu;
        menu = Object.values(menu);
        const selectedMainMenuItem = getAnchor('main') || 'dashboard';
        if ( typeof fields !== 'undefined' ) {
            let subMenu = getSubMenu(menu, selectedMainMenuItem);
            const selectedSubMenuItem = getSelectedSubMenuItem(subMenu, fields);
            const { nextMenuItem, previousMenuItem }  = getPreviousAndNextMenuItems(menu, selectedSubMenuItem);
            subMenu.menu_items = dropEmptyMenuItems(subMenu.menu_items, fields, selectedSubMenuItem);
            const hasPremiumItems =  subMenu.menu_items.filter((item) => {return (item.premium===true)}).length>0;
            set((state) => ({subMenuLoaded:true, menu: menu, nextMenuItem:nextMenuItem, previousMenuItem:previousMenuItem, selectedMainMenuItem: selectedMainMenuItem, selectedSubMenuItem:selectedSubMenuItem, subMenu: subMenu, hasPremiumItems: hasPremiumItems}));

        } else {
            set((state) => ({menu: menu, selectedMainMenuItem: selectedMainMenuItem}));

        }
    }
}));
export default useMenu;


// Parses menu items and nested items in single array
const menuItemParser = (parsedMenuItems, menuItems) => {
    menuItems.forEach((menuItem) => {
        if( menuItem.visible ) {
            parsedMenuItems.push(menuItem.id);
            if( menuItem.hasOwnProperty('menu_items') ) {
                menuItemParser(parsedMenuItems, menuItem.menu_items);
            }
        }
    });
    return parsedMenuItems;
}

const getPreviousAndNextMenuItems = (menu, selectedSubMenuItem) => {
    let previousMenuItem;
    let nextMenuItem;
    const parsedMenuItems = [];
    menuItemParser(parsedMenuItems, menu);
    // Finds current menu item index
    const currentMenuItemIndex = parsedMenuItems.findIndex((menuItem) => menuItem === selectedSubMenuItem);
    if( currentMenuItemIndex !== -1 ) {
        previousMenuItem = parsedMenuItems[ currentMenuItemIndex === 0 ? '' : currentMenuItemIndex - 1];
        //if the previous menu item has a submenu, we should move one more back, because it will select the current sub otherwise.
        const previousMenuHasSubMenu = getMenuItemByName(previousMenuItem, menu).hasOwnProperty('menu_items');
        if (previousMenuHasSubMenu) {
            previousMenuItem = parsedMenuItems[ currentMenuItemIndex === 0 ? '' : currentMenuItemIndex - 2]
        }
        nextMenuItem = parsedMenuItems[ currentMenuItemIndex === parsedMenuItems.length - 1 ? '' : currentMenuItemIndex + 1];
        previousMenuItem = previousMenuItem ? previousMenuItem : parsedMenuItems[0];
        nextMenuItem = nextMenuItem ? nextMenuItem : parsedMenuItems[parsedMenuItems.length - 1]
    }
    return { nextMenuItem, previousMenuItem };
}

const dropEmptyMenuItems = (menuItems, fields, selectedSubMenuItem) => {
    const newMenuItems = menuItems;
    for (const [index, menuItem] of menuItems.entries()) {
        const menuItemFields = fields.filter((field) => {
            return (field.menu_id === menuItem.id && field.visible && !field.conditionallyDisabled )
        });
        if( menuItemFields.length === 0 && !menuItem.hasOwnProperty('menu_items') )  {
            newMenuItems[index].visible = false;
        } else {
            newMenuItems[index].visible = true;
            if( menuItem.hasOwnProperty('menu_items') ) {
                newMenuItems[index].menu_items = dropEmptyMenuItems(menuItem.menu_items, fields, selectedSubMenuItem);
            }
        }
    }
    return newMenuItems;
}

/*
* filter sidebar menu from complete menu structure
*/
const getSubMenu = (menu, selectedMainMenuItem) => {
    let subMenu = [];
    for (const key in menu) {
        if ( menu.hasOwnProperty(key) && menu[key].id === selectedMainMenuItem ){
            subMenu = menu[key];
        }
    }
    subMenu = addVisibleToMenuItems(subMenu);
    return subMenu;
}

/**
 * Get the current selected menu item based on the hash, selecting subitems if the main one is empty.
 */
const getSelectedSubMenuItem = (subMenu, fields) => {
    let fallBackMenuItem = subMenu && subMenu.menu_items.hasOwnProperty(0) ? subMenu.menu_items[0].id : 'general';
    let foundAnchorInMenu;

    //get flat array of menu items
    let parsedMenuItems = menuItemParser([], subMenu.menu_items);
    let anchor = getAnchor('menu');
    //check if this anchor actually exists in our current submenu. If not, clear it
    foundAnchorInMenu = parsedMenuItems.filter(menu_item => menu_item === anchor);
    if ( !foundAnchorInMenu ) {
        anchor = false;
    }
    let selectedMenuItem =  anchor ? anchor : fallBackMenuItem;
    //check if menu item has fields. If not, try a subitem
    let fieldsInMenu = fields.filter(field => field.menu_id === selectedMenuItem);
    if ( fieldsInMenu.length===0 ) {
        //look up the current menu item
        let menuItem = getMenuItemByName(selectedMenuItem, subMenu.menu_items);
        if (menuItem && menuItem.menu_items && menuItem.menu_items.hasOwnProperty(0)) {
            selectedMenuItem = menuItem.menu_items[0].id;
        }
    }
    return selectedMenuItem;
}

//Get a menu item by name from the menu array
const getMenuItemByName = (name, menuItems) => {
    for (const key in menuItems ){
        let menuItem = menuItems[key];
        if ( menuItem.id === name ) {
            return menuItem;
        }
        if ( menuItem.menu_items ) {
            let found = getMenuItemByName(name, menuItem.menu_items);
            if (found) return found;
        }
    }
    return false;
}

const addVisibleToMenuItems = (menu) => {
    let newMenuItems = menu.menu_items;
    for (let [index, menuItem] of menu.menu_items.entries()) {
        menuItem.visible = true;
        if( menuItem.hasOwnProperty('menu_items') ) {
            menuItem = addVisibleToMenuItems(menuItem);
        }
        newMenuItems[index] = menuItem;
    }
    menu.menu_items = newMenuItems;
    menu.visible = true;
    return menu;
}
