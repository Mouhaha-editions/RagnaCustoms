// const app = require('../utils/core');

$(document).on('click', '.form-rating', function () {
    let t = $(this);
    let ratingItems = $(this).find('.rating');

    let getStarsRating = function () {
        let rating = 0;
        for (let ratingItem of ratingItems) {
            if ($(ratingItem).hasClass("filled")) {
                rating++;
            }
        }
        return rating;
    };

    let setStarsRating = function () {
        t.data('rating', getStarsRating());
        t.trigger('change');
    };

    const fillStar = function (item) {
        $(item).addClass("filled");
    };

    const emptyStar = function (item) {
        $(item).removeClass("filled");
    };

    const toggleStars = function () {

        const itemIndex = $(this).data('id');
        for (let i = 0; i <= itemIndex; i++) {
            fillStar(ratingItems[i]);
        }

        for (let i = itemIndex + 1; i < ratingItems.length; i++) {
            emptyStar(ratingItems[i]);
        }

        setStarsRating();
    };

    for (const ratingItem of ratingItems) {
        $(ratingItem).on('click', toggleStars);
    }

    setStarsRating();


});

//
// app.querySelector('.form-rating', function (ratingInputs) {
//   for (const ratingInput of ratingInputs) {
//     const filledClass = 'filled',
//           ratingItems = Array.from(ratingInput.children);
//
//     const getStarsRating = function () {
//       let rating = 0;
//
//       for (const ratingItem of ratingItems) {
//         if (ratingItem.classList.contains(filledClass)) {
//           rating++;
//         }
//       }
//
//       return rating;
//     };
//
//     const setStarsRating = function () {
//       ratingInput.setAttribute('data-rating', getStarsRating());
//       var event = new Event('change');
//       ratingInput.dispatchEvent(event);
//     };
//
//     const fillStar = function (item) {
//       item.classList.add(filledClass);
//     };
//
//     const emptyStar = function (item) {
//       item.classList.remove(filledClass);
//     };
//
//     const toggleStars = function () {
//       const itemIndex = ratingItems.indexOf(this);
//
//       for (let i = 0; i <= itemIndex; i++) {
//         fillStar(ratingItems[i]);
//       }
//
//       for (let i = itemIndex + 1; i < ratingItems.length; i++) {
//         emptyStar(ratingItems[i]);
//       }
//
//       setStarsRating();
//     };
//
//     for (const ratingItem of ratingItems) {
//       ratingItem.addEventListener('click', toggleStars);
//     }
//
//     setStarsRating();
//   }
// });