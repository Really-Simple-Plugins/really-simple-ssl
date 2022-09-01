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
            menu:this.props.menu,
            selectedMainMenuItem:this.props.selectedMainMenuItem,
        };
    }

    componentDidUpdate(){
        this.state = {
            selectedMainMenuItem:this.props.selectedMainMenuItem,
        };
    }

    render() {
        if ( ! this.props.isAPILoaded ) {
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
                                this.props.menu.menu_items.map((menuItem, i) =>
                                    <MenuItem
                                        key={i}
                                        isAPILoaded={this.props.isAPILoaded}
                                        menuItem={menuItem}
                                        selectMenu={this.props.selectMenu}
                                        selectStep={this.props.selectStep}
                                        selectedMenuItem={this.props.selectedMenuItem}
                                        selectedMainMenuItem={this.props.selectedMainMenuItem}
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