(()=>{var t={6272:(t,e,r)=>{"use strict";var n=r(308),o={"text/plain":"Text","text/html":"Url",default:"Text"};t.exports=function(t,e){var r,i,c,s,u,a,f=!1;e||(e={}),r=e.debug||!1;try{if(c=n(),s=document.createRange(),u=document.getSelection(),(a=document.createElement("span")).textContent=t,a.ariaHidden="true",a.style.all="unset",a.style.position="fixed",a.style.top=0,a.style.clip="rect(0, 0, 0, 0)",a.style.whiteSpace="pre",a.style.webkitUserSelect="text",a.style.MozUserSelect="text",a.style.msUserSelect="text",a.style.userSelect="text",a.addEventListener("copy",(function(n){if(n.stopPropagation(),e.format)if(n.preventDefault(),void 0===n.clipboardData){r&&console.warn("unable to use e.clipboardData"),r&&console.warn("trying IE specific stuff"),window.clipboardData.clearData();var i=o[e.format]||o.default;window.clipboardData.setData(i,t)}else n.clipboardData.clearData(),n.clipboardData.setData(e.format,t);e.onCopy&&(n.preventDefault(),e.onCopy(n.clipboardData))})),document.body.appendChild(a),s.selectNodeContents(a),u.addRange(s),!document.execCommand("copy"))throw new Error("copy command was unsuccessful");f=!0}catch(n){r&&console.error("unable to copy using execCommand: ",n),r&&console.warn("trying IE specific stuff");try{window.clipboardData.setData(e.format||"text",t),e.onCopy&&e.onCopy(window.clipboardData),f=!0}catch(n){r&&console.error("unable to copy using clipboardData: ",n),r&&console.error("falling back to prompt"),i=function(t){var e=(/mac os x/i.test(navigator.userAgent)?"⌘":"Ctrl")+"+C";return t.replace(/#{\s*key\s*}/g,e)}("message"in e?e.message:"Copy to clipboard: #{key}, Enter"),window.prompt(i,t)}}finally{u&&("function"==typeof u.removeRange?u.removeRange(s):u.removeAllRanges()),a&&document.body.removeChild(a),c()}return f}},308:t=>{t.exports=function(){var t=document.getSelection();if(!t.rangeCount)return function(){};for(var e=document.activeElement,r=[],n=0;n<t.rangeCount;n++)r.push(t.getRangeAt(n));switch(e.tagName.toUpperCase()){case"INPUT":case"TEXTAREA":e.blur();break;default:e=null}return t.removeAllRanges(),function(){"Caret"===t.type&&t.removeAllRanges(),t.rangeCount||r.forEach((function(e){t.addRange(e)})),e&&e.focus()}}},5872:(t,e,r)=>{"use strict";r(2452);var n=r(1568);t.exports=n("Array","includes")},8472:(t,e,r)=>{"use strict";r(8235);var n=r(4880);t.exports=n.Object.assign},7580:(t,e,r)=>{"use strict";r(1088);var n=r(4880);t.exports=n.Object.entries},4060:(t,e,r)=>{"use strict";r(8332);var n=r(4880);t.exports=n.Object.values},1896:(t,e,r)=>{"use strict";var n=r(9063),o=r(4596),i=TypeError;t.exports=function(t){if(n(t))return t;throw new i(o(t)+" is not a function")}},2328:(t,e,r)=>{"use strict";var n=r(1840),o=r(8340),i=r(368).f,c=n("unscopables"),s=Array.prototype;void 0===s[c]&&i(s,c,{configurable:!0,value:o(null)}),t.exports=function(t){s[c][t]=!0}},8424:(t,e,r)=>{"use strict";var n=r(808),o=String,i=TypeError;t.exports=function(t){if(n(t))return t;throw new i(o(t)+" is not an object")}},2196:(t,e,r)=>{"use strict";var n=r(9740),o=r(4160),i=r(9480),c=function(t){return function(e,r,c){var s,u=n(e),a=i(u),f=o(c,a);if(t&&r!=r){for(;a>f;)if((s=u[f++])!=s)return!0}else for(;a>f;f++)if((t||f in u)&&u[f]===r)return t||f||0;return!t&&-1}};t.exports={includes:c(!0),indexOf:c(!1)}},5983:(t,e,r)=>{"use strict";var n=r(1447),o=n({}.toString),i=n("".slice);t.exports=function(t){return i(o(t),8,-1)}},4304:(t,e,r)=>{"use strict";var n=r(6216),o=r(9976),i=r(4560),c=r(368);t.exports=function(t,e,r){for(var s=o(e),u=c.f,a=i.f,f=0;f<s.length;f++){var l=s[f];n(t,l)||r&&n(r,l)||u(t,l,a(e,l))}}},9120:(t,e,r)=>{"use strict";var n=r(6040);t.exports=!n((function(){function t(){}return t.prototype.constructor=null,Object.getPrototypeOf(new t)!==t.prototype}))},3652:(t,e,r)=>{"use strict";var n=r(3528),o=r(368),i=r(9200);t.exports=n?function(t,e,r){return o.f(t,e,i(1,r))}:function(t,e,r){return t[e]=r,t}},9200:t=>{"use strict";t.exports=function(t,e){return{enumerable:!(1&t),configurable:!(2&t),writable:!(4&t),value:e}}},3244:(t,e,r)=>{"use strict";var n=r(9063),o=r(368),i=r(316),c=r(1544);t.exports=function(t,e,r,s){s||(s={});var u=s.enumerable,a=void 0!==s.name?s.name:e;if(n(r)&&i(r,a,s),s.global)u?t[e]=r:c(e,r);else{try{s.unsafe?t[e]&&(u=!0):delete t[e]}catch(t){}u?t[e]=r:o.f(t,e,{value:r,enumerable:!1,configurable:!s.nonConfigurable,writable:!s.nonWritable})}return t}},1544:(t,e,r)=>{"use strict";var n=r(5624),o=Object.defineProperty;t.exports=function(t,e){try{o(n,t,{value:e,configurable:!0,writable:!0})}catch(r){n[t]=e}return e}},3528:(t,e,r)=>{"use strict";var n=r(6040);t.exports=!n((function(){return 7!==Object.defineProperty({},1,{get:function(){return 7}})[1]}))},9308:(t,e,r)=>{"use strict";var n=r(5624),o=r(808),i=n.document,c=o(i)&&o(i.createElement);t.exports=function(t){return c?i.createElement(t):{}}},8232:t=>{"use strict";t.exports="undefined"!=typeof navigator&&String(navigator.userAgent)||""},3356:(t,e,r)=>{"use strict";var n,o,i=r(5624),c=r(8232),s=i.process,u=i.Deno,a=s&&s.versions||u&&u.version,f=a&&a.v8;f&&(o=(n=f.split("."))[0]>0&&n[0]<4?1:+(n[0]+n[1])),!o&&c&&(!(n=c.match(/Edge\/(\d+)/))||n[1]>=74)&&(n=c.match(/Chrome\/(\d+)/))&&(o=+n[1]),t.exports=o},1568:(t,e,r)=>{"use strict";var n=r(5624),o=r(1447);t.exports=function(t,e){return o(n[t].prototype[e])}},4656:t=>{"use strict";t.exports=["constructor","hasOwnProperty","isPrototypeOf","propertyIsEnumerable","toLocaleString","toString","valueOf"]},3748:(t,e,r)=>{"use strict";var n=r(5624),o=r(4560).f,i=r(3652),c=r(3244),s=r(1544),u=r(4304),a=r(5272);t.exports=function(t,e){var r,f,l,p,v,y=t.target,b=t.global,d=t.stat;if(r=b?n:d?n[y]||s(y,{}):n[y]&&n[y].prototype)for(f in e){if(p=e[f],l=t.dontCallGetSet?(v=o(r,f))&&v.value:r[f],!a(b?f:y+(d?".":"#")+f,t.forced)&&void 0!==l){if(typeof p==typeof l)continue;u(p,l)}(t.sham||l&&l.sham)&&i(p,"sham",!0),c(r,f,p,t)}}},6040:t=>{"use strict";t.exports=function(t){try{return!!t()}catch(t){return!0}}},5744:(t,e,r)=>{"use strict";var n=r(6040);t.exports=!n((function(){var t=function(){}.bind();return"function"!=typeof t||t.hasOwnProperty("prototype")}))},892:(t,e,r)=>{"use strict";var n=r(5744),o=Function.prototype.call;t.exports=n?o.bind(o):function(){return o.apply(o,arguments)}},3788:(t,e,r)=>{"use strict";var n=r(3528),o=r(6216),i=Function.prototype,c=n&&Object.getOwnPropertyDescriptor,s=o(i,"name"),u=s&&"something"===function(){}.name,a=s&&(!n||n&&c(i,"name").configurable);t.exports={EXISTS:s,PROPER:u,CONFIGURABLE:a}},1447:(t,e,r)=>{"use strict";var n=r(5744),o=Function.prototype,i=o.call,c=n&&o.bind.bind(i,i);t.exports=n?c:function(t){return function(){return i.apply(t,arguments)}}},4960:(t,e,r)=>{"use strict";var n=r(5624),o=r(9063);t.exports=function(t,e){return arguments.length<2?(r=n[t],o(r)?r:void 0):n[t]&&n[t][e];var r}},364:(t,e,r)=>{"use strict";var n=r(1896),o=r(952);t.exports=function(t,e){var r=t[e];return o(r)?void 0:n(r)}},5624:function(t,e,r){"use strict";var n=function(t){return t&&t.Math===Math&&t};t.exports=n("object"==typeof globalThis&&globalThis)||n("object"==typeof window&&window)||n("object"==typeof self&&self)||n("object"==typeof r.g&&r.g)||n("object"==typeof this&&this)||function(){return this}()||Function("return this")()},6216:(t,e,r)=>{"use strict";var n=r(1447),o=r(6804),i=n({}.hasOwnProperty);t.exports=Object.hasOwn||function(t,e){return i(o(t),e)}},6480:t=>{"use strict";t.exports={}},6836:(t,e,r)=>{"use strict";var n=r(4960);t.exports=n("document","documentElement")},784:(t,e,r)=>{"use strict";var n=r(3528),o=r(6040),i=r(9308);t.exports=!n&&!o((function(){return 7!==Object.defineProperty(i("div"),"a",{get:function(){return 7}}).a}))},6212:(t,e,r)=>{"use strict";var n=r(1447),o=r(6040),i=r(5983),c=Object,s=n("".split);t.exports=o((function(){return!c("z").propertyIsEnumerable(0)}))?function(t){return"String"===i(t)?s(t,""):c(t)}:c},8460:(t,e,r)=>{"use strict";var n=r(1447),o=r(9063),i=r(9136),c=n(Function.toString);o(i.inspectSource)||(i.inspectSource=function(t){return c(t)}),t.exports=i.inspectSource},5444:(t,e,r)=>{"use strict";var n,o,i,c=r(280),s=r(5624),u=r(808),a=r(3652),f=r(6216),l=r(9136),p=r(8192),v=r(6480),y="Object already initialized",b=s.TypeError,d=s.WeakMap;if(c||l.state){var g=l.state||(l.state=new d);g.get=g.get,g.has=g.has,g.set=g.set,n=function(t,e){if(g.has(t))throw new b(y);return e.facade=t,g.set(t,e),e},o=function(t){return g.get(t)||{}},i=function(t){return g.has(t)}}else{var h=p("state");v[h]=!0,n=function(t,e){if(f(t,h))throw new b(y);return e.facade=t,a(t,h,e),e},o=function(t){return f(t,h)?t[h]:{}},i=function(t){return f(t,h)}}t.exports={set:n,get:o,has:i,enforce:function(t){return i(t)?o(t):n(t,{})},getterFor:function(t){return function(e){var r;if(!u(e)||(r=o(e)).type!==t)throw new b("Incompatible receiver, "+t+" required");return r}}}},9063:t=>{"use strict";var e="object"==typeof document&&document.all;t.exports=void 0===e&&void 0!==e?function(t){return"function"==typeof t||t===e}:function(t){return"function"==typeof t}},5272:(t,e,r)=>{"use strict";var n=r(6040),o=r(9063),i=/#|\.prototype\./,c=function(t,e){var r=u[s(t)];return r===f||r!==a&&(o(e)?n(e):!!e)},s=c.normalize=function(t){return String(t).replace(i,".").toLowerCase()},u=c.data={},a=c.NATIVE="N",f=c.POLYFILL="P";t.exports=c},952:t=>{"use strict";t.exports=function(t){return null==t}},808:(t,e,r)=>{"use strict";var n=r(9063);t.exports=function(t){return"object"==typeof t?null!==t:n(t)}},2804:t=>{"use strict";t.exports=!1},6232:(t,e,r)=>{"use strict";var n=r(4960),o=r(9063),i=r(6056),c=r(9448),s=Object;t.exports=c?function(t){return"symbol"==typeof t}:function(t){var e=n("Symbol");return o(e)&&i(e.prototype,s(t))}},9480:(t,e,r)=>{"use strict";var n=r(960);t.exports=function(t){return n(t.length)}},316:(t,e,r)=>{"use strict";var n=r(1447),o=r(6040),i=r(9063),c=r(6216),s=r(3528),u=r(3788).CONFIGURABLE,a=r(8460),f=r(5444),l=f.enforce,p=f.get,v=String,y=Object.defineProperty,b=n("".slice),d=n("".replace),g=n([].join),h=s&&!o((function(){return 8!==y((function(){}),"length",{value:8}).length})),m=String(String).split("String"),x=t.exports=function(t,e,r){"Symbol("===b(v(e),0,7)&&(e="["+d(v(e),/^Symbol\(([^)]*)\).*$/,"$1")+"]"),r&&r.getter&&(e="get "+e),r&&r.setter&&(e="set "+e),(!c(t,"name")||u&&t.name!==e)&&(s?y(t,"name",{value:e,configurable:!0}):t.name=e),h&&r&&c(r,"arity")&&t.length!==r.arity&&y(t,"length",{value:r.arity});try{r&&c(r,"constructor")&&r.constructor?s&&y(t,"prototype",{writable:!1}):t.prototype&&(t.prototype=void 0)}catch(t){}var n=l(t);return c(n,"source")||(n.source=g(m,"string"==typeof e?e:"")),t};Function.prototype.toString=x((function(){return i(this)&&p(this).source||a(this)}),"toString")},1736:t=>{"use strict";var e=Math.ceil,r=Math.floor;t.exports=Math.trunc||function(t){var n=+t;return(n>0?r:e)(n)}},7048:(t,e,r)=>{"use strict";var n=r(3528),o=r(1447),i=r(892),c=r(6040),s=r(4152),u=r(8167),a=r(2460),f=r(6804),l=r(6212),p=Object.assign,v=Object.defineProperty,y=o([].concat);t.exports=!p||c((function(){if(n&&1!==p({b:1},p(v({},"a",{enumerable:!0,get:function(){v(this,"b",{value:3,enumerable:!1})}}),{b:2})).b)return!0;var t={},e={},r=Symbol("assign detection"),o="abcdefghijklmnopqrst";return t[r]=7,o.split("").forEach((function(t){e[t]=t})),7!==p({},t)[r]||s(p({},e)).join("")!==o}))?function(t,e){for(var r=f(t),o=arguments.length,c=1,p=u.f,v=a.f;o>c;)for(var b,d=l(arguments[c++]),g=p?y(s(d),p(d)):s(d),h=g.length,m=0;h>m;)b=g[m++],n&&!i(v,d,b)||(r[b]=d[b]);return r}:p},8340:(t,e,r)=>{"use strict";var n,o=r(8424),i=r(5045),c=r(4656),s=r(6480),u=r(6836),a=r(9308),f=r(8192),l="prototype",p="script",v=f("IE_PROTO"),y=function(){},b=function(t){return"<"+p+">"+t+"</"+p+">"},d=function(t){t.write(b("")),t.close();var e=t.parentWindow.Object;return t=null,e},g=function(){try{n=new ActiveXObject("htmlfile")}catch(t){}var t,e,r;g="undefined"!=typeof document?document.domain&&n?d(n):(e=a("iframe"),r="java"+p+":",e.style.display="none",u.appendChild(e),e.src=String(r),(t=e.contentWindow.document).open(),t.write(b("document.F=Object")),t.close(),t.F):d(n);for(var o=c.length;o--;)delete g[l][c[o]];return g()};s[v]=!0,t.exports=Object.create||function(t,e){var r;return null!==t?(y[l]=o(t),r=new y,y[l]=null,r[v]=t):r=g(),void 0===e?r:i.f(r,e)}},5045:(t,e,r)=>{"use strict";var n=r(3528),o=r(4859),i=r(368),c=r(8424),s=r(9740),u=r(4152);e.f=n&&!o?Object.defineProperties:function(t,e){c(t);for(var r,n=s(e),o=u(e),a=o.length,f=0;a>f;)i.f(t,r=o[f++],n[r]);return t}},368:(t,e,r)=>{"use strict";var n=r(3528),o=r(784),i=r(4859),c=r(8424),s=r(8732),u=TypeError,a=Object.defineProperty,f=Object.getOwnPropertyDescriptor,l="enumerable",p="configurable",v="writable";e.f=n?i?function(t,e,r){if(c(t),e=s(e),c(r),"function"==typeof t&&"prototype"===e&&"value"in r&&v in r&&!r[v]){var n=f(t,e);n&&n[v]&&(t[e]=r.value,r={configurable:p in r?r[p]:n[p],enumerable:l in r?r[l]:n[l],writable:!1})}return a(t,e,r)}:a:function(t,e,r){if(c(t),e=s(e),c(r),o)try{return a(t,e,r)}catch(t){}if("get"in r||"set"in r)throw new u("Accessors not supported");return"value"in r&&(t[e]=r.value),t}},4560:(t,e,r)=>{"use strict";var n=r(3528),o=r(892),i=r(2460),c=r(9200),s=r(9740),u=r(8732),a=r(6216),f=r(784),l=Object.getOwnPropertyDescriptor;e.f=n?l:function(t,e){if(t=s(t),e=u(e),f)try{return l(t,e)}catch(t){}if(a(t,e))return c(!o(i.f,t,e),t[e])}},692:(t,e,r)=>{"use strict";var n=r(9232),o=r(4656).concat("length","prototype");e.f=Object.getOwnPropertyNames||function(t){return n(t,o)}},8167:(t,e)=>{"use strict";e.f=Object.getOwnPropertySymbols},1304:(t,e,r)=>{"use strict";var n=r(6216),o=r(9063),i=r(6804),c=r(8192),s=r(9120),u=c("IE_PROTO"),a=Object,f=a.prototype;t.exports=s?a.getPrototypeOf:function(t){var e=i(t);if(n(e,u))return e[u];var r=e.constructor;return o(r)&&e instanceof r?r.prototype:e instanceof a?f:null}},6056:(t,e,r)=>{"use strict";var n=r(1447);t.exports=n({}.isPrototypeOf)},9232:(t,e,r)=>{"use strict";var n=r(1447),o=r(6216),i=r(9740),c=r(2196).indexOf,s=r(6480),u=n([].push);t.exports=function(t,e){var r,n=i(t),a=0,f=[];for(r in n)!o(s,r)&&o(n,r)&&u(f,r);for(;e.length>a;)o(n,r=e[a++])&&(~c(f,r)||u(f,r));return f}},4152:(t,e,r)=>{"use strict";var n=r(9232),o=r(4656);t.exports=Object.keys||function(t){return n(t,o)}},2460:(t,e)=>{"use strict";var r={}.propertyIsEnumerable,n=Object.getOwnPropertyDescriptor,o=n&&!r.call({1:2},1);e.f=o?function(t){var e=n(this,t);return!!e&&e.enumerable}:r},5660:(t,e,r)=>{"use strict";var n=r(3528),o=r(6040),i=r(1447),c=r(1304),s=r(4152),u=r(9740),a=i(r(2460).f),f=i([].push),l=n&&o((function(){var t=Object.create(null);return t[2]=2,!a(t,2)})),p=function(t){return function(e){for(var r,o=u(e),i=s(o),p=l&&null===c(o),v=i.length,y=0,b=[];v>y;)r=i[y++],n&&!(p?r in o:a(o,r))||f(b,t?[r,o[r]]:o[r]);return b}};t.exports={entries:p(!0),values:p(!1)}},7664:(t,e,r)=>{"use strict";var n=r(892),o=r(9063),i=r(808),c=TypeError;t.exports=function(t,e){var r,s;if("string"===e&&o(r=t.toString)&&!i(s=n(r,t)))return s;if(o(r=t.valueOf)&&!i(s=n(r,t)))return s;if("string"!==e&&o(r=t.toString)&&!i(s=n(r,t)))return s;throw new c("Can't convert object to primitive value")}},9976:(t,e,r)=>{"use strict";var n=r(4960),o=r(1447),i=r(692),c=r(8167),s=r(8424),u=o([].concat);t.exports=n("Reflect","ownKeys")||function(t){var e=i.f(s(t)),r=c.f;return r?u(e,r(t)):e}},4880:(t,e,r)=>{"use strict";var n=r(5624);t.exports=n},2696:(t,e,r)=>{"use strict";var n=r(952),o=TypeError;t.exports=function(t){if(n(t))throw new o("Can't call method on "+t);return t}},8192:(t,e,r)=>{"use strict";var n=r(8196),o=r(320),i=n("keys");t.exports=function(t){return i[t]||(i[t]=o(t))}},9136:(t,e,r)=>{"use strict";var n=r(5624),o=r(1544),i="__core-js_shared__",c=n[i]||o(i,{});t.exports=c},8196:(t,e,r)=>{"use strict";var n=r(2804),o=r(9136);(t.exports=function(t,e){return o[t]||(o[t]=void 0!==e?e:{})})("versions",[]).push({version:"3.35.1",mode:n?"pure":"global",copyright:"© 2014-2024 Denis Pushkarev (zloirock.ru)",license:"https://github.com/zloirock/core-js/blob/v3.35.1/LICENSE",source:"https://github.com/zloirock/core-js"})},8972:(t,e,r)=>{"use strict";var n=r(3356),o=r(6040),i=r(5624).String;t.exports=!!Object.getOwnPropertySymbols&&!o((function(){var t=Symbol("symbol detection");return!i(t)||!(Object(t)instanceof Symbol)||!Symbol.sham&&n&&n<41}))},4160:(t,e,r)=>{"use strict";var n=r(3288),o=Math.max,i=Math.min;t.exports=function(t,e){var r=n(t);return r<0?o(r+e,0):i(r,e)}},9740:(t,e,r)=>{"use strict";var n=r(6212),o=r(2696);t.exports=function(t){return n(o(t))}},3288:(t,e,r)=>{"use strict";var n=r(1736);t.exports=function(t){var e=+t;return e!=e||0===e?0:n(e)}},960:(t,e,r)=>{"use strict";var n=r(3288),o=Math.min;t.exports=function(t){var e=n(t);return e>0?o(e,9007199254740991):0}},6804:(t,e,r)=>{"use strict";var n=r(2696),o=Object;t.exports=function(t){return o(n(t))}},8176:(t,e,r)=>{"use strict";var n=r(892),o=r(808),i=r(6232),c=r(364),s=r(7664),u=r(1840),a=TypeError,f=u("toPrimitive");t.exports=function(t,e){if(!o(t)||i(t))return t;var r,u=c(t,f);if(u){if(void 0===e&&(e="default"),r=n(u,t,e),!o(r)||i(r))return r;throw new a("Can't convert object to primitive value")}return void 0===e&&(e="number"),s(t,e)}},8732:(t,e,r)=>{"use strict";var n=r(8176),o=r(6232);t.exports=function(t){var e=n(t,"string");return o(e)?e:e+""}},4596:t=>{"use strict";var e=String;t.exports=function(t){try{return e(t)}catch(t){return"Object"}}},320:(t,e,r)=>{"use strict";var n=r(1447),o=0,i=Math.random(),c=n(1..toString);t.exports=function(t){return"Symbol("+(void 0===t?"":t)+")_"+c(++o+i,36)}},9448:(t,e,r)=>{"use strict";var n=r(8972);t.exports=n&&!Symbol.sham&&"symbol"==typeof Symbol.iterator},4859:(t,e,r)=>{"use strict";var n=r(3528),o=r(6040);t.exports=n&&o((function(){return 42!==Object.defineProperty((function(){}),"prototype",{value:42,writable:!1}).prototype}))},280:(t,e,r)=>{"use strict";var n=r(5624),o=r(9063),i=n.WeakMap;t.exports=o(i)&&/native code/.test(String(i))},1840:(t,e,r)=>{"use strict";var n=r(5624),o=r(8196),i=r(6216),c=r(320),s=r(8972),u=r(9448),a=n.Symbol,f=o("wks"),l=u?a.for||a:a&&a.withoutSetter||c;t.exports=function(t){return i(f,t)||(f[t]=s&&i(a,t)?a[t]:l("Symbol."+t)),f[t]}},2452:(t,e,r)=>{"use strict";var n=r(3748),o=r(2196).includes,i=r(6040),c=r(2328);n({target:"Array",proto:!0,forced:i((function(){return!Array(1).includes()}))},{includes:function(t){return o(this,t,arguments.length>1?arguments[1]:void 0)}}),c("includes")},8235:(t,e,r)=>{"use strict";var n=r(3748),o=r(7048);n({target:"Object",stat:!0,arity:2,forced:Object.assign!==o},{assign:o})},1088:(t,e,r)=>{"use strict";var n=r(3748),o=r(5660).entries;n({target:"Object",stat:!0},{entries:function(t){return o(t)}})},8332:(t,e,r)=>{"use strict";var n=r(3748),o=r(5660).values;n({target:"Object",stat:!0},{values:function(t){return o(t)}})}},e={};function r(n){var o=e[n];if(void 0!==o)return o.exports;var i=e[n]={exports:{}};return t[n].call(i.exports,i,i.exports,r),i.exports}r.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return r.d(e,{a:e}),e},r.d=(t,e)=>{for(var n in e)r.o(e,n)&&!r.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:e[n]})},r.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(t){if("object"==typeof window)return window}}(),r.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),(()=>{"use strict";r(5872),r(8472),r(4060),r(7580);var t=r(6272),e=r.n(t),n=window.jQuery;function o(t){return n(t).is(":checked")}function i(t,e){n(t).parent().after('<span class="gpasc-manage-settings-link"><a href="#'.concat(e,'">Manage Settings</a></span>'))}!function(){for(var t=function(t,e){o(t)&&i(t,e),n(t).on("change",(function(){o(t)?i(t,e):n(t).parent().siblings(".gpasc-manage-settings-link").remove()}))},r=0,c=[{selector:"#_gform_setting_auto_save_and_load_enabled",anchor:"gform-settings-section-auto-save-and-load-settings"},{selector:"#_gform_setting_draft_management_enabled",anchor:"gform-settings-section-draft-management-settings"}];r<c.length;r++){var s=c[r];t(s.selector,s.anchor)}!function(){var t=this,r=n("#gpasc-copy-shortcode-button"),o=function(t){n("#gpasc-copy-shortcode-button > .gpasc-shortcode-copy-button-text").text(t)};r.on("click",(function(r){return i=t,c=void 0,u=function(){var t;return function(t,e){var r,n,o,i,c={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return i={next:s(0),throw:s(1),return:s(2)},"function"==typeof Symbol&&(i[Symbol.iterator]=function(){return this}),i;function s(s){return function(u){return function(s){if(r)throw new TypeError("Generator is already executing.");for(;i&&(i=0,s[0]&&(c=0)),c;)try{if(r=1,n&&(o=2&s[0]?n.return:s[0]?n.throw||((o=n.return)&&o.call(n),0):n.next)&&!(o=o.call(n,s[1])).done)return o;switch(n=0,o&&(s=[2&s[0],o.value]),s[0]){case 0:case 1:o=s;break;case 4:return c.label++,{value:s[1],done:!1};case 5:c.label++,n=s[1],s=[0];continue;case 7:s=c.ops.pop(),c.trys.pop();continue;default:if(!((o=(o=c.trys).length>0&&o[o.length-1])||6!==s[0]&&2!==s[0])){c=0;continue}if(3===s[0]&&(!o||s[1]>o[0]&&s[1]<o[3])){c.label=s[1];break}if(6===s[0]&&c.label<o[1]){c.label=o[1],o=s;break}if(o&&c.label<o[2]){c.label=o[2],c.ops.push(s);break}o[2]&&c.ops.pop(),c.trys.pop();continue}s=e.call(t,c)}catch(t){s=[6,t],n=0}finally{r=o=0}if(5&s[0])throw s[1];return{value:s[0]?s[1]:void 0,done:!0}}([s,u])}}}(this,(function(i){r.preventDefault();try{t=n("#gpasc-copy-shortcode-text").val()||"",e()(String(t)),o("Copied!"),setTimeout((function(){o("Copy Shortcode")}),1e3)}catch(t){console.error(t)}return[2]}))},new((s=void 0)||(s=Promise))((function(t,e){function r(t){try{o(u.next(t))}catch(t){e(t)}}function n(t){try{o(u.throw(t))}catch(t){e(t)}}function o(e){var o;e.done?t(e.value):(o=e.value,o instanceof s?o:new s((function(t){t(o)}))).then(r,n)}o((u=u.apply(i,c||[])).next())}));var i,c,s,u}))}()}()})()})();
//# sourceMappingURL=gp-advanced-save-and-continue-form-settings.js.map