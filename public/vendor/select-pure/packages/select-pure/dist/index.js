var t,e,i,s;const o=globalThis.trustedTypes,n=o?o.createPolicy("lit-html",{createHTML:t=>t}):void 0,l=`lit$${(Math.random()+"").slice(9)}$`,r="?"+l,h=`<${r}>`,a=document,d=(t="")=>a.createComment(t),c=t=>null===t||"object"!=typeof t&&"function"!=typeof t,p=Array.isArray,u=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,v=/-->/g,b=/>/g,f=/>|[ 	\n\r](?:([^\s"'>=/]+)([ 	\n\r]*=[ 	\n\r]*(?:[^ 	\n\r"'`<>=]|("|')|))|$)/g,g=/'/g,m=/"/g,y=/^(?:script|style|textarea)$/i,x=(t=>(e,...i)=>({_$litType$:t,strings:e,values:i}))(1),w=Symbol.for("lit-noChange"),S=Symbol.for("lit-nothing"),k=new WeakMap,O=a.createTreeWalker(a,129,null,!1);class ${constructor({strings:t,_$litType$:e},i){let s;this.parts=[];let a=0,c=0;const p=t.length-1,x=this.parts,[w,S]=((t,e)=>{const i=t.length-1,s=[];let o,r=2===e?"<svg>":"",a=u;for(let e=0;e<i;e++){const i=t[e];let n,d,c=-1,p=0;for(;p<i.length&&(a.lastIndex=p,d=a.exec(i),null!==d);)p=a.lastIndex,a===u?"!--"===d[1]?a=v:void 0!==d[1]?a=b:void 0!==d[2]?(y.test(d[2])&&(o=RegExp("</"+d[2],"g")),a=f):void 0!==d[3]&&(a=f):a===f?">"===d[0]?(a=null!=o?o:u,c=-1):void 0===d[1]?c=-2:(c=a.lastIndex-d[2].length,n=d[1],a=void 0===d[3]?f:'"'===d[3]?m:g):a===m||a===g?a=f:a===v||a===b?a=u:(a=f,o=void 0);const x=a===f&&t[e+1].startsWith("/>")?" ":"";r+=a===u?i+h:c>=0?(s.push(n),i.slice(0,c)+"$lit$"+i.slice(c)+l+x):i+l+(-2===c?(s.push(void 0),e):x)}const d=r+(t[i]||"<?>")+(2===e?"</svg>":"");return[void 0!==n?n.createHTML(d):d,s]})(t,e);if(this.el=$.createElement(w,i),O.currentNode=this.el.content,2===e){const t=this.el.content,e=t.firstChild;e.remove(),t.append(...e.childNodes)}for(;null!==(s=O.nextNode())&&x.length<p;){if(1===s.nodeType){if(s.hasAttributes()){const t=[];for(const e of s.getAttributeNames())if(e.endsWith("$lit$")||e.startsWith(l)){const i=S[c++];if(t.push(e),void 0!==i){const t=s.getAttribute(i.toLowerCase()+"$lit$").split(l),e=/([.?@])?(.*)/.exec(i);x.push({type:1,index:a,name:e[2],strings:t,ctor:"."===e[1]?U:"?"===e[1]?N:"@"===e[1]?H:E})}else x.push({type:6,index:a})}for(const e of t)s.removeAttribute(e)}if(y.test(s.tagName)){const t=s.textContent.split(l),e=t.length-1;if(e>0){s.textContent=o?o.emptyScript:"";for(let i=0;i<e;i++)s.append(t[i],d()),O.nextNode(),x.push({type:2,index:++a});s.append(t[e],d())}}}else if(8===s.nodeType)if(s.data===r)x.push({type:2,index:a});else{let t=-1;for(;-1!==(t=s.data.indexOf(l,t+1));)x.push({type:7,index:a}),t+=l.length-1}a++}}static createElement(t,e){const i=a.createElement("template");return i.innerHTML=t,i}}function C(t,e,i=t,s){var o,n,l,r;if(e===w)return e;let h=void 0!==s?null===(o=i.Σi)||void 0===o?void 0:o[s]:i.Σo;const a=c(e)?void 0:e._$litDirective$;return(null==h?void 0:h.constructor)!==a&&(null===(n=null==h?void 0:h.O)||void 0===n||n.call(h,!1),void 0===a?h=void 0:(h=new a(t),h.T(t,i,s)),void 0!==s?(null!==(l=(r=i).Σi)&&void 0!==l?l:r.Σi=[])[s]=h:i.Σo=h),void 0!==h&&(e=C(t,h.S(t,e.values),h,s)),e}class P{constructor(t,e){this.l=[],this.N=void 0,this.D=t,this.M=e}u(t){var e;const{el:{content:i},parts:s}=this.D,o=(null!==(e=null==t?void 0:t.creationScope)&&void 0!==e?e:a).importNode(i,!0);O.currentNode=o;let n=O.nextNode(),l=0,r=0,h=s[0];for(;void 0!==h;){if(l===h.index){let e;2===h.type?e=new A(n,n.nextSibling,this,t):1===h.type?e=new h.ctor(n,h.name,h.strings,this,t):6===h.type&&(e=new R(n,this,t)),this.l.push(e),h=s[++r]}l!==(null==h?void 0:h.index)&&(n=O.nextNode(),l++)}return o}v(t){let e=0;for(const i of this.l)void 0!==i&&(void 0!==i.strings?(i.I(t,i,e),e+=i.strings.length-2):i.I(t[e])),e++}}class A{constructor(t,e,i,s){this.type=2,this.N=void 0,this.A=t,this.B=e,this.M=i,this.options=s}setConnected(t){var e;null===(e=this.P)||void 0===e||e.call(this,t)}get parentNode(){return this.A.parentNode}get startNode(){return this.A}get endNode(){return this.B}I(t,e=this){t=C(this,t,e),c(t)?t===S||null==t||""===t?(this.H!==S&&this.R(),this.H=S):t!==this.H&&t!==w&&this.m(t):void 0!==t._$litType$?this._(t):void 0!==t.nodeType?this.$(t):(t=>{var e;return p(t)||"function"==typeof(null===(e=t)||void 0===e?void 0:e[Symbol.iterator])})(t)?this.g(t):this.m(t)}k(t,e=this.B){return this.A.parentNode.insertBefore(t,e)}$(t){this.H!==t&&(this.R(),this.H=this.k(t))}m(t){const e=this.A.nextSibling;null!==e&&3===e.nodeType&&(null===this.B?null===e.nextSibling:e===this.B.previousSibling)?e.data=t:this.$(a.createTextNode(t)),this.H=t}_(t){var e;const{values:i,_$litType$:s}=t,o="number"==typeof s?this.C(t):(void 0===s.el&&(s.el=$.createElement(s.h,this.options)),s);if((null===(e=this.H)||void 0===e?void 0:e.D)===o)this.H.v(i);else{const t=new P(o,this),e=t.u(this.options);t.v(i),this.$(e),this.H=t}}C(t){let e=k.get(t.strings);return void 0===e&&k.set(t.strings,e=new $(t)),e}g(t){p(this.H)||(this.H=[],this.R());const e=this.H;let i,s=0;for(const o of t)s===e.length?e.push(i=new A(this.k(d()),this.k(d()),this,this.options)):i=e[s],i.I(o),s++;s<e.length&&(this.R(i&&i.B.nextSibling,s),e.length=s)}R(t=this.A.nextSibling,e){var i;for(null===(i=this.P)||void 0===i||i.call(this,!1,!0,e);t&&t!==this.B;){const e=t.nextSibling;t.remove(),t=e}}}class E{constructor(t,e,i,s,o){this.type=1,this.H=S,this.N=void 0,this.V=void 0,this.element=t,this.name=e,this.M=s,this.options=o,i.length>2||""!==i[0]||""!==i[1]?(this.H=Array(i.length-1).fill(S),this.strings=i):this.H=S}get tagName(){return this.element.tagName}I(t,e=this,i,s){const o=this.strings;let n=!1;if(void 0===o)t=C(this,t,e,0),n=!c(t)||t!==this.H&&t!==w,n&&(this.H=t);else{const s=t;let l,r;for(t=o[0],l=0;l<o.length-1;l++)r=C(this,s[i+l],e,l),r===w&&(r=this.H[l]),n||(n=!c(r)||r!==this.H[l]),r===S?t=S:t!==S&&(t+=(null!=r?r:"")+o[l+1]),this.H[l]=r}n&&!s&&this.W(t)}W(t){t===S?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,null!=t?t:"")}}class U extends E{constructor(){super(...arguments),this.type=3}W(t){this.element[this.name]=t===S?void 0:t}}class N extends E{constructor(){super(...arguments),this.type=4}W(t){t&&t!==S?this.element.setAttribute(this.name,""):this.element.removeAttribute(this.name)}}class H extends E{constructor(){super(...arguments),this.type=5}I(t,e=this){var i;if((t=null!==(i=C(this,t,e,0))&&void 0!==i?i:S)===w)return;const s=this.H,o=t===S&&s!==S||t.capture!==s.capture||t.once!==s.once||t.passive!==s.passive,n=t!==S&&(s===S||o);o&&this.element.removeEventListener(this.name,this,s),n&&this.element.addEventListener(this.name,this,t),this.H=t}handleEvent(t){var e,i;"function"==typeof this.H?this.H.call(null!==(i=null===(e=this.options)||void 0===e?void 0:e.host)&&void 0!==i?i:this.element,t):this.H.handleEvent(t)}}class R{constructor(t,e,i){this.element=t,this.type=6,this.N=void 0,this.V=void 0,this.M=e,this.options=i}I(t){C(this,t)}}null===(e=(t=globalThis).litHtmlPlatformSupport)||void 0===e||e.call(t,$,A),(null!==(i=(s=globalThis).litHtmlVersions)&&void 0!==i?i:s.litHtmlVersions=[]).push("2.0.0-rc.2");const T=window.ShadowRoot&&(void 0===window.ShadyCSS||window.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,z=Symbol();class I{constructor(t,e){if(e!==z)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=t}get styleSheet(){return T&&void 0===this.t&&(this.t=new CSSStyleSheet,this.t.replaceSync(this.cssText)),this.t}toString(){return this.cssText}}const _=new Map,j=(t,...e)=>{const i=e.reduce(((e,i,s)=>e+(t=>{if(t instanceof I)return t.cssText;if("number"==typeof t)return t;throw Error(`Value passed to 'css' function must be a 'css' function result: ${t}. Use 'unsafeCSS' to pass non-literal values, but\n            take care to ensure page security.`)})(i)+t[s+1]),t[0]);let s=_.get(i);return void 0===s&&_.set(i,s=new I(i,z)),s},L=(t,e)=>{T?t.adoptedStyleSheets=e.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet)):e.forEach((e=>{const i=document.createElement("style");i.textContent=e.cssText,t.appendChild(i)}))},M=T?t=>t:t=>t instanceof CSSStyleSheet?(t=>{let e="";for(const i of t.cssRules)e+=i.cssText;return(t=>new I(t+"",z))(e)})(t):t;var B,D,V,W;const q={toAttribute(t,e){switch(e){case Boolean:t=t?"":null;break;case Object:case Array:t=null==t?t:JSON.stringify(t)}return t},fromAttribute(t,e){let i=t;switch(e){case Boolean:i=null!==t;break;case Number:i=null===t?null:Number(t);break;case Object:case Array:try{i=JSON.parse(t)}catch(t){i=null}}return i}},K=(t,e)=>e!==t&&(e==e||t==t),F={attribute:!0,type:String,converter:q,reflect:!1,hasChanged:K};class J extends HTMLElement{constructor(){super(),this.Πi=new Map,this.Πo=void 0,this.Πl=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this.Πh=null,this.u()}static addInitializer(t){var e;null!==(e=this.v)&&void 0!==e||(this.v=[]),this.v.push(t)}static get observedAttributes(){this.finalize();const t=[];return this.elementProperties.forEach(((e,i)=>{const s=this.Πp(i,e);void 0!==s&&(this.Πm.set(s,i),t.push(s))})),t}static createProperty(t,e=F){if(e.state&&(e.attribute=!1),this.finalize(),this.elementProperties.set(t,e),!e.noAccessor&&!this.prototype.hasOwnProperty(t)){const i="symbol"==typeof t?Symbol():"__"+t,s=this.getPropertyDescriptor(t,i,e);void 0!==s&&Object.defineProperty(this.prototype,t,s)}}static getPropertyDescriptor(t,e,i){return{get(){return this[e]},set(s){const o=this[t];this[e]=s,this.requestUpdate(t,o,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(t){return this.elementProperties.get(t)||F}static finalize(){if(this.hasOwnProperty("finalized"))return!1;this.finalized=!0;const t=Object.getPrototypeOf(this);if(t.finalize(),this.elementProperties=new Map(t.elementProperties),this.Πm=new Map,this.hasOwnProperty("properties")){const t=this.properties,e=[...Object.getOwnPropertyNames(t),...Object.getOwnPropertySymbols(t)];for(const i of e)this.createProperty(i,t[i])}return this.elementStyles=this.finalizeStyles(this.styles),!0}static finalizeStyles(t){const e=[];if(Array.isArray(t)){const i=new Set(t.flat(1/0).reverse());for(const t of i)e.unshift(M(t))}else void 0!==t&&e.push(M(t));return e}static"Πp"(t,e){const i=e.attribute;return!1===i?void 0:"string"==typeof i?i:"string"==typeof t?t.toLowerCase():void 0}u(){var t;this.Πg=new Promise((t=>this.enableUpdating=t)),this.L=new Map,this.Π_(),this.requestUpdate(),null===(t=this.constructor.v)||void 0===t||t.forEach((t=>t(this)))}addController(t){var e,i;(null!==(e=this.ΠU)&&void 0!==e?e:this.ΠU=[]).push(t),void 0!==this.renderRoot&&this.isConnected&&(null===(i=t.hostConnected)||void 0===i||i.call(t))}removeController(t){var e;null===(e=this.ΠU)||void 0===e||e.splice(this.ΠU.indexOf(t)>>>0,1)}"Π_"(){this.constructor.elementProperties.forEach(((t,e)=>{this.hasOwnProperty(e)&&(this.Πi.set(e,this[e]),delete this[e])}))}createRenderRoot(){var t;const e=null!==(t=this.shadowRoot)&&void 0!==t?t:this.attachShadow(this.constructor.shadowRootOptions);return L(e,this.constructor.elementStyles),e}connectedCallback(){var t;void 0===this.renderRoot&&(this.renderRoot=this.createRenderRoot()),this.enableUpdating(!0),null===(t=this.ΠU)||void 0===t||t.forEach((t=>{var e;return null===(e=t.hostConnected)||void 0===e?void 0:e.call(t)})),this.Πl&&(this.Πl(),this.Πo=this.Πl=void 0)}enableUpdating(t){}disconnectedCallback(){var t;null===(t=this.ΠU)||void 0===t||t.forEach((t=>{var e;return null===(e=t.hostDisconnected)||void 0===e?void 0:e.call(t)})),this.Πo=new Promise((t=>this.Πl=t))}attributeChangedCallback(t,e,i){this.K(t,i)}"Πj"(t,e,i=F){var s,o;const n=this.constructor.Πp(t,i);if(void 0!==n&&!0===i.reflect){const l=(null!==(o=null===(s=i.converter)||void 0===s?void 0:s.toAttribute)&&void 0!==o?o:q.toAttribute)(e,i.type);this.Πh=t,null==l?this.removeAttribute(n):this.setAttribute(n,l),this.Πh=null}}K(t,e){var i,s,o;const n=this.constructor,l=n.Πm.get(t);if(void 0!==l&&this.Πh!==l){const t=n.getPropertyOptions(l),r=t.converter,h=null!==(o=null!==(s=null===(i=r)||void 0===i?void 0:i.fromAttribute)&&void 0!==s?s:"function"==typeof r?r:null)&&void 0!==o?o:q.fromAttribute;this.Πh=l,this[l]=h(e,t.type),this.Πh=null}}requestUpdate(t,e,i){let s=!0;void 0!==t&&(((i=i||this.constructor.getPropertyOptions(t)).hasChanged||K)(this[t],e)?(this.L.has(t)||this.L.set(t,e),!0===i.reflect&&this.Πh!==t&&(void 0===this.Πk&&(this.Πk=new Map),this.Πk.set(t,i))):s=!1),!this.isUpdatePending&&s&&(this.Πg=this.Πq())}async"Πq"(){this.isUpdatePending=!0;try{for(await this.Πg;this.Πo;)await this.Πo}catch(t){Promise.reject(t)}const t=this.performUpdate();return null!=t&&await t,!this.isUpdatePending}performUpdate(){var t;if(!this.isUpdatePending)return;this.hasUpdated,this.Πi&&(this.Πi.forEach(((t,e)=>this[e]=t)),this.Πi=void 0);let e=!1;const i=this.L;try{e=this.shouldUpdate(i),e?(this.willUpdate(i),null===(t=this.ΠU)||void 0===t||t.forEach((t=>{var e;return null===(e=t.hostUpdate)||void 0===e?void 0:e.call(t)})),this.update(i)):this.Π$()}catch(t){throw e=!1,this.Π$(),t}e&&this.E(i)}willUpdate(t){}E(t){var e;null===(e=this.ΠU)||void 0===e||e.forEach((t=>{var e;return null===(e=t.hostUpdated)||void 0===e?void 0:e.call(t)})),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(t)),this.updated(t)}"Π$"(){this.L=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this.Πg}shouldUpdate(t){return!0}update(t){void 0!==this.Πk&&(this.Πk.forEach(((t,e)=>this.Πj(e,this[e],t))),this.Πk=void 0),this.Π$()}updated(t){}firstUpdated(t){}}var Z,G,Q,X,Y,tt;J.finalized=!0,J.shadowRootOptions={mode:"open"},null===(D=(B=globalThis).reactiveElementPlatformSupport)||void 0===D||D.call(B,{ReactiveElement:J}),(null!==(V=(W=globalThis).reactiveElementVersions)&&void 0!==V?V:W.reactiveElementVersions=[]).push("1.0.0-rc.1"),(null!==(Z=(tt=globalThis).litElementVersions)&&void 0!==Z?Z:tt.litElementVersions=[]).push("3.0.0-rc.1");class et extends J{constructor(){super(...arguments),this.renderOptions={host:this},this.Φt=void 0}createRenderRoot(){var t,e;const i=super.createRenderRoot();return null!==(t=(e=this.renderOptions).renderBefore)&&void 0!==t||(e.renderBefore=i.firstChild),i}update(t){const e=this.render();super.update(t),this.Φt=((t,e,i)=>{var s,o;const n=null!==(s=null==i?void 0:i.renderBefore)&&void 0!==s?s:e;let l=n._$litPart$;if(void 0===l){const t=null!==(o=null==i?void 0:i.renderBefore)&&void 0!==o?o:null;n._$litPart$=l=new A(e.insertBefore(d(),t),t,void 0,i)}return l.I(t),l})(e,this.renderRoot,this.renderOptions)}connectedCallback(){var t;super.connectedCallback(),null===(t=this.Φt)||void 0===t||t.setConnected(!0)}disconnectedCallback(){var t;super.disconnectedCallback(),null===(t=this.Φt)||void 0===t||t.setConnected(!1)}render(){return w}}et.finalized=!0,et._$litElement$=!0,null===(Q=(G=globalThis).litElementHydrateSupport)||void 0===Q||Q.call(G,{LitElement:et}),null===(Y=(X=globalThis).litElementPlatformSupport)||void 0===Y||Y.call(X,{LitElement:et});const it=t=>e=>"function"==typeof e?((t,e)=>(window.customElements.define(t,e),e))(t,e):((t,e)=>{const{kind:i,elements:s}=e;return{kind:i,elements:s,finisher(e){window.customElements.define(t,e)}}})(t,e),st=(t,e)=>"method"===e.kind&&e.descriptor&&!("value"in e.descriptor)?{...e,finisher(i){i.createProperty(e.key,t)}}:{kind:"field",key:Symbol(),placement:"own",descriptor:{},originalKey:e.key,initializer(){"function"==typeof e.initializer&&(this[e.key]=e.initializer.call(this))},finisher(i){i.createProperty(e.key,t)}};function ot(t){return(e,i)=>void 0!==i?((t,e,i)=>{e.constructor.createProperty(i,t)})(t,e,i):st(t,e)}const nt=t=>null!=t?t:S,lt=13,rt=9;var ht=function(t,e,i,s){var o,n=arguments.length,l=n<3?e:null===s?s=Object.getOwnPropertyDescriptor(e,i):s;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(t,e,i,s);else for(var r=t.length-1;r>=0;r--)(o=t[r])&&(l=(n<3?o(l):n>3?o(e,i,l):o(e,i))||l);return n>3&&l&&Object.defineProperty(e,i,l),l};let at=class extends et{constructor(){super(),this.selected=null!==this.getAttribute("selected"),this.disabled=null!==this.getAttribute("disabled"),this.value=this.getAttribute("value")||"",this.label=this.textContent||this.getAttribute("label")||"",this.onClick=this.onClick.bind(this),this.select=this.select.bind(this),this.unselect=this.unselect.bind(this),this.getOption=this.getOption.bind(this)}static get styles(){return j`.option{align-items:center;background-color:var(--background-color,#fff);box-sizing:border-box;color:var(--color,#000);cursor:pointer;display:flex;font-family:var(--font-family,inherit);font-size:var(--font-size,14px);font-weight:var(--font-weight,400);height:var(--select-height,44px);height:var(--select-height,44px);justify-content:flex-start;padding:var(--padding,0 10px);width:100%}.option:not(.disabled):focus,.option:not(.disabled):not(.selected):hover{background-color:var(--hover-background-color,#e3e3e3);color:var(--hover-color,#000)}.selected{background-color:var(--selected-background-color,#e3e3e3);color:var(--selected-color,#000)}.disabled{background-color:var(--disabled-background-color,#e3e3e3);color:var(--disabled-color,#000);cursor:default}`}getOption(){return{label:this.label,value:this.value,select:this.select,unselect:this.unselect,selected:this.selected,disabled:this.disabled}}select(){this.selected=!0,this.setAttribute("selected","")}unselect(){this.selected=!1,this.removeAttribute("selected")}setOnSelectCallback(t){this.onSelect=t}onClick(t){this.onSelect&&!this.disabled?this.onSelect(this.value):t.stopPropagation()}handleKeyPress(t){t.which===lt&&this.onClick(t)}render(){const t=["option"];return this.selected&&t.push("selected"),this.disabled&&t.push("disabled"),x`<div class="${t.join(" ")}" @click="${this.onClick}" @keydown="${this.handleKeyPress}" tabindex="${nt(this.disabled?"0":void 0)}">${this.label}</div>`}};ht([ot()],at.prototype,"selected",void 0),ht([ot()],at.prototype,"disabled",void 0),ht([ot()],at.prototype,"value",void 0),ht([ot()],at.prototype,"label",void 0),at=ht([it("option-pure")],at);var dt=function(t,e,i,s){var o,n=arguments.length,l=n<3?e:null===s?s=Object.getOwnPropertyDescriptor(e,i):s;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)l=Reflect.decorate(t,e,i,s);else for(var r=t.length-1;r>=0;r--)(o=t[r])&&(l=(n<3?o(l):n>3?o(e,i,l):o(e,i))||l);return n>3&&l&&Object.defineProperty(e,i,l),l};const ct={label:"",value:"",select:()=>{},unselect:()=>{},disabled:!1,hidden:!1};let pt=class extends et{constructor(){super(),this.options=[],this.visible=!1,this.selectedOption=ct,this._selectedOptions=[],this.disabled=null!==this.getAttribute("disabled"),this.multiple=null!==this.getAttribute("multiple"),this.name=this.getAttribute("name")||"",this._id=this.getAttribute("id")||"",this.formName=this.name||this.id,this.value="",this.values=[],this.defaultLabel=this.getAttribute("default-label")||"",this.nativeSelect=null,this.form=null,this.hiddenInput=null,this.close=this.close.bind(this),this.onSelect=this.onSelect.bind(this),this.processOptions=this.processOptions.bind(this),this.processForm=this.processForm.bind(this)}static get styles(){return j`.select-wrapper{position:relative}.select-wrapper:hover .select{z-index:2}.select{bottom:0;display:flex;flex-wrap:wrap;left:0;position:absolute;right:0;top:0;width:var(--select-width,100%)}.label:focus{outline:var(--select-outline,2px solid #e3e3e3)}.label:after{border-bottom:1px solid var(--color,#000);border-right:1px solid var(--color,#000);box-sizing:border-box;content:"";display:block;height:10px;margin-top:-2px;transform:rotate(45deg);transition:.2s ease-in-out;width:10px}.label.visible:after{margin-bottom:-4px;margin-top:0;transform:rotate(225deg)}select{-webkit-appearance:none;-moz-appearance:none;appearance:none;position:relative;opacity:0;z-index:1}.label,select{align-items:center;background-color:var(--background-color,#fff);border-radius:var(--border-radius,4px);border:var(--border-width,1px) solid var(--border-color,#000);box-sizing:border-box;color:var(--color,#000);cursor:pointer;display:flex;font-family:var(--font-family,inherit);font-size:var(--font-size,14px);font-weight:var(--font-weight,400);min-height:var(--select-height,44px);justify-content:space-between;padding:var(--padding,0 10px);width:100%}.dropdown{background-color:var(--border-color,#000);border-radius:var(--border-radius,4px);border:var(--border-width,1px) solid var(--border-color,#000);display:none;flex-direction:column;gap:var(--border-width,1px);justify-content:space-between;max-height:calc(var(--select-height,44px) * 4 + var(--border-width,1px) * 3);overflow-y:scroll;position:absolute;top:calc(var(--select-height,44px) + var(--dropdown-gap,0px));width:calc(100% - var(--border-width,1px) * 2);z-index:var(--dropdown-z-index,2)}.dropdown.visible{display:flex}.disabled{background-color:var(--disabled-background-color,#bdc3c7);color:var(--disabled-color,#ecf0f1);cursor:default}.multi-selected{background-color:var(--selected-background-color,#e3e3e3);border-radius:var(--border-radius,4px);color:var(--selected-color,#000);display:flex;gap:8px;justify-content:space-between;padding:2px 4px}.multi-selected-wrapper{display:flex;flex-wrap:wrap;gap:4px;width:calc(100% - 30px)}.cross:after{content:'\\00d7';display:inline-block;height:100%;text-align:center;width:12px}`}firstUpdated(){this.processOptions(),this.processForm()}open(){this.disabled||(document.body.removeEventListener("click",this.close),this.visible=!0,setTimeout((()=>{document.body.addEventListener("click",this.close)})))}close(){this.visible=!1,document.body.removeEventListener("click",this.close)}enable(){this.disabled=!1}disable(){this.disabled=!0}get selectedIndex(){var t;return null===(t=this.nativeSelect)||void 0===t?void 0:t.selectedIndex}set selectedIndex(t){t&&this.onSelect(this.options[t].value)}get selectedOptions(){var t;return null===(t=this.nativeSelect)||void 0===t?void 0:t.selectedOptions}processForm(){this.form=this.closest("form"),this.form&&(this.hiddenInput=document.createElement("input"),this.hiddenInput.setAttribute("type","hidden"),this.hiddenInput.setAttribute("name",this.formName),this.form.appendChild(this.hiddenInput))}handleNativeSelectChange(){var t;this.selectedIndex=null===(t=this.nativeSelect)||void 0===t?void 0:t.selectedIndex}processOptions(){this.nativeSelect=this.shadowRoot.querySelector("select");const t=this.querySelectorAll("option-pure");for(let e=0;e<t.length;e++){const i=t[e],{value:s,label:o,select:n,unselect:l,selected:r,hidden:h,disabled:a}=i.getOption();this.options.push({label:o,value:s,select:n,unselect:l,hidden:h,disabled:a}),r&&this.selectOption(this.options[e],!0),i.setOnSelectCallback(this.onSelect),e!==t.length-1||this.selectedOption.value||this.multiple||this.selectOption(this.options[0],!0)}}onSelect(t){for(let e=0;e<this.options.length;e++){const i=this.options[e];i.value!==t?this.multiple||i.unselect():this.selectOption(i)}this.visible=!1}selectOption(t,e){if(this.multiple){const e=this._selectedOptions.find((({value:e})=>e===t.value));if(e){const i=this._selectedOptions.indexOf(e);this.values.splice(i,1),this._selectedOptions.splice(i,1),t.unselect()}else this.values.push(t.value),this._selectedOptions.push(t),t.select();this.requestUpdate()}else this.selectedOption=t,this.value=t.value,t.select();if(this.form&&this.hiddenInput){this.hiddenInput.value=this.multiple?this.values.join(","):this.value;const t=new Event("change",{bubbles:!0});this.hiddenInput.dispatchEvent(t)}e||this.afterSelect()}afterSelect(){this.dispatchEvent(new Event("change"))}handleKeyPress(t){t.which!==lt&&t.which!==rt||this.open()}onCrossClick(t,e){t.stopPropagation(),this.onSelect(e)}renderNativeOptions(){return this.options.map((({value:t,label:e,hidden:i,disabled:s})=>{let o=this.selectedOption.value===t;return this.multiple&&(o=Boolean(this._selectedOptions.find((e=>e.value===t)))),x`<option value="${t}" ?selected="${o}" ?hidden="${i}" ?disabled="${s}">${e}</option>`}))}renderLabel(){return this.multiple&&this._selectedOptions.length?x`<div class="multi-selected-wrapper">${this._selectedOptions.map((({label:t,value:e})=>x`<span class="multi-selected">${t} <span class="cross" @click="${t=>this.onCrossClick(t,e)}"></span></span>`))}</div>`:this.selectedOption.label||this.defaultLabel}render(){const t=["label"];return this.disabled&&t.push("disabled"),this.visible&&t.push("visible"),x`<div class="select-wrapper"><select @change="${this.handleNativeSelectChange}" ?disabled="${this.disabled}" ?multiple="${this.multiple}" name="${nt(this.name||void 0)}" id="${nt(this.id||void 0)}" size="1">${this.renderNativeOptions()}</select><div class="select"><div class="${t.join(" ")}" @click="${this.visible?this.close:this.open}" @keydown="${this.handleKeyPress}" tabindex="0">${this.renderLabel()}</div><div class="dropdown${this.visible?" visible":""}"><slot></slot></div></div></div>`}};dt([ot()],pt.prototype,"options",void 0),dt([ot()],pt.prototype,"visible",void 0),dt([ot()],pt.prototype,"selectedOption",void 0),dt([ot()],pt.prototype,"_selectedOptions",void 0),dt([ot()],pt.prototype,"disabled",void 0),dt([ot()],pt.prototype,"multiple",void 0),dt([ot()],pt.prototype,"name",void 0),dt([ot()],pt.prototype,"_id",void 0),dt([ot()],pt.prototype,"formName",void 0),dt([ot()],pt.prototype,"value",void 0),dt([ot()],pt.prototype,"values",void 0),dt([ot()],pt.prototype,"defaultLabel",void 0),pt=dt([it("select-pure")],pt);
