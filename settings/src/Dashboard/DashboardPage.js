import GridBlock from "./GridBlock";
import ProgressHeader from "./Progress/ProgressBlockHeader";
import ProgressBlock from "./Progress/ProgressBlock";
import ProgressFooter from "./Progress/ProgressFooter";
import SslLabsHeader from "./SslLabs/SslLabsHeader";
import SslLabs from "./SslLabs/SslLabs";
import SslLabsFooter from "./SslLabs/SslLabsFooter";
import VulnerabilitiesHeader from "./Vulnerabilities/VulnerabilitiesHeader";
import Vulnerabilities from "./Vulnerabilities/Vulnerabilities";
import VulnerabilitiesFooter from "./Vulnerabilities/VulnerabilitiesFooter";
import TipsTricks from "./TipsTricks/TipsTricks";
import TipsTricksFooter from "./TipsTricks/TipsTricksFooter";
import OtherPluginsHeader from "./OtherPlugins/OtherPluginsHeader";
import OtherPlugins from "./OtherPlugins/OtherPlugins";
import { __ } from '@wordpress/i18n';
import DashboardPlaceholder from "../Placeholder/DashboardPlaceholder";
import useFields from "../Settings/FieldsData";
import ErrorBoundary from "../utils/ErrorBoundary";

const DashboardPage = () => {
    const {fieldsLoaded} = useFields();

    const blocks = [
        {
            id: 'progress',
            header: ProgressHeader,
            content: ProgressBlock,
            footer: ProgressFooter,
            class: ' rsssl-column-2',
        },
        {
            id: 'ssllabs',
            header: SslLabsHeader,
            content: SslLabs,
            footer: SslLabsFooter,
            class: 'border-to-border',
        },
        {
            id: 'wpvul',
            header: VulnerabilitiesHeader,
            content: Vulnerabilities,
            footer: VulnerabilitiesFooter,
            class: 'border-to-border',
        },
        {
            id: 'tips_tricks',
            title: __("Tips & Tricks", 'really-simple-ssl'),
            content: TipsTricks,
            footer: TipsTricksFooter,
            class: ' rsssl-column-2',
        },
        {
            id: 'other-plugins',
            header: OtherPluginsHeader,
            content: OtherPlugins,
            class: ' rsssl-column-2 no-border no-background',
        },
    ]
    return (
        <>
            {!fieldsLoaded && <DashboardPlaceholder></DashboardPlaceholder>}
            {fieldsLoaded && blocks.map((block, i) => <ErrorBoundary key={"grid_"+i} fallback={"Could not load dashboard block"}><GridBlock  block={block}/></ErrorBoundary>)}
        </>
    );

}
export default DashboardPage