import MenuPlaceholder from '../Placeholder/MenuPlaceholder';
import MenuItem from './MenuItem';
import { __ } from '@wordpress/i18n';
import useMenu from "./MenuData";
import useLicense from "../Settings/License/LicenseData";
/**
 * Menu block, rendering the entire menu
 */
const Menu = () => {
    const {subMenu, hasPremiumItems, subMenuLoaded} = useMenu();
    const {licenseStatus} = useLicense();

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
                    { hasPremiumItems && !rsssl_settings.is_premium && licenseStatus!=='valid' &&
                        <div className="rsssl-premium-menu-item"><div><a target="_blank" rel="noopener noreferrer" href={rsssl_settings.upgrade_link} className='button button-black'>{__('Upgrade', 'really-simple-ssl')}</a></div></div>
                    }
                </div>
            </div>
            <div className="rsssl-grid-item-footer">

            </div>
        </div>
    )
}
export default Menu;
