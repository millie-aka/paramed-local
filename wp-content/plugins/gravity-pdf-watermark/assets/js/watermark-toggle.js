/**
 * PDF Settings
 * Dependencies: jQuery
 */

(function ($) {
  $(function () {
    var $checkbox = $('#gfpdf_settings\\[watermark_toggle\\]')
    var $fields = $('.gfpdf-watermark')

    $checkbox.on('click', function () {
      $fields.toggle()
    })

    if ($checkbox.is(':checked')) {
      $fields.show()
    } else {
      $fields.hide()
    }
  })
})(jQuery)
