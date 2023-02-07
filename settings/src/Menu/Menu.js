import Placeholder from '../Placeholder/Placeholder';
import MenuItem from './MenuItem';
import { __ } from '@wordpress/i18n';
import useMenu from "./MenuData";
import useLicense from "../Settings/License/LicenseData";
/**
 * Menu block, rendering the entire menu
 */
const Menu = (props) => {
    const {subMenu, hasPremiumItems, subMenuLoaded} = useMenu();
    const {licenseStatus} = useLicense();

    if ( !subMenuLoaded ) {
        return(
            <div className="rsssl-wizard-menu rsssl-grid-item">
                <div className="rsssl-grid-item-header">
                    ...<h1 className="rsssl-h4"></h1>
                </div>
                <div className="rsssl-grid-item-content">
                    <Placeholder lines="3"></Placeholder>
                </div>
            </div>
        )
    }
    return (
        <div className="rsssl-wizard-menu rsssl-grid-item">
            <div className="rsssl-grid-item-header">
                <h1 className="rsssl-h4">{subMenu.title}</h1>
            </div>
            <div className="rsssl-grid-item-content">
                <div className="rsssl-wizard-menu-items">
                    { subMenu.menu_items.map((menuItem, i) => <MenuItem key={i} menuItem={menuItem} /> ) }
                    { hasPremiumItems && !rsssl_settings.is_premium && licenseStatus!=='valid' &&
                        <div className="rsssl-premium-menu-item"><div><a target="_blank" href={rsssl_settings.upgrade_link} className='button button-black'>{__('Go Pro', 'really-simple-ssl')}</a></div></div>
                    }
                </div>
            </div>
            <div className="rsssl-grid-item-footer">

            </div>
        </div>
    )
}
export default Menu;
