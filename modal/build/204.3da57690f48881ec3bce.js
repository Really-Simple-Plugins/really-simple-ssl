(self.webpackChunkreally_simple_ssl_modal=self.webpackChunkreally_simple_ssl_modal||[]).push([[204],{204:function(e,t,l){"use strict";l.r(t),l.d(t,{default:function(){return p}});var r=l(196),n=l(609),s=l(307),a=l(736),o=l(317),c=l.n(o);class m extends r.Component{constructor(e){super(e),this.state={hasError:!1,error:null,errorInfo:null},this.resetError=this.resetError.bind(this)}static getDerivedStateFromError(e){return{hasError:!0}}componentDidCatch(e,t){this.setState({error:e,errorInfo:t}),console.log("ErrorBoundary",e,t)}resetError(){this.setState({hasError:!1,error:null,errorInfo:null})}render(){return this.state.hasError?(0,r.createElement)("div",null,(0,r.createElement)("h1",null,"Something went wrong."),(0,r.createElement)("p",null,this.props.fallback),(0,r.createElement)("button",{onClick:this.resetError},"Try Again")):this.props.children}}m.propTypes={children:c().node,fallback:c().node};var i=m,p=({title:e,subTitle:t,buttons:o,content:c,list:m,confirmAction:p,confirmText:u,alternativeAction:d,alternativeText:E,alternativeClassName:f,isOpen:h,setOpen:g,className:y})=>{const[w,_]=(0,s.useState)(null);let b="undefined"!=typeof rsssl_modal?rsssl_modal.plugin_url:rsssl_settings.plugin_url;f=f||"rsssl-warning",(0,s.useEffect)((()=>{w||Promise.all([l.e(357),l.e(658)]).then(l.bind(l,658)).then((({default:e})=>{_((()=>e))}))}),[]);let C=y?" "+y:"";return wp.element.createElement(r.Fragment,null,h&&wp.element.createElement(r.Fragment,null,wp.element.createElement(i,{fallback:"Error loading modal"},wp.element.createElement(n.Modal,{className:"rsssl-modal"+C,shouldCloseOnClickOutside:!1,shouldCloseOnEsc:!1,title:e,onRequestClose:()=>g(!1),open:h},wp.element.createElement("div",{className:"rsssl-modal-body"},t&&wp.element.createElement("p",null,t),c&&wp.element.createElement(r.Fragment,null,c),m&&w&&wp.element.createElement("ul",null,m.map(((e,t)=>wp.element.createElement("li",{key:t},wp.element.createElement(w,{name:e.icon,color:e.color}),e.text))))),wp.element.createElement("div",{className:"rsssl-modal-footer"},wp.element.createElement("div",{className:"rsssl-modal-footer-image"},wp.element.createElement("img",{className:"rsssl-logo",src:b+"assets/img/really-simple-ssl-logo.svg",alt:"Really Simple SSL"})),wp.element.createElement("div",{className:"rsssl-modal-footer-buttons"},wp.element.createElement(n.Button,{onClick:()=>g(!1)},(0,a.__)("Cancel","really-simple-ssl")),o&&wp.element.createElement(r.Fragment,null,o),!o&&wp.element.createElement(r.Fragment,null,E&&wp.element.createElement(n.Button,{className:f,onClick:()=>d()},E),u&&wp.element.createElement(n.Button,{isPrimary:!0,onClick:()=>p()},u))))))))}},545:function(e,t,l){"use strict";var r=l(825);function n(){}function s(){}s.resetWarningCache=n,e.exports=function(){function e(e,t,l,n,s,a){if(a!==r){var o=new Error("Calling PropTypes validators directly is not supported by the `prop-types` package. Use PropTypes.checkPropTypes() to call them. Read more at http://fb.me/use-check-prop-types");throw o.name="Invariant Violation",o}}function t(){return e}e.isRequired=e;var l={array:e,bigint:e,bool:e,func:e,number:e,object:e,string:e,symbol:e,any:e,arrayOf:t,element:e,elementType:e,instanceOf:t,node:e,objectOf:t,oneOf:t,oneOfType:t,shape:t,exact:t,checkPropTypes:s,resetWarningCache:n};return l.PropTypes=l,l}},317:function(e,t,l){e.exports=l(545)()},825:function(e){"use strict";e.exports="SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED"}}]);