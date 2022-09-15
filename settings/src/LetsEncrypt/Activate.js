import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';
import Icon from "../utils/Icon";
import Onboarding from "../Onboarding/Onboarding";

import {
    Button,
} from '@wordpress/components';
const Activate = (props) => {
    return (
        <div className="rsssl-lets-encrypt-tests">
            <Onboarding selectMainMenu={props.selectMainMenu}/>
         </div>
    )
}

export default Activate;