import api from '@wordpress/api';
import * as cmplz_api from './utils/api';
let changedFields = [];

function in_array(needle, haystack) {
	let length = haystack.length;
	for(let i = 0; i < length; i++) {
		if(haystack[i] == needle) return true;
	}
	return false;
}
import {
    Button,
    Icon,
    Panel,
    PanelBody,
    PanelRow,
    Placeholder,
    Spinner,
    TextControl,
    RadioControl,
	SelectControl,
__experimentalNumberControl as NumberControl,
    ToggleControl,
} from '@wordpress/components';

import {
    Fragment,
    render,
    Component,
} from '@wordpress/element';

import { __ } from '@wordpress/i18n';
/**
 * Javascript and CSS editor components
 */
import AceEditor from "react-ace";
import "ace-builds/src-noconflict/mode-javascript";
import "ace-builds/src-noconflict/mode-css";
import "ace-builds/src-noconflict/theme-tomorrow_night_bright";

class Help extends Component {
	render(){
		let field = this.props.field;
		if (field.help) {
			return (
				<div className="rsp-react__help_notice">
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

class Field extends Component {
	onChangeHandler(fieldValue) {
		let fields = this.props.fields;
		let field = this.props.field;
		if (!in_array(field.id, changedFields)) {
			changedFields.push(field.id);
		}
		fields[this.props.index]['value'] = fieldValue;
		this.setState( { fields } )
	}
	render(){
		let field = this.props.field;
		let fieldValue = field.value;
		let fields = this.props.fields;
		let options = [];
		if ( field.type==='radio' || field.type==='select' ) {
			for (var key in field.options) {
				if (field.options.hasOwnProperty(key)) {
					let item = new Object;
					item.label = field.options[key];
					item.value = key;
					options.push(item);
				}
			}
		}
		if ( field.type==='checkbox' ){
			return (
				<PanelBody>
					<ToggleControl
						checked= { field.value==1 }
						help={ field.comment }
						label={ field.label }
						onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
					/>
				</PanelBody>
			);
		}
		if ( field.type==='radio' ){
			return (
				<PanelBody>
					<RadioControl
						label={ field.label }
						onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
						selected={ fieldValue }
						options={ options }
					/>
				</PanelBody>			);
		}
		if ( field.type==='text' ){
			return (
				<PanelBody>
					<TextControl
						help={ field.comment }
						label={ field.label }
						onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
						value= { fieldValue }
					/>
				</PanelBody>
			);
		}
		if ( field.type==='number' ){
			return (
				<PanelBody>
					<NumberControl
						onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
						help={ field.comment }
						label={ field.label }
						value= { fieldValue }
					/>
				</PanelBody>
			);
		}
		if ( field.type==='email'){
			return (
				<PanelBody>
					<TextControl
						help={ field.comment }
						label={ field.label }
						onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
						value= { fieldValue }
					/>
				</PanelBody>
			);
		}
		if ( field.type==='select') {
			return (
			<PanelBody>
				<SelectControl
					// multiple
					help={ field.comment }
					label={ field.label }
					onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
					value= { fieldValue }
					options={ options }
				/>
			</PanelBody>
			)
		}
		if (field.type==='css' || field.type==='javascript') {
			return (
				<AceEditor
					placeholder={field.default}
					mode={field.type}
					theme="tomorrow_night_bright"
					name={field.name}
					onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
					fontSize={14}
					showPrintMargin={true}
					showGutter={true}
					highlightActiveLine={true}
					value={fieldValue}
					setOptions={{
						enableBasicAutocompletion: false,
						enableLiveAutocompletion: false,
						enableSnippets: false,
						showLineNumbers: true,
						tabSize: 2,
					}}/>
			)
		}
		return (
			'not found field type '+field.type
		);
	}
}

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
			<div className="rsp-react-menu">
				{menuItems.map((menuItem, i) => <MenuItem key={i} isAPILoaded={isAPILoaded} menuItem={menuItem} selectMenu={this.props.selectMenu} />)}
			</div>
		)
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
		if ( ! isAPILoaded ) {
			return (
				<Placeholder></Placeholder>
			);
		}

		let selectedFields = fields.filter(field => field.step === selectedMenuItem.id);

		return (
			<div className="rsp-react-settings">
				<div className="rsp-react-container">
					<div className="rsp-react__main">
						<div className="rsp-react__fields">
							<Panel>
								{selectedFields.map((field, i) => <Field key={i} index={i} field={field} fields={selectedFields}/>)}
								<PanelBody
									icon="admin-plugins"
								>
									<Button
										isPrimary
										onClick={ this.props.save }
									>
										{ __( 'Save', 'rsp-react' ) }
									</Button>
								</PanelBody>
							</Panel>
						</div>
					</div>
					<div className="rsp-react__help">
						{selectedFields.map((field, i) => <Help key={i} index={i} field={field} />)}
					</div>
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
			fields:'',
			menu:'',
			menuItems:'',
            isAPILoaded: false,
        };

		this.getFields().then(( response ) => {
			let fields = response.fields;
			let menu = response.menu;

			//if count >1, it's a wizard
			//@todo extend this to allow for wizard
			let menuItems = [];
			if ( menu.length==1 ) {
				menuItems = menu[0].sections;
			}
			let selectedMenuItem = menuItems[0];
			this.menu = menu;
			this.menuItems = menuItems;
			this.fields = fields;
			this.selectedMenuItem = selectedMenuItem;
			this.setState({
				isAPILoaded: true,
				fields: fields,
				menu: menu,
				menuItems:menuItems,
				selectedMenuItem: selectedMenuItem,
			});
		});
	}
	getFields(){
		return cmplz_api.getFields().then( ( response ) => {
			return response.data;
		});
	}

	selectMenu(selectedMenuItem){
		this.setState({
			selectedMenuItem :selectedMenuItem
		});
	}

	save(){
		const {
			fields,
		} = this.state;
		let saveFields = [];
		for (const field of fields){
			if (in_array(field.id, changedFields)){
				saveFields.push(field);
			}
		}
		cmplz_api.setFields(saveFields).then(( response ) => {
			dispatch('core/notices').createNotice(
				'success',
				__( 'Settings Saved', 'complianz-gdpr' ),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
			changedFields = [];
		});
	}

    componentDidMount() {
		this.save = this.save.bind(this);
		this.selectMenu = this.selectMenu.bind(this);
    }

    render() {
        const {
            fields,
			menu,
			menuItems,
			selectedMenuItem,
            isAPILoaded,
        } = this.state;

		if ( ! isAPILoaded ) {
			return (
				<Placeholder></Placeholder>
			);
		}

        return (
            <Fragment>
				<Menu isAPILoaded={isAPILoaded} menuItems={this.menuItems} menu={this.menu} selectMenu={this.selectMenu}/>
				<Settings isAPILoaded={isAPILoaded} fields={this.fields} save={this.save} selectedMenuItem={selectedMenuItem}/>
				<div className="rsp-react__notices">
					<Notices/>
				</div>
            </Fragment>
        )
    }
}

document.addEventListener( 'DOMContentLoaded', () => {
	const htmlOutput = document.getElementById( 'rsp-react-content' );
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
