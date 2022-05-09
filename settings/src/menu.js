import {
    Placeholder,
} from '@wordpress/components';
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

          let menuIsSelected = this.props.selectedMenuItem===this.props.menuItem;
        if (this.props.menuItem.menu_items) {
            for (const item of this.props.menuItem.menu_items){
                if (item === this.props.selectedMenuItem ){
                    menuIsSelected=true;
                }
            }
        }

        return (
            <div className="rsssl-menu-item">
                <a href="#" onClick={ () => this.handleClick() }>{this.props.menuItem.title}</a>
                <div className="rsssl-submenu-item">
                    {this.props.menuItem.menu_items && menuIsSelected && this.props.menuItem.menu_items.map((menuItem, i) => <MenuItem key={i} menuItem={menuItem} selectMenu={this.props.selectMenu} selectedMenuItem={this.props.selectedMenuItem}/>)}
                </div>
            </div>
        )
    }
}

class Menu extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            fields:this.props.fields,
            menu: this.props.menu,
            menuItems: this.props.menuItems,
            isAPILoaded: this.props.isAPILoaded,
        };
    }

    render() {
        const {
            fields,
            menu,
            menuItems,
            isAPILoaded,
        } = this.state;

        if ( ! isAPILoaded ) {
            return (
                <Placeholder></Placeholder>
            );
        }

        return (
            <div className="rsssl-wizard-menu">
                <h1>{this.props.menu.title}</h1>
                {menuItems.map((menuItem, i) => <MenuItem key={i} isAPILoaded={isAPILoaded} menuItem={menuItem} selectMenu={this.props.selectMenu} selectedMenuItem={this.props.selectedMenuItem}/>)}
            </div>
        )
    }
}

export default Menu;