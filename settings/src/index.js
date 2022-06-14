import * as rsssl_api from './utils/api';
import in_array from './utils/lib';

import {
    Button,
    Panel,
    PanelBody,
		PanelRow,
    Placeholder,
} from '@wordpress/components';


import {
    Fragment,
    render,
    Component,
} from '@wordpress/element';
import { more } from '@wordpress/icons';

import { __ } from '@wordpress/i18n';
import Field from './fields';
import Menu from './Menu';
import GridBlock from './GridBlock';

/**
 * Render a help notice in the sidebar
 */
class Help extends Component {
	handleClick(id){
		console.log(id);
		let el = document.querySelector('[data-help_index="'+id+'"]');
		if (el.classList.contains('rsssl-wizard__help_open')) {
			el.classList.remove('rsssl-wizard__help_open');
		} else {
			el.classList.add('rsssl-wizard__help_open');
		}
	}
	render(){
		let notice = this.props.help;
		if ( !notice.title ){
			notice.title = notice.text;
			notice.text = false;
		}

		let titleClass = 'rsssl-wizard__help_title';
		if (notice.text) titleClass+= ' rsssl-wizard__help_has_content';

		return (
			<div data-help_index={this.props.index} className="rsssl-wizard__help_notice" data-field_id={this.props.field}>
				<div className={titleClass} onClick={ () => this.handleClick(this.props.index) }>{notice.title}</div>
				{notice.text && <div className="rsssl-wizard__help_content" dangerouslySetInnerHTML={{__html: notice.text}}></div>}
			</div>
		);

	}
}

/**
 * Render a grouped block of settings
 */
class SettingsGroup extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			fields:this.props.fields,
			isAPILoaded: this.props.isAPILoaded,
		};
		this.fields = this.props.fields;
	}

	render(){
		let selectedMenuItem = this.props.selectedMenuItem;
		let selectedFields = [];
		//get all fields with group_id this.props.group_id
		for (const selectedField of this.props.fields){
			if (selectedField.group_id === this.props.group ){
				selectedFields.push(selectedField);
			}
		}
		return (
				<Panel>
					<PanelBody title={selectedMenuItem.title} initialOpen={ true }>
						{selectedMenuItem.intro && <div className="rsssl-settings-block-intro">{selectedMenuItem.intro}</div>}
						{selectedFields.map((field, i) => <Field key={i} index={i} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField} saveChangedFields={this.props.saveChangedFields} field={field} fields={selectedFields}/>)}
							<Button
									isPrimary
									onClick={ this.props.save }>
								{ __( 'Save', 'really-simple-ssl' ) }
							</Button>
					</PanelBody>
				</Panel>
		)
	}
}

/**
 * Renders the selected settings
 *
 */
class Settings extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			fields:this.props.fields,
			progress:this.props.progress,
			isAPILoaded: this.props.isAPILoaded,
		};
		this.fields = this.props.fields;
	}

	render() {
		const {
			fields,
			progress,
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
		let selectedFields = fields.filter(field => field.menu_id === selectedMenuItem);
		let groups = [];
		for (const selectedField of selectedFields){
			if ( !in_array(selectedField.group_id, groups) ){
				groups.push(selectedField.group_id);
			}
		}

		//convert progress notices to an array useful for the help blocks
		let notices = [];
		for (const notice of progress.notices){
			if ( notice.menu_id === selectedMenuItem ) {
				let help = {};
				help.title = notice.output.title ? notice.output.title : false;
				help.label = notice.output.label;
				help.id = notice.field_id;
				help.text = notice.output.msg;
				notices.push(help);
			}
		}
		for (const notice of selectedFields.filter(field => field.help)){
			let help = notice.help;
			help.id = notice.id;
			notices.push(notice.help);
		}
		let selectedMenuItemObject = menu.menu_items.filter(menutItem => menutItem.id === selectedMenuItem)[0];
		return (
			<Fragment>
				<div className="rsssl-wizard-settings">
						{groups.map((group, i) => <SettingsGroup key={i} index={i} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField} selectedMenuItem={selectedMenuItemObject} saveChangedFields={this.props.saveChangedFields} group={group} fields={selectedFields}/>)}
				</div>
				<div className="rsssl-wizard-help">
					{notices.map((field, i) => <Help key={i} index={i} help={field} fieldId={field.id}/>)}
				</div>
			</Fragment>
		)
	}
}

/**
 * Renders the settings page with Menu and currently selected settings
 *
 */

class SettingsPage extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
			selectedMenuItem:'general',
			selectedStep:1,
			fields:'',
			menu:'',
			menuItems:'',
            isAPILoaded: false,
			changedFields:'',
			progress:'',
        };
	}

	updateFieldsListWithConditions(){
		for (const field of this.props.fields){
			this.props.fields[this.props.fields.indexOf(field)].visible = !(field.hasOwnProperty('react_conditions') && !this.validateConditions(field.react_conditions, this.props.fields));
		}
	}

	saveChangedFields(changedField){
		this.updateFieldsListWithConditions();
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

	validateConditions(conditions, fields){
		let relation = conditions.relation === 'OR' ? 'OR' : 'AND';
		delete conditions['relation'];
		let conditionApplies = true;
		for (const key in conditions) {
			if ( conditions.hasOwnProperty(key) ) {
				let invert = key.indexOf('!')===0;
				let thisConditionApplies = true;
				let subConditionsArray = conditions[key];
				if ( subConditionsArray.hasOwnProperty('relation') ) {
					thisConditionApplies = this.validateConditions(subConditionsArray, fields)
				} else {
					for (const conditionField in subConditionsArray) {
						if ( subConditionsArray.hasOwnProperty(conditionField) ) {
							let conditionValue = subConditionsArray[conditionField];
							let conditionFields = fields.filter(field => field.id === conditionField);
							if (conditionFields.hasOwnProperty(0)){
								if (conditionFields[0].type==='checkbox') {
									let actualValue = +conditionFields[0].value;
									conditionValue = +conditionValue;
									thisConditionApplies = actualValue == conditionValue;
								} else {
									thisConditionApplies = conditionFields[0].value === conditionValue;
								}
							}
						}
					}
					if ( invert ){
						thisConditionApplies = !thisConditionApplies;
					}
				}
				if ( relation === 'AND' ) {
					conditionApplies = conditionApplies && thisConditionApplies;
				} else {
					conditionApplies = conditionApplies || thisConditionApplies;
				}
			}
		}
		return conditionApplies;
	}

    componentDidMount() {
		this.save = this.save.bind(this);
		this.saveChangedFields = this.saveChangedFields.bind(this);
		this.updateFieldsListWithConditions = this.updateFieldsListWithConditions.bind(this);
		this.updateFieldsListWithConditions();
		let fields = this.props.fields;
		let menu = this.props.menu;
		let progress = this.props.progress;
		//if count >1, it's a wizard
		let menuItems = [];
		let changedFields = [];
		menuItems = menu.menu_items;
		let selectedMenuItem = this.props.selectedMenuItem;
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
			progress: this.props.progress,
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
			progress,
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
			for ( let i = 0; i < length; i++ ) {
				if ( menuItems[i]['step']!=selectedStep ){
					menuItems.splice(i, 1);
				}
			}
		}

        return (
            <Fragment>
							<Menu isAPILoaded={isAPILoaded} menuItems={this.menuItems} menu={this.menu} selectMenu={this.props.selectMenu} selectedMenuItem={this.props.selectedMenuItem}/>
							<Settings highLightField={this.props.highLightField} highLightedField={this.props.highLightedField} isAPILoaded={isAPILoaded} fields={this.fields} progress={progress} saveChangedFields={this.saveChangedFields} menu={menu} save={this.save} selectedMenuItem={this.props.selectedMenuItem} selectedStep={selectedStep}/>
							<Notices className="rsssl-wizard-notices"/>
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
		return (
			<Fragment>
				{blocks.map((block, i) => <GridBlock key={i} block={block} isApiLoaded={this.props.isAPILoaded} fields={this.props.fields} highLightField={this.props.highLightField}/>)}
			</Fragment>
		);
	}
}

class Header extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			highContrast:false,
		};
	}
	handleClick(menuId){
		this.props.selectMainMenu(menuId);
	}
	componentDidMount() {
		this.handleClick = this.handleClick.bind(this);
		console.log(this.props.fields);
		for (const field of this.props.fields){
			console.log("field");
			console.log(field);
			if (field.id === 'high_contrast' ){
				this.highContrast = field.value;
			}
		}

		this.setState({
			highContrast: this.highContrast,
		});

	}
	render() {
		const {
			highContrast,
		} = this.state;
		let menu = rsssl_settings.menu;
		let plugin_url = rsssl_settings.plugin_url;
		return (
			<div className="rsssl-header-container">
				<div className="rsssl-header">
					<img className="rsssl-logo" src={plugin_url+"assets/img/really-simple-ssl-logo.png"} alt="Really Simple SSL logo" />
					<div className="rsssl-header-left">
						<nav className="rsssl-header-menu">
							<ul>
							{menu.map((menu_item, i) => <li><a key={i} onClick={ () => this.handleClick(menu_item.id) } href={"#" + menu_item.id.toString()} >{menu_item.label}</a></li>)}
							</ul>
						</nav>
					</div>
					<div className="rsssl-header-right">
							<a href="https://really-simple-ssl.com/knowledge-base"
								 className={!rsssl_settings.premium && 'button button-black'}
								 target="_blank">{__("Documentation", "really-simple-ssl")}</a>
							{rsssl_settings.premium &&
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
class Page extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			selectedMainMenuItem:'dashboard',
			selectedMenuItem:'general',
			highLightedField:'',
			fields:'',
			menu:'',
			progress:'',
			isAPILoaded: false,
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

	componentDidMount() {
		this.selectMenu = this.selectMenu.bind(this);
		this.highLightField = this.highLightField.bind(this);
		this.selectMainMenu = this.selectMainMenu.bind(this);
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
		} = this.state;

		return (
			<div className="rsssl-wrapper">
				<Header selectedMainMenuItem={selectedMainMenuItem} selectMainMenu={this.selectMainMenu} fields={fields}/>
				<div className={"rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem}>
					{selectedMainMenuItem==='settings' && <SettingsPage selectMenu={this.selectMenu} highLightField={this.highLightField} highLightedField={this.highLightedField} selectedMenuItem={selectedMenuItem} isAPILoaded={isAPILoaded} fields={fields} menu={menu} progress={progress}/> }
					{selectedMainMenuItem==='dashboard' && <DashboardPage isAPILoaded={isAPILoaded} fields={fields} highLightField={this.highLightField}/> }
				</div>
			</div>
		);
	}
}

/**
 * Initialize the whole thing
 */

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
