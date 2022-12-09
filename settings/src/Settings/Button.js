import {Component} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import Hyperlink from "../utils/Hyperlink";
import * as rsssl_api from "../utils/api";

/**
 * Render a help notice in the sidebar
 */
class Button extends Component {
    constructor() {
        super( ...arguments );
        this.onClickHandler = this.onClickHandler.bind(this);
        this.state = {
            success:true,
            message: '',
        }
    }
    onClickHandler(action) {
                this.setState({
                    success: true,
                    message: '',
                });
        console.log(action);
        let data = {};
        rsssl_api.doAction(action, data).then( ( response ) => {
            console.log(action);

            this.setState({
                success: response.data.success,
                message: response.data.message,
            });
        });
    }
    render(){
        const {
            success,
            message,
        } = this.state;
        let messageClass = success ? 'rsssl-success' : 'rsssl-error';
        let field = this.props.field;
        return (
            <>
                <label>{field.label}</label>
                { this.props.field.url &&
                    <Hyperlink className="button button-default" text={field.button_text} url={field.url}/>
                }
                { field.action && message && <div className={"rsssl-message "+messageClass}>
                    {message}
                </div>}
                { field.action &&
                    <button onClick={ () => this.onClickHandler( field.action ) }  className="button button-default">{field.button_text}</button>
                }

            </>
        );
    }
}

export default Button



