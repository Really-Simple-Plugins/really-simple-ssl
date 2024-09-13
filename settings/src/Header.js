import {useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import Notices from "./Settings/Notices";
import useMenu from "./Menu/MenuData";
import {addUrlRef} from "./utils/AddUrlRef";

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
                <img className="rsssl-logo" src={plugin_url+"assets/img/really-simple-security-logo.svg"} alt="Really Simple Security logo" />
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
                        <a className="rsssl-knowledge-base-link" href={addUrlRef("https://really-simple-ssl.com/knowledge-base")} target="_blank" rel="noopener noreferrer">{__("Documentation", "really-simple-ssl")}</a>}
                    { rsssl_settings.le_generated_by_rsssl &&
                        <a href={rsssl_settings.letsencrypt_url}>{__("Let's Encrypt","really-simple-ssl")}</a>
                    }
                    {rsssl_settings.pro_plugin_active && (
                        <>
                            {(() => {
                                const supportUrl = rsssl_settings.dashboard_url + '#settings&highlightfield=premium_support';
                                return (
                                    <a
                                        href={supportUrl}
                                        className="button button-black"
                                        target="_self"
                                        rel="noopener noreferrer"
                                    >
                                        {__("Support", "really-simple-ssl")}
                                    </a>
                                );
                            })()}
                        </>
                    )}
                    { !rsssl_settings.pro_plugin_active &&
                        <a href={rsssl_settings.upgrade_link}
                           className="button button-black"
                           target="_blank" rel="noopener noreferrer">{__("Go Pro", "really-simple-ssl")}</a>
                    }
                </div>
            </div>
            <Notices className="rsssl-wizard-notices"/>
        </div>
    );

}
export default Header