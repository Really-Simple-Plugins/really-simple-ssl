
import {Component} from "@wordpress/element";
import Icon from "../utils/Icon";
import { __ } from '@wordpress/i18n';
import { Tooltip } from '@wordpress/components';

class CheckboxControl extends Component {
    constructor() {
        super( ...arguments );
        this.onChangeHandler = this.onChangeHandler.bind(this);
    }

    onChangeHandler(e) {
        let fieldValue = e.target.value;
        console.log("handler in control")
        console.log(fieldValue);
        this.props.onChangeHandler(fieldValue)
    }

    render(){
        let field = this.props.field;
        return (
            <>
                <div className="components-base-control components-toggle-control">
                    <div className="components-base-control__field">
                        <div data-wp-component="HStack" className="components-flex components-h-stack">
                            <span className="components-form-toggle is-checked">
                                <input
                                checked= { field.value==1 }
                                onChange={ ( e ) => this.onChangeHandler(e) }
                                className="components-form-toggle__input" id={field.id} type="checkbox" />
                                <span className="components-form-toggle__track"></span>
                                <span className="components-form-toggle__thumb"></span>
                            </span>
                            <label for={field.id} className="components-toggle-control__label">{field.label}</label>
                             <Tooltip text="More TEST information"><div>i</div></Tooltip>
                        </div>
                    </div>
                </div>

            </>

        );
    }
}

export default CheckboxControl



