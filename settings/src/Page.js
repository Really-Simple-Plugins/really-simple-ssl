import {useEffect, useState} from "@wordpress/element";
import Header from "./Header";
import DashboardPage from "./Dashboard/DashboardPage";
import Modal from "./Modal/Modal";
import PagePlaceholder from './Placeholder/PagePlaceholder';
import OnboardingModal from "./Onboarding/OnboardingModal";
import getAnchor from "./utils/getAnchor";
import useFields from "./Settings/FieldsData";
import useMenu from "./Menu/MenuData";

const Page = (props) => {
    const {error, fields, changedFields, fetchFieldsData, updateFieldsData, fieldsLoaded} = useFields();
    const {selectedMainMenuItem, fetchMenuData } = useMenu();

    const [Settings, setSettings] = useState(null);
    const [Notices, setNotices] = useState(null);
    const [Menu, setMenu] = useState(null);
    useEffect( () => {
        if (selectedMainMenuItem !== 'dashboard' ){
            import ("./Settings/Settings").then(({ default: Settings }) => {
                setSettings(() => Settings);
            });
            import("./Settings/Notices").then(({ default: Notices }) => {
                setNotices(() => Notices);
            });
            import ("./Menu/Menu").then(({ default: Menu }) => {
                setMenu(() => Menu);
            });
        }
    }, [selectedMainMenuItem]);

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
                            { selectedMainMenuItem !== 'dashboard' && Settings && Menu && Notices &&
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