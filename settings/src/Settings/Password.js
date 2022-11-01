import {
    Component,
} from '@wordpress/element';

class Password extends Component {
    constructor() {
        super( ...arguments );
    }

    onChangeHandler(fieldValue) {
        let fields = this.props.fields;
        let field = this.props.field;
        fields[this.props.index]['value'] = fieldValue;
        this.props.saveChangedFields( field.id )
        this.setState({
            fields: fields,
        })
    }

    render(){
        let field = this.props.field;
        let fieldValue = field.value;
        let fields = this.props.fields;

        /**
         * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
         */
        return (
            <div className="components-base-control">
             <div className="components-base-control__field">
                 <label
                     className="components-base-control__label"
                     htmlFor={field.id}>{field.label}</label>
                 <input className="components-text-control__input"
                        type="password"
                        id={field.id}
                        value={fieldValue}
                        onChange={ ( e ) => this.onChangeHandler(e.target.value) }
                 />
             </div>
            </div>
        );

    }
}

export default Password;