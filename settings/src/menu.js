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
        this.props.selectMenu(this.props.menuItem);
    }

    componentDidMount() {
        this.handleClick = this.handleClick.bind(this);
    }

    render(){
        const {
            menuItem,
            isAPILoaded,
        } = this.state;
        return (
            <div>
                <a href="#" onClick={ () => this.handleClick() }>{this.props.menuItem.title}</a>
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
                {menuItems.map((menuItem, i) => <MenuItem key={i} isAPILoaded={isAPILoaded} menuItem={menuItem} selectMenu={this.props.selectMenu} />)}
            </div>
        )
    }
}

export default Menu;