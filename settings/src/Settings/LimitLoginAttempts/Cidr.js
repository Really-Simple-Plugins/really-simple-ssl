import React, {useState, useEffect} from "react";
import IpAddressDataTableStore from "./IpAddressDataTableStore";

const Cidr = () => {

    const [lowestIP, setLowestIP] = useState("");
    const [highestIP, setHighestIP] = useState("");
    const {setIpAddress, validateIpRange, setIpRange} = IpAddressDataTableStore();

    //if the lowestIP or highestIP is changed, we validate the IP range
    useEffect(() => {
        if (lowestIP || highestIP) {
            validateIpRange(lowestIP, highestIP);
        }
    }, [lowestIP, highestIP]);

    return (
        <>
            {/* ip address input for ipv4 and ipv4 */}
            <div className="rsssl-ip-address-input">
                <div className="rsssl-ip-address-input__inner">
                    <div className="rsssl-ip-address-input__icon"></div>
                    <input
                        id="lowestIP"
                        type="text"
                        className="rsssl-ip-address-input__input"
                        placeholder="Enter IP range start"
                        value={lowestIP}
                        onChange={ (e) => setLowestIP(e.target.value)}
                    />
                </div>
                <div className="rsssl-ip-address-input__inner">
                    <div className="rsssl-ip-address-input__icon"></div>
                    <input
                        id="highestIP"
                        type="text"
                        className="rsssl-ip-address-input__input"
                        placeholder="Enter IP range end"
                        value={highestIP}
                        onChange={(e) => setHighestIP(e.target.value)}
                    />
                </div>
            </div>
        </>
    );
}

export default Cidr;