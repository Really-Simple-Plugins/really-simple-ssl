import { __ } from '@wordpress/i18n';
import Hyperlink from "../utils/Hyperlink";
import * as rsssl_api from "../utils/api";
import useFields from "./FieldsData";
import Icon from "../utils/Icon";
import {useState} from "@wordpress/element";

/**
 * Render a help notice in the sidebar
 */
const Button = (props) => {
    const {addHelpNotice} = useFields();
    const [processing, setProcessing] = useState(false);
    const onClickHandler = (action) => {
        let data = {};
        console.log("send mail");
        setProcessing(true);
        rsssl_api.doAction(action, data).then( ( response ) => {
            let label = response.success ? 'success' : 'warning';
            let title = __( "Test notification by email", 'really-simple-ssl' );
            let text = response.message;
            addHelpNotice(props.field.id, label, text, title, false);
            setProcessing(false);
        });
    }

    let is_disabled = !!props.field.disabled;

    return (
        <>
            { props.field.url &&
                <Hyperlink className={"button button-default"} disabled={is_disabled} text={props.field.button_text} url={props.field.url}/>
            }
            { props.field.action &&
                <button onClick={ () => onClickHandler( props.field.action ) }  className="button button-default" disabled={is_disabled}>
                    {props.field.button_text}
                    {processing && <Icon name = "loading" color = 'grey' />}
                </button>
            }
        </>
    );
}

export default Button