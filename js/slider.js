$(document).ready(function() {
    // Cloud slider - RAM
    $('#ramslider').slider();
    $('#ramslider').on('slide', function(slideEvt) {
        $('#ramsliderVal').text(slideEvt.value);
    });

    // Cloud slider - CPU
    $('#cpuslider').slider();
    $('#cpuslider').on('slide', function(slideEvt) {
        $('#cpusliderVal').text(slideEvt.value);
    });

    // Cloud slider - disk
    $('#diskslider').slider();
    $('#diskslider').on('slide', function(slideEvt) {
        $('#disksliderVal').text(slideEvt.value);
    });
});