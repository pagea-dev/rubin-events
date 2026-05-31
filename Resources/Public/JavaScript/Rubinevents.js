document.addEventListener('DOMContentLoaded', () => {

    initTinysliders();

});


function initTinysliders() {
    if(document.getElementsByClassName('multislider')) {
        var msliders = document.querySelectorAll('.multislider');
        for (var i = 0; i < msliders.length; i++) {

            opt = JSON.parse(msliders[i].getAttribute('data-tiny-slider'));
            opt.container = msliders[i];

            let multiSlider = tns(opt);

        }
    }
}