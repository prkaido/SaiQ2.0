/* Slider.js - Minimal */
(function() {
  var sliderInit = function() {
    var slides = document.querySelectorAll('.slider-item');
    if (slides.length > 0) {
      slides[0].classList.add('active');
    }
  };
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', sliderInit);
  } else {
    sliderInit();
  }
})();
