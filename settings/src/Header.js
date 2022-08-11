import {Component} from "@wordpress/element";
import { __ } from '@wordpress/i18n';

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
    render() {
        let menu = rsssl_settings.menu;
        let plugin_url = rsssl_settings.plugin_url;
        let active_menu_item = this.props.selectedMainMenuItem;
        let knowledgeBaseBtnClass = !rsssl_settings.pro_active ?'button button-black' :'';
        return (
            <div className="rsssl-header-container">
                <div className="rsssl-header">
                    <img className="rsssl-logo" src={plugin_url+"assets/img/really-simple-ssl-logo.svg"} alt="Really Simple SSL logo" />
                    <div className="rsssl-header-left">
                        <nav className="rsssl-header-menu">
                            <ul>
                                {menu.map((menu_item, i) => <li key={i}><a className={ active_menu_item === menu_item.id ? 'active' : '' } onClick={ () => this.handleClick(menu_item.id) } href={"#" + menu_item.id.toString()} >{menu_item.label}</a></li>)}
                            </ul>
                        </nav>
                    </div>
                    <div className="rsssl-header-right">
                        <a href="https://really-simple-ssl.com/knowledge-base"
                           className={knowledgeBaseBtnClass}
                           target="_blank">{__("Documentation", "really-simple-ssl")}</a>
                        {rsssl_settings.pro_active &&
                            <a href="https://wordpress.org/support/plugin/really-simple-ssl/"
                               className="button button-black"
                               target="_blank">{__("Support", "really-simple-ssl")}</a>
                        }
                    </div>
                </div>
            </div>
        );
    }
}
export default Header