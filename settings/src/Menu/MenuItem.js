import {
    Component,
} from '@wordpress/element';

class MenuItem extends Component {
    constructor() {
        super( ...arguments );
        this.menuItem = this.props.menuItem;
        this.state = {
            menuItem: this.props.menuItem,
            isAPILoaded: this.props.isAPILoaded,
        };
    }

    handleClick(){
        this.props.selectMenu(this.props.menuItem.id);
    }

    componentDidMount() {
        this.handleClick = this.handleClick.bind(this);
    }

    render(){
        const {
            menuItem,
            isAPILoaded,
        } = this.state;
        /**
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

        let activeClass = menuIsSelected ? ' rsssl-active' : '';
        let featuredClass = this.props.menuItem.featured ? ' rsssl-featured' : '';
        let href = '#settings/'+this.props.menuItem.id;
        return (
            this.props.menuItem.visible && <div className={"rsssl-menu-item" + activeClass + featuredClass}>
                <a href={href} onClick={() => this.handleClick()}>
                    <span>{this.props.menuItem.title}</span>
                    {this.props.menuItem.featured && <p className="rsssl-menu-item-featured">{this.props.menuItem.featured}</p>}
                </a>
                { (this.props.menuItem.menu_items && menuIsSelected) && <div className="rsssl-submenu-item">
                    {this.props.menuItem.menu_items.map(
                        (subMenuItem, i) => subMenuItem.visible && <MenuItem key={i}
                                                                             menuItem={subMenuItem}
                                                                             selectMenu={this.props.selectMenu}
                                                                             selectedMenuItem={this.props.selectedMenuItem}/>
                    )}
                </div>}
            </div>
        )
    }
}

export default MenuItem