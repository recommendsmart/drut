(function($, Drupal) {

  $("#toTop").click(function() {
    $("html, body").animate({
      scrollTop: 0
    }, 1000);
  });

  Drupal.behaviors.imageSlider = {
    attach: function(context, settings) {
      $('.product-image-slider').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: '.product-image-slider-nav'
      });

      $('.product-image-slider-nav').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        asNavFor: '.product-image-slider',
        focusOnSelect: true,
        arrows: false
      });
    }
  };

  // $('.hero-banner-slider-conatiner').once().owlCarousel({
  //   loop: true,
  //   margin: 10,
  //   nav: true,
  //   autoWidth:true,
  //   responsive: {
  //     0: {
  //       items: 1
  //     },
  //     600: {
  //       items: 3
  //     },
  //     1000: {
  //       items: 5
  //     }
  //   }
  // })

  setTimeout(function() {
    $('.block-commerce-cart-blocks').css('display', 'flex');
  }, 2000)


})(jQuery, Drupal);

jQuery('.hero-banner-slider-conatiner').once().slick({
  slidesToShow: 1,
  slidesToScroll: 1,
  dots: true,
  infinite: true,
  cssEase: 'linear',
  adaptiveHeight: true
});
