import { __ } from "@wordpress/i18n";
import {useEffect, useState} from "@wordpress/element";

const DeactivationModal = () => {
    const [isOpen, setOpen] = useState(false);
    const [RssslModal, setRssslModal] = useState(false);
    const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
    const isPremium = rsssl_modal.pro_plugin_active;
    const handleClick = (event) => {
        event.preventDefault();
        setOpen(true);
    };

    const deactivateKeepHttps = () => {
        if ( !targetPluginLink ) {
            return;
        }
        targetPluginLink.removeEventListener('click', handleClick);
        //click the targetPluginLink
        setOpen(false);
        window.location.href = rsssl_modal.deactivate_keep_https;
    }

    const deactivateAndRevert = () => {
        if ( !targetPluginLink ) {
            return;
        }
        setOpen(false);
        window.location.href = targetPluginLink.getAttribute('href');
    }

    useEffect(() => {
        if ( !targetPluginLink ) {
            return;
        }
        // Attach the click event listener to each link element
        targetPluginLink.addEventListener('click', handleClick);

        // Clean up the event listeners when the component unmounts
        return () => {
            targetPluginLink.removeEventListener('click', handleClick);
        };
    }, []);

    useEffect(() => {
        if ( !targetPluginLink ) {
            return;
        }
        if ( isOpen ) {
            targetPluginLink.removeEventListener('click', handleClick);
        } else {
            targetPluginLink.addEventListener('click', handleClick);
        }
    }, [isOpen]);

    useEffect( () => {
        if (!RssslModal) {
            import ("../Modal/RssslModal").then(({default: RssslModal}) => {
                setRssslModal(() => RssslModal);
            });
        }
    }, [isOpen]);

    const content = () => {
        return (
            <>
                {__("Please choose the correct deactivation method, and before you go; you will miss out on below and future features in Really Simple Security", "really-simple-ssl")}
                {isPremium && <> <b>Pro</b></>}
                !
            </>
        );
    }

    const list = isPremium ? [
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Performant HTTPS redirection", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Vulnerability detection", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Security Headers", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Advanced hardening", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Mixed content scan", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Two-step verification", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Password security", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Limit login attempts", "really-simple-ssl"),
            },
        ] :
        [
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Performant HTTPS redirection", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Vulnerability detection", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("WordPress hardening", "really-simple-ssl"),
            },
            {
                'icon':'circle-times',
                'color':'red',
                'text': __("Mixed content fixer", "really-simple-ssl"),
            },
        ];
    return (
        <>
            {RssslModal && <RssslModal title={__("Are you sure?", "really-simple-ssl")}
                        confirmText = {__("Deactivate", "really-simple-ssl")}
                        confirmAction = {() => deactivateKeepHttps() }
                        alternativeText = {__("Deactivate and use HTTP", "really-simple-ssl") }
                        alternativeAction = { () =>  deactivateAndRevert() }
                        alternativeClassName = "rsssl-modal-warning"
                        content={content()}
                        list={list}
                        isOpen={isOpen}
                        setOpen={setOpen} />
            }
        </>
    );
}
export default DeactivationModal;