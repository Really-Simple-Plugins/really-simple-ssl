import {Component} from "@wordpress/element";
import Icon from "../utils/Icon";
import { __ } from '@wordpress/i18n';
import { Tooltip } from '@wordpress/components';
/*
* The tooltip can't be included in the native toggleControl, so we have to build our own.
*/

class CheckboxControl extends Component {
    constructor() {
        super( ...arguments );
        this.onChangeHandler = this.onChangeHandler.bind(this);
    }

    onChangeHandler(e) {
        let fieldValue = !this.props.field.value;
        this.props.onChangeHandler(fieldValue)
    }
    render(){
        let field = this.props.field;
        let is_checked = field.value ? 'is-checked' : '';
        let tooltipColor = field.warning ? 'red': 'black';
        return (
            <>
                <div className="components-base-control components-toggle-control">
                    <div className="components-base-control__field">
                        <div data-wp-component="HStack" className="components-flex components-h-stack">
                            <span className={ "components-form-toggle "+is_checked}>
                                <input
                                checked={field.value}
                                className="components-form-toggle__input"
                                onChange={ ( e ) => this.onChangeHandler(e) }
                                id={field.id}
                                type="checkbox"
                            />
                            <span className="components-form-toggle__track"></span>
                            <span className="components-form-toggle__thumb"></span>
                            </span>
                            {field.tooltip &&
                                <div className="rsssl-tooltip">
                                    <Tooltip text={field.tooltip}>
                                        <div><Icon name = "info" color = {tooltipColor} /></div>
                                    </Tooltip>
                                    <div></div>
                                </div>
                            }
                            <label for={field.id} className="components-toggle-control__label">{field.label}</label>

                        </div>
                    </div>
                </div>

            </>

        );
    }
}

export default CheckboxControl



