import {__} from "@wordpress/i18n";
import Hyperlink from "./Hyperlink";

const Error = (props) => {
    if (props.error) {
        console.log("errors detected during the loading of the settings page");
        console.log(props.error);
    }
    let description = false;
    let url = 'https://really-simple-ssl.com/instructions/how-to-debug-a-blank-settings-page-in-really-simple-ssl/';

    let generic_rest_blocked_message = __("Please check if security settings on the server or a plugin is blocking the requests from Really Simple SSL.", "really-simple-ssl");
    let message = false;
    if (props.error) {
        message = props.error.message;
        if ( typeof message !== 'string'  ) {
            message = JSON.stringify(message);
        }
        if ( props.error.code==='rest_no_route') {
            description = __("The Really Simple SSL Rest API is disabled.", "really-simple-ssl")+" "+generic_rest_blocked_message
        } else if ( props.error.data.status === '404') {
            description = __("The Really Simple SSL Rest API returned a not found.", "really-simple-ssl")+" "+generic_rest_blocked_message;
        } else if ( props.error.data.status === '403') {
            description = __("The Really Simple SSL Rest API returned a 403 forbidden error.", "really-simple-ssl")+" "+generic_rest_blocked_message;
        }
        if (message.length>100){
            message = message.substring(0, 100)+ '...';
        }

    }

    return (
        <>
            {props.error && <div className="rsssl-rest-error-message">
                <h3>{__("A problem was detected during the loading of the settings", "really-simple-ssl")}</h3>
                {description &&
                    <p>{description}</p>
                }

                <div>
                    <p>{__("The request returned the following errors:", "really-simple-ssl")}</p>
                    <ul>
                        {props.error.code && <li>{__("Response code:", "really-simple-ssl")}&nbsp;{props.error.code}</li>}
                        {props.error.data.status && <li>{__("Status code:", "really-simple-ssl")}&nbsp;{props.error.data.status}</li>}
                        {message && <li>{__("Server response:", "really-simple-ssl")}&nbsp;{message}</li>}
                    </ul>
                </div>
                <Hyperlink className="button button-default" target="_blank" rel="noopener noreferrer" text={__("More information","really-simple-ssl")} url={url}/>

            </div>}
        </>
    )
}
export default Error