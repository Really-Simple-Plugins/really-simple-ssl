import { __ } from '@wordpress/i18n';
import useMenu from "./MenuData";

const MenuItem = (props) => {
    const {selectedSubMenuItem, selectedMainMenuItem, subMenu, menu} = useMenu();
    const menuIsSelected = isSelectedMenuItem(selectedSubMenuItem, props.menuItem);

    const ensureArray = (data) => {
        return Array.isArray(data) ? data : [data];
    }

    let menuClass = menuIsSelected ? ' rsssl-active' : '';
    menuClass += props.menuItem.featured ? ' rsssl-featured' : '';
    menuClass += props.menuItem.new ? ' rsssl-new' : '';
    menuClass += props.menuItem.premium && !rsssl_settings.pro_plugin_active ? ' rsssl-premium' : '';
    let menuLink = props.menuItem.directLink || '#'+selectedMainMenuItem+'/'+props.menuItem.id;

    return (
        <>
            {props.menuItem.visible && (
                <>
                    {props.isMainMenu ? (
                        <div className="rsssl-main-menu">
                            <div className={"rsssl-menu-item" + menuClass}>
                                <a href={menuLink}>
                                    <span>{props.menuItem.title}</span>
                                    {props.menuItem.featured && <span className='rsssl-menu-item-beta-pill'>{__('Beta', 'really-simple-ssl')}</span>}
                                    {props.menuItem.new && <span className='rsssl-menu-item-new-pill'>{__('New', 'really-simple-ssl')}</span>}
                                </a>
                            </div>
                        </div>
                    ) : (
                        <div className={"rsssl-menu-item" + menuClass}>
                            <a href={menuLink}>
                                <span>{props.menuItem.title}</span>
                                {props.menuItem.featured && <span className='rsssl-menu-item-beta-pill'>{__('Beta', 'really-simple-ssl')}</span>}
                                {props.menuItem.new && <span className='rsssl-menu-item-new-pill'>{__('New', 'really-simple-ssl')}</span>}
                            </a>
                        </div>
                    )}

                    {props.menuItem.menu_items && menuIsSelected && (
                        <div className="rsssl-submenu-item">
                            {ensureArray(props.menuItem.menu_items).map((subMenuItem, i) => (
                                subMenuItem.visible && <MenuItem key={"submenuItem" + i} menuItem={subMenuItem} isMainMenu={false} />
                            ))}
                        </div>
                    )}
                </>
            )}
        </>
    );



}

export default MenuItem

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
