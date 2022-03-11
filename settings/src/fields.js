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
import in_array from './utils/lib';

import {
    Component,
} from '@wordpress/element';

class Field extends Component {
    onChangeHandler(fieldValue) {
        let fields = this.props.fields;
        let field = this.props.field;
        this.props.saveChangedFields( field.id )
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
        return (
            'not found field type '+field.type
        );
    }
}

export default Field;