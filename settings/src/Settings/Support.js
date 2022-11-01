import {
    Button,
    TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import {
    Component,
} from '@wordpress/element';
import Placeholder from '../Placeholder/Placeholder';
import * as rsssl_api from "../utils/api";

class Support extends Component {
    constructor() {
        super( ...arguments );
                this.state = {
                    message :'',
                    sending :false,
                };
    }
    componentDidMount() {
        this.onChangeHandler = this.onChangeHandler.bind(this);
        this.onClickHandler = this.onClickHandler.bind(this);
    }

    onChangeHandler(message) {
        this.setState({
            message :message,
        });
    }

    onClickHandler(event) {
            this.setState({
                sending :true,
            });
        return rsssl_api.runTest('supportData', 'refresh').then( ( response ) => {
            const {
                message,
            } = this.state;
            let encodedMessage = message.replace(/(?:\r\n|\r|\n)/g, '--br--');
            let url = 'https://really-simple-ssl.com/support'
            +'?customername=' + encodeURIComponent(response.data.customer_name)
            + '&email=' + response.data.email
            + '&domain=' + response.data.domain
            + '&scanresults=' + encodeURIComponent(response.data.scan_results)
            + '&licensekey=' + encodeURIComponent(response.data.license_key)
            + '&supportrequest=' + encodeURIComponent(encodedMessage)
            + '&htaccesscontents=' + response.data.htaccess_contents
            + '&debuglog=' + response.data.system_status;
            window.location.assign(url);
        });
    }

    render(){
        const {
            message,
            sending,
        } = this.state;
        let disabled = sending || message.length==0;
        let textAreaDisabled = sending;
        return (
            <>
                <TextareaControl
                        disabled={textAreaDisabled}
                        placeholder={__("Type your question here","really-simple-ssl")}
                        onChange={ ( message ) => this.onChangeHandler(message) }
                />
                <Button
                    disabled={disabled}
                    variant="secondary"
                    onClick={ ( e ) => this.onClickHandler(e) }>
                    { __( 'Send', 'really-simple-ssl' ) }
                </Button>
            </>
        );

    }
}

export default Support;