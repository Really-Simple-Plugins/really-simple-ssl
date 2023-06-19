import {useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import Notices from "./Settings/Notices";
import useMenu from "./Menu/MenuData";

const Header = () => {
    const {menu, selectedMainMenuItem, fetchMenuData} = useMenu();
    let plugin_url = rsssl_settings.plugin_url;
    useEffect( () => {
        fetchMenuData();
    }, [] );

    let menuItems = menu.filter( item => item!==null );
    return (
        <div className="rsssl-header-container">
            <div className="rsssl-header">
                <img className="rsssl-logo" src={plugin_url+"assets/img/really-simple-ssl-logo.svg"} alt="Really Simple SSL logo" />
                <div className="rsssl-header-left">
                    <nav className="rsssl-header-menu">
                        <ul>
                            {menuItems.map((menu_item, i) =>
                                <li key={"menu-"+i}><a className={ selectedMainMenuItem === menu_item.id ? 'active' : '' } href={"#" + menu_item.id.toString()} >{menu_item.title}</a></li>)}

                        </ul>
                    </nav>
                </div>
                <div className="rsssl-header-right">
                    { !rsssl_settings.le_generated_by_rsssl &&
                        <a className="rsssl-knowledge-base-link" href="https://really-simple-ssl.com/knowledge-base" target="_blank">{__("Documentation", "really-simple-ssl")}</a>}
                    { rsssl_settings.le_generated_by_rsssl &&
                        <a href={rsssl_settings.letsencrypt_url}>{__("Let's Encrypt","really-simple-ssl")}</a>
                    }
                    { rsssl_settings.pro_plugin_active &&
                        <a href="https://wordpress.org/support/plugin/really-simple-ssl/"
                           className="button button-black"
                           target="_blank">{__("Support", "really-simple-ssl")}</a>
                    }
                    { !rsssl_settings.pro_plugin_active &&
                        <a href={rsssl_settings.upgrade_link}
                           className="button button-black"
                           target="_blank">{__("Go Pro", "really-simple-ssl")}</a>
                    }
                </div>
            </div>
            <Notices className="rsssl-wizard-notices"/>
        </div>
    );

}
export default Header