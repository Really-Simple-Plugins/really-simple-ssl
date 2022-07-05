import {Component} from "@wordpress/element";
import * as rsssl_api from "./utils/api";
import Header from "./Header";
import DashboardPage from "./DashboardPage";
import SettingsPage from "./SettingsPage";
import Modal from "./Modal";

class Page extends Component {
    constructor() {
        super( ...arguments );
        this.pageProps=[];
        this.pageProps['licenseStatus'] = rsssl_settings.licenseStatus;
        this.state = {
            selectedMainMenuItem:'dashboard',
            selectedMenuItem:'general',
            highLightedField:'',
            fields:'',
            menu:'',
            progress:'',
            isAPILoaded: false,
            pageProps:this.pageProps,
            showModal:false,
            modalData:[],
        };

        this.getFields().then(( response ) => {
            let fields = response.fields;
            let menu = response.menu;
            let progress = response.progress;
            this.menu = menu;
            this.progress = progress;
            this.fields = fields;
            this.setState({
                isAPILoaded: true,
                fields: fields,
                menu: menu,
                progress: progress,
            });
        });
    }
    getFields(){
        return rsssl_api.getFields().then( ( response ) => {
            return response.data;
        });
    }
    /**
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

    /**
     * Handle instantiation of a modal window
     * @param showModal
     * @param data
     */
    handleModal(showModal, data) {
        this.setState({
            showModal: showModal,
            modalData : data,
        })
    }

    componentDidMount() {
        this.selectMenu = this.selectMenu.bind(this);
        this.handleModal = this.handleModal.bind(this);
        this.highLightField = this.highLightField.bind(this);
        this.updateField = this.updateField.bind(this);
        this.selectMainMenu = this.selectMainMenu.bind(this);
        this.setPageProps = this.setPageProps.bind(this);

        this.setState({
            selectedMainMenuItem: 'dashboard',
            selectedMenuItem: 'general',
        });
    }

    selectMenu(selectedMenuItem){
        this.setState({
            selectedMenuItem :selectedMenuItem
        });
    }

    selectStep(selectedStep){
        this.setState({
            selectedStep :selectedStep
        });
    }

    selectMainMenu(selectedMainMenuItem){
        this.setState({
            selectedMainMenuItem :selectedMainMenuItem
        });
    }

    /**
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
        } = this.state;

        return (
            <div className="rsssl-wrapper">
                {showModal && <Modal handleModal={this.handleModal} data={modalData}/>}
                <Header selectedMainMenuItem={selectedMainMenuItem} selectMainMenu={this.selectMainMenu} fields={fields}/>
                <div className={"rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem}>
                    {selectedMainMenuItem==='settings' && <SettingsPage pageProps={this.pageProps} handleModal={this.handleModal} updateField={this.updateField} setPageProps={this.setPageProps} selectMenu={this.selectMenu} highLightField={this.highLightField} highLightedField={this.highLightedField} selectedMenuItem={selectedMenuItem} isAPILoaded={isAPILoaded} fields={fields} menu={menu} progress={progress}/> }
                    {selectedMainMenuItem==='dashboard' && <DashboardPage isAPILoaded={isAPILoaded} fields={fields} highLightField={this.highLightField}/> }
                </div>
            </div>
        );
    }
}
export default Page