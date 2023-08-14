!function(e){var t={};function n(a){if(t[a])return t[a].exports;var r=t[a]={i:a,l:!1,exports:{}};return e[a].call(r.exports,r,r.exports,n),r.l=!0,r.exports}n.m=e,n.c=t,n.d=function(e,t,a){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:a})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var a=Object.create(null);if(n.r(a),Object.defineProperty(a,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(a,r,function(t){return e[t]}.bind(null,r));return a},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=8)}([function(e,t){e.exports=window.wp.element},function(e,t){e.exports=window.wp.i18n},function(e,t){e.exports=window.wc.wcBlocksRegistry},function(e,t){e.exports=window.wc.wcSettings},function(e,t){e.exports=window.wp.htmlEntities},,,,function(e,t,n){"use strict";n.r(t);var a,r,o,c=n(0),i=(n(4),n(1)),l=n(2),u=n(3),d=function(){var e=Object(u.getSetting)("clearpay_data",null);if(!e)throw new Error("Clearpay initialization data is not available");return e},p=function(e){var t;return"GB"!=(null===(t=d())||void 0===t?void 0:t.country)?Object(c.createElement)(m,e):Object(c.createElement)(f,e)},f=function(e){var t,n,a=null===(t=d())||void 0===t?void 0:t.testmode,r={target:"#afterpay-widget-container",locale:null===(n=d())||void 0===n?void 0:n.locale,amount:{amount:(e.billing.cartTotal.value/Math.pow(10,e.billing.currency.minorUnit)).toString(),currency:e.billing.currency.code}};return Object(c.useEffect)((function(){if("undefined"!=typeof AfterPay){var e=document.createElement("script");e.innerHTML="window.afterpayWidget = new AfterPay.Widgets.PaymentSchedule(".concat(JSON.stringify(r),")"),document.body.appendChild(e)}}),[]),Object(c.useEffect)((function(){window.afterpayWidget&&window.afterpayWidget.update({amount:r.amount})}),[e.billing.cartTotal.value]),Object(c.createElement)("div",null,"production"!=a&&Object(c.createElement)("p",{className:"clearpay-test-mode-warning-text"},"TEST MODE ENABLED"),Object(c.createElement)("div",{id:"afterpay-widget-container"}))},m=function(e){var t=d(),n=t.testmode,a=t.locale,r=e.billing,o=r.cartTotal,l=r.currency,u=o.value/Math.pow(10,l.minorUnit),p=l.prefix+u.toLocaleString(a.replace("_","-"),{minimumFractionDigits:l.minorUnit})+l.suffix,f="EUR"===l.code?Object(i.__)("Three","woo_clearpay"):Object(i.__)("Four","woo_clearpay"),m=Object(i.__)("%s interest-free payments totalling","woo_clearpay"),s=Object(i.sprintf)(m,f);return Object(c.useEffect)((function(){var e=document.createElement("script");e.innerHTML="\n\t\t\twindow.afterpayPlacement = new Afterpay.AfterpayPlacement();\n\t\t\twindow.afterpayPlacement.type = 'price-table';\n\t\t\twindow.afterpayPlacement.amount = '".concat(u,"';\n\t\t\twindow.afterpayPlacement.locale = '").concat(a,"';\n\t\t\twindow.afterpayPlacement.currency = '").concat(l.code,"';\n\t\t\twindow.afterpayPlacement.priceTableTheme = 'white';\n\t\t\tdocument.querySelector('#clearpay-checkout-instalment-info-container .instalment-wrapper').appendChild(window.afterpayPlacement);\n\t\t"),document.body.appendChild(e)}),[]),Object(c.useEffect)((function(){window.afterpayPlacement&&(window.afterpayPlacement.amount=u)}),[u]),Object(c.createElement)("div",null,"production"!=n&&Object(c.createElement)("p",{className:"clearpay-test-mode-warning-text"},"TEST MODE ENABLED"),Object(c.createElement)("div",{className:"instalment-info-container",id:"clearpay-checkout-instalment-info-container"},Object(c.createElement)("p",{className:"header-text"},s," ",Object(c.createElement)("strong",null,p)),Object(c.createElement)("div",{className:"instalment-wrapper"})))};Object(l.registerPaymentMethod)({name:"clearpay",label:Object(c.createElement)("img",{src:null===(a=d())||void 0===a?void 0:a.logo_url,alt:Object(i.__)("Clearpay","woo_clearpay")}),ariaLabel:Object(i.__)("Clearpay payment method","woo_clearpay"),canMakePayment:function(e){var t,n,a=e.cartTotals,r=Math.max("1.00",parseFloat(null===(t=d())||void 0===t?void 0:t.min)),o=parseFloat(null===(n=d())||void 0===n?void 0:n.max),c=parseFloat(a.total_price)/Math.pow(10,a.currency_minor_unit);return c>=r&&c<=o},content:Object(c.createElement)(p,null),edit:Object(c.createElement)(p,null),supports:{features:null!==(r=null===(o=d())||void 0===o?void 0:o.supports)&&void 0!==r?r:[]}})}]);