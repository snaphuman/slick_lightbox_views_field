(function ($, Drupal) {

  $('.slick-lightbox-btn').on('click', function(e) {
    e.preventDefault();

    const id = $(this).data('id');

    $('#slick-lightbox-' + id).slickLightbox();
    $('#slick-lightbox-' + id + ' .slide:first-child a').click();
  });
})(jQuery, Drupal, drupalSettings );
