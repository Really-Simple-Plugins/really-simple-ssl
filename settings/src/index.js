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

		this.getFields().then(( response ) => {
			let fields = response.fields;
			let menu = response.menu;
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
				fields: fields,
				menu: menu,
				menuItems:menuItems,
				selectedMenuItem: selectedMenuItem,
				selectedStep: selectedStep,
				changedFields: changedFields,
			});
		});
	}
	getFields(){
		return rsssl_api.getFields().then( ( response ) => {
			return response.data;
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
		if (menu.is_wizard){
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

document.addEventListener( 'DOMContentLoaded', () => {
	const htmlOutput = document.getElementById( 'rsssl-wizard-content' );
	if ( htmlOutput ) {
		render(
			<SettingsPage/>,
			htmlOutput
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
