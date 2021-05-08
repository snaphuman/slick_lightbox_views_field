(function ($, Drupal) {

  $('.slick-lightbox-btn').on('click', function(e) {
    e.preventDefault();

    const id = $(this).data('id');
    const type = $(this).data('type');

    $('#slick-lightbox-' + type + '-' + id).slickLightbox().on({
      'hide.slickLightbox': function () {
        $(this).unslickLightbox();
      },
    });
    $('#slick-lightbox-' + type + '-' + id + ' .slide:first-child a').click();
  });

})(jQuery, Drupal, drupalSettings );
