import React, {useEffect, useState} from 'react';
import {__} from "@wordpress/i18n";
import Icon from "../../utils/Icon";
import IpAddressDataTableStore   from "./IpAddressDataTableStore";




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
    const { maskError } = IpAddressDataTableStore();

    let is_checked = props.switchValue ? 'is-checked' : '';

    return (
        <>
            <label
                htmlFor={props.id}
                className="rsssl-label"
            >{props.label}</label>
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
                {/* if icon is active, show it */}
                {props.showSwitch && (
                    <p>
                        <div className="components-base-control components-toggle-control">
                            <div className="components-base-control__field">
                                <div data-wp-component="HStack" className="components-flex components-h-stack">
                                    <label className="components-toggle-control__label">{props.switchTitle}</label>
                                <span className={"components-form-toggle " + is_checked}>
                                    <input
                                        onKeyDown={props.switchAction}
                                        checked={props.switchValue}
                                        className="components-form-toggle__input"
                                        onChange={props.switchAction}
                                        type="checkbox"
                                    />
                                    <span className="components-form-toggle__track"></span>
                                    <span className="components-form-toggle__thumb"></span>
                                 </span>
                                </div>
                            </div>
                        </div>
                    </p>

                )}
            </div>
            {maskError && <span
                style={{color: 'red', marginLeft: '10px'}}>{__('Invalid ip address', 'really-simple-ssl')}</span>}
        </>
    )
}

export default IpAddressInput;