import {useEffect, useState} from "@wordpress/element";
import Header from "./Header";
import PagePlaceholder from './Placeholder/PagePlaceholder';
import getAnchor from "./utils/getAnchor";
import useFields from "./Settings/FieldsData";
import useMenu from "./Menu/MenuData";
import useOnboardingData from "./Onboarding/OnboardingData";
import useModal from "./Modal/ModalData";
import {setLocaleData} from "@wordpress/i18n";
import ErrorBoundary from "./utils/ErrorBoundary";
const Page = () => {
    const {error, fields, changedFields, fetchFieldsData, updateFieldsData, fieldsLoaded} = useFields();
    const {showOnboardingModal, fetchOnboardingModalStatus, modalStatusLoaded,} = useOnboardingData();
    const {selectedMainMenuItem, fetchMenuData } = useMenu();
    const {showModal} = useModal();

    const [Settings, setSettings] = useState(null);
    const [DashboardPage, setDashboardPage] = useState(null);
    const [Notices, setNotices] = useState(null);
    const [Menu, setMenu] = useState(null);
    const [ToastContainer, setToastContainer] = useState(null);

    useEffect(() => {
        if ( !modalStatusLoaded ) {
            fetchOnboardingModalStatus();
        }
    }, []);

    //load the chunk translations passed to us from the rsssl_settings object
    //only works in build mode, not in dev mode.
    useEffect(() => {
        rsssl_settings.json_translations.forEach( (translationsString) => {
            let translations = JSON.parse(translationsString);
            let localeData = translations.locale_data[ 'really-simple-ssl' ] || translations.locale_data.messages;
            localeData[""].domain = 'really-simple-ssl';
            setLocaleData( localeData, 'really-simple-ssl' );
        });
    },[]);

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
            import ( "./Dashboard/DashboardPage").then(async ({default: DashboardPage}) => {
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

    // async load react-toastify
    useEffect(() => {
        import('react-toastify').then((module) => {
            const ToastContainer = module.ToastContainer;
            setToastContainer(() => ToastContainer);
        });
    }, []);

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
            {OnboardingModal && <ErrorBoundary fallback={"Could not load onboarding modal"}><OnboardingModal /></ErrorBoundary>}

            {Modal && <ErrorBoundary fallback={"Could not load modal"}><Modal/></ErrorBoundary>}
            {
                    <>
                        <Header />
                        <div className={"rsssl-content-area rsssl-grid rsssl-" + selectedMainMenuItem}>
                            { selectedMainMenuItem !== 'dashboard' && Settings && Menu && Notices &&
                               <>
                                   <ErrorBoundary fallback={"Could not load menu"}><Menu /></ErrorBoundary>
                                   <ErrorBoundary fallback={"Could not load settings"}><Settings/></ErrorBoundary>
                                   <ErrorBoundary fallback={"Could not load notices"}><Notices className="rsssl-wizard-notices"/></ErrorBoundary>
                               </>
                            }
                            { selectedMainMenuItem === 'dashboard' && DashboardPage &&
                                <ErrorBoundary fallback={"Could not load menu"}><DashboardPage /></ErrorBoundary>
                            }
                        </div>
                    </>

            }
            {ToastContainer && (
                <ToastContainer
                    position="bottom-right"
                    autoClose={2000}
                    limit={3}
                    hideProgressBar
                    newestOnTop
                    closeOnClick
                    pauseOnFocusLoss
                    pauseOnHover
                    theme="light"
                /> )}
        </div>
    );

}
export default Page