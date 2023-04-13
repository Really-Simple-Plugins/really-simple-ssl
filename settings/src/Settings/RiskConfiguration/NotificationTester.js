import * as rsssl_api from "../../utils/api";
import {Button} from "@wordpress/components";
import Icon from "../../utils/Icon";
import useFields from "../FieldsData";



const NotificationTester = (props) => {

    const {field, disabled} = props;
    const {addHelpNotice} = useFields();

    function doTestNotification() {
        //Test one the email notification
        rsssl_api.doAction( 'vulnerabilities_test_notification' ).then
        ( ( response ) => {

        });
        addHelpNotice(
            field.id,
            'success',
            __('All notifications are triggered successfully, please check your email to double-check if you can receive emails.','really-simple-ssl'),
            __('Test notifications','really-simple-ssl'),
            false
        );
    }

    function labelWrap(field) {
        let tooltipColor = field.warning ? 'red': 'black';
        return (
            <>
                <div className="cmplz-label-text">{field.label}</div>
                {field.tooltip && <Icon name = "info-open" tooltip={field.tooltip} color = {tooltipColor} />}
            </>
        )
    }

    return (
        <>
            {/*{labelWrap(field)}*/}
            <Button
                isDefault
                required={ field.required }
                placeholder={ field.placeholder }
                disabled={ disabled }
                help={ field.comment }
                text={ field.button_text }
                onClick={ () => doTestNotification() }
            />
        </>
    )
}

export default NotificationTester