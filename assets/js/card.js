const THRESHOLD = 15;

$(document).on('mousemove', '.card', function (e) {
    let {clientX, clientY, currentTarget} = e;
    let {clientWidth, clientHeight} = currentTarget;
    let {left, top} = currentTarget.getBoundingClientRect();

    let horizontal = (clientX - left) / clientWidth;
    let vertical = (clientY - top) / clientHeight;
    let rotateX = (THRESHOLD / 2 - horizontal * THRESHOLD).toFixed(2);
    let rotateY = (vertical * THRESHOLD - THRESHOLD / 2).toFixed(2);

    $(this).css({transform: `perspective(${clientWidth}px) rotateX(${rotateY}deg) rotateY(${rotateX}deg) scale3d(1, 1, 1)`});
});

$(document).on('mouseleave', '.card', function (e) {
    $(this).css({transform: `perspective(${e.currentTarget.clientWidth}px) rotateX(0deg) rotateY(0deg)`});
});