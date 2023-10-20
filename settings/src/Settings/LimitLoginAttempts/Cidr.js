import React, { useState, useEffect } from "react";
import IpAddressDataTableStore from "./IpAddressDataTableStore";
import IpAddressInput from "./IpAddressInput";

const Cidr = () => {
    const [lowestIP, setLowestIP] = useState("");
    const [highestIP, setHighestIP] = useState("");
    const [validated, setValidated] = useState(false);
    const { setIpAddress, validateIpRange, setIpRange } = IpAddressDataTableStore();


    const cleanupIpAddress = (ipAddress) => {
        return ipAddress.replace(/,/g, '.');
    }

    const handleLowestIPChange = (ip) => {
        setLowestIP(cleanupIpAddress(ip));
    }

    const handleHighestIPChange = (ip) => {
        setHighestIP(cleanupIpAddress(ip));
    }

    return (
        <>
            <div className="rsssl-ip-address-input">
                <div className="rsssl-ip-address-input__inner">
                    <div className="rsssl-ip-address-input__icon"></div>
                    <IpAddressInput
                        id="lowestIP"
                        type="text"
                        className="rsssl-ip-address-input__input"
                        value={lowestIP}
                        onChange={ (e) => handleLowestIPChange(e.target.value)}
                    />
                </div>
                <div className="rsssl-ip-address-input__inner">
                    <div className="rsssl-ip-address-input__icon"></div>
                    <IpAddressInput
                        id="highestIP"
                        type="text"
                        className="rsssl-ip-address-input__input"
                        value={highestIP}
                        onChange={(e) => handleHighestIPChange(e.target.value)}
                    />
                </div>
                <div className={'rsssl-container'}>
                    <div className={'rsssl-container__inner'}>
                        <button
                            className={'button button--primary'}
                            onClick={() => {
                                validateIpRange(lowestIP, highestIP);
                            }}
                        >
                            Validate
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}

export default Cidr;
