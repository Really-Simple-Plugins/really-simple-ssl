import {__} from '@wordpress/i18n';
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "../FieldsData";

const IpAddressModule = (props) => {
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    const { selectedFilter } = props;
    return (
        <div className="rsssl-ip-address-module">
            selecte name = {selectedFilter}
        </div>
    );
}

export default IpAddressModule;