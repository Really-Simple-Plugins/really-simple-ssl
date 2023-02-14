
/*
    * This file is part of Really Simple SSL.
    * Really Simple SSL
    *
    * @package Really Simple SSL
    * @author Marcel Santing
    *
    * @link https://really-simple-ssl.com/
    * This function is a test button to test if the notifications is working.
 */
import {Button} from "@wordpress/components";


const VulnerableMeasuresTest = (props) => {
    //when run it tests the notification
    const testNotification = () => {

    }

    return (
        <div className="textright">
            <label>{props.field.label}</label>
            <Button isSecondary onClick={ (e) => testNotification(e) }>{props.field.button_text}</Button>
        </div>

    )
}

export default VulnerableMeasuresTest