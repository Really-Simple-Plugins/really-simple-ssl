import {useState} from '@wordpress/element';
import {__} from "@wordpress/i18n";
import Icon from "../../utils/Icon";
import IpAddressDataTableStore from "./IpAddressDataTableStore";


/**
 * Visual aid for adding an IP address to the list of blocked IP addresses
 *
 * @param props
 * @returns {*}
 * @constructor
 */
const IpAddressInput = (props) => {

    const [value, setValue] = useState("");
    const [error, setError] = useState(false);
    const {maskError, setMaskError} = IpAddressDataTableStore();

    return (
        <>
        {props.label &&
            <label
                htmlFor={props.id}
                className="rsssl-label"
            >{props.label}</label>
        }
            <br></br>
            <div className="input-container">
                <input
                    type="text"
                    id={props.id}
                    name={props.name}
                    value={props.value}
                    className={`rsssl-input full ${maskError ? 'rsssl-error' : 'rsssl-success'}`}
                    onChange={props.onChange}
                />
            </div>
            {maskError && <span
                style={{color: 'red', marginLeft: '10px'}}>{__('Invalid ip address', 'really-simple-ssl')}</span>}
        </>
    )
}

export default IpAddressInput;