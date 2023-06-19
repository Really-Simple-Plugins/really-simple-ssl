import { __ } from '@wordpress/i18n';
import Hyperlink from "../utils/Hyperlink";
import * as rsssl_api from "../utils/api";
import useFields from "./FieldsData";

/**
 * Render a help notice in the sidebar
 */
const Button = (props) => {
    const {addHelpNotice} = useFields();

    const onClickHandler = (action) => {
        let data = {};
        rsssl_api.doAction(action, data).then( ( response ) => {
            let label = response.success ? 'success' : 'warning';
            let title = __( "Test notification by email", 'really-simple-ssl' );
            let text = response.message;
            addHelpNotice(props.field.id, label, text, title, false);
        });
    }
    return (
        <>
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
