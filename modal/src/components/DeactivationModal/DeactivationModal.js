import {__} from "@wordpress/i18n";
import {useEffect, useState} from "@wordpress/element";

const DeactivationModal = () => {

    const [isOpen, setOpen] = useState(false);
    const [RssslModal, setRssslModal] = useState(false);
    const [targetPluginLink, setTargetPluginLink] = useState(null);
    const isPremium = rsssl_modal.pro_plugin_active;

    useEffect(() => {
        // Dynamically set the targetPluginLink based on isPremium
        const linkId = isPremium ? 'deactivate-really-simple-security-pro' : 'deactivate-really-simple-security';

        const handleDeactivationClick = (event) => {
            const target = event.target.closest(`#${linkId}`);
            if (target) {
                event.preventDefault();
                setTargetPluginLink(target);
                setOpen(true); // Show modal
            }
        };

        // Ensure we intercept the click BEFORE any other handlers
        document.addEventListener('click', handleDeactivationClick, true);

        // Also try to set the link element immediately if it exists
        const linkElement = document.getElementById(linkId);
        if (linkElement) {
            setTargetPluginLink(linkElement);
        }

        // Clean up the event listener
        return () => {
            document.removeEventListener('click', handleDeactivationClick, true);
        };
    }, [isPremium]); // Re-run this effect if isPremium changes

    const deactivateKeepHttps = () => {
        setOpen(false);
        window.location.href = rsssl_modal.deactivate_keep_https;
    };

    const deactivateAndRevert = () => {
        setOpen(false);
        window.location.href = rsssl_modal.deactivate_revert_https;
    };

    useEffect(() => {
        if (!RssslModal && isOpen) {
            import("../Modal/RssslModal").then(({default: LoadedRssslModal}) => {
                setRssslModal(() => LoadedRssslModal);
            });
        }
    }, [isOpen, RssslModal]);

    const content = () => {
        return (
            <>
                {__("Please choose the correct deactivation method, and before you go; you will miss out on below and future features in Really Simple Security", "really-simple-ssl")}
                {isPremium && <> <b>Pro</b></>}
                !
            </>
        );
    };

    const list = isPremium ? [
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("SSL Encryption", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("Firewall", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("Vulnerability Management", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("WordPress Hardening", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("Login Protection", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("Two-Factor Authentication", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("Limit Login Attempts", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("Visitor Protection", "really-simple-ssl"),
            },
        ] :
        [
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("SSL Encryption", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("Vulnerability Management", "really-simple-ssl"),
            },
            {
                'icon': 'circle-times',
                'color': 'red',
                'text': __("WordPress hardening", "really-simple-ssl"),
            },
        ];
    return (
        <>
            {RssslModal && <RssslModal title={__("Are you sure?", "really-simple-ssl")}
                                       confirmText={__("Deactivate", "really-simple-ssl")}
                                       confirmAction={() => deactivateKeepHttps()}
                                       alternativeText={__("Deactivate and use HTTP", "really-simple-ssl")}
                                       alternativeAction={() => deactivateAndRevert()}
                                       alternativeClassName="rsssl-modal-warning"
                                       content={content()}
                                       list={list}
                                       isOpen={isOpen}
                                       setOpen={setOpen}/>
            }
        </>
    );
}
export default DeactivationModal;