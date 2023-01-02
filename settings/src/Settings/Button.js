import { __ } from '@wordpress/i18n';
import Hyperlink from "../utils/Hyperlink";
import * as rsssl_api from "../utils/api";

/**
 * Render a help notice in the sidebar
 */
const Button = (props) => {
    const onClickHandler = (action) => {
        let data = {};
        rsssl_api.doAction(action, data).then( ( response ) => {
            let help = {}
            help.label = response.data.success ? 'success' : 'warning';
            help.title = __( "Mail sending test", 'really-simple-ssl' );
            help.text = response.data.message;
            props.addNotice(props.field.id, help);
        });
    }
    return (
        <>
            <label>{props.field.label}</label>
            { props.field.url &&
                <Hyperlink className="button button-default" text={props.field.button_text} url={props.field.url}/>
            }
            { props.field.action &&
                <button onClick={ () => onClickHandler( props.field.action ) }  className="button button-default">{props.field.button_text}</button>
            }

        </>
    );
}

export default Button



