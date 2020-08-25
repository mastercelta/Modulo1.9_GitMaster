


(function(){var a = false, b = /xyz/.test(function(){xyz})?/\b_super\b/:/.*/; this.PortholeClass = function(){}; PortholeClass.extend = function(g){var f = this.prototype; a = true; var e = new this(); a = false; for (var d in g){e[d] = typeof g[d] == "function" && typeof f[d] == "function" && b.test(g[d])?(function(h, i){return function(){var k = this._super; this._super = f[h]; var j = i.apply(this, arguments); this._super = k; return j}})(d, g[d]):g[d]}function c(){if (!a && this.init){this.init.apply(this, arguments)}}c.prototype = e; c.prototype.constructor = c; c.extend = arguments.callee; return c}})(); (function(c){var b = {debug:false, trace:function(d){if (this.debug && c.console !== undefined){c.console.log("Porthole: " + d)}}, error:function(d){if (c.console !== undefined){c.console.error("Porthole: " + d)}}}; b.WindowProxy = function(){}; b.WindowProxy.prototype = {post:function(e, d){}, addEventListener:function(d){}, removeEventListener:function(d){}}; b.WindowProxyBase = PortholeClass.extend({init:function(d){if (d === undefined){d = ""}this.targetWindowName = d; this.origin = c.location.protocol + "//" + c.location.host; this.eventListeners = []}, getTargetWindowName:function(){return this.targetWindowName}, getOrigin:function(){return this.origin}, getTargetWindow:function(){return b.WindowProxy.getTargetWindow(this.targetWindowName)}, post:function(e, d){if (d === undefined){d = "*"}this.dispatchMessage({data:e, sourceOrigin:this.getOrigin(), targetOrigin:d, sourceWindowName:c.name, targetWindowName:this.getTargetWindowName()})}, addEventListener:function(d){this.eventListeners.push(d); return d}, removeEventListener:function(g){var d; try{d = this.eventListeners.indexOf(g); this.eventListeners.splice(d, 1)} catch (h){this.eventListeners = []}}, dispatchEvent:function(f){var d; for (d = 0; d < this.eventListeners.length; d++){try{this.eventListeners[d](f)} catch (g){}}}}); b.WindowProxyLegacy = b.WindowProxyBase.extend({init:function(d, e){this._super(e); if (d !== null){this.proxyIFrameName = this.targetWindowName + "ProxyIFrame"; this.proxyIFrameLocation = d; this.proxyIFrameElement = this.createIFrameProxy()} else{this.proxyIFrameElement = null; b.trace("proxyIFrameUrl is null, window will be a receiver only"); this.post = function(){throw new Error("Receiver only window")}}}, createIFrameProxy:function(){var d = document.createElement("iframe"); d.setAttribute("id", this.proxyIFrameName); d.setAttribute("name", this.proxyIFrameName); d.setAttribute("src", this.proxyIFrameLocation); d.setAttribute("frameBorder", "1"); d.setAttribute("scrolling", "auto"); d.setAttribute("width", 30); d.setAttribute("height", 30); d.setAttribute("style", "position: absolute; left: -100px; top:0px;"); if (d.style.setAttribute){d.style.setAttribute("cssText", "position: absolute; left: -100px; top:0px;")}document.body.appendChild(d); return d}, dispatchMessage:function(e){var d = c.encodeURIComponent; if (this.proxyIFrameElement){var f = this.proxyIFrameLocation + "#" + d(b.WindowProxy.serialize(e)); this.proxyIFrameElement.setAttribute("src", f); this.proxyIFrameElement.height = this.proxyIFrameElement.height > 50?50:100}}}); b.WindowProxyHTML5 = b.WindowProxyBase.extend({init:function(d, e){this._super(e); this.eventListenerCallback = null}, dispatchMessage:function(d){this.getTargetWindow().postMessage(b.WindowProxy.serialize(d), d.targetOrigin)}, addEventListener:function(e){if (this.eventListeners.length === 0){var d = this; if (c.addEventListener){this.eventListenerCallback = function(f){d.eventListener(d, f)}; c.addEventListener("message", this.eventListenerCallback, false)} else{if (c.attachEvent){this.eventListenerCallback = function(f){d.eventListener(d, c.event)}; c.attachEvent("onmessage", this.eventListenerCallback)}}}return this._super(e)}, removeEventListener:function(d){this._super(d); if (this.eventListeners.length === 0){if (c.removeEventListener){c.removeEventListener("message", this.eventListenerCallback)} else{if (c.detachEvent){if (typeof c.onmessage === "undefined"){c.onmessage = null}c.detachEvent("onmessage", this.eventListenerCallback)}}this.eventListenerCallback = null}}, eventListener:function(e, d){var f = b.WindowProxy.unserialize(d.data); if (f && (e.targetWindowName === "" || f.sourceWindowName == e.targetWindowName)){e.dispatchEvent(new b.MessageEvent(f.data, d.origin, e))}}}); if (!c.postMessage){b.trace("Using legacy browser support"); b.WindowProxy = b.WindowProxyLegacy.extend({})} else{b.trace("Using built-in browser support"); b.WindowProxy = b.WindowProxyHTML5.extend({})}b.WindowProxy.serialize = function(d){if (typeof JSON === "undefined"){throw new Error("Porthole serialization depends on JSON!")}return JSON.stringify(d)}; b.WindowProxy.unserialize = function(g){if (typeof JSON === "undefined"){throw new Error("Porthole unserialization dependens on JSON!")}try{var d = JSON.parse(g)} catch (f){return false}return d}; b.WindowProxy.getTargetWindow = function(d){if (d === ""){return parent} else{if (d === "top" || d === "parent"){return c[d]}}return c.frames[d]}; b.MessageEvent = function a(f, d, e){this.data = f; this.origin = d; this.source = e}; b.WindowProxyDispatcher = {forwardMessageEvent:function(i){var g, h = c.decodeURIComponent, f, d; if (document.location.hash.length > 0){g = b.WindowProxy.unserialize(h(document.location.hash.substr(1))); f = b.WindowProxy.getTargetWindow(g.targetWindowName); d = b.WindowProxyDispatcher.findWindowProxyObjectInWindow(f, g.sourceWindowName); if (d){if (d.origin === g.targetOrigin || g.targetOrigin === "*"){d.dispatchEvent(new b.MessageEvent(g.data, g.sourceOrigin, d))} else{b.error("Target origin " + d.origin + " does not match desired target of " + g.targetOrigin)}} else{b.error("Could not find window proxy object on the target window")}}}, findWindowProxyObjectInWindow:function(d, g){var f; if (d){for (f in d){if (Object.prototype.hasOwnProperty.call(d, f)){try{if (d[f] !== null && typeof d[f] === "object" && d[f] instanceof d.Porthole.WindowProxy && d[f].getTargetWindowName() === g){return d[f]}} catch (h){}}}}return null}, start:function(){if (c.addEventListener){c.addEventListener("resize", b.WindowProxyDispatcher.forwardMessageEvent, false)} else{if (c.attachEvent && c.postMessage !== "undefined"){c.attachEvent("onresize", b.WindowProxyDispatcher.forwardMessageEvent)} else{if (document.body.attachEvent){c.attachEvent("onresize", b.WindowProxyDispatcher.forwardMessageEvent)} else{b.error("Cannot attach resize event")}}}}}; if (typeof c.exports !== "undefined"){c.exports.Porthole = b} else{c.Porthole = b}})(this);

!function(e){var n=null,a=null;e.fn.extend({createWebpay:function(n){try{var t=a=e.extend(e.fn.createWebpay.options,n);if(e.fn.isNullorEmpty(t.url))throw"The Url Parameter is empty";e.fn.isDefined(a.success)&&(t.params+="&callbacksuccess=true"),e.fn.isDefined(a.cancel)&&(t.params+="&callbackcancel=true"),a.sessionToken&&(t.params+="&sessiontoken=true");var s=this;e.fn.createiframe(s,t)}catch(r){e.fn.error(r)}},changeAmount:function(a){e(".Ã¯framecenpos").length&&n.post({amount:a})},submitAction:function(){e(".Ã¯framecenpos").length&&n.post({submit:!0})}}),e.fn.createWebpay.options={url:"",params:"",width:"100%",height:"450",success:void 0,cancel:void 0,sessionToken:!1},e.fn.error=function(n){e.fn.isNullorEmpty(n.message)?e.fn.isNullorEmpty(n.description)?alert(n):alert("ExcepciÃ³n: "+n.description):alert("ExcepciÃ³n: "+n.message)},e.fn.isDefined=function(e){return"undefined"!=typeof window[e]?!0:"function"==typeof e?!0:!1},e.fn.isNullorEmpty=function(e){return void 0!=e&&""!=e&&null!=e?!1:!0},e.fn.createiframe=function(a,t){var s=window.location.hash;if(t.ispost&&""!=s){var r=s.split("--param=");e.fn.isNullorEmpty(r[1])||(t.params=r[1])}var c=t.url+"?"+t.params;e(a).html("<iframe frameBorder='0'  id='cenposPayIFrameId' name='cenposPayIFrameId'  class='Ã¯framecenpos'  src='"+c+"' width='"+t.width+"' height='"+t.height+"' ></iframe>"),n=new Porthole.WindowProxy(t.url.toString().replace("default.aspx","")+"proxy.html","cenposPayIFrameId"),n.addEventListener(e.fn.onMessage1)},e.fn.getUrlVars=function(){var e={};window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(n,a,t){e[a]=t});return e},e.fn.onMessage1=function(n){n.data.callbacksuccess&&e.fn.isDefined(a.success)?a.success(n.data.transaction):n.data.callbackcancel&&e.fn.isDefined(a.cancel)&&a.cancel(n.data.transaction)}}(jQuery);

jQuery.noConflict();
var Activate3dSecure = false;
var CardinalVO = "";
jQuery(document).ready(function () {
    if(Activate3dSecure) CardinalToken();
});
 function CardinalToken(){
    var $ = jQuery;
    CardinalVO = $.parseJSON(CardinalVO);
    var form = document.createElement("form");
    $(form).attr("id", "cardinalform").attr("name", "Secure3d").attr("action", CardinalVO.ACSUrl).attr("method", "post")
    $(form).attr("target", "Secure3d");

    $(form).append('<input type="hidden" name="PaReq" value="' + CardinalVO.Payload + '" />');
    $(form).append('<input type="hidden" name="TermUrl" value="' + CardinalVO.UrlReturnCardinal + '" />');
    $(form).append('<input type="hidden" name="MD" value="' + CardinalVO.TransactionID + '" />');

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
 }
 
 function RedirectSuccessCardinal(element){
    var $ = jQuery;
    var url = element.contentWindow.location;
    if(url.toString().indexOf("response3dsecure") > 0){
        //$J(element).hide();
        //// parent.location.href= url;
    }
 }
 
 function SendToken(url, urlimage){
    var $ = jQuery;
     $.ajax({
        type: "POST",
        url: url,
        data: {save: true},
        beforeSend:function(){
            urlimage = urlimage.replace("index.php/image", "skin/frontend/base/default/images/simplewebpay/") + "loader.gif";
            $("#payment-progress-opcheckout").append("<div id='loadersavecard' style='background-color: rgba(255,255,255,0.5);width:100%;position: relative;z-index: 100;top: 0;height: 130px;margin-top: -120px;'><img style='display: block;margin: 28px 0 0 71px;float: left;' src='"+urlimage+"' /></div>");
        },
        success:function(msg){
            $("#loadersavecard").remove();
            msg = $.parseJSON(msg);
            if(msg.Result === 0){
                $("#SendTokenClick").hide();
            }else if(msg.Result === 102){
                $("#SendTokenClick").attr("style","color:#000;text-decoration: inherit !important;");
                $("#SendTokenClick").removeAttr("onclick");
                $("#SendTokenClick").html(msg.Message);
            }else{
                alert(msg.Message);
            }
        }
    });
 }