if(!com) var com={};
if(!com.licorize) com.licorize={};
if(!com.licorize.wp_plugin) com.licorize.wp_plugin={};

com.licorize.wp_plugin = {
  __lic_domain: "licorize.com",
  __lic_dynamicServer : "",
  __lic_staticServer : "",
  __lic_screenWidth : 600,
  __lic_screenHeight : 400,
  __lic_initialized : false,
  __lic_pendingData: {},

  init: function() {
    this.__lic_dynamicServer = window.location.protocol + "//" + com.licorize.wp_plugin.__lic_domain;
    this.__lic_staticServer = this.__lic_dynamicServer + "/static";

	window.$lic = window.jQuery;

	$lic.fn.mb_bringToFront = function(zIndexContext) {
		var zi = 10;
		var els = zIndexContext && zIndexContext != "auto" ? $lic(zIndexContext) : $lic("*");
		els.not(".alwaysOnTop").each(function() {
			if ($lic(this).css("position") == "absolute" || $lic(this).css("position") == "fixed") {
				var cur = parseInt($lic(this).css('zIndex'));
				zi = cur > zi ? parseInt($lic(this).css('zIndex')) : zi;
			}
		});
		$lic(this).not(".alwaysOnTop").css('zIndex', zi += 1);
		return zi;
	};

	// CLOSE LICORIZE IFRAME
	// HTML5 support needed
	var onmessage = function(e) {
		if(e.origin !== com.licorize.wp_plugin.__lic_dynamicServer)
			return;

		if ('closeLicIframe' == e.data)
			$lic("#lic__close").click();
		else if ('LICORIZE_NEED_LOGIN' == e.data) {
			com.licorize.wp_plugin.update(com.licorize.wp_plugin.__lic_pendingData);
		} else {
			var resp = $lic.parseJSON(e.data)
			if("savedLicStrip" == resp.message)
				com.licorize.wp_plugin.showSavedMessage(resp.messageTxt);
		}
	};

	var isIE = $lic.browser.msie;
	var isIE_7 = isIE && parseFloat($lic.browser.version) < 8;

	if (!isIE_7) {
		if (typeof window.addEventListener != 'undefined') {
			window.addEventListener('message', onmessage, false);
		} else if (typeof window.attachEvent != 'undefined') {
			window.attachEvent('onmessage', onmessage);
		}
	}

	com.licorize.wp_plugin.__lic_initialized = true;
  },

  autoUpdate : function(tag, params) {
    $lic(".licorizeAutoUpdateForm").remove();

	com.licorize.wp_plugin.__lic_pendingData = params;

    var iframe;
    if ($lic.browser.msie) {
      iframe = $lic("<iframe frameborder='0' name='__licorizeIfrAutoUpdate' src='about:blank'>");
    } else {
      iframe = $lic("<iframe>");
      iframe.attr("name", '__licorizeIfrAutoUpdate');
      iframe.attr("src",'about:blank');
    }
    iframe.attr("id", "__licorizeIfrAutoUpdate").css({display:"none"});
    $lic("body").append(iframe);
    
    params.id = 0;
    params.tags = ( undefined == tag || tag == "") ? "remindMeLater" : tag;
    params.type = "REMIND_ME_LATER";
    params.notes = params.selection;
    params.selection = "";
    params.CM = "SV";
    params.licorizeAutoUpdate = "yes";
    params.t = (new Date()).getTime();

    var form = $lic("<form />").attr("id", "__licorizeIfrAutoUpdateForm")
            .attr("action", com.licorize.wp_plugin.__lic_dynamicServer + "/applications/licorize/manage/addStripBkg.jsp")
            .attr("target", "__licorizeIfrAutoUpdate")
            .attr("method", "POST")
            .css({display:"none"})
            .addClass("licorizeAutoUpdateForm");

    $lic.each(params, function(key, value) {
      var ele = $lic("<input>").attr("type", "hidden").attr("name", key).attr("value", value);
      form.append(ele);
    });
    $lic("body").append(form);
    form.submit();
  },

  update : function(data) {
    $lic(".licorizeAutoUpdateForm").remove();

    com.licorize.wp_plugin.__lic_pendingData = data;
    data.bookmarklet="yes";
    data.type="BOOKMARK";
    
    var div = $lic("#lic__licorizeDiv");
    if (div.size() <= 0) {
      var mode = (document.compatMode == 'CSS1Compat') ? 'Standards' : 'Quirks';

      var newDiv = $lic("<div>");
      div.css("background-color", "red");
      newDiv.attr("id", "lic__licorizeDiv").css({"position":mode == "Quirks" ? "absolute" : "fixed","top":0,"right":50,"height":0, "width":com.licorize.wp_plugin.__lic_screenWidth,"z-index":10000});

      var iframe;
      if ($lic.browser.msie) {
        iframe = $lic("<iframe frameborder='0' name='__licorizeIfr' src='about:blank'>").attr("id", "__licorizeIfr").css({width:"100%",height:"100%", border:"none"});
      } else {
        iframe = $lic("<iframe>");
        iframe.attr("name", '__licorizeIfr');
        iframe.attr("src",'about:blank').attr("id", "__licorizeIfr").css({width:"100%",height:"100%", border:"none"});
      }
      newDiv.append(iframe);

      var close = $lic("<div>").attr("id", "lic__close");
      close.click(function() {
        $lic("#lic__licorizeDiv").animate({height:0}, 200, function() {
          $lic(this).remove();
        });
      }).css({background:"url(" + com.licorize.wp_plugin.__lic_staticServer + "/images/closeBig.png) no-repeat", position:"absolute", cursor:"pointer"})
              .attr("title", "close Licorize")
              .hover();
      newDiv.append(close);

      $lic("body").append(newDiv);

      newDiv.animate({height:com.licorize.wp_plugin.__lic_screenHeight}, 200).mb_bringToFront();
    }

    var form = $lic("<form />").attr("id", "__licorizeIfrAutoUpdateForm")
            .attr("action", com.licorize.wp_plugin.__lic_dynamicServer + "/applications/licorize/manage/addStrip.jsp")
            .attr("target", "__licorizeIfr")
            .attr("method", "POST")
            .css({display:"none"})
            .addClass("licorizeAutoUpdateForm");

    $lic.each(data, function(key, value) {
      var ele = $lic("<input>").attr("type", "hidden").attr("name", key).attr("value", value);
      form.append(ele);
    });
    $lic("body").append(form);
    form.submit();    
  },

  showSavedMessage: function(message) {
    var messageDiv = $lic("<div />").attr("id","lic_messageBox").css("display", "none");
    messageDiv.append(message);
    $lic("body").append(messageDiv);
    $lic("#lic_messageBox").fadeIn().delay(700).slideUp(300, function() { $lic(this).remove() });
  },
  
  licorize_it: function(action, url, title, keywords) {
  	var data = {
      url: url,
      title: title,
      keywords: keywords,
      selection: '',
      images: '',
      haccas: ''
    };
    
    if("REMIND" == action) {
    	com.licorize.wp_plugin.autoUpdate("", data);
    } else {
    	com.licorize.wp_plugin.update(data);
    }
  }
};

com.licorize.wp_plugin.init();