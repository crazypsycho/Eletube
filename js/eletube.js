(function($) {
  'use strict'

  let eletubeOverlayIsRemoved = false


  $(function() {
    if (localStorage.eletubeAcceptAlways == '1') {
      removeEletubeOverlay()
    }
    markItemAsCurrent()
  })


  $(document).on('click', '.eletube-unlock-video', unlockVideo)
  $(document).on('click', '.eletube-item', changeVideo)

  function removeEletubeOverlay() {
    let $video = $('.eletube-video-iframe')
    $('.eletube-video-overlay').remove()
    $video.prop('src', $video.data('src'))
    eletubeOverlayIsRemoved = true
  }

  function changeVideo(e) {
    let $item = $(e.currentTarget),
      $video = $('.eletube-video-iframe'),
      scrollOffset = $('.elementor-element-d5f3526').outerHeight() + 50;
    $video.data('src', $item.data('url'))

    if (eletubeOverlayIsRemoved) {
      $video.prop('src', $item.data('url'))
    }

    $('html, body').animate({
      scrollTop: ($('.eletube-video-iframe').offset().top - scrollOffset) + 'px'
    })

    markItemAsCurrent()
  }

  function unlockVideo(e) {
    e.preventDefault()
    let $checkBox = $('.eletube-always-unlock')
    if ($checkBox.is(':checked')) {
      localStorage.setItem('eletubeAcceptAlways', '1')
    }
    removeEletubeOverlay()
  }

  function markItemAsCurrent() {
    let $video = $('.eletube-video-iframe'),
      $items = $('.eletube-item')

    $items.removeClass('current')
    $items.each(function(index, item) {
      let $item = $(item)
      if ($item.data('url') == $video.data('src')) {
        $item.addClass('current')
      }
    })
  }

})(jQuery)