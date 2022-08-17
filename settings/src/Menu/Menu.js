import Placeholder from '../Placeholder/Placeholder';
import MenuItem from './MenuItem';

import {
    Component,
} from '@wordpress/element';

/**
 * Menu block, rendering th entire menu
 */
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
                <div className="rsssl-wizard-menu rsssl-grid-item">
                    <div className="rsssl-grid-item-header">
                        <h1 className="rsssl-h4">{this.props.menu.title}</h1>
                    </div>
                    <div className="rsssl-grid-item-content">
                        <div className="rsssl-wizard-menu-items">
                            {
                                menuItems.map((menuItem, i) =>
                                    <MenuItem
                                        key={i}
                                        isAPILoaded={isAPILoaded}
                                        menuItem={menuItem}
                                        selectMenu={this.props.selectMenu}
                                        selectStep={this.props.selectStep}
                                        selectedMenuItem={this.props.selectedMenuItem}
                                        getPreviousAndNextMenuItems={this.props.getPreviousAndNextMenuItems}
                                    />
                                )
                            }
                        </div>
                    </div>
                    <div className="rsssl-grid-item-footer">

                    </div>
                </div>
        )
    }
}

export default Menu;