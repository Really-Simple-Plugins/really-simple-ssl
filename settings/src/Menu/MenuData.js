import {create} from 'zustand';
import getAnchor from "../utils/getAnchor";

const useMenu = create(( set, get ) => ({
    menu: [],
    subMenuLoaded:false,
    previousMenuItem:false,
    nextMenuItem:false,
    selectedMainMenuItem:false,
    selectedSubMenuItem:false,
    selectedFilter: false,
    activeGroupId: false,
    hasPremiumItems:false,
    subMenu:{title:' ',menu_items:[]},
    setSelectedSubMenuItem: async (selectedSubMenuItem) => {
        let selectedMainMenuItem = getMainMenuForSubMenu(selectedSubMenuItem);
        set(state => ({ selectedSubMenuItem,selectedMainMenuItem }))
        // window.location.href=rsssl_settings.dashboard_url+'#'+selectedMainMenuItem+'/'+selectedSubMenuItem;
        window.location.hash = selectedMainMenuItem+'/'+selectedSubMenuItem;
    },
    setSelectedMainMenuItem: (selectedMainMenuItem) => {
        set(state => ({ selectedMainMenuItem }))
        // window.location.href=rsssl_settings.dashboard_url+'#'+selectedMainMenuItem;
        window.location.hash = selectedMainMenuItem;
    },
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
        menu = menu.filter( item => !item.default_hidden || selectedMainMenuItem===item.id);

        if ( typeof fields !== 'undefined' ) {
            let subMenu = getSubMenu(menu, selectedMainMenuItem);
            const selectedSubMenuItem = getSelectedSubMenuItem(subMenu, fields);
            subMenu.menu_items = dropEmptyMenuItems(subMenu.menu_items, fields, selectedSubMenuItem);
            const { nextMenuItem, previousMenuItem }  = getPreviousAndNextMenuItems(menu, selectedSubMenuItem, fields);
            const hasPremiumItems =  subMenu.menu_items.filter((item) => {return (item.premium===true)}).length>0;
            set((state) => ({subMenuLoaded:true, menu: menu, nextMenuItem:nextMenuItem, previousMenuItem:previousMenuItem, selectedMainMenuItem: selectedMainMenuItem, selectedSubMenuItem:selectedSubMenuItem, subMenu: subMenu, hasPremiumItems: hasPremiumItems}));
        } else {
            set((state) => ({menu: menu, selectedMainMenuItem: selectedMainMenuItem}));

        }
    },
    getDefaultSubMenuItem: async (fields) => {
        let subMenuLoaded = get().subMenuLoaded;
        if (!subMenuLoaded){
            await get().fetchMenuData(fields);
        }
        let subMenu = get().subMenu;
        let fallBackMenuItem = subMenuLoaded && subMenu.hasOwnProperty(0) ? subMenu[0].id : 'general';
        let anchor = getAnchor('menu');
        let foundAnchorInMenu = false;
        //check if this anchor actually exists in our current submenu. If not, clear it
        for (const key in this.menu.menu_items) {
            if ( subMenu.hasOwnProperty(key) &&  subMenu[key].id === anchor ){
                foundAnchorInMenu=true;
            }
        }
        if ( !foundAnchorInMenu ) anchor = false;
        return anchor ? anchor : fallBackMenuItem;
    }
}));
export default useMenu;


// Parses menu items and nested items in single array
const menuItemParser = (parsedMenuItems, menuItems = [], fields = []) => {
    if (!Array.isArray(menuItems)) {
        console.error('menuItems is not an array:', menuItems);
        return parsedMenuItems;
    }

    menuItems.forEach((menuItem) => {
        if (menuItem.visible) {
            parsedMenuItems.push(menuItem.id);
            if (menuItem.hasOwnProperty('menu_items')) {
                menuItem.menu_items = dropEmptyMenuItems(menuItem.menu_items, fields);
                menuItemParser(parsedMenuItems, menuItem.menu_items, fields);
            }
        }
    });

    return parsedMenuItems;
}

// const menuItemParser = (parsedMenuItems, menuItems, fields) => {
//     menuItems.forEach((menuItem) => {
//         if( menuItem.visible ) {
//             parsedMenuItems.push(menuItem.id);
//             if( menuItem.hasOwnProperty('menu_items') ) {
//                 menuItem.menu_items = dropEmptyMenuItems(menuItem.menu_items, fields );
//                 menuItemParser(parsedMenuItems, menuItem.menu_items, fields);
//             }
//         }
//     });
//     return parsedMenuItems;
// }



const getPreviousAndNextMenuItems = (menu, selectedSubMenuItem, fields) => {
    let previousMenuItem;
    let nextMenuItem;
    const parsedMenuItems = [];
    menuItemParser(parsedMenuItems, menu, fields);
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

const dropEmptyMenuItems = (menuItems, fields) => {
    if (!Array.isArray(fields)) {
        console.error('Fields is not an array or is undefined', fields);
        return menuItems;  // Exit early to avoid further processing
    }

    const newMenuItems = [];

    for (const menuItem of menuItems) {
        let menuItemFields = fields.filter((field) => field.menu_id === menuItem.id && field.visible);

        if (menuItemFields.length === 0 && !menuItem.hasOwnProperty('menu_items')) {
            // Do nothing. We don't push it to the newMenuItems
        } else {
            let newMenuItem = { ...menuItem, visible: true }; // Deep copy of menuItem with visible set to true
            if (menuItem.hasOwnProperty('menu_items')) {
                newMenuItem.menu_items = dropEmptyMenuItems(menuItem.menu_items, fields);
            }
            newMenuItems.push(newMenuItem);
        }
    }

    return newMenuItems;
}

// const dropEmptyMenuItems = (menuItems, fields) => {
//     const newMenuItems = menuItems;
//     for (const [index, menuItem] of menuItems.entries()) {
//         let menuItemFields = fields.filter((field) => {
//             return (field.menu_id === menuItem.id )
//         });
//
//         menuItemFields = menuItemFields.filter((field) => {
//             return ( field.visible )
//         });
//         if ( menuItemFields.length === 0 && !menuItem.hasOwnProperty('menu_items') )  {
//             newMenuItems[index].visible = false;
//         } else {
//             newMenuItems[index].visible = true;
//             if( menuItem.hasOwnProperty('menu_items') ) {
//                 newMenuItems[index].menu_items = dropEmptyMenuItems(menuItem.menu_items, fields);
//             }
//         }
//
//
//     }
//     return newMenuItems;
// }

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

/*
* Get the main menu item for a submenu item
*/
const getMainMenuForSubMenu = (findMenuItem) => {
    let menu = rsssl_settings.menu;
    for (const mainKey in menu) {
        let mainMenuItem = menu[mainKey];
        if (mainMenuItem.id===findMenuItem) {
            return mainMenuItem.id;
        }
        if (mainMenuItem.menu_items){
            for (const subKey in mainMenuItem.menu_items) {
                let subMenuItem = mainMenuItem.menu_items[subKey];
                if (subMenuItem.id===findMenuItem) {
                    return mainMenuItem.id;
                }
                if (subMenuItem.menu_items){
                    for (const sub2Key in subMenuItem.menu_items) {
                        let sub2MenuItem = subMenuItem.menu_items[sub2Key];
                        if (sub2MenuItem.id===findMenuItem) {
                            return mainMenuItem.id;
                        }
                    }
                }
            }
        }
    }
    return false;
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
    if (typeof menu === 'string') {
        return menu; // If menu is a string, just return it as is
    }

    let newMenuItems = menu.menu_items;

    if (!Array.isArray(menu.menu_items)) {
        newMenuItems = [];
        for (const key in menu.menu_items) {
            if (typeof menu.menu_items[key] === 'object') {
                newMenuItems.push(menu.menu_items[key]);
            }
        }
    }

    for (let [index, menuItem] of newMenuItems.entries()) {
        if (typeof menuItem === 'object') {
            menuItem.visible = true;
            if( menuItem.hasOwnProperty('menu_items') ) {
                menuItem = addVisibleToMenuItems(menuItem);
            }
            newMenuItems[index] = menuItem;
        }
    }

    menu.menu_items = newMenuItems;
    menu.visible = true;
    return menu;
}

// const addVisibleToMenuItems = (menu) => {
//     let newMenuItems = menu.menu_items;
//     if (!Array.isArray(menu.menu_items)) {
//         //wait whut not an array, well let us fix that
//         newMenuItems = [];
//         for (const key in menu.menu_items) {
//             newMenuItems.push(menu.menu_items[key]);
//         }
//     }
//     for (let [index, menuItem] of menu.menu_items.entries()) {
//         menuItem.visible = true;
//         if( menuItem.hasOwnProperty('menu_items') ) {
//             menuItem = addVisibleToMenuItems(menuItem);
//         }
//         newMenuItems[index] = menuItem;
//     }
//     menu.menu_items = newMenuItems;
//     menu.visible = true;
//     return menu;
// }
