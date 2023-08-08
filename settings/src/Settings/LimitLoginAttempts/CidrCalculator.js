import React, { useState } from "react";

const CidrCalculator = () => {
    const [lowestIP, setLowestIP] = useState("");
    const [highestIP, setHighestIP] = useState("");
    const [cidrNotation, setCidrNotation] = useState("");

    const ipToNumber = (ip) =>
        ip.split(".").reduce((acc, cur) => (acc << 8) + parseInt(cur, 10), 0);

    const cidrFromIPRange = () => {
        //first we check if the IP's are valid
        if (!lowestIP || !highestIP) {
            alert("Please enter a valid IP address");
            return;
        }

        if (ipToNumber(lowestIP) > ipToNumber(highestIP)) {
            alert("Lowest IP address should be lower than highest IP address");
            return;
        }


        const lowIPNumber = ipToNumber(lowestIP);
        const highIPNumber = ipToNumber(highestIP);

        // Find the prefix length (subnet mask) by counting common bits
        let prefixLength = 32;
        while ((lowIPNumber & (1 << (32 - prefixLength))) === (highIPNumber & (1 << (32 - prefixLength)))) {
            prefixLength -= 1;
        }

        const cidr = `${lowestIP}/${prefixLength}`;
        setCidrNotation(cidr);
    };

    return (
        <div>
            <div>
                <label>Lowest IP Address:</label>
                <input
                    type="text"
                    value={lowestIP}
                    onChange={(e) => setLowestIP(e.target.value)}
                />
            </div>
            <div>
                <label>Highest IP Address:</label>
                <input
                    type="text"
                    value={highestIP}
                    onChange={(e) => setHighestIP(e.target.value)}
                />
            </div>
            <button onClick={cidrFromIPRange}>Calculate CIDR</button>
            {cidrNotation && <div>CIDR Notation: {cidrNotation}</div>}
        </div>
    );
};

export default CidrCalculator;
