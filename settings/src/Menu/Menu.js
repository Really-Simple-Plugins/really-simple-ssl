import MenuPlaceholder from '../Placeholder/MenuPlaceholder';
import MenuItem from './MenuItem';
import useMenu from "./MenuData";
/**
 * Menu block, rendering the entire menu
 */
const Menu = () => {
    const {subMenu, subMenuLoaded} = useMenu();

    if ( !subMenuLoaded ) {
        return(
            <MenuPlaceholder />
        )
    }

    return (
        <div className="rsssl-wizard-menu rsssl-grid-item">
            <div className="rsssl-grid-item-header">
                <h1 className="rsssl-h4">{subMenu.title}</h1>
            </div>
            <div className="rsssl-grid-item-content">
                <div className="rsssl-wizard-menu-items">
                    { subMenu.menu_items.map((menuItem, i) => <MenuItem key={"menuItem-"+i} menuItem={menuItem} isMainMenu={true} /> ) }
                </div>
            </div>
            <div className="rsssl-grid-item-footer">

            </div>
        </div>
    )
}
export default Menu;
