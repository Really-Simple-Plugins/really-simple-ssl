import React, { useState } from 'react';

function CidrCalculator() {
    const [oct0, setOct0] = useState(0);
    const [oct1, setOct1] = useState(0);
    const [oct2, setOct2] = useState(0);
    const [oct3, setOct3] = useState(0);
    const [prefix, setPrefix] = useState(24);
    const [startRange, setStartRange] = useState("");
    const [endRange, setEndRange] = useState("");

    const updateFromRange = () => {
        const [startIp, prefixValue] = startRange.split('/');
        const [o0, o1, o2, o3] = startIp.split('.');

        if (prefixValue) {
            setOct0(oct0);
            setOct1(oct1);
            setOct2(oct2);
            setOct3(oct3);
            setPrefix(Number(prefixValue)); // Make sure prefixValue is a number
        } else {
            console.error("Invalid CIDR format");
        }
    };

    const calculateClass = () => {
        if (prefix >= 8 && prefix <= 15) return "A";
        else if (prefix >= 16 && prefix <= 23) return "B";
        else if (prefix >= 24 && prefix <= 30) return "C";
        else return "";
    };

    // The rest of your functions (e.g., calculateClass, IPBinary, etc.) remain the same

    return (
        <div className="row center">
            <div className="col s12 m12 l12">
                <div className="card">
                    <div className="card-content">
                        <span className="card-title">IP Subnet Calculator</span>
                        <div className="divider"></div><br/>
                        <input placeholder="Start IP Range" value={startRange} onChange={e => setStartRange(e.target.value)} style={{width:'150px'}}/>
                        <input placeholder="End IP Range" value={endRange} onChange={e => setEndRange(e.target.value)} style={{width:'150px'}}/>
                        <button onClick={updateFromRange}>Update from Range</button>
                        {/* The rest of your inputs and display values remain the same */}
                        <div className="divider"></div><br/>
                        <input value={oct0} onChange={e => setOct0(e.target.value)} type="number" min="0" max="255" style={{width:'50px'}}/>
                        <input value={oct1} onChange={e => setOct1(e.target.value)} type="number" min="0" max="255" style={{width:'50px'}}/>
                        <input value={oct2} onChange={e => setOct2(e.target.value)} type="number" min="0" max="255" style={{width:'50px'}}/>
                        <input value={oct3} onChange={e => setOct3(e.target.value)} type="number" min="0" max="255" style={{width:'50px'}}/>
                         / <input value={prefix} onChange={e => setPrefix(e.target.value)} type="number" min="0" max="32" style={{width:'50px'}}/>
                        <br/>
                        Class: {calculateClass()}<br/>
                        {/* Similar calls for other calculated values */}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default CidrCalculator;
