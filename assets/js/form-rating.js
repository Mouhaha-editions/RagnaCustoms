// const app = require('../utils/core');

$(document).on('click', 'form .rating', function () {
    let t = $(this);
    let ratingItems = $(this).parent().find('.rating');

    let getStarsRating = function () {
        let rating = 0;
        for (let ratingItem of ratingItems) {
            if ($(ratingItem).find("i").hasClass("fas")) {
                rating++;
            }
        }
        return rating;
    };

    let setStarsRating = function () {
        t.parent().data('rating', getStarsRating());
        t.parent().trigger('change');
    };

    const fillStar = function (item) {
        $(item).find("i").removeClass("far")
        $(item).find("i").addClass("fas")
    };

    const emptyStar = function (item) {
        $(item).find("i").removeClass("fas")
        $(item).find("i").addClass("far")
    };

    const toggleStars = function () {

        const itemIndex = t.data('id');
        for (let i = 0; i <= itemIndex; i++) {
            fillStar(ratingItems[i]);
        }

        for (let i = itemIndex + 1; i < ratingItems.length; i++) {
            emptyStar(ratingItems[i]);
        }

        setStarsRating();
    };

   toggleStars();
});