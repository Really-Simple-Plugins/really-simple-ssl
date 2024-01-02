import React from "react";
const DatatablePlaceholder = (props) => {
    let lines = props.lines;
    if ( !lines ) lines = 3;
    return (
        <div className="rsssl-datatable-placeholder">
            {Array.from({length: lines}).map((item, i) => (<div key={'datatable-placeholder-'+i} ></div>))}
        </div>
    );

}

export default DatatablePlaceholder;