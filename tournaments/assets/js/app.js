
// any CSS you import will output into a single css file (app.css in this case)
import '../css/app.css';
import $ from 'jquery';
import 'bootstrap';
import './argon/plugins/chartjs.min';
import  '../../public/bundles/pagination/js/see-more.js';
/*global document, window*/

/* DOM elements with background images */
let backgrounds = document.querySelectorAll(".parallax-background");

$(() => {
    "use strict";
    
    /* global namespace */
    let global = {
    	"window": $(window),
        "document": $(document),
        "parallaxBackground": $(backgrounds)
    };

    /* check if the element is in viewport */
    $.fn.isInViewport = function() {
    	let self = $(this);

        let elementTop = self.offset().top;
        let elementBottom = elementTop + self.outerHeight();

        let viewportTop = global.window.scrollTop();
        let viewportBottom = viewportTop + global.window.height();

        return elementBottom > viewportTop && elementTop < viewportBottom;
    };

    global.window.on("scroll", () => {
        let scroll = global.document.scrollTop();
        let offset = -0.4;

        global.parallaxBackground.each(function() {
        	let self = $(this);
            let selfPosition = self.offset().top;

        	if (self.isInViewport()) {
                self.css({
                    "background-position": "50% " + (selfPosition * offset - scroll * offset) + "px"
                });
			}
        });
    });

    /* --- GESTION INPUT --- */
    $('.form-group').each(function() {

        if($(this).find('input').val() != '') {
            $(this).find('input').addClass('active');
        }

        $(this).find('input').blur(function(){
            if($(this).val() == '') {
                $(this).removeClass('active');
            } else {
                $(this).addClass('active');
            }
        });

        $(this).find('input').blur();
    });
    

    /* --- GESTION CLICK MENU FOND SOMBRE --- */
    $('.navbar-toggler').click(function() {
        $('.responsive-dark-background').toggle();
        $(this).find('#nav-icon-menu').toggleClass('open');
        $('body').toggleClass('hidden');
    });

    /* --- GESTION CLICK BOUTON SCROLL TOP --- */
    $('#btn-scroll-top').click(function() {
        $('html, body').animate({scrollTop:0},500);
    });

    $(window).on("scroll", function() {
        let scrollHeight = $(document).height();
        let scrollPosition = $(window).height() + $(window).scrollTop();
        if ((scrollHeight - scrollPosition) / scrollHeight <= 0.5) {
            $('#btn-scroll-top').css('display', 'block');
        } else {
            $('#btn-scroll-top').css('display', 'none');
        }
    });
    let hashtag = window.location.hash;
    $("[href='" + hashtag + "']").click();
    $(".nav a").on('click', function(){
        window.location.hash = $(this).attr('href');
    });
    /* --- GESTION MODAL ERREURS --- */
    if($('.alert-modal').length) {
        $('.alert-modal').modal('show');
    }

    /* -------------------------------------------------- */
    /* Styles des select custom */
    /* On les refait entièrement, car impossibilité de les changer tel quel (car chaque navigateur à son select) */
    /* -------------------------------------------------- */
    var x, i, j, l, ll, selElmnt, a, b, c;
    /*look for any elements with the class "light-custom-select":*/
    x = document.getElementsByClassName("light-custom-select");
    l = x.length;
    for (i = 0; i < l; i++) {
    selElmnt = x[i].getElementsByTagName("select")[0];
    ll = selElmnt.length;
    /*for each element, create a new DIV that will act as the selected item:*/
    a = document.createElement("DIV");
    a.setAttribute("class", "select-selected");
    a.innerHTML = selElmnt.options[selElmnt.selectedIndex].innerHTML;
    x[i].appendChild(a);
    /*for each element, create a new DIV that will contain the option list:*/
    b = document.createElement("DIV");
    b.setAttribute("class", "select-items select-hide");
    for (j = 1; j < ll; j++) {
        /*for each option in the original select element,
        create a new DIV that will act as an option item:*/
        c = document.createElement("DIV");
        c.innerHTML = selElmnt.options[j].innerHTML;
        c.addEventListener("click", function(e) {
            /*when an item is clicked, update the original select box,
            and the selected item:*/
            var y, i, k, s, h, sl, yl;
            s = this.parentNode.parentNode.getElementsByTagName("select")[0];
            sl = s.length;
            h = this.parentNode.previousSibling;
            for (i = 0; i < sl; i++) {
            if (s.options[i].innerHTML == this.innerHTML) {
                s.selectedIndex = i;
                h.innerHTML = this.innerHTML;
                y = this.parentNode.getElementsByClassName("same-as-selected");
                yl = y.length;
                for (k = 0; k < yl; k++) {
                y[k].removeAttribute("class");
                }
                this.setAttribute("class", "same-as-selected");
                break;
            }
            }
            h.click();
        });
        b.appendChild(c);
    }
    x[i].appendChild(b);
    a.addEventListener("click", function(e) {
        /*when the select box is clicked, close any other select boxes,
        and open/close the current select box:*/
        e.stopPropagation();
        closeAllSelect(this);
        this.nextSibling.classList.toggle("select-hide");
        this.classList.toggle("select-arrow-active");
        });
    }
    function closeAllSelect(elmnt) {
    /*a function that will close all select boxes in the document,
    except the current select box:*/
    var x, y, i, xl, yl, arrNo = [];
    x = document.getElementsByClassName("select-items");
    y = document.getElementsByClassName("select-selected");
    xl = x.length;
    yl = y.length;
    for (i = 0; i < yl; i++) {
        if (elmnt == y[i]) {
        arrNo.push(i)
        } else {
        y[i].classList.remove("select-arrow-active");
        }
    }
    for (i = 0; i < xl; i++) {
        if (arrNo.indexOf(i)) {
        x[i].classList.add("select-hide");
        }
    }
    
    console.log($('.light-custom-select select option:selected').text());
    
    }
    /*if the user clicks anywhere outside the select box,
    then close all select boxes:*/
    document.addEventListener("click", closeAllSelect);


});
