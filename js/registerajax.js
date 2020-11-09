/**
* @file
*/

(function ($, Drupal) {
Drupal.AjaxCommands.prototype.registerComplete = function (ajax, response, status) {
  $message = "Congratulations, account created successfully <br>" +
  "- The username is " + response.register_fields.name + "<br>" +
  "- Use the username as password but you can change any time. <br>" +
  "- This page will be reload in 10 seconds, thanks.";
  jQuery(".multistep-form input.button").prop('disabled', true);
  jQuery(".region.region-highlighted").append('<div class="messages__wrapper layout-container"><div role="contentinfo" class="messages messages--status">' + $message + '</div></div>');

  setTimeout(function(){
    location.reload();
    }, 10000);
}

})(jQuery, Drupal);
