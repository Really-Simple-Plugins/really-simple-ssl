import {Component} from "@wordpress/element";
import * as rsssl_api from "./utils/api";
import Header from "./Header";
import DashboardPage from "./DashBoard/DashboardPage";
import SettingsPage from "./Settings/SettingsPage";
import Modal from "./Modal/Modal";
import PagePlaceholder from './Placeholder/PagePlaceholder';
import OnboardingModal from "./OnboardingModal";
import getAnchor from "./utils/getAnchor";

class Page extends Component {
    constructor() {
        super( ...arguments );
        this.pageProps=[];
        this.pageProps['licenseStatus'] = rsssl_settings.licenseStatus;

        this.selectMenu = this.selectMenu.bind(this);
        this.getSelectedMenu = this.getSelectedMenu.bind(this);
        this.selectStep = this.selectStep.bind(this);
        this.handleModal = this.handleModal.bind(this);
        this.highLightField = this.highLightField.bind(this);
        this.updateField = this.updateField.bind(this);
        this.selectMainMenu = this.selectMainMenu.bind(this);
        this.setPageProps = this.setPageProps.bind(this);
        this.getPreviousAndNextMenuItems = this.getPreviousAndNextMenuItems.bind(this);
        this.state = {
            selectedMainMenuItem: '',
            selectedMenuItem: '',
            selectedStep: 1,
            highLightedField:'',
            fields:'',
            menu:[],
            progress:'',
            isAPILoaded: false,
            pageProps:this.pageProps,
            showModal:false,
            modalData:[],
            dropItemFromModal:false,
            nextMenuItem: '',
            previousMenuItem: ''
        };

        this.getFields().then(( response ) => {
            this.superMenu = response.menu;
            let selectedMainMenuItem =  getAnchor('main') || 'dashboard';
            this.menu = this.getSelectedMenu(this.superMenu, selectedMainMenuItem);
            let firstMenuItem = this.menu && this.menu.menu_items.hasOwnProperty(0) ? this.menu.menu_items[0].id : 'general';
            this.fields = response.fields;
            this.progress = response.progress;
            this.setState({
                isAPILoaded: true,
                selectedMainMenuItem: selectedMainMenuItem,
                selectedMenuItem: getAnchor('menu') || firstMenuItem,
                fields: this.fields,
                menu: this.menu,
                progress: this.progress,
            }, () => {
                this.getPreviousAndNextMenuItems();
            });
        });
    }

    componentDidMount(){
        window.addEventListener('hashchange', () => {
            let selectedMainMenuItem =  getAnchor('main') || 'dashboard';
            this.menu = this.getSelectedMenu(this.superMenu, selectedMainMenuItem);
            this.setState({
                selectedMainMenuItem: selectedMainMenuItem,
                selectedMenuItem: this.getDefaultMenuItem(),
                menu:this.menu,
            }, () => {
                this.getPreviousAndNextMenuItems();
            });
        });
    }

    /*
    * filter sidebar menu from complete menu structure
    */

    getSelectedMenu(superMenu, selectedMainMenuItem){
        for (const key in superMenu) {
            if ( superMenu.hasOwnProperty(key) ){
                if ( superMenu[key] && superMenu[key].id === selectedMainMenuItem) {
                    return superMenu[key];
                }
            }
        }
    }

    getFields(){
        return rsssl_api.getFields().then( ( response ) => {
            return response.data;
        });
    }
    /*
     * Allow child blocks to set data on the gridblock
     * @param key
     * @param value
     */
    setPageProps(key, value){
        this.pageProps[key] = value;
        this.setState({
            pageProps: this.pageProps,
        })
    }

    /*
     * Handle instantiation of a modal window
     * @param showModal
     * @param data
     * @param dropItem
     */
    handleModal(showModal, data, dropItem) {
        this.setState({
            showModal: showModal,
            modalData : data,
            dropItemFromModal : dropItem
        })
    }

    selectMenu(selectedMenuItem){
        this.setState({
            selectedMenuItem: selectedMenuItem
        });
    }

    selectStep(selectedStep){
        this.setState({
            selectedStep: selectedStep
        });
    }

    getDefaultMenuItem(){
        let fallBackMenuItem = this.menu && this.menu.menu_items.hasOwnProperty(0) ? this.menu.menu_items[0].id : 'general';
        let anchor = getAnchor('menu');
        let foundAnchorInMenu = false;
        //check if this anchor actually exists in our current submenu. If not, clear it
        for (const key in this.menu.menu_items) {
            if ( this.menu.menu_items.hasOwnProperty(key) &&  this.menu.menu_items[key].id === anchor ){
                foundAnchorInMenu=true;
            }
        }
        if ( !foundAnchorInMenu ) anchor = false;
        return anchor ? anchor : fallBackMenuItem;
    }

    selectMainMenu(selectedMainMenuItem){
        this.menu = this.getSelectedMenu(this.superMenu, selectedMainMenuItem);
        let selectedMenuItem = this.getDefaultMenuItem();
        this.setState({
            menu : this.menu,
            selectedMainMenuItem :selectedMainMenuItem,
            selectedMenuItem :selectedMenuItem
        });
    }

    /*
     * Update a field
     * @param field
     */
    updateField(field) {
        let fields = this.fields;
        for (const fieldItem of fields){
            if (fieldItem.id === field.id ){
                fieldItem.value = field.value;
            }
        }
        this.fields = fields;
        this.setState({
            fields :fields
        });
    }

    highLightField(fieldId){
        //switch to settings page
        console.log("highlight field id switch to settings page");
        this.selectMainMenu('settings');
        //get menu item based on fieldId
        let selectedField = null;
        let fields = this.fields.filter(field => field.id === fieldId);
        if (fields.length) {
            selectedField = fields[0];
            this.selectMenu(selectedField.menu_id);
        }
        this.highLightedField = fieldId;
    }
    /*
     * Get anchor from URL
     * @returns {string|boolean}
     */

    // Parses menu items and nested items in single array
    menuItemParser (parsedMenuItems, menuItems) {
        menuItems.forEach((menuItem) => {
            if(menuItem.visible) {
                parsedMenuItems.push(menuItem.id);
                if(menuItem.hasOwnProperty('menu_items')) {
                    this.menuItemParser(parsedMenuItems, menuItem.menu_items);
                }
            }
        });

        return parsedMenuItems;
    }

    getPreviousAndNextMenuItems () {
        let previousMenuItem;
        let nextMenuItem;
        const { menu_items: menuItems } = this.state.menu;
        const parsedMenuItems = [];
        this.menuItemParser(parsedMenuItems, menuItems);
        // Finds current menu item index
        const currentMenuItemIndex = parsedMenuItems.findIndex((menuItem) => menuItem === this.state.selectedMenuItem)
        if(currentMenuItemIndex !== -1) {
            previousMenuItem = parsedMenuItems[ currentMenuItemIndex === 0 ? '' : currentMenuItemIndex - 1];
            nextMenuItem = parsedMenuItems[ currentMenuItemIndex === parsedMenuItems.length - 1 ? '' : currentMenuItemIndex + 1];
            this.setState({
                previousMenuItem: previousMenuItem ? previousMenuItem : parsedMenuItems[0],
                nextMenuItem: nextMenuItem ? nextMenuItem : parsedMenuItems[parsedMenuItems.length - 1]
            });
        }
        return { nextMenuItem, previousMenuItem };
    }

    render() {
        const {
            selectedMainMenuItem,
            selectedMenuItem,
            fields,
            menu,
            progress,
            isAPILoaded,
            showModal,
            modalData,
            dropItemFromModal,
        } = this.state;
        return (
            <div className="rsssl-wrapper">
                <OnboardingModal setPageProps={this.setPageProps} />
                {!isAPILoaded && <PagePlaceholder></PagePlaceholder>}
                {showModal && <Modal handleModal={this.handleModal} data={modalData}/>}
                {isAPILoaded &&
                    (
                        <>
                            <Header
                                selectedMainMenuItem={selectedMainMenuItem}
                                selectMainMenu={this.selectMainMenu}
                                superMenu = {this.superMenu}
                                fields={fields} />
                            <div className={"rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem}>
                                { selectedMainMenuItem !== 'dashboard' &&
                                    <SettingsPage
                                        dropItemFromModal={dropItemFromModal}
                                        pageProps={this.pageProps}
                                        handleModal={this.handleModal}
                                        getDefaultMenuItem={this.getDefaultMenuItem}
                                        updateField={this.updateField}
                                        setPageProps={this.setPageProps}
                                        selectMenu={this.selectMenu}
                                        selectStep={this.selectStep}
                                        selectedStep={this.state.selectedStep}
                                        highLightField={this.highLightField}
                                        highLightedField={this.highLightedField}
                                        selectedMenuItem={selectedMenuItem}
                                        selectedMainMenuItem={selectedMainMenuItem}
                                        isAPILoaded={isAPILoaded}
                                        fields={fields}
                                        menu={menu}
                                        progress={progress}
                                        getPreviousAndNextMenuItems={this.getPreviousAndNextMenuItems}
                                        nextMenuItem={this.state.nextMenuItem}
                                        previousMenuItem={this.state.previousMenuItem} />
                                }
                                { selectedMainMenuItem === 'dashboard' &&
                                    <DashboardPage isAPILoaded={isAPILoaded} fields={fields} selectMainMenu={this.selectMainMenu} highLightField={this.highLightField} pageProps={this.pageProps}/>
                                }
                            </div>
                        </>
                    )
                }
            </div>
        );
    }
}
export default Page