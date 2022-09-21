import {
    Component,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
class MenuItem extends Component {
    constructor() {
        super( ...arguments );
    }

    handleClick(){
        this.props.selectMenu(this.props.menuItem.id);
    }

    componentDidMount() {
        this.handleClick = this.handleClick.bind(this);
    }

    render(){
        /*
         * Menu is selected if the item is the same, or if it is a child.
         */
        let menuIsSelected = this.props.selectedMenuItem===this.props.menuItem.id;
        if (this.props.menuItem.menu_items) {
            for (const item of this.props.menuItem.menu_items){
                if (item.id === this.props.selectedMenuItem ){
                    menuIsSelected=true;
                }
            }
        }

        let menuClass = menuIsSelected ? ' rsssl-active' : '';
        menuClass += this.props.menuItem.featured ? ' rsssl-featured' : '';
        menuClass += this.props.menuItem.premium && !rsssl_settings.pro_plugin_active ? ' rsssl-premium' : '';
        let href = '#'+this.props.selectedMainMenuItem+'/'+this.props.menuItem.id;
        return (
            <>
            {this.props.menuItem.visible && <div className={"rsssl-menu-item" + menuClass}>
                <a href={href} onClick={() => this.handleClick()}>
                    <span>{this.props.menuItem.title}</span>
                    {this.props.menuItem.featured && <><span className='rsssl-menu-item-featured-pill'>{__('New', 'really-simple-ssl')}</span></>}
                </a>
                { (this.props.menuItem.menu_items && menuIsSelected) && <div className="rsssl-submenu-item">
                    {this.props.menuItem.menu_items.map(
                        (subMenuItem, i) => subMenuItem.visible && <MenuItem key={i}
                                                                             menuItem={subMenuItem}
                                                                             selectMenu={this.props.selectMenu}
                                                                             selectedMenuItem={this.props.selectedMenuItem}/>
                    )}
                </div>}
            </div>}
            </>
        )
    }
}

export default MenuItem