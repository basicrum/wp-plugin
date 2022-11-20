<?php

if ( ! defined( 'ABSPATH' ) ){
    exit;
}
function basicrum_code() {
// On plugin install, all values doesn't exist in db. That's why we do a check and get values from function basicrum_options_default()

    if ( !isset( get_option('basicrum_options')['url_to_send_data'] ) ) {
        $url = basicrum_options_default()['url_to_send_data'];
    } else {
        $url = get_option('basicrum_options')['url_to_send_data'];
    }

    if ( !isset( get_option('basicrum_options')['delay_sending_data'] ) ) {
        $milliseconds = basicrum_options_default()['delay_sending_data'];
    } else {
        $milliseconds = get_option('basicrum_options')['delay_sending_data'];
    }

    if ( !isset( get_option('basicrum_options')['monitoring_type'] ) ) {
        $boomerang_version = basicrum_options_default()['monitoring_type'];
    } else {
        $boomerang_version = get_option('basicrum_options')['monitoring_type'];
    }

    $boomerangConfig = <<<EOT
<script data-cfasync="false">
(function(w) {
    var WAIT_AFTER_ONLOAD_MILLISECONDS = {{$milliseconds}};

    if (!w) {
        return;
    }

    BOOMR = w.BOOMR || {};
    BOOMR.plugins = BOOMR.plugins || {};

    BOOMR.plugins.WaitAfterOnload = {
        complete: false,

        init: function() {
            BOOMR.subscribe("page_ready", function() {
                setTimeout(function() {
                    this.complete = true;
                    BOOMR.sendBeacon();
                }.bind(this), WAIT_AFTER_ONLOAD_MILLISECONDS);
            }, {}, this);
        },

        is_complete: function() {
            return this.complete;
        }
    };

    w.basicRumBoomerangConfig = {
        beacon_url: "{{$url}}",
        instrument_xhr: true,
        Continuity: {
            enabled: true
        },
        ResourceTiming: {
            "enabled": true,
            "splitAtPath": true
        }
    }
})(window);
</script>
EOT;

    $boomerangUrl = plugin_dir_url(__FILE__) . "boomerang-". $boomerang_version .".cutting-edge.min.js";
    $loader = '(function(){if(window.BOOMR&&(window.BOOMR.version||window.BOOMR.snippetExecuted)){return}window.BOOMR=window.BOOMR||{};window.BOOMR.snippetStart=(new Date).getTime();window.BOOMR.snippetExecuted=true;window.BOOMR.snippetVersion=14;window.BOOMR.url="' . $boomerangUrl . '";var e=document.currentScript||document.getElementsByTagName("script")[0],a=e.parentNode,s=false,t=3e3;function n(){if(s){return}var e=document.createElement("script");e.id="boomr-scr-as";e.src=window.BOOMR.url;e.async=true;a.appendChild(e);s=true}function o(e){s=true;var t,o=document,n,i,d,r=window;window.BOOMR.snippetMethod=e?"if":"i";n=function(e,t){var n=o.createElement("script");n.id=t||"boomr-if-as";n.src=window.BOOMR.url;BOOMR_lstart=(new Date).getTime();e=e||o.body;e.appendChild(n)};if(!window.addEventListener&&window.attachEvent&&navigator.userAgent.match(/MSIE [67]\./)){window.BOOMR.snippetMethod="s";n(a,"boomr-async");return}i=document.createElement("IFRAME");i.src="about:blank";i.title="";i.role="presentation";i.loading="eager";d=(i.frameElement||i).style;d.width=0;d.height=0;d.border=0;d.display="none";a.appendChild(i);try{r=i.contentWindow;o=r.document.open()}catch(e){t=document.domain;i.src="javascript:var d=document.open();d.domain=\'"+t+"\';void 0;";r=i.contentWindow;o=r.document.open()}if(t){o._boomrl=function(){this.domain=t;n()};o.write("<bo"+"dy onload=\'document._boomrl();\'>")}else{r._boomrl=function(){n()};if(r.addEventListener){r.addEventListener("load",r._boomrl,false)}else if(r.attachEvent){r.attachEvent("onload",r._boomrl)}}o.close()}var i=document.createElement("link");if(i.relList&&typeof i.relList.supports==="function"&&i.relList.supports("preload")&&"as"in i){window.BOOMR.snippetMethod="p";i.href=window.BOOMR.url;i.rel="preload";i.as="script";i.addEventListener("load",n);i.addEventListener("error",function(){o(true)});setTimeout(function(){if(!s){o(true)}},t);BOOMR_lstart=(new Date).getTime();a.appendChild(i)}else{o(false)}function d(e){window.BOOMR_onload=e&&e.timeStamp||(new Date).getTime()}if(window.addEventListener){window.addEventListener("load",d,false)}else if(window.attachEvent){window.attachEvent("onload",d)}})();';

    // Output the scripts
    echo $boomerangConfig;
    echo '<script data-cfasync="false">' . $loader . '</script>';
}

if ( !isset( get_option('basicrum_options')['script_position'] ) ) {
    $action = basicrum_options_default()['script_position'];
} else {
    $action = get_option('basicrum_options')['script_position'];
}

add_action( $action, 'basicrum_code');