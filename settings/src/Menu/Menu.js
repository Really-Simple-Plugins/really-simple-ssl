import Placeholder from '../Placeholder/Placeholder';
import MenuItem from './MenuItem';
import { __ } from '@wordpress/i18n';
/**
 * Menu block, rendering th entire menu
 */
const Menu = (props) => {
     let hasPremiumItems =  props.menu.menu_items.filter((item) => {
        return (item.premium===true)
    }).length>0;

    if ( ! props.isAPILoaded ) {
        return (
            <Placeholder></Placeholder>
        );
    }

    return (
        <div className="rsssl-wizard-menu rsssl-grid-item">
            <div className="rsssl-grid-item-header">
                <h1 className="rsssl-h4">{props.menu.title}</h1>
            </div>
            <div className="rsssl-grid-item-content">
                <div className="rsssl-wizard-menu-items">
                    {
                        props.menu.menu_items.map((menuItem, i) =>
                            <MenuItem
                                key={i}
                                isAPILoaded={props.isAPILoaded}
                                menuItem={menuItem}
                                selectMenu={props.selectMenu}
                                selectStep={props.selectStep}
                                selectedMenuItem={props.selectedMenuItem}
                                selectedMainMenuItem={props.selectedMainMenuItem}
                                getPreviousAndNextMenuItems={props.getPreviousAndNextMenuItems}
                            />
                        )
                    }
                    { hasPremiumItems && !rsssl_settings.pro_plugin_active &&
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