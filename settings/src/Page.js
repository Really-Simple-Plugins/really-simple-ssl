import {useEffect, useState} from "@wordpress/element";
import Header from "./Header";
import PagePlaceholder from './Placeholder/PagePlaceholder';
import getAnchor from "./utils/getAnchor";
import useFields from "./Settings/FieldsData";
import useMenu from "./Menu/MenuData";
import useOnboardingData from "./Onboarding/OnboardingData";
import useModal from "./Modal/ModalData";

const Page = () => {
    const {error, fields, changedFields, fetchFieldsData, updateFieldsData, fieldsLoaded} = useFields();
    const {showOnboardingModal, fetchOnboardingModalStatus, modalStatusLoaded,} = useOnboardingData();
    const {selectedMainMenuItem, fetchMenuData } = useMenu();
    const {showModal} = useModal();

    const [Settings, setSettings] = useState(null);
    const [DashboardPage, setDashboardPage] = useState(null);
    const [Notices, setNotices] = useState(null);
    const [Menu, setMenu] = useState(null);

    useEffect(() => {
        if ( !modalStatusLoaded ) {
            fetchOnboardingModalStatus();
        }
    }, []);

    useEffect( () => {
        if (selectedMainMenuItem !== 'dashboard' ){
            if (!Settings) {
                import ("./Settings/Settings").then(({default: Settings}) => {
                    setSettings(() => Settings);
                });
            }
            if (!Notices) {
                import("./Settings/Notices").then(({default: Notices}) => {
                    setNotices(() => Notices);
                });
            }
            if (!Menu) {
                import ("./Menu/Menu").then(({default: Menu}) => {
                    setMenu(() => Menu);
                });
            }
        }
        if (selectedMainMenuItem === 'dashboard' && !DashboardPage ){
            import ( "./Dashboard/DashboardPage").then(({ default: DashboardPage }) => {
                setDashboardPage(() => DashboardPage);
            });
        }

    }, [selectedMainMenuItem]);

    const [OnboardingModal, setOnboardingModal] = useState(null);
    useEffect( () => {
        if ( showOnboardingModal && !OnboardingModal ){
            import ("./Onboarding/OnboardingModal").then(({ default: OnboardingModal }) => {
                setOnboardingModal(() => OnboardingModal);
            });
        }

    }, [showOnboardingModal]);

    const [Modal, setModal] = useState(null);
    useEffect( () => {
        if ( showModal && !Modal ){
            import ( "./Modal/Modal").then(({ default: Modal }) => {
                setModal(() => Modal);
            });
        }

    }, [showModal]);

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
        updateFieldsData(subMenuItem);
    }, [changedFields] );

    useEffect( () => {
        let subMenuItem = getAnchor('menu');
        fetchFieldsData(subMenuItem);
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
            {OnboardingModal && <OnboardingModal />}

            {Modal && <Modal/>}
            {

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
                            { selectedMainMenuItem === 'dashboard' && DashboardPage &&
                                <DashboardPage />
                            }
                        </div>
                    </>

            }
        </div>
    );

}
export default Page