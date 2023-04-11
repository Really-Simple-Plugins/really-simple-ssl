import {useEffect} from "@wordpress/element";
import Header from "./Header";
import DashboardPage from "./Dashboard/DashboardPage";
import Menu from "./Menu/Menu";
import Settings from "./Settings/Settings";
import Notices from "./Settings/Notices";
import Modal from "./Modal/Modal";
import PagePlaceholder from './Placeholder/PagePlaceholder';
import OnboardingModal from "./Onboarding/OnboardingModal";
import getAnchor from "./utils/getAnchor";
import useFields from "./Settings/FieldsData";
import useMenu from "./Menu/MenuData";

const Page = (props) => {
    const {error, fields, changedFields, fetchFieldsData, updateFieldsData, fieldsLoaded} = useFields();
    const {selectedMainMenuItem, fetchMenuData } = useMenu();


    useEffect( () => {
        if ( fieldsLoaded ) {
            fetchMenuData(fields);
            window.addEventListener('hashchange', (e) => {
                fetchMenuData(fields);
            });
        }
    }, [fields] );

    useEffect( () => {
        let subMenuItem = getAnchor('menu');
        const run = async () => {
            await updateFieldsData(subMenuItem);
        }
        run();
    }, [changedFields] );

    useEffect( () => {
        let subMenuItem = getAnchor('menu');
        const run = async () => {
            await fetchFieldsData(subMenuItem);
        }
        run();
    }, [] );


    if (error) {
        return (
            <>
                <PagePlaceholder error={error}></PagePlaceholder>
            </>
        )
    }
    return (
        <div className="rsssl-wrapper">
            <OnboardingModal />
            {!fieldsLoaded && <PagePlaceholder></PagePlaceholder>}
            <Modal/>
            {fieldsLoaded &&
                (
                    <>
                        <Header />
                        <div className={"rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem}>
                            { selectedMainMenuItem !== 'dashboard' &&
                               <>
                                   <Menu />
                                   <Settings/>
                                   <Notices className="rsssl-wizard-notices"/>
                               </>

                            }
                            { selectedMainMenuItem === 'dashboard' &&
                                <DashboardPage />
                            }
                        </div>
                    </>
                )
            }
        </div>
    );

}
export default Page