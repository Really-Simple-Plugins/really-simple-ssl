import {Component} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import getAnchor from "./utils/getAnchor";
import * as rsssl_api from "./utils/api";
import sleeper from "./utils/sleeper";
import {dispatch,} from '@wordpress/data';
import Notices from "./Settings/Notices";

class Header extends Component {
    constructor() {
        super( ...arguments );
    }
    handleClick(menuId){
        this.props.selectMainMenu(menuId);
    }
    componentDidMount() {
        this.handleClick = this.handleClick.bind(this);
    }

    handleClearCache(){
        let data = {};
        rsssl_api.doAction('clear_cache', data).then( ( response ) => {
            const notice = dispatch('core/notices').createNotice(
                'success',
                __( 'Cleared all test caches', 'really-simple-ssl' ),
                {
                    __unstableHTML: true,
                    id: 'rsssl_clear_cache',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then(( response ) => {
                dispatch('core/notices').removeNotice('rsssl_clear_cache');
            });
            this.props.getFields();
        });
    }
    render() {
        let plugin_url = rsssl_settings.plugin_url;
        let active_menu_item = this.props.selectedMainMenuItem;
        var menu =Object.values(this.props.superMenu);
        menu = menu.filter( item => item!==null );
        //filter out hidden menus if not in the anchor
        let anchor = getAnchor('main');
        menu = menu.filter( item => !item.default_hidden || anchor===item.id);

        return (
            <div className="rsssl-header-container">
                <div className="rsssl-header">
                    <img className="rsssl-logo" src={plugin_url+"assets/img/really-simple-ssl-logo.svg"} alt="Really Simple SSL logo" />
                    <div className="rsssl-header-left">
                        <nav className="rsssl-header-menu">
                            <ul>
                                {menu.map((menu_item, i) =>
                                  <li key={i}><a className={ active_menu_item === menu_item.id ? 'active' : '' } onClick={ () => this.handleClick(menu_item.id) } href={"#" + menu_item.id.toString()} >{menu_item.title}</a></li>)}

                            </ul>
                        </nav>
                    </div>
                    <div className="rsssl-header-right">
                        <a className="rsssl-knowledge-base-link" href="#" onClick={ () => this.handleClearCache() }>{__("Clear test caches", "really-simple-ssl")}</a>
                        <a className="rsssl-knowledge-base-link" href="https://really-simple-ssl.com/knowledge-base" target="_blank">{__("Documentation", "really-simple-ssl")}</a>
                        {rsssl_settings.pro_plugin_active &&
                            <a href="https://wordpress.org/support/plugin/really-simple-ssl/"
                               className="button button-black"
                               target="_blank">{__("Support", "really-simple-ssl")}</a>
                        }
                        {!rsssl_settings.pro_plugin_active &&
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
}
export default Header