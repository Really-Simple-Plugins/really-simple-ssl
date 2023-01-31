import { __ } from '@wordpress/i18n';

const MenuItem = (props) => {
    const handleClick = () => {
        props.selectMenu(props.menuItem.id);
    }

    /*
     * Menu is selected if the item is the same, or if it is a child.
     */
    let menuIsSelected = props.selectedMenuItem===props.menuItem.id;
    if (props.menuItem.menu_items) {
        for (const item of props.menuItem.menu_items){
            if (item.id === props.selectedMenuItem ){
                menuIsSelected=true;
            }
        }
    }

    let menuClass = menuIsSelected ? ' rsssl-active' : '';
    menuClass += props.menuItem.featured ? ' rsssl-featured' : '';
    menuClass += props.menuItem.premium && !rsssl_settings.pro_plugin_active ? ' rsssl-premium' : '';
    let href = '#'+props.selectedMainMenuItem+'/'+props.menuItem.id;
    return (
        <>
        {props.menuItem.visible && <div className={"rsssl-menu-item" + menuClass}>
            <a href={href} onClick={() => handleClick()}>
                <span>{props.menuItem.title}</span>
                {props.menuItem.featured && <><span className='rsssl-menu-item-featured-pill'>{__('New', 'really-simple-ssl')}</span></>}
            </a>
            { (props.menuItem.menu_items && menuIsSelected) && <div className="rsssl-submenu-item">
                {props.menuItem.menu_items.map(
                    (subMenuItem, i) => subMenuItem.visible && <MenuItem key={i}
                                                                         menuItem={subMenuItem}
                                                                         selectMenu={props.selectMenu}
                                                                         selectedMenuItem={props.selectedMenuItem}
                                                                         selectedMainMenuItem={props.selectedMainMenuItem}
                                                                         />
                )}
            </div>}
        </div>}
        </>
    )
}

export default MenuItem