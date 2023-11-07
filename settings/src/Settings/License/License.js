import TaskElement from "../../Dashboard/TaskElement";
import * as rsssl_api from "../../utils/api";
import { __ } from '@wordpress/i18n';
import useFields from "./../FieldsData";
import useLicense from "./LicenseData";
import {useState, useEffect} from "@wordpress/element";
const License = (props) => {
    const {fields, setChangedField, updateField} = useFields();
    const {licenseStatus, setLicenseStatus} = useLicense();
    const [noticesLoaded, setNoticesLoaded] = useState(false);
    const [fieldsUpdateComplete, setFieldsUpdateComplete] = useState(false);
    const [notices, setNotices] = useState(false);

    const getLicenseNotices = () => {
        return rsssl_api.runTest('licenseNotices', 'refresh').then( ( response ) => {
            return response;
        });
    }

    useEffect( () => {
        getLicenseNotices().then(( response ) => {
            setLicenseStatus(response.licenseStatus);
            setNotices(response.notices);
            setNoticesLoaded(true);
        });
    }, [fields] );

    const onChangeHandler = (fieldValue) => {
        setChangedField( field.id, fieldValue )
        updateField(field.id, fieldValue);
    }

    const toggleActivation = () => {
         setNoticesLoaded(false);
        if ( licenseStatus==='valid' ) {
            rsssl_api.runTest('deactivate_license').then( ( response ) => {
                setLicenseStatus(response.licenseStatus);
                setNotices(response.notices);
                setNoticesLoaded(true);
            });
        } else {
            let data = {};
            data.license = props.field.value;
            rsssl_api.doAction('activate_license', data).then( ( response ) => {
                setLicenseStatus(response.licenseStatus);
                setNotices(response.notices);
                setNoticesLoaded(true);
            });
        }
    }

    let field = props.field;
    /**
     * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
     */
    return (
            <div className="components-base-control">
             <div className="components-base-control__field">
                 <label
                     className="components-base-control__label"
                     htmlFor={field.id}>{field.label}</label>
                  <div className="rsssl-license-field">
                     <input className="components-text-control__input"
                            type="password"
                            id={field.id}
                            value={field.value}
                            onChange={ ( e ) => onChangeHandler(e.target.value) }
                     />
                     <button className="button button-default" onClick={ () => toggleActivation() }>
                     {licenseStatus==='valid' && <>{__("Deactivate","really-simple-ssl")}</>}
                     {licenseStatus!=='valid' && <>{__("Activate","really-simple-ssl")}</>}
                     </button>
                 </div>
             </div>
                {noticesLoaded && notices.map((notice, i) => <TaskElement key={'task-'+i} index={i} notice={notice} highLightField=""/>)}
            </div>
    );
}

export default License;