import * as rsssl_api from './utils/api';
import in_array from './utils/lib';

import {
    Button,
    Panel,
    PanelBody,
    Placeholder,
} from '@wordpress/components';

import {
    Fragment,
    render,
    Component,
} from '@wordpress/element';

import { __ } from '@wordpress/i18n';
import Field from './fields';
import Menu from './Menu';
import GridBlock from './GridBlock';


class Help extends Component {
	render(){
		let field = this.props.field;
		if (field.help) {
			return (
				<div className="rsssl-wizard__help_notice">
					{field.help}
				</div>
			);
		} else {
			return (
				<p></p>
			);
		}
	}
}


class Settings extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			fields:this.props.fields,
			isAPILoaded: this.props.isAPILoaded,
		};
		this.fields = this.props.fields;
	}

	render() {
		const {
			fields,
			isAPILoaded,
		} = this.state;
		let selectedMenuItem = this.props.selectedMenuItem;
		let selectedStep = this.props.selectedStep;
		let menu = this.props.menu;
		if ( ! isAPILoaded ) {
			return (
				<Placeholder></Placeholder>
			);
		}
		let selectedFields = fields.filter(field => field.menu_id === selectedMenuItem.id);
		return (
			<div className="rsssl-wizard-settings">
				<div className="rsssl-wizard__main">
					<Panel>
						{selectedFields.map((field, i) => <Field key={i} index={i} saveChangedFields={this.props.saveChangedFields} field={field} fields={selectedFields}/>)}
						<PanelBody
							icon="admin-plugins"
						>
							<Button
								isPrimary
								onClick={ this.props.save }
							>
								{ __( 'Save', 'really-simple-ssl' ) }
							</Button>
						</PanelBody>
					</Panel>
				</div>
				<div className="rsssl-wizard__help">
					{selectedFields.map((field, i) => <Help key={i} index={i} field={field} />)}
				</div>
			</div>
		)
	}
}

class SettingsPage extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
			selectedMenuItem:'',
			selectedStep:1,
			fields:'',
			menu:'',
			menuItems:'',
            isAPILoaded: false,
			changedFields:'',
        };
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

	saveChangedFields(changedField){
		let changedFields = this.changedFields;
		if (!in_array(changedField, changedFields)) {
			changedFields.push(changedField);
		}
		this.changedFields = changedFields;
		this.setState({
			changedFields :changedFields
		});
	}

	save(){
		const {
			fields,
		} = this.state;
		let saveFields = [];
		for (const field of fields){
			if (in_array(field.id, this.changedFields)){
				saveFields.push(field);
			}
		}
		rsssl_api.setFields(saveFields).then(( response ) => {
			dispatch('core/notices').createNotice(
				'success',
				__( 'Settings Saved', 'really-simple-ssl' ),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
			this.changedFields = [];
			this.setState({
				changedFields :[]
			});

		});
	}

    componentDidMount() {
		this.save = this.save.bind(this);
		this.selectMenu = this.selectMenu.bind(this);
		this.saveChangedFields = this.saveChangedFields.bind(this);
		let fields = this.props.fields;
		let menu = this.props.menu;
		//if count >1, it's a wizard
		let menuItems = [];
		let changedFields = [];
		menuItems = menu.menu_items;
		let selectedMenuItem = menuItems[0];
		let selectedStep = 1;
		this.menu = menu;
		this.menuItems = menuItems;
		this.fields = fields;
		this.selectedMenuItem = selectedMenuItem;
		this.selectedStep = selectedStep;
		this.changedFields = changedFields;
		this.setState({
			isAPILoaded: true,
			fields: this.props.fields,
			menu: this.props.menu,
			menuItems:menuItems,
			selectedMenuItem: selectedMenuItem,
			selectedStep: selectedStep,
			changedFields: changedFields,
		});
    }

    render() {
        const {
            fields,
			menu,
			menuItems,
			selectedMenuItem,
			selectedStep,
            isAPILoaded,
        } = this.state;

		if ( ! isAPILoaded ) {
			return (
				<Placeholder></Placeholder>
			);
		}

		//maybe filter step
		if ( menu.is_wizard ){
			let length = menuItems.length;
			let temp = []
			for(let i = 0; i < length; i++) {
				if ( menuItems[i]['step']!=selectedStep ){
					menuItems.splice(i, 1);
				}
			}
		}

        return (
            <Fragment>
				<Menu isAPILoaded={isAPILoaded} menuItems={this.menuItems} menu={this.menu} selectMenu={this.selectMenu}/>
				<Settings isAPILoaded={isAPILoaded} fields={this.fields} saveChangedFields={this.saveChangedFields} menu={menu} save={this.save} selectedMenuItem={selectedMenuItem} selectedStep={selectedStep}/>
				<div className="rsssl-wizard__notices">
					<Notices/>
				</div>
            </Fragment>
        )
    }
}

class DashboardPage extends Component {
	constructor() {
		super( ...arguments );
	}

	render() {
		let blocks = rsssl_settings.blocks;
		console.log(blocks);
		return (
			<div className="rsssl-grid">
				{blocks.map((block, i) => <GridBlock key={i} block={block}/>)}
			</div>
		);
	}
}

class Header extends Component {
	constructor() {
		super( ...arguments );
	}
	handleClick(menuId){
		this.props.selectMenu(menuId);
	}
	componentDidMount() {
		this.handleClick = this.handleClick.bind(this);
	}
	render() {
		let menu = rsssl_settings.menu;
		let plugin_url = rsssl_settings.plugin_url;
		return (
			<div className="rsssl-header nav-tab-wrapper <?php echo $high_contrast ?>">
				<div className="rsssl-logo-container">
					<div id="rsssl-logo"><img src={plugin_url+"/assets/really-simple-ssl-logo.png"} alt="review-logo" /></div>
				</div>
				{menu.map((menu_item, i) => <a key={i} onClick={ () => this.handleClick(menu_item.id) } className='nav-tab' href='#' >{menu_item.label}</a>)}
				<div className="header-links">
					<div className="documentation">
						<a href="https://really-simple-ssl.com/knowledge-base"
						   className={rsssl_settings.premium && 'button button-primary'}
						   target="_blank">{__("Documentation", "really-simple-ssl")}</a>
					</div>
					<div className="header-upsell">
						{rsssl_settings.premium &&
						<div className="documentation">
							<a href="https://wordpress.org/support/plugin/really-simple-ssl/"
							   className="button button-primary"
							   target="_blank">{__("Support", "really-simple-ssl")}</a>
						</div>}
					</div>
				</div>
			</div>
		);
	}
}
class Page extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			selectedMainMenuItem:'dashboard',
			fields:'',
			menu:'',
			isAPILoaded: false,
		};

		this.getFields().then(( response ) => {
			let fields = response.fields;
			let menu = response.menu;
			this.menu = menu;
			this.fields = fields;
			this.setState({
				isAPILoaded: true,
				fields: fields,
				menu: menu,
			});
		});
	}
	getFields(){
		return rsssl_api.getFields().then( ( response ) => {
			return response.data;
		});
	}
	componentDidMount() {
		this.selectMenu = this.selectMenu.bind(this);
		this.setState({
			selectedMainMenuItem: 'dashboard',
		});
	}

	selectMenu(selectedMainMenuItem){
		this.setState({
			selectedMainMenuItem :selectedMainMenuItem
		});
	}

	render() {
		const {
			selectedMainMenuItem,
			fields,
			menu,
			isAPILoaded,
		} = this.state;
		return (
			<div id="rsssl-wrapper">
				<Header selectedMainMenuItem={selectedMainMenuItem} selectMenu={this.selectMenu}/>
				<div id="rsssl-container">
					{selectedMainMenuItem==='settings' && <SettingsPage isAPILoaded={isAPILoaded} fields={fields} menu={menu}/> }
					{selectedMainMenuItem==='dashboard' && <DashboardPage/> }
				</div>
			</div>
		);
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'really-simple-ssl' );
	if ( container ) {
		render(
			<Page/>,
			container
		);
	}
});



/**
 * Notice after saving was successfull
 */
import { SnackbarList } from '@wordpress/components';

import {
    dispatch,
    useDispatch,
    useSelect,
} from '@wordpress/data';

import { store as noticesStore } from '@wordpress/notices';

const Notices = () => {
    const notices = useSelect(
        ( select ) =>
            select( noticesStore )
                .getNotices()
                .filter( ( notice ) => notice.type === 'snackbar' ),
        []
    );
    const { removeNotice } = useDispatch( noticesStore );
    return (
        <SnackbarList
            className="edit-site-notices"
            notices={ notices }
            onRemove={ removeNotice }
        />
    );
};

// <div className="rsssl-settings-saved rsssl-settings-saved--fade-in">
// 	<div className="rsssl-settings-saved__text_and_icon">
// 		<span><div className="rsssl-tooltip-icon dashicons-before rsssl-icon rsssl-success check"><svg width="18"
// 																									   height="18"
// 																									   viewBox="0 0 1792 1792"
// 																									   xmlns="http://www.w3.org/2000/svg"><path
// 			d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"></path></svg></div></span>
// 		<span><?php _e('Changes saved successfully', 'really-simple-ssl') ?> </span>
// 	</div>
// </div>
