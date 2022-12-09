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
    }
    onClickHandler(action) {
        let data = {};
        rsssl_api.doAction(action, data).then( ( response ) => {
            let help = {}
            help.label = response.data.success ? 'success' : 'warning';
            help.title = __( "Mail sending test", 'really-simple-ssl' );
            help.text = response.data.message;
            this.props.addNotice(this.props.field.id, help);

        });
    }
    render(){
        let field = this.props.field;
        return (
            <>
                <label>{field.label}</label>
                { this.props.field.url &&
                    <Hyperlink className="button button-default" text={field.button_text} url={field.url}/>
                }
                { field.action &&
                    <button onClick={ () => this.onClickHandler( field.action ) }  className="button button-default">{field.button_text}</button>
                }

            </>
        );
    }
}

export default Button



