import { __ } from '@wordpress/i18n';
import {addUrlRef} from "../../utils/AddUrlRef";

const TipsTricksFooter = () => {


    return (
        <>
            <a href={addUrlRef("https://really-simple-ssl.com/knowledge-base-overview/")} target="_blank"rel="noopener noreferrer" className="button button-secondary">{ __("Documentation", "really-simple-ssl")}</a>
        </>

);

}
export default TipsTricksFooter