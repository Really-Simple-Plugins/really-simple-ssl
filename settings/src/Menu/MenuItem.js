import { __ } from '@wordpress/i18n';
import useMenu from "./MenuData";

const MenuItem = (props) => {
    const {selectedSubMenuItem, selectedMainMenuItem, setSelectedSubMenuItem, subMenu, menu} = useMenu();

    // const {selectedSubMenuItem, selectedMainMenuItem, subMenu, menu} = useMenu();

    /*
     * Menu is selected if the item is the same, or if it is a child.
     */
    let menuIsSelected = selectedSubMenuItem===props.menuItem.id;
    if (props.menuItem.menu_items) {
        for (const item of props.menuItem.menu_items){
            if (item.id === selectedSubMenuItem ){
                menuIsSelected=true;
            }
        }
    }

    const ensureArray = (data) => {
        return Array.isArray(data) ? data : [data];
    }

    /**
     *
     * @param menuItem
     * @param event
     *
     * Open first submenu item automatically
     */
    const handleMainMenuItemClick = (menuItem, event) => {
        let firstSubMenuItemId = null;

        // Function to find the first submenu item recursively
        const findFirstSubMenuItem = (item) => {
            if (item.menu_items && item.menu_items.length > 0) {
                firstSubMenuItemId = item.menu_items[0].id;
                if (item.menu_items[0].menu_items) {
                    findFirstSubMenuItem(item.menu_items[0]);
                }
            }
        };

        findFirstSubMenuItem(menuItem);

        // If a submenu item is found, prevent default and stop propagation
        if (firstSubMenuItemId) {
            event.preventDefault();
            event.stopPropagation();
            setSelectedSubMenuItem(firstSubMenuItemId);
        }
    }

    let menuClass = menuIsSelected ? ' rsssl-active' : '';
    menuClass += props.menuItem.featured ? ' rsssl-featured' : '';
    menuClass += props.menuItem.new ? ' rsssl-new' : '';
    menuClass += props.menuItem.premium && !rsssl_settings.pro_plugin_active ? ' rsssl-premium' : '';
    let href = '#'+selectedMainMenuItem+'/'+props.menuItem.id;

    let menuLink = props.menuItem.directLink || '#'+selectedMainMenuItem+'/'+props.menuItem.id;

    return (
        <>
            {props.menuItem.visible && <div className={"rsssl-menu-item" + menuClass}>
                <a href={menuLink} onClick={(event) => handleMainMenuItemClick(props.menuItem, event)}>
                    <span>{props.menuItem.title}</span>
                    {props.menuItem.featured && <><span className='rsssl-menu-item-beta-pill'>{__('Beta', 'really-simple-ssl')}</span></>}
                    {props.menuItem.new && <><span className='rsssl-menu-item-new-pill'>{__('New', 'really-simple-ssl')}</span></>}
                </a>
                { (props.menuItem.menu_items && menuIsSelected) && <div className="rsssl-submenu-item">
                    {ensureArray(props.menuItem.menu_items).map(
                        (subMenuItem, i) => subMenuItem.visible && <MenuItem key={"submenuItem"+i} menuItem={subMenuItem} />
                    )}
                </div>}
            </div>}
        </>
    )
}

export default MenuItem