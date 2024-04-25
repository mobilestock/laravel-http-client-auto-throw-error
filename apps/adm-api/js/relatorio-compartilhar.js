$(document).ready(function () {
   $(document).on("click", '.whatsapp', function () {
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        var sText = "Text to share";
        var sUrl = "Link to share";
        var sMsg = encodeURIComponent(sText) + " - " + encodeURIComponent(sUrl);
        var whatsapp_url = "whatsapp://send?text=" + sMsg;
        window.location.href = whatsapp_url;
     }
     else {
        alert("Whatsapp client not available.");
     }
  });
});
