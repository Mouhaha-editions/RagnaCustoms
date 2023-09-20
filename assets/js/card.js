const THRESHOLD = 15;

$(document).on('mousemove', '.card-cover', function (e) {
    let {clientX, clientY, currentTarget} = e;
    let {clientWidth, clientHeight} = currentTarget;
    let {left, top} = currentTarget.getBoundingClientRect();

    let horizontal = (clientX - left) / clientWidth;
    let vertical = (clientY - top) / clientHeight;
    let rotateX = (THRESHOLD / 2 - horizontal * THRESHOLD).toFixed(2);
    let rotateY = (vertical * THRESHOLD - THRESHOLD / 2).toFixed(2);

    if($(this).find('.glow').length > 0 ) {
        $(this).find('.glow')
        $(this).find('.glow').css({'top': (clientY - top - clientWidth)+"px",'left': (clientX - left - clientHeight)+"px"})
    }else{
        $(this).append('<div class="glow"></div>');
        $(this).find('.glow').css({'width': (clientWidth*2) +"px",'height': (clientHeight*2 )+"px"})
    }

    $(this).css({transform: `perspective(${clientWidth}px) rotateX(${rotateY}deg) rotateY(${rotateX}deg) scale3d(1, 1, 1)`});
});

$(document).on('mouseleave', '.card-cover', function (e) {
    $(this).css({transform: `perspective(${e.currentTarget.clientWidth}px) rotateX(0deg) rotateY(0deg)`});
    $(this).find('.glow').remove();

});